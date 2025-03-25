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
    
    $query = "SELECT c.*, s.subject_name, COUNT(DISTINCT e.student_id) as student_count 
             FROM class c 
             LEFT JOIN subject s ON c.subject_id = s.subject_id 
             LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
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
                SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) AS active_classes,
                COUNT(DISTINCT c.class_id) AS total_classes,
                COUNT(DISTINCT e.student_id) AS total_students,
                SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) AS completed_classes
              FROM class c
              LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
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
        
        // Handle optional values correctly
        $classSize = isset($data['class_size']) && $data['class_size'] !== '' ? $data['class_size'] : null;
        $price = isset($data['price']) ? $data['price'] : 0; // Default price if not set
        
        // Insert into class table
        $stmt = $conn->prepare("INSERT INTO class (subject_id, class_name, class_desc, tutor_id, start_date, end_date, class_size, status, is_free, price, thumbnail) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)");
        $stmt->bind_param("ississdids", 
            $data['subject_id'],
            $data['class_name'],
            $data['class_desc'],
            $data['tutor_id'],
            $data['start_date'],
            $data['end_date'],
            $classSize,
            $data['is_free'],
            $price,
            $data['thumbnail']
        );
        $stmt->execute();
        $class_id = $conn->insert_id;
        
        // Insert class schedules if provided
        if (!empty($data['schedules'])) {
            $scheduleStmt = $conn->prepare("INSERT INTO class_schedule (class_id, user_id, role, session_date, start_time, end_time, status) 
                                            VALUES (?, ?, 'TUTOR', ?, ?, ?, 'pending')");

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
    
    $sql = "SELECT cs.*, u.first_name, u.last_name, u.profile_picture, u.role, c.class_name,
                   e.status as enrollment_status
            FROM class_schedule cs
            LEFT JOIN users u ON cs.user_id = u.uid
            LEFT JOIN class c ON cs.class_id = c.class_id
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND u.uid = e.student_id
            WHERE cs.class_id = ?
            ORDER BY cs.session_date ASC, cs.start_time ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
/**
 * Get subject details by course ID or subject name
 */
function getSubjectDetails($identifier, $by = 'course_id') {
    global $conn;

    if ($by === 'course_id') {
        // For listing subjects in a course
        $stmt = $conn->prepare("
            SELECT 
                s.subject_id, s.subject_name, s.subject_desc, s.image, 
                COUNT(DISTINCT c.class_id) AS class_count, 
                COUNT(DISTINCT e.student_id) AS student_count
            FROM subject s
            LEFT JOIN class c ON s.subject_id = c.subject_id AND c.status = 'active'
            LEFT JOIN enrollments e ON e.class_id = c.class_id AND e.status = 'active'
            WHERE s.is_active = 1 AND s.course_id = ?
            GROUP BY s.subject_id
        ");
        $stmt->bind_param("i", $identifier);
    } else {
        // For getting detailed subject information
        $stmt = $conn->prepare("
            SELECT 
                s.*, 
                c.course_name, 
                c.course_desc,
                (SELECT COUNT(DISTINCT cl.class_id) 
                 FROM class cl 
                 WHERE cl.subject_id = s.subject_id AND cl.status = 'active') AS active_classes,
                (SELECT COUNT(DISTINCT e.student_id) 
                 FROM class cl 
                 JOIN enrollments e ON cl.class_id = e.class_id 
                 WHERE cl.subject_id = s.subject_id AND e.status = 'active') AS total_students,
                (SELECT AVG(sf.rating) 
                 FROM class cl 
                 JOIN session_feedback sf ON cl.tutor_id = sf.tutor_id 
                 WHERE cl.subject_id = s.subject_id) AS average_rating,
                (SELECT 
                    CASE 
                        WHEN COUNT(DISTINCT e2.student_id) = 0 THEN 0 
                        ELSE COUNT(DISTINCT e.student_id) * 100.0 / COUNT(DISTINCT e2.student_id) 
                    END
                 FROM class cl 
                 JOIN enrollments e ON cl.class_id = e.class_id AND e.status = 'completed'
                 LEFT JOIN enrollments e2 ON cl.class_id = e2.class_id
                 WHERE cl.subject_id = s.subject_id) AS completion_rate
            FROM subject s
            JOIN course c ON s.course_id = c.course_id
            WHERE s.subject_name = ?
        ");
        $stmt->bind_param("s", $identifier);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($by === 'course_id') {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}
/**
 * Get active classes for a subject and tutor
 */
function getActiveClassesForSubject($subject_id, $tutor_id) {
    global $conn;
    
    $sql = "SELECT 
                c.*, 
                COUNT(DISTINCT e.student_id) AS student_count
            FROM class c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            WHERE c.subject_id = ? 
                AND c.tutor_id = ? 
                AND c.status = 'active'
            GROUP BY c.class_id
            ORDER BY c.start_date ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $subject_id, $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get students enrolled in a class with their progress
 * 
 * @param int $class_id Class ID
 * @return array Array of students with their progress details
 */
function getClassStudents($class_id) {
    global $conn;
    
    $sql = "SELECT DISTINCT u.*, e.status as enrollment_status,
            (SELECT COUNT(*) FROM class_schedule cs2 
             WHERE cs2.class_id = ? AND cs2.user_id = u.uid AND cs2.status = 'completed') as completed_sessions,
            (SELECT COUNT(*) FROM class_schedule cs2 
             WHERE cs2.class_id = ? AND cs2.user_id = u.uid) as total_sessions
            FROM users u
            JOIN enrollments e ON u.uid = e.student_id
            WHERE e.class_id = ?
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

    try {
        $query = "SELECT 
                    c.*,
                    s.subject_name, s.subject_desc, s.image AS subject_image,
                    co.course_name, co.course_desc,
                    CONCAT(u.first_name,' ',u.last_name) AS techguru_name,
                    u.uid AS techguru_id,
                    u.email AS techguru_email,
                    u.profile_picture AS techguru_profile,
                    COUNT(DISTINCT e.student_id) AS total_students,
                    SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_students,
                    (SELECT COUNT(*) 
                     FROM class_schedule 
                     WHERE class_id = c.class_id AND status = 'completed') AS completed_sessions,
                    (SELECT COUNT(*) 
                     FROM class_schedule 
                     WHERE class_id = c.class_id) AS total_sessions,
                    ROUND(
                        (SELECT COUNT(*) 
                         FROM class_schedule 
                         WHERE class_id = c.class_id AND status = 'completed') 
                        / NULLIF(
                          (SELECT COUNT(*) 
                           FROM class_schedule 
                           WHERE class_id = c.class_id), 0
                        ) * 100, 2) AS completion_rate,
                    CASE 
                        WHEN c.end_date < NOW() THEN 'completed'
                        WHEN c.start_date > NOW() THEN 'upcoming'
                        WHEN c.status = 'inactive' THEN 'inactive'
                        ELSE 'ongoing'
                    END AS status
                  FROM class c
                  JOIN subject s ON c.subject_id = s.subject_id
                  JOIN course co ON s.course_id = co.course_id
                  JOIN users u ON c.tutor_id = u.uid
                  LEFT JOIN enrollments e ON c.class_id = e.class_id
                  WHERE c.class_id = ? " . 
                  ($tutor_id !== null ? "AND c.tutor_id = ? " : "") . 
                  "GROUP BY c.class_id";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }

        if ($tutor_id !== null) {
            $stmt->bind_param("ii", $class_id, $tutor_id);
        } else {
            $stmt->bind_param("i", $class_id);
        }

        $stmt->execute();
        $class = $stmt->get_result()->fetch_assoc();

        if (!$class) {
            log_error("Class not found: " . $class_id);
            return null;
        }

        return $class;
    } catch (Exception $e) {
        log_error("Error fetching class details: " . $e->getMessage());
        return null;
    }
}




/**
 * Update a class's status and notify relevant users
 * 
 * @param int $class_id The ID of the class
 * @param int|null $tutor_id Optional tutor ID for verification. If null, acts as admin access
 * @param string $new_status New status (active, restricted, completed, pending)
 * @return bool True if update was successful
 */
function updateClassStatus($class_id, $tutor_id = null, $new_status) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // If tutor_id is provided, verify ownership
        if ($tutor_id !== null) {
            $verify = $conn->prepare("SELECT tutor_id FROM class WHERE class_id = ?");
            $verify->bind_param("i", $class_id);
            $verify->execute();
            $result = $verify->get_result()->fetch_assoc();
            
            if (!$result || $result['tutor_id'] != $tutor_id) {
                throw new Exception("Unauthorized status update attempt");
            }
        }
        
        // Update class status
        $query = "UPDATE class SET status = ? WHERE class_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $new_status, $class_id);
        $stmt->execute();
        
        // Get class and user details for notification
        $query = "SELECT c.class_name, c.tutor_id, u.email as tutor_email 
                 FROM class c 
                 JOIN users u ON c.tutor_id = u.uid 
                 WHERE c.class_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Send notification to tutor
        $message = "Your class '{$result['class_name']}' status has been updated to " . ucfirst($new_status);
        insertNotification(
            $result['tutor_id'],
            'TECHGURU',
            $message,
            "class-details?id=$class_id",
            $class_id,
            'bi bi-info-circle',
            'text-info'
        );
        
        // Send notifications to enrolled students
        $query = "SELECT e.student_id, u.email 
                 FROM enrollments e 
                 JOIN users u ON e.student_id = u.uid 
                 WHERE e.class_id = ? AND e.status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($students as $student) {
            $message = "Class '{$result['class_name']}' status has been updated to " . ucfirst($new_status);
            insertNotification(
                $student['student_id'],
                'TECHKID',
                $message,
                "class-details?id=$class_id",
                $class_id,
                'bi bi-info-circle',
                'text-info'
            );
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Error updating class status: " . $e->getMessage(),'database');
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
function getClassesWithPagination($page = 1, $items_per_page = 10) {
    global $conn;

    try {
        $offset = ($page - 1) * $items_per_page;

        // Query to get paginated class data
        $query = "SELECT 
                    c.class_id,
                    c.class_name,
                    s.subject_name,
                    CONCAT(u.first_name, ' ', u.last_name) as techguru_name,
                    COUNT(DISTINCT e.student_id) as enrolled_students,
                    c.status
                FROM class c
                LEFT JOIN subject s ON c.subject_id = s.subject_id
                LEFT JOIN users u ON c.tutor_id = u.uid
                LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
                GROUP BY c.class_id
                ORDER BY c.class_id DESC
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $items_per_page, $offset);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get total class count
        $count_query = "SELECT COUNT(*) as total FROM class";
        $total_result = $conn->query($count_query);
        if (!$total_result) {
            throw new Exception("Count query failed: " . $conn->error);
        }

        $total_classes = $total_result->fetch_assoc()['total'];
        $total_pages = ceil($total_classes / $items_per_page);

        return [
            'classes' => $classes,
            'total_pages' => $total_pages
        ];
    } catch (Exception $e) {
        log_error("Error in getClassesWithPagination(): " . $e->getMessage(), 2);
        return [
            'classes' => [],
            'total_pages' => 0
        ];
    }
}
function getEnrolledStudents($class_id) {
    global $conn;

    try {
        // Ensure the enrollments table exists
        $create_enrollments_table = "
            CREATE TABLE IF NOT EXISTS `enrollments` (
                `enrollment_id` INT PRIMARY KEY AUTO_INCREMENT,
                `class_id` INT NOT NULL,
                `student_id` INT NOT NULL,
                `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
                FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(uid) ON DELETE CASCADE,
                UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`)
            )";
        $conn->query($create_enrollments_table);
        
        // Fetch enrolled students
        $students_query = "SELECT 
                            u.*,
                            e.enrollment_date,
                            e.status as enrollment_status
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.uid
                        WHERE e.class_id = ?
                        ORDER BY e.enrollment_date DESC";

        $stmt = $conn->prepare($students_query);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error fetching enrolled students: " . $e->getMessage(),'database');
        return [];
    }
}


/**
 * Update class schedules
 * 
 * @param int $class_id The ID of the class
 * @param array $schedules Array of new schedules
 * @param int $tutor_id The ID of the tutor
 * @return array Success status and message
 */
function updateClassSchedules($class_id, $schedules, $tutor_id) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        log_error("was called");
        // Verify class ownership
        $verify = $conn->prepare("SELECT tutor_id FROM class WHERE class_id = ?");
        $verify->bind_param("i", $class_id);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        
        if (!$result || $result['tutor_id'] != $tutor_id) {
            throw new Exception("Unauthorized schedule update attempt");
        }
        // Delete existing schedules
        $delete = $conn->prepare("DELETE FROM class_schedule WHERE class_id = ? AND user_id = ?");
        $delete->bind_param("ii", $class_id, $tutor_id);
        $delete->execute();
        
        // Insert new schedules
        $insert = $conn->prepare("INSERT INTO class_schedule (class_id, user_id, session_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
        
        foreach ($schedules as $schedule) {
            $insert->bind_param("iisss", 
                $class_id,
                $tutor_id,
                $schedule['session_date'],
                $schedule['start_time'],
                $schedule['end_time']
            );
            $insert->execute();
        }

        $conn->commit();
        return ['success' => true, 'message' => 'Schedule updated successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Schedule update failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update schedule'];
    }
}

function getClassRecordings() {
    return [];
}

function getClassRecordingsCount() {
    return 0;
}

?>
