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
    
    $query = "SELECT 
                c.*,
                s.subject_name,
                COUNT(DISTINCT e.student_id) as student_count,
                (
                    SELECT COUNT(*) 
                    FROM class_schedule cs 
                    WHERE cs.class_id = c.class_id 
                    AND cs.status = 'completed'
                ) as completed_sessions,
                (
                    SELECT COUNT(*) 
                    FROM class_schedule cs 
                    WHERE cs.class_id = c.class_id
                ) as total_sessions,
                (
                    SELECT cs.session_date
                    FROM class_schedule cs 
                    WHERE cs.class_id = c.class_id 
                    AND cs.status IN ('pending', 'confirmed')
                    AND cs.session_date >= CURDATE()
                    ORDER BY cs.session_date ASC, cs.start_time ASC
                    LIMIT 1
                ) as next_session_date,
                (
                    SELECT CONCAT(
                        DATE_FORMAT(cs.start_time, '%h:%i %p'), 
                        ' - ', 
                        DATE_FORMAT(cs.end_time, '%h:%i %p')
                    )
                    FROM class_schedule cs 
                    WHERE cs.class_id = c.class_id 
                    AND cs.status IN ('pending', 'confirmed')
                    AND cs.session_date >= CURDATE()
                    ORDER BY cs.session_date ASC, cs.start_time ASC
                    LIMIT 1
                ) as next_session_time,
                (
                    SELECT cs.status
                    FROM class_schedule cs 
                    WHERE cs.class_id = c.class_id 
                    AND cs.status IN ('pending', 'confirmed')
                    AND cs.session_date >= CURDATE()
                    ORDER BY cs.session_date ASC, cs.start_time ASC
                    LIMIT 1
                ) as next_session_status
             FROM class c 
             LEFT JOIN subject s ON c.subject_id = s.subject_id 
             LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
             WHERE c.tutor_id = ? 
             GROUP BY c.class_id 
             ORDER BY 
                CASE 
                    WHEN c.status = 'active' THEN 1
                    WHEN c.status = 'pending' THEN 2
                    ELSE 3
                END,
                next_session_date ASC,
                c.start_date DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $classes = $result->fetch_all(MYSQLI_ASSOC);

        // Process the results
        foreach ($classes as &$class) {
            // Calculate progress percentage
            $class['progress'] = $class['total_sessions'] > 0 
                ? round(($class['completed_sessions'] / $class['total_sessions']) * 100) 
                : 0;

            // Format next session info
            if (!empty($class['next_session_date'])) {
                $class['next_session_date'] = date('M d, Y', strtotime($class['next_session_date']));
            } else {
                $class['next_session_date'] = 'No scheduled date';
                $class['next_session_time'] = 'No scheduled time';
                $class['next_session_status'] = null;
            }

            // Ensure all fields have default values
            $class = array_merge([
                'student_count' => 0,
                'completed_sessions' => 0,
                'total_sessions' => 0,
                'progress' => 0,
                'next_session_status' => 'pending'
            ], $class);
        }

        return $classes;
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
            COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.class_id END) AS active_classes,
            COUNT(DISTINCT c.class_id) AS total_classes,
            COUNT(DISTINCT e.student_id) AS total_students,
            COUNT(DISTINCT CASE WHEN c.status = 'completed' THEN c.class_id END) AS completed_classes
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
         
        // Validate required fields
        $required_fields = ['subject_id', 'class_name', 'class_desc', 'tutor_id', 'start_date', 'end_date', 'days', 'time_slots'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Validate dates
        $start_date = new DateTime($data['start_date']);
        $end_date = new DateTime($data['end_date']);
        if ($start_date > $end_date) {
            throw new Exception("End date cannot be earlier than start date");
        }
        
        // Handle optional values correctly
        $classSize = isset($data['class_size']) && $data['class_size'] !== '' ? $data['class_size'] : null;
        $price = isset($data['price']) ? $data['price'] : 0; // Default price if not set
        $is_free = isset($data['is_free']) ? $data['is_free'] : 1; // Default to free class
        
        // Handle file upload for thumbnail
        $thumbnail = 'default.jpg'; // Default thumbnail
        if (isset($_FILES['classCover']) && $_FILES['classCover']['error'] === 0) {
            $file_ext = strtolower(pathinfo($_FILES['classCover']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
            }
            // Will be renamed after class creation with class_id
            $thumbnail = "temp_" . time() . "." . $file_ext;
        }
        
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
            $is_free,
            $price,
            $thumbnail
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create class record");
        }
        $class_id = $conn->insert_id;
        
        // Handle thumbnail upload if exists
        if (isset($_FILES['classCover']) && $_FILES['classCover']['error'] === 0) {
            $new_filename = $class_id . "." . $file_ext;
            $upload_path = CLASS_IMG . $new_filename;
            
            if (!move_uploaded_file($_FILES['classCover']['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload thumbnail");
            }
            
            // Update the filename in database
            $stmt = $conn->prepare("UPDATE class SET thumbnail = ? WHERE class_id = ?");
            $stmt->bind_param("si", $new_filename, $class_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update thumbnail record");
            }
        }

        // Generate and insert schedules
        $schedules = generateClassSchedules(
            $data['start_date'],
            $data['end_date'],
            $data['days'],
            $data['time_slots']
        );

        if (empty($schedules)) {
            throw new Exception("No valid schedules could be generated");
        }

        $stmt = $conn->prepare("INSERT INTO class_schedule (class_id, session_date, start_time, end_time, status) VALUES ( ?, ?, ?, ?, 'pending')");
        
        foreach ($schedules as $schedule) {
            $stmt->bind_param("isss", 
                $class_id,
                $schedule['date'],
                $schedule['start_time'],
                $schedule['end_time']
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to create schedule record");
            }
        }

        // Send notification to tutor
        sendNotification(
            $data['tutor_id'],
            'TECHGURU',
            "Your class '{$data['class_name']}' has been created successfully",
            BASE . "dashboard/t/class",
            $class_id,
            'bi-mortarboard',
            'text-success'
        );

        // Log the successful creation
        log_error("Class created successfully: {$data['class_name']} (ID: {$class_id}) by tutor {$data['tutor_id']}", "info");
        
        $conn->commit();
        
        return [
            'success' => true,
            'class_id' => $class_id,
            'message' => 'Class created successfully',
            'schedules' => $schedules
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Error creating class: " . $e->getMessage(), "database");
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
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
 * Generate class schedules based on time slots
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
                    'day' => $day_name,
                    'date' => $current_date->format('Y-m-d'),
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
    
    $sql = "SELECT cs.*, c.class_name
            FROM class_schedule cs
            LEFT JOIN class c ON cs.class_id = c.class_id
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
    
    $sql = "SELECT DISTINCT 
                u.*,
                e.status as enrollment_status,
                e.enrollment_date,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM class_schedule cs 
                     JOIN attendance a ON cs.schedule_id = a.schedule_id 
                     WHERE cs.class_id = ? 
                     AND a.status = 'present'), 
                    0
                ) as completed_sessions,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM class_schedule cs 
                     WHERE cs.class_id = ? 
                     AND cs.session_date <= CURRENT_DATE), 
                    0
                ) as total_sessions,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM class_schedule cs 
                     WHERE cs.class_id = ?), 
                    0
                ) as all_sessions
            FROM users u
            JOIN enrollments e ON u.uid = e.student_id
            WHERE e.class_id = ? AND e.status != 'dropped'
            ORDER BY u.first_name, u.last_name";
            
    try {
    $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $class_id, $class_id, $class_id, $class_id);
    $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Process each student's data
        foreach ($students as &$student) {
            // Calculate progress percentage safely
            $student['total_sessions'] = max($student['total_sessions'], 1); // Prevent division by zero
            $student['progress_percentage'] = round(($student['completed_sessions'] / $student['total_sessions']) * 100);
            
            // Add additional useful information
            $student['all_sessions'] = (int)$student['all_sessions'];
            $student['remaining_sessions'] = $student['all_sessions'] - $student['total_sessions'];
            $student['attendance_rate'] = $student['total_sessions'] > 0 
                ? round(($student['completed_sessions'] / $student['total_sessions']) * 100) 
                : 0;
        }

        return $students;
    } catch (Exception $e) {
        log_error("Error getting class students: " . $e->getMessage(), 'database');
        return [];
    }
}

/**
 * Get all files for a class
 * 
 * @param int $class_id Class ID
 * @return array Array of files with uploader details
 */
function getClassFiles($class_id) {
    global $conn;
    
    try {
        // Load the UnifiedFileManagement class
        require_once BACKEND . 'unified_file_management.php';
        $fileManager = new UnifiedFileManagement();
        
        // Use the new method to get class files
        return $fileManager->getClassFiles($class_id);
    } catch (Exception $e) {
        log_error("Error getting class files: " . $e->getMessage());
        return [];
    }
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
        $delete = $conn->prepare("DELETE FROM class_schedule WHERE class_id = ?");
        $delete->bind_param("i", $class_id);
        $delete->execute();
        
        // Insert new schedules
        $insert = $conn->prepare("INSERT INTO class_schedule (class_id, session_date, start_time, end_time, status) VALUES (?, ?, ?, ?, 'pending')");
        
        foreach ($schedules as $schedule) {
            $insert->bind_param("isss", 
                $class_id,
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
function getScheduleStatus($schedule_id) {
    global $conn;
    
    try {
        // First, check if the status is already completed or canceled in the database
        $stmt = $conn->prepare("SELECT session_date, start_time, status FROM class_schedule WHERE schedule_id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return 'unknown'; // Schedule not found
        }
        
        $schedule = $result->fetch_assoc();
        
        // If status is already completed or canceled, return that
        if ($schedule['status'] === 'completed' || $schedule['status'] === 'canceled') {
            return $schedule['status'];
        }
        
        // Otherwise calculate status based on date and time
        $date = $schedule['session_date'];
        $startTime = $schedule['start_time'];
        $scheduleDateTime = strtotime("$date $startTime");
        $now = time();

        if ($scheduleDateTime < $now) {
            return 'completed';
        } elseif (($scheduleDateTime - $now) < 24 * 60 * 60) {
            return 'upcoming';
        } else {
            return 'scheduled';
        }
    } catch (Exception $e) {
        log_error("Error in getScheduleStatus: " . $e->getMessage(), 'database');
        return 'unknown';
    }
}
function getClassFolders($class_id) {
    global $conn;
    
    $sql = "SELECT f.folder_id as id, f.folder_name, f.user_id as created_by, f.class_id, f.google_folder_id, f.parent_folder_id, f.created_at,
            (SELECT COUNT(*) FROM unified_files WHERE folder_id = f.folder_id) as file_count 
            FROM file_folders f 
            WHERE f.class_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFolderFiles($folder_id) {
    global $conn;
    
    $sql = "SELECT f.*, c.category_name, u.first_name, u.last_name 
            FROM unified_files f 
            LEFT JOIN file_categories c ON f.category_id = c.category_id
            LEFT JOIN users u ON f.user_id = u.uid 
            WHERE f.folder_id = ? 
            ORDER BY f.upload_time DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $folder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function createFolder($class_id, $folder_name, $user_id) {
    global $conn;
    
    try {
        // Load the UnifiedFileManagement class
        require_once BACKEND . 'management/unified_file_management.php';
        $fileManager = new UnifiedFileManagement();
        
        // Use the new method to create a folder
        $result = $fileManager->createFolder($folder_name, $user_id, $class_id);
        
        return $result;
    } catch (Exception $e) {
        log_error("Error creating folder: " . $e->getMessage());
        return false;
    }
}

function renameFolder($folder_id, $new_name, $user_id) {
    global $conn;
    
    try {
        // Load the UnifiedFileManagement class
        require_once BACKEND . 'management/unified_file_management.php';
        $fileManager = new UnifiedFileManagement();
        
        // Use the new method to rename a folder
        $result = $fileManager->renameFolder($folder_id, $user_id, $new_name);
        
        return $result;
    } catch (Exception $e) {
        log_error("Error renaming folder: " . $e->getMessage());
        return false;
    }
}

function deleteFolder($folder_id, $user_id) {
    global $conn;
    
    try {
        // Load the UnifiedFileManagement class
        require_once BACKEND . 'management/unified_file_management.php';
        $fileManager = new UnifiedFileManagement();
        
        // Use the new method to delete a folder
        $result = $fileManager->deleteFolder($folder_id, $user_id);
        
        return $result;
    } catch (Exception $e) {
        log_error("Error deleting folder: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all feedbacks for a specific class
 * 
 * @param int $class_id The ID of the class
 * @return array Array of feedbacks with student details
 */
function getClassFeedbacks($class_id) {
    global $conn;
    
    $query = "SELECT 
                sf.rating_id,
                sf.session_id,
                sf.student_id,
                sf.rating,
                sf.feedback,
                sf.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.profile_picture,
                cs.session_date,
                DATE_FORMAT(cs.start_time, '%h:%i %p') AS session_time
             FROM session_feedback sf
             JOIN class_schedule cs ON sf.session_id = cs.schedule_id
             JOIN users u ON sf.student_id = u.uid
             WHERE cs.class_id = ?
             ORDER BY sf.created_at DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting class feedbacks: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all feedbacks for a TechGuru across all classes
 * 
 * @param int $tutor_id The ID of the tutor
 * @return array Array of feedbacks with class and student details
 */
function getAllTutorFeedbacks($tutor_id) {
    global $conn;
    
    $query = "SELECT 
                sf.rating_id,
                sf.session_id,
                sf.student_id,
                sf.rating,
                sf.feedback,
                sf.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                u.profile_picture,
                cs.session_date,
                DATE_FORMAT(cs.start_time, '%h:%i %p') AS session_time,
                c.class_id,
                c.class_name
             FROM session_feedback sf
             JOIN class_schedule cs ON sf.session_id = cs.schedule_id
             JOIN class c ON cs.class_id = c.class_id
             JOIN users u ON sf.student_id = u.uid
             WHERE sf.tutor_id = ?
             ORDER BY sf.created_at DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting tutor feedbacks: " . $e->getMessage());
        return [];
    }
}

/**
 * Get meeting analytics data for a specific class or tutor
 * 
 * @param int $class_id Optional class ID to filter analytics by class
 * @param int $tutor_id Optional tutor ID to filter analytics by tutor
 * @return array Array of analytics data
 */
function getMeetingAnalytics($class_id = null, $tutor_id = null) {
    global $conn;
    
    try {
        $conditions = [];
        $params = [];
        $types = '';
        
        // Build query conditions based on parameters
        if ($class_id !== null) {
            $conditions[] = "cs.class_id = ?";
            $params[] = $class_id;
            $types .= 'i';
        }
        
        if ($tutor_id !== null) {
            $conditions[] = "ma.tutor_id = ?";
            $params[] = $tutor_id;
            $types .= 'i';
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Get overall analytics summary
        $query = "SELECT 
                  COUNT(DISTINCT m.meeting_id) AS total_sessions,
                  COALESCE(SUM(ma.participant_count), 0) AS total_participants,
                  COALESCE(SUM(TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time)), 0) AS total_minutes,
                  COALESCE(SUM(ma.recording_available), 0) AS total_recordings
              FROM meetings m
              JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
              JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
              $whereClause";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc() ?? [];

        // Convert minutes to hours
        $totalHours = isset($summary['total_minutes']) ? round($summary['total_minutes'] / 60, 1) : 0;

        // Get recent sessions
        $recentQuery = "SELECT 
                      m.meeting_id,
                      cs.schedule_id,
                      cs.class_id,
                      c.class_name,
                      DATE_FORMAT(ma.start_time, '%b %d, %Y') AS session_date,
                      DATE_FORMAT(ma.start_time, '%h:%i %p') AS start_time,
                      DATE_FORMAT(ma.end_time, '%h:%i %p') AS end_time,
                      ma.participant_count,
                      TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time) AS duration_minutes,
                      ma.recording_available
                  FROM meetings m
                  JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                  JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                  JOIN class c ON cs.class_id = c.class_id
                  $whereClause
                  ORDER BY ma.start_time DESC
                  LIMIT 10";
        
        $stmt = $conn->prepare($recentQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $recentSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get session activity
        $activityQuery = "SELECT 
                        DATE_FORMAT(ma.start_time, '%Y-%m-%d') AS date,
                        COUNT(DISTINCT m.meeting_id) AS session_count,
                        SUM(ma.participant_count) AS participant_count,
                        AVG(TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time)) AS avg_duration
                    FROM meetings m
                    JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                    JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                    $whereClause
                    GROUP BY DATE_FORMAT(ma.start_time, '%Y-%m-%d')
                    ORDER BY date ASC
                    LIMIT 30";
        
        $stmt = $conn->prepare($activityQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $activityData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get engagement data
        $engagementQuery = "SELECT 
                          ma.participant_count,
                          COUNT(m.meeting_id) AS frequency
                      FROM meetings m
                      JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                      JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                      $whereClause
                      GROUP BY ma.participant_count
                      ORDER BY ma.participant_count ASC";
        
        $stmt = $conn->prepare($engagementQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $engagementData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get duration distribution
        $durationQuery = "SELECT 
                        CASE
                            WHEN TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time) < 30 THEN 'Under 30 min'
                            WHEN TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time) BETWEEN 30 AND 59 THEN '30-59 min'
                            WHEN TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time) BETWEEN 60 AND 89 THEN '60-89 min'
                            WHEN TIMESTAMPDIFF(MINUTE, ma.start_time, ma.end_time) BETWEEN 90 AND 119 THEN '90-119 min'
                            ELSE '120+ min'
                        END AS duration_range,
                        COUNT(m.meeting_id) AS frequency
                    FROM meetings m
                    JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                    JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                    $whereClause
                    GROUP BY duration_range
                    ORDER BY 
                        CASE duration_range
                            WHEN 'Under 30 min' THEN 1
                            WHEN '30-59 min' THEN 2
                            WHEN '60-89 min' THEN 3
                            WHEN '90-119 min' THEN 4
                            ELSE 5
                        END";
        
        $stmt = $conn->prepare($durationQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $durationData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Return compiled analytics data
        return [
            'total_sessions' => $summary['total_sessions'] ?? 0,
            'total_participants' => $summary['total_participants'] ?? 0,
            'total_hours' => $totalHours,
            'total_recordings' => $summary['total_recordings'] ?? 0,
            'recent_sessions' => $recentSessions,
            'activity_data' => $activityData,
            'engagement_data' => $engagementData,
            'duration_data' => $durationData
        ];
    } catch (Exception $e) {
        log_error("Error getting meeting analytics: " . $e->getMessage(), 'database');
        return [
            'total_sessions' => 0,
            'total_participants' => 0,
            'total_hours' => 0,
            'total_recordings' => 0,
            'recent_sessions' => [],
            'activity_data' => [],
            'engagement_data' => [],
            'duration_data' => []
        ];
    }
}


/**
 * Insert or update meeting analytics data after a meeting ends
 * 
 * @param int $meeting_id The meeting ID
 * @param int $tutor_id The tutor ID
 * @param array $analytics The analytics data to store
 * @return bool Success status
 */
function updateMeetingAnalytics($meeting_id, $tutor_id, $analytics) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Check if analytics record already exists
        $stmt = $conn->prepare("SELECT id FROM meeting_analytics WHERE meeting_id = ?");
        $stmt->bind_param("i", $meeting_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $analytics_id = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("
                UPDATE meeting_analytics 
                SET 
                    participant_count = ?,
                    duration = ?,
                    start_time = ?,
                    end_time = ?,
                    recording_available = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "iissii", 
                $analytics['participant_count'],
                $analytics['duration'],
                $analytics['start_time'],
                $analytics['end_time'],
                $analytics['recording_available'],
                $analytics_id
            );
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO meeting_analytics 
                (meeting_id, tutor_id, participant_count, duration, start_time, end_time, recording_available)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiisssi", 
                $meeting_id,
                $tutor_id,
                $analytics['participant_count'],
                $analytics['duration'],
                $analytics['start_time'],
                $analytics['end_time'],
                $analytics['recording_available']
            );
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to save meeting analytics");
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Error updating meeting analytics: " . $e->getMessage(), 'database');
        return false;
    }
}

/**
 * Get recording visibility settings for a class
 * 
 * @param int $class_id The class ID
 * @return array An array of recording visibility settings
 */
function getRecordingVisibilitySettings($class_id) {
    global $conn;
    
    try {
        $query = "SELECT 
                    rv.recording_id, 
                    rv.is_visible, 
                    rv.created_at,
                    rv.updated_at,
                    CONCAT(u.first_name, ' ', u.last_name) AS updated_by_name
                FROM recording_visibility rv
                JOIN users u ON rv.created_by = u.uid
                WHERE rv.class_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['recording_id']] = $row;
        }
        
        return $settings;
    } catch (Exception $e) {
        log_error("Error getting recording visibility settings: " . $e->getMessage(), 'database');
        return [];
    }
}

/**
 * Update recording visibility setting
 * 
 * @param string $recording_id The recording ID from BBB
 * @param int $class_id The class ID
 * @param bool $is_visible Whether the recording should be visible to students
 * @param int $user_id The user ID of who made the change
 * @return bool Success status
 */
function updateRecordingVisibility($recording_id, $class_id, $is_visible, $user_id) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Check if setting already exists
        $query = "SELECT id FROM recording_visibility WHERE recording_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $recording_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $visible = $is_visible ? 1 : 0;
        
        if ($result->num_rows > 0) {
            // Update existing record
            $query = "UPDATE recording_visibility 
                      SET is_visible = ?, created_by = ? 
                      WHERE recording_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $visible, $user_id, $recording_id);
        } else {
            // Insert new record
            $query = "INSERT INTO recording_visibility 
                      (recording_id, class_id, is_visible, created_by) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("siis", $recording_id, $class_id, $visible, $user_id);
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to update recording visibility");
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Error updating recording visibility: " . $e->getMessage(), 'database');
        return false;
    }
}

/**
 * Get all visible recordings for a student
 * 
 * @param int $student_id The student ID
 * @return array An array of visible recordings
 */
function getVisibleRecordingsForStudent($student_id) {
    global $conn;
    
    try {
        // Get all classes the student is enrolled in
        $query = "SELECT c.class_id, c.class_name
                  FROM enrollments e
                  JOIN class c ON e.class_id = c.class_id
                  WHERE e.student_id = ? AND e.status = 'active'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $allRecordings = [];
        
        // Initialize meeting management
        require_once BACKEND . 'meeting_management.php';
        $meeting = new MeetingManagement();
        
        foreach ($classes as $class) {
            // Get all recordings for this class that are marked as visible
            $query = "SELECT rv.recording_id
                      FROM recording_visibility rv
                      WHERE rv.class_id = ? AND rv.is_visible = 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $class['class_id']);
            $stmt->execute();
            $visibleRecordings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // If there are visible recordings, get their details from BBB
            if (!empty($visibleRecordings)) {
                $result = $meeting->getClassRecordings($class['class_id']);
                
                if ($result['success'] && !empty($result['recordings'])) {
                    foreach ($result['recordings'] as $recording) {
                        // Check if this recording is in our visible list
                        $isVisible = array_filter($visibleRecordings, function($item) use ($recording) {
                            return $item['recording_id'] === $recording['recordID'];
                        });
                        
                        if (!empty($isVisible)) {
                            $recording['class_id'] = $class['class_id'];
                            $recording['class_name'] = $class['class_name'];
                            $allRecordings[] = $recording;
                        }
                    }
                }
            }
        }
        
        // Sort recordings by date, newest first
        usort($allRecordings, function($a, $b) {
            return strtotime($b['startTime']) - strtotime($a['startTime']);
        });
        
        return $allRecordings;
    } catch (Exception $e) {
        log_error("Error getting visible recordings for student: " . $e->getMessage(), 'database');
        return [];
    }
}
/**
 * Get aggregated statistics for meetings
 * @param string $tutorId The tutor's ID
 * @param string $period daily|weekly|monthly
 * @param string $startDate Start date in Y-m-d format
 * @param string $endDate End date in Y-m-d format
 * @return array Aggregated statistics
 */
function getAggregatedStats($tutorId, $period = 'daily', $startDate = null, $endDate = null) {
    global $conn;
    try {
        $groupBy = '';
        switch ($period) {
            case 'daily':
                $groupBy = 'DATE(start_time)';
                break;
            case 'weekly':
                $groupBy = 'YEARWEEK(start_time)';
                break;
            case 'monthly':
                $groupBy = 'DATE_FORMAT(start_time, "%Y-%m")';
                break;
            default:
                throw new Exception("Invalid period specified");
        }

        $query = "SELECT 
                    $groupBy as period,
                    COUNT(*) as total_sessions,
                    SUM(participant_count) as total_participants,
                    AVG(participant_count) as avg_participants,
                    AVG(duration) as avg_duration,
                    SUM(recording_available) as total_recordings
                 FROM meeting_analytics 
                 WHERE tutor_id = ?";

        $params = [$tutorId];

        if ($startDate && $endDate) {
            $query .= " AND start_time BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $query .= " GROUP BY $groupBy ORDER BY period";

        $stmt = $conn->prepare($query);
        $stmt->execute($params); 
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calculate additional metrics
        foreach ($results as &$row) {
            $row['engagement_rate'] = $row['avg_participants'] / ($row['total_sessions'] ?: 1);
            $row['recording_rate'] = $row['total_recordings'] / ($row['total_sessions'] ?: 1) * 100;
        }

        return $results;

    } catch (Exception $e) {
        log_error("Analytics aggregation error: " . $e->getMessage(), "error");
        return [];
    }
}

/**
 * Get participation trends by hour of day
 * @param string $tutorId The tutor's ID
 * @param string $startDate Start date in Y-m-d format
 * @param string $endDate End date in Y-m-d format
 * @return array Hourly participation trends
 */
function getParticipationTrends($tutorId, $startDate = null, $endDate = null) {
    global $conn;
    try {
        $query = "SELECT 
                    DATE_FORMAT(start_time, '%H:00') as hour_of_day,
                    AVG(participant_count) as avg_participants,
                    COUNT(*) as session_count
                 FROM meeting_analytics 
                 WHERE tutor_id = ?";
        
        $params = [$tutorId];

        if ($startDate && $endDate) {
            $query .= " AND start_time BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $query .= " GROUP BY hour_of_day ORDER BY hour_of_day";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Participation trend analysis error: " . $e->getMessage(), "error");
        return [];
    }
}

/**
 * Get duration distribution statistics
 * @param string $tutorId The tutor's ID
 * @param string $startDate Start date in Y-m-d format
 * @param string $endDate End date in Y-m-d format
 * @return array Duration distribution data
 */
function getDurationDistribution($tutorId, $startDate = null, $endDate = null) {
    global $conn;
    try {
        $query = "SELECT 
                    CASE 
                        WHEN duration <= 1800 THEN '0-30'
                        WHEN duration <= 3600 THEN '31-60'
                        WHEN duration <= 5400 THEN '61-90'
                        ELSE '90+'
                    END as duration_range,
                    COUNT(*) as session_count
                 FROM meeting_analytics 
                 WHERE tutor_id = ?";
        
        $params = [$tutorId];

        if ($startDate && $endDate) {
            $query .= " AND start_time BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $query .= " GROUP BY duration_range ORDER BY 
                    CASE duration_range 
                        WHEN '0-30' THEN 1 
                        WHEN '31-60' THEN 2 
                        WHEN '61-90' THEN 3 
                        ELSE 4 
                    END";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Duration distribution analysis error: " . $e->getMessage(), "error");
        return [];
    }
}

/**
 * Get recent sessions with details
 * @param string $tutorId The tutor's ID
 * @param int $limit Number of recent sessions to retrieve
 * @return array Recent session data
 */
function getRecentSessions($tutorId, $limit = 5) {
    global $conn;
    try {
        $query = "SELECT 
                    ma.*,
                    m.name as meeting_name
                 FROM meeting_analytics ma
                 JOIN meetings m ON ma.meeting_id = m.meeting_id
                 WHERE ma.tutor_id = ?
                 ORDER BY ma.start_time DESC
                 LIMIT ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([$tutorId, $limit]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Recent sessions retrieval error: " . $e->getMessage(), "error");
        return [];
    }
}

/**
 * Get overall statistics summary
 * @param string $tutorId The tutor's ID
 * @return array Summary statistics
 */
function getStatsSummary($tutorId) {
    global $conn;
    try {
        $query = "SELECT 
                    COUNT(*) as total_sessions,
                    SUM(participant_count) as total_participants,
                    AVG(duration) as avg_duration,
                    SUM(recording_available) as total_recordings
                 FROM meeting_analytics 
                 WHERE tutor_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([$tutorId]);
        $result = $stmt->get_result()->fetch_assoc();

        // Add calculated metrics
        $result['total_hours'] = round(($result['avg_duration'] * $result['total_sessions']) / 3600, 1);
        $result['avg_participants'] = round($result['total_participants'] / ($result['total_sessions'] ?: 1), 1);

        return $result;

    } catch (Exception $e) {
        log_error("Stats summary error: " . $e->getMessage(), "error");
        return [];
    }
}

/**
 * Get detailed analytics for a specific class
 * @param int $classId Class identifier
 * @param string $tutorId Tutor's user ID
 * @return array Comprehensive analytics data for the class
 */
function getClassAnalytics($classId, $tutorId) {
    global $conn;
    try {
        // Summary statistics
        $summaryQuery = "SELECT 
            COUNT(*) as total_sessions,
            SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_minutes,
            COUNT(DISTINCT m.meeting_uid) as total_meetings,
            SUM(CASE WHEN m.recording_url IS NOT NULL THEN 1 ELSE 0 END) as total_recordings
        FROM class_schedule cs
        LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
        WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')";

        $stmt = $conn->prepare($summaryQuery);
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $summaryResult = $stmt->get_result()->fetch_assoc();

        // Calculate enrollments to get a sense of participants
        $enrollmentQuery = "SELECT COUNT(*) as total_enrollments 
                            FROM enrollments 
                            WHERE class_id = ? AND status = 'active'";
        $stmt = $conn->prepare($enrollmentQuery);
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $enrollmentResult = $stmt->get_result()->fetch_assoc();

        // Session activity over time
        $activityQuery = "SELECT 
            cs.session_date, 
            COUNT(*) as session_count,
            SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_duration
        FROM class_schedule cs
        WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')
        GROUP BY cs.session_date
        ORDER BY cs.session_date";

        $stmt = $conn->prepare($activityQuery);
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $activityResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Recent sessions
        $recentQuery = "SELECT 
            cs.schedule_id,
            cs.session_date,
            cs.start_time,
            cs.end_time,
            cs.status,
            m.meeting_uid,
            m.recording_url,
            TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) as duration_minutes
        FROM class_schedule cs
        LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
        WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')
        ORDER BY cs.session_date DESC, cs.start_time DESC
        LIMIT 5";

        $stmt = $conn->prepare($recentQuery);
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $recentSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Session duration distribution
        $durationQuery = "SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) <= 30 THEN '0-30 min'
                WHEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) <= 60 THEN '31-60 min'
                WHEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) <= 90 THEN '61-90 min'
                ELSE '90+ min'
            END as duration_range,
            COUNT(*) as session_count
        FROM class_schedule cs
        WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')
        GROUP BY duration_range";

        $stmt = $conn->prepare($durationQuery);
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $durationDistribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Format the data for the frontend
        $totalHours = round($summaryResult['total_minutes'] / 60, 1);
        
        return [
            'success' => true,
            'stats' => [
                'total_sessions' => $summaryResult['total_sessions'],
                'total_hours' => $totalHours,
                'total_participants' => $enrollmentResult['total_enrollments'],
                'total_recordings' => $summaryResult['total_recordings'],
                'avg_duration' => $summaryResult['total_sessions'] > 0 ? 
                                round($summaryResult['total_minutes'] / $summaryResult['total_sessions'], 1) : 0
            ],
            'session_activity' => $activityResult,
            'recent_sessions' => $recentSessions,
            'duration_distribution' => $durationDistribution
        ];
    } catch (Exception $e) {
        log_error("Error retrieving class analytics: " . $e->getMessage(), "meeting");
        return [
            'success' => false,
            'error' => 'Failed to retrieve analytics data',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get overall tutor analytics across all classes
 * @param string $tutorId Tutor's user ID
 * @param string $period Period to analyze (last_week, last_month, all_time)
 * @return array Analytics data across all classes
 */
function getTutorAnalytics($tutorId, $period = 'all_time') {
    global $conn;
    try {
        // Date condition based on period
        $dateCondition = "";
        if ($period === 'last_week') {
            $dateCondition = " AND cs.session_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        } elseif ($period === 'last_month') {
            $dateCondition = " AND cs.session_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }

        // Overall summary
        $summaryQuery = "SELECT 
            COUNT(*) as total_sessions,
            SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_minutes,
            COUNT(DISTINCT cs.class_id) as total_classes,
            SUM(CASE WHEN m.recording_url IS NOT NULL THEN 1 ELSE 0 END) as total_recordings
        FROM class_schedule cs
        JOIN class c ON cs.class_id = c.class_id
        LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
        WHERE c.tutor_id = ? AND cs.status IN ('completed', 'confirmed')" . $dateCondition;

        $stmt = $conn->prepare($summaryQuery);
        $stmt->bind_param("s", $tutorId);
        $stmt->execute();
        $summaryResult = $stmt->get_result()->fetch_assoc();

        // Get total enrollments across all classes
        $enrollmentQuery = "SELECT COUNT(*) as total_enrollments 
                            FROM enrollments e
                            JOIN class c ON e.class_id = c.class_id
                            WHERE c.tutor_id = ? AND e.status = 'active'";
        $stmt = $conn->prepare($enrollmentQuery);
        $stmt->bind_param("s", $tutorId);
        $stmt->execute();
        $enrollmentResult = $stmt->get_result()->fetch_assoc();

        // Session activity by date
        $activityQuery = "SELECT 
            cs.session_date, 
            COUNT(*) as session_count,
            SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_duration
        FROM class_schedule cs
        JOIN class c ON cs.class_id = c.class_id
        WHERE c.tutor_id = ? AND cs.status IN ('completed', 'confirmed')" . $dateCondition . "
        GROUP BY cs.session_date
        ORDER BY cs.session_date";

        $stmt = $conn->prepare($activityQuery);
        $stmt->bind_param("s", $tutorId);
        $stmt->execute();
        $activityResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Session activity by class
        $classActivityQuery = "SELECT 
            c.class_id,
            c.class_name,
            COUNT(cs.schedule_id) as session_count,
            SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_duration
        FROM class c
        LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.status IN ('completed', 'confirmed')
        WHERE c.tutor_id = ?" . $dateCondition . "
        GROUP BY c.class_id
        ORDER BY session_count DESC";

        $stmt = $conn->prepare($classActivityQuery);
        $stmt->bind_param("s", $tutorId);
        $stmt->execute();
        $classActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Format the data for the frontend
        $totalHours = round($summaryResult['total_minutes'] / 60, 1);
        
        return [
            'success' => true,
            'stats' => [
                'total_sessions' => $summaryResult['total_sessions'],
                'total_hours' => $totalHours,
                'total_classes' => $summaryResult['total_classes'],
                'total_participants' => $enrollmentResult['total_enrollments'],
                'total_recordings' => $summaryResult['total_recordings'],
                'avg_duration' => $summaryResult['total_sessions'] > 0 ? 
                                round($summaryResult['total_minutes'] / $summaryResult['total_sessions'], 1) : 0
            ],
            'session_activity' => $activityResult,
            'class_activity' => $classActivity
        ];
    } catch (Exception $e) {
        log_error("Error retrieving tutor analytics: " . $e->getMessage(), "meeting");
        return [
            'success' => false,
            'error' => 'Failed to retrieve analytics data',
            'message' => $e->getMessage()
        ];
    }
}
?>
