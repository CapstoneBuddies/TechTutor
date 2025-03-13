<?php
/**
 * Class management functions for TechTutor platform
 * Handles all class-related database operations for TechGuru users
 */

/**
 * Get all classes for a TechGuru
 * 
 * @param int $tutor_id The ID of the tutor
 * @return array Array of classes with their details
 */
function getTechGuruClasses($tutor_id) {
    global $conn;
    
    $query = "SELECT c.*, s.subject_name, COUNT(DISTINCT cs.user_id) as student_count 
             FROM class c 
             LEFT JOIN subject s ON c.subject_id = s.subject_id 
             LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
             WHERE c.tutor_id = ? 
             GROUP BY c.class_id 
             ORDER BY c.start_date DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting tutor classes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get statistics about a TechGuru's classes
 * 
 * @param int $tutor_id The ID of the tutor
 * @return array Array containing class statistics
 */
function getClassStats($tutor_id) {
    global $conn;
    
    $query = "SELECT 
                SUM(CASE WHEN c.is_active = 1 THEN 1 ELSE 0 END) as active_classes,
                COUNT(DISTINCT c.class_id) as total_classes,
                COUNT(DISTINCT cs.user_id) as total_students,
                SUM(CASE WHEN c.is_active = 0 THEN 1 ELSE 0 END) as completed_classes
              FROM class c
              LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
              WHERE c.tutor_id = ?";
              
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting class stats: " . $e->getMessage());
        return [
            'active_classes' => 0,
            'total_classes' => 0,
            'total_students' => 0,
            'completed_classes' => 0
        ];
    }
}

/**
 * Delete a class and all its related data
 * 
 * @param int $class_id The ID of the class to delete
 * @param int $tutor_id The ID of the tutor who owns the class
 * @return bool True if deletion was successful, false otherwise
 */
function deleteClass($class_id, $tutor_id) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // First verify the class belongs to this tutor
        $verify = $conn->prepare("SELECT tutor_id, class_name FROM class WHERE class_id = ?");
        $verify->bind_param("i", $class_id);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        
        if (!$result || $result['tutor_id'] != $tutor_id) {
            throw new Exception("Unauthorized deletion attempt");
        }
        
        // Delete notifications first (due to foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM notifications WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        
        // Delete class schedules
        $stmt = $conn->prepare("DELETE FROM class_schedule WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();

        // Delete any class files
        $stmt = $conn->prepare("DELETE FROM file_management WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        
        // Add notification for students before deleting the class
        insertNotification(
            null,
            'TECHKID',
            'Class "' . htmlspecialchars($result['class_name']) . '" has been cancelled.',
            BASE . 'dashboard/courses',
            null, // Set class_id as null since we're deleting the class
            'bi-exclamation-circle',
            'text-danger'
        );
        
        // Delete the class
        $stmt = $conn->prepare("DELETE FROM class WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting class: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new class with schedules
 * 
 * @param array $data Class data including schedules
 * @return array Success status and class ID or error message
 */
function createClass($data) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Handle unlimited students (null in database)
        $classSize = empty($data['class_size']) ? null : $data['class_size'];
        
        // Insert into class table
        $stmt = $conn->prepare("INSERT INTO class (subject_id, class_name, class_desc, tutor_id, start_date, end_date, class_size, is_active, is_free, price, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)");
        $stmt->bind_param("ississiidd", 
            $data['subject_id'],
            $data['class_name'],
            $data['class_desc'],
            $data['tutor_id'],
            $data['start_date'],
            $data['end_date'],
            $classSize,
            $data['is_free'],
            $data['price'],
            $data['thumbnail']
        );
        $stmt->execute();
        $class_id = $conn->insert_id;
        
        // Insert class schedules
        $scheduleStmt = $conn->prepare("INSERT INTO class_schedule (class_id, user_id, role, session_date, start_time, end_time, status) VALUES (?, ?, 'TUTOR', ?, ?, ?, 'confirmed')");
        
        foreach ($data['schedules'] as $schedule) {
            $scheduleStmt->bind_param("iisss", 
                $class_id,
                $data['tutor_id'],
                $schedule['session_date'],
                $schedule['start_time'],
                $schedule['end_time']
            );
            $scheduleStmt->execute();
        }
        
        $conn->commit();
        return ['success' => true, 'class_id' => $class_id];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get subject details by name
 * 
 * @param string $subject_name Subject name to search for
 * @return array|null Subject details or null if not found
 */
function getSubjectByName($subject_name) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM subject WHERE subject_name = ?");
    $stmt->bind_param("s", $subject_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Generate class schedules based on days and time slots
 * 
 * @param string $start_date Start date (Y-m-d)
 * @param string $end_date End date (Y-m-d)
 * @param array $days Days of the week
 * @param array $time_slots Array of time slots with start and end times
 * @return array Generated schedules
 */
function generateClassSchedules($start_date, $end_date, $days, $time_slots) {
    $schedules = [];
    $current_date = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    while ($current_date <= $end) {
        $day_name = strtolower($current_date->format('l'));
        if (in_array($day_name, $days)) {
            foreach ($time_slots as $slot) {
                $schedules[] = [
                    'session_date' => $current_date->format('Y-m-d'),
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end']
                ];
            }
        }
        $current_date->modify('+1 day');
    }
    
    return $schedules;
}

/**
 * Get class schedules with user details
 * 
 * @param int $class_id Class ID
 * @return array Array of schedules with user details
 */
function getClassSchedules($class_id) {
    global $conn;
    
    $sql = "SELECT cs.*, u.first_name, u.last_name, u.profile_picture, u.role
            FROM class_schedule cs
            LEFT JOIN users u ON cs.user_id = u.uid
            WHERE cs.class_id = ?
            ORDER BY cs.session_date ASC, cs.start_time ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get students enrolled in a class with their progress
 * 
 * @param int $class_id Class ID
 * @return array Array of students with their progress details
 */
function getClassStudents($class_id) {
    global $conn;
    
    $sql = "SELECT DISTINCT u.*, cs.status as enrollment_status,
            (SELECT COUNT(*) FROM class_schedule cs2 
             WHERE cs2.class_id = ? AND cs2.user_id = u.uid AND cs2.status = 'completed') as completed_sessions,
            (SELECT COUNT(*) FROM class_schedule cs2 
             WHERE cs2.class_id = ? AND cs2.user_id = u.uid) as total_sessions
            FROM users u
            JOIN class_schedule cs ON u.uid = cs.user_id
            WHERE cs.class_id = ? AND cs.role = 'STUDENT'
            ORDER BY u.first_name, u.last_name";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $class_id, $class_id, $class_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get files uploaded to a class
 * 
 * @param int $class_id Class ID
 * @return array Array of files with uploader details
 */
function getClassFiles($class_id) {
    global $conn;
    
    $sql = "SELECT f.*, u.first_name, u.last_name, u.role
            FROM file_management f
            JOIN users u ON f.user_id = u.uid
            WHERE f.class_id = ?
            ORDER BY f.upload_time DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a single class's details
 * 
 * @param int $class_id The ID of the class
 * @param int|null $tutor_id Optional tutor ID for verification. If null, acts as admin access
 * @return array|null Class details or null if not found/unauthorized
 */
function getClassDetails($class_id, $tutor_id = null) {
    global $conn;
    
    $query = "SELECT c.*, s.subject_name, s.subject_desc, s.image as subject_image,
                     co.course_name, co.course_desc,
                     u.first_name, u.last_name, u.profile_picture,
                     COUNT(DISTINCT cs.user_id) as total_students,
                     SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) as completed_students,
                     (SELECT AVG(r.rating) FROM ratings r WHERE r.tutor_id = c.tutor_id) as average_rating,
                     (SELECT COUNT(*) FROM class_schedule WHERE class_id = c.class_id AND role = 'STUDENT' AND status = 'completed') as completed_sessions,
                     (SELECT COUNT(*) FROM class_schedule WHERE class_id = c.class_id AND role = 'STUDENT') as total_sessions,
                     CASE 
                        WHEN c.end_date < NOW() THEN 'completed'
                        WHEN c.start_date > NOW() THEN 'upcoming'
                        WHEN c.is_active = 0 THEN 'inactive'
                        ELSE 'ongoing'
                     END as status
              FROM class c
              JOIN subject s ON c.subject_id = s.subject_id
              JOIN course co ON s.course_id = co.course_id
              JOIN users u ON c.tutor_id = u.uid
              LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
              WHERE c.class_id = ? " . 
              ($tutor_id !== null ? "AND c.tutor_id = ? " : "") .
              "GROUP BY c.class_id";
              
    try {
        $stmt = $conn->prepare($query);
        if ($tutor_id !== null) {
            $stmt->bind_param("ii", $class_id, $tutor_id);
        } else {
            $stmt->bind_param("i", $class_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting class details: " . $e->getMessage());
        return null;
    }
}

/**
 * Update a class's status (active/inactive)
 * 
 * @param int $class_id The ID of the class
 * @param int|null $tutor_id Optional tutor ID for verification. If null, acts as admin access
 * @param bool $is_active The new status
 * @return bool True if update was successful
 */
function updateClassStatus($class_id, $tutor_id = null, $is_active) {
    global $conn;
    
    try {
        // Verify ownership if tutor_id is provided
        if ($tutor_id !== null) {
            $verify = $conn->prepare("SELECT tutor_id FROM class WHERE class_id = ?");
            $verify->bind_param("i", $class_id);
            $verify->execute();
            $result = $verify->get_result()->fetch_assoc();
            
            if (!$result || $result['tutor_id'] != $tutor_id) {
                return false;
            }
        }
        
        $stmt = $conn->prepare("UPDATE class SET is_active = ? WHERE class_id = ?");
        $stmt->bind_param("ii", $is_active, $class_id);
        
        if ($stmt->execute()) {
            // Get class details for notification
            $class = getClassDetails($class_id);
            if ($class) {
                $status = $is_active ? 'activated' : 'deactivated';
                $icon = $is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                $color = $is_active ? 'text-success' : 'text-danger';
                
                // Notify the tutor if admin made the change
                if ($tutor_id === null) {
                    require_once 'notifications.php';
                    insertNotification(
                        $class['tutor_id'],
                        'TECHGURU',
                        "Your class '{$class['class_name']}' has been {$status} by admin",
                        "/techguru/class-details?id={$class_id}",
                        $class_id,
                        $icon,
                        $color
                    );
                }
            }
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error updating class status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all course details
 * 
 * @return array Array of all courses with their details
 */
function getCourseDetails() {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM course");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update class details
 * 
 * @param array $data Class data including class_id, class_name, class_desc, class_size, is_free, and price
 * @return array Success status and error message if any
 */
function updateClass($data) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // First verify the class exists and get current details
        $verify = $conn->prepare("SELECT class_name, is_free, price FROM class WHERE class_id = ?");
        $verify->bind_param("i", $data['class_id']);
        $verify->execute();
        $currentClass = $verify->get_result()->fetch_assoc();
        
        if (!$currentClass) {
            throw new Exception("Class not found");
        }
        
        // Update class details
        $stmt = $conn->prepare("UPDATE class SET 
            class_name = ?,
            class_desc = ?,
            class_size = ?,
            is_free = ?,
            price = ?
            WHERE class_id = ?");
            
        $stmt->bind_param("ssiidi", 
            $data['class_name'],
            $data['class_desc'],
            $data['class_size'],
            $data['is_free'],
            $data['price'],
            $data['class_id']
        );
        
        $stmt->execute();
        
        // If price changed, notify enrolled students
        if ($currentClass['is_free'] != $data['is_free'] || $currentClass['price'] != $data['price']) {
            $priceChangeMsg = $data['is_free'] ? 
                "Class is now free!" : 
                "Class price updated to ₱" . number_format($data['price'], 2);
                
            sendNotification(
                null,
                'TECHKID',
                "Price update for '{$data['class_name']}': {$priceChangeMsg}",
                "/class-details?id={$data['class_id']}",
                $data['class_id'],
                'bi-currency-dollar',
                'text-info'
            );
        }
        
        $conn->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating class: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
