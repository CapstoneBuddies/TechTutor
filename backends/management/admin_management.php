<?php
/**
 * Get all courses with their subject and enrollment counts
 * 
 * @return array Array of courses with counts
 */
function getCoursesWithCounts() {
    global $conn;
    
    try {
        $query = "SELECT 
            c.course_id,
            c.course_name,
            c.course_desc,
            COUNT(DISTINCT s.subject_id) as subject_count,
            COUNT(DISTINCT cl.tutor_id) as tutor_count,
            COUNT(DISTINCT e.student_id) as student_count
        FROM course c
        LEFT JOIN subject s ON c.course_id = s.course_id
        LEFT JOIN class cl ON s.subject_id = cl.subject_id
        LEFT JOIN enrollments e ON cl.class_id = e.class_id AND e.status = 'active'
        GROUP BY c.course_id, c.course_name, c.course_desc
        ORDER BY c.course_name";
        
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error getting courses with counts: " . $e->getMessage(), 'database');
        return [];
    }
}

/**
 * Get class details for admin view
 * 
 * @param int $class_id The class ID
 * @return array|null Class details or null if not found
 */
function getAdminClassDetails($class_id) {
    global $conn;
    
    try {
        $query = "SELECT 
            c.*,
            s.subject_name,
            s.subject_desc,
            co.course_name,
            u.first_name as tutor_first_name,
            u.last_name as tutor_last_name,
            u.email as tutor_email,
            COUNT(DISTINCT e.student_id) as enrolled_students
        FROM class c
        JOIN subject s ON c.subject_id = s.subject_id
        JOIN course co ON s.course_id = co.course_id
        JOIN users u ON c.tutor_id = u.uid
        LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
        WHERE c.class_id = ?
        AND c.status != 'pending'
        AND u.status = 1
        GROUP BY c.class_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        log_error("Error getting admin class details: " . $e->getMessage(), 'database');
        return null;
    }
}

/**
 * Get all subjects with their course and enrollment counts
 * 
 * @return array Array of subjects with counts
 */
function getSubjectsWithCounts() {
    global $conn;
    
    try {
        $query = "SELECT 
            s.subject_id,
            s.subject_name,
            s.subject_desc,
            s.image,
            s.is_active,
            c.course_id,
            c.course_name,
            COUNT(DISTINCT cl.tutor_id) as tutor_count,
            COUNT(DISTINCT e.student_id) as student_count
        FROM subject s
        LEFT JOIN course c ON s.course_id = c.course_id
        LEFT JOIN class cl ON s.subject_id = cl.subject_id
        LEFT JOIN enrollments e ON cl.class_id = e.class_id AND e.status = 'active'
        GROUP BY s.subject_id, s.subject_name, s.subject_desc, s.is_active, c.course_id, c.course_name
        ORDER BY c.course_name, s.subject_name";
        
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error getting subjects with counts: " . $e->getMessage(), 'database');
        return [];
    }
}

/**
 * Get all courses for dropdown
 * 
 * @return array Array of courses
 */
function getAllCourses() {
    global $conn; 
    
    try {
        $query = "SELECT c.*, (SELECT count(*) FROM subject WHERE course_id = c.course_id AND is_active = 1) AS subject_count FROM course c ORDER BY course_name;";
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error getting courses: " . $e->getMessage(), 'database');
        return [];
    }
}

/**
 * Update subject status and notify relevant users
 * 
 * @param int $subject_id The subject ID
 * @param bool $is_active Whether to set the subject as active
 * @return bool True if successful, false otherwise
 */
function updateSubjectStatus($subject_id, $is_active) {
    global $conn;

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE subject SET is_active = ? WHERE subject_id = ?");
        $stmt->bind_param("ii", $is_active, $subject_id);
        $stmt->execute();
        $stmt->close(); 

        $stmt = $conn->prepare("
            SELECT s.subject_name, s.course_id, c.course_name 
            FROM subject s 
            JOIN course c ON s.course_id = c.course_id 
            WHERE s.subject_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $subject = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$subject) {
            throw new Exception("Subject not found");
        }

        $stmt = $conn->prepare("
            SELECT DISTINCT u.uid, u.email 
            FROM class cl 
            JOIN users u ON cl.tutor_id = u.uid 
            WHERE cl.subject_id = ? 
            AND u.status = 1");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $tutors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $status = $is_active ? 'activated' : 'deactivated';
        foreach ($tutors as $tutor) {
            insertNotification(
                $tutor['uid'],
                'TECHGURU',
                "Subject '{$subject['subject_name']}' in course '{$subject['course_name']}' has been {$status}",
                "class/details?subject_id={$subject_id}",
                null,
                $is_active ? 'bi-check-circle' : 'bi-x-circle',
                $is_active ? 'text-success' : 'text-danger'
            );
        }

        $conn->commit();
        return ['success' => true, 'message' => "{$subject['subject_name']} has been successfully {$status}"];
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Error updating subject status: " . $e->getMessage());
        return ['success' => false, 'message' => "An error occurred while updating subject information"];
    }
}


/**
 * Get subject statistics
 * 
 * @return array Array containing statistics
 */
function getSubjectStatistics() {
    global $conn;
    
    try {
        $query = "SELECT 
            (SELECT COUNT(DISTINCT subject_id) FROM subject) as total_subjects,
            (SELECT COUNT(DISTINCT subject_id) FROM subject WHERE is_active = 1) as active_subjects,
            (SELECT COUNT(DISTINCT subject_id) FROM subject WHERE is_active = 0) as inactive_subjects,
            (SELECT COUNT(DISTINCT course_id) FROM course) as total_courses,
            (SELECT COUNT(DISTINCT u.uid) 
             FROM users u 
             JOIN class c ON u.uid = c.tutor_id 
             WHERE u.role = 'TECHGURU' AND u.status = 'ACTIVE') as active_tutors,
            (SELECT COUNT(DISTINCT e.student_id) 
             FROM enrollments e 
             WHERE e.status = 'active') as enrolled_students";
        
        $result = $conn->query($query);
        return $result->fetch_assoc() ?: [
            'total_subjects' => 0,
            'active_subjects' => 0,
            'inactive_subjects' => 0,
            'total_courses' => 0,
            'active_tutors' => 0,
            'enrolled_students' => 0
        ];
    } catch (Exception $e) {
        log_error("Error getting subject statistics: " . $e->getMessage());
        return [
            'total_subjects' => 0,
            'active_subjects' => 0,
            'inactive_subjects' => 0,
            'total_courses' => 0,
            'active_tutors' => 0,
            'enrolled_students' => 0
        ];
    }
}

/**
 * Add a new course
 * 
 * @param string $courseName Name of the course
 * @param string $courseDesc Description of the course
 * @return array Operation result with success status and message
 */
function addCourse($courseName, $courseDesc = '') {
    global $conn;
    
    try {
        if (empty($courseName)) {
            return ['success' => false, 'message' => 'Course name is required'];
        }

        $conn->begin_transaction();

        // Check if course already exists
        $stmt = $conn->prepare("SELECT course_id FROM course WHERE course_name = ?");
        $stmt->bind_param("s", $courseName);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Course already exists'];
        }

        // Insert new course
        $stmt = $conn->prepare("INSERT INTO course (course_name, course_desc) VALUES (?, ?)");
        $stmt->bind_param("ss", $courseName, $courseDesc);
        $stmt->execute();
        
        $courseId = $conn->insert_id;
        
        // Log the action
        log_error("New course added: $courseName (ID: $courseId)", "security");
        
        $conn->commit();
        return ['success' => true, 'message' => 'Course added successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Error adding course: " . $e->getMessage(), 'database');
        return ['success' => false, 'message' => 'Failed to add course'];
    }
}

/**
 * Add a new subject to a course
 * 
 * @param int $courseId ID of the course
 * @param string $subjectName Name of the subject
 * @param string $subjectDesc Description of the subject
 * @param string $subjectImage Cover Image of the subject
 * @return array Operation result with success status and message
 */
function addSubject($courseId, $subjectName, $subjectDesc, $subjectImage) {
    global $conn;
    
    try {
        if (empty($subjectName)) {
            return ['success' => false, 'message' => 'Subject name is required'];
        }

        $conn->begin_transaction();

        // Verify course exists
        $stmt = $conn->prepare("SELECT course_id FROM course WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Course not found'];
        }

        // Check if subject already exists in this course
        $stmt = $conn->prepare("SELECT subject_id FROM subject WHERE subject_name = ? AND course_id = ?");
        $stmt->bind_param("si", $subjectName, $courseId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Subject already exists in this course'];
        }

        // Insert new subject
        $stmt = $conn->prepare("INSERT INTO subject (course_id, subject_name, subject_desc, is_active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("iss", $courseId, $subjectName, $subjectDesc);
        $stmt->execute();
        
        $subjectId = $conn->insert_id;
        
        // Save image filename to the db
         if (isset($_FILES['subjectImage']) && $_FILES['subjectImage']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['subjectImage'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed extensions
            $allowedExt = array('jpg', 'jpeg', 'png', 'gif');
            
            // Validate file type and size
            if (!in_array($fileExt, $allowedExt)) {
                $response['message'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedExt);
                echo json_encode($response);
                exit();
            }
            
            if ($fileSize > 5242880) { // 5MB in bytes
                $response['message'] = 'File size too large. Maximum size: 5MB';
                echo json_encode($response);
                exit();
            }
            
            // Create new filename with user ID
            $newFileName = $subjectId . '.' . $fileExt;
            $uploadPath = ROOT_PATH . '/assets/img/subjects/' . $newFileName;
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $stmt->prepare("UPDATE subject SET image = ? WHERE subject_id = ?");
                $stmt->bind_param('si',$newFileName,$subjectId);
                if(!$stmt->execute()) {
                    throw new Exception("Subject ID:{$subjectId}, Cover Image failed to save");
                }
            } else {
                log_error("Failed to move uploaded file from $fileTmp to $uploadPath", 'database');
                $response['message'] = 'Failed to upload Subject Cover Photo';
                echo json_encode($response);
                exit();
            }
         }
        // Log the action
        log_error("New subject added: $subjectName in course ID: $courseId (Subject ID: $subjectId)", "security");
        
        $conn->commit();
        return ['success' => true, 'message' => 'Subject added successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        log_error("Error adding subject: " . $e->getMessage(), 'database');
        return ['success' => false, 'message' => 'Failed to add subject'];
    }
}
function editSubject() {
    return ['success' => false, 'message' => 'test'];
}
/** 
 * Retrieving user profile details
 */
function getUserDetails($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE uid = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); 

        return $stmt->get_result()->fetch_assoc();
    } catch (mysqli_sql_exception $m) {
        log_error($m->getMessage(), 'database');
        return [];
    } catch (Exception $e) {
        log_error($e->getMessage());
        return [];
    }
}
function updateUserRole($userId, $newRole) {
    global $conn;

    try {
        // Update role in the database
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE uid = ?");
        $result = $stmt->execute([$newRole, $userId]);

        if ($result) {
            // Fetch user details for email notification & notification
            $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE uid = ?");
            $stmt->execute([$userId]);
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                // Email Notification
                $subject = "Your Account Role Has Been Updated";

                $body = "
                    <div style='font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px;'>
                        <div style='max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #0d6efd; text-align: center;'>Role Update Notification</h2>
                            <p>Dear <strong>{$user['first_name']} {$user['last_name']}</strong>,</p>
                            <p>We would like to inform you that your role on <strong>TechTutor</strong> has been updated.</p>
                            <p><strong>New Role:</strong> <span style='color: #198754;'>{$newRole}</span></p>
                            <p>If you have any questions or concerns, please contact support.</p>
                            <hr>
                            <p style='font-size: 12px; text-align: center; color: #6c757d;'>This is an automated email, please do not reply.</p>
                        </div>
                    </div>
                ";

                $mailer = getMailerInstance();
                $mailer->addAddress($user['email']);
                $mailer->Subject = $subject;
                $mailer->Body = $body;

                // Try sending the email
                if (!$mailer->send()) {
                    throw new Exception("Failed to send email to {$user['email']}");
                }

                // Save notification to the database
                $message = "Your role has been updated to <strong>{$newRole}</strong>.";
                $link = BASE . "profile"; // Redirect to the user's profile
                sendNotification($userId, $newRole, $message, $link, null, 'bi-person-badge-fill', 'text-warning');
            }
        }

        return $result;
    } catch (Exception $e) {
        // Log the error
        log_error($e->getMessage(), 'mail');
        return false;
    }
}
function updateCover($subjectId, $file) {
    global $conn;
    // Validate inputs
    if (!$subjectId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid subject ID or file upload']);
        return false;
    }

    $uploadDir = ROOT_PATH . '/assets/img/subjects/'; // Adjust path if needed

    // Ensure directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    try {
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];

        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowed extensions
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type and size
        if (!in_array($fileExt, $allowedExt)) {
            log_error("Invalid file extension");
            http_response_code(400);
            return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedExt)];
        }

        if ($fileSize > 5242880) { // 5MB limit
            http_response_code(400);
            return ['success' => false, 'message' => 'File size too large. Maximum: 5MB'];
        }

        // Create a unique filename
        $newFileName = "{$subjectId}." . $fileExt;
        $uploadPath = $uploadDir . $newFileName;

        // delete current file
        $stmt= $conn->prepare("SELECT image FROM subject WHERE subject_id = ?");
        $stmt->bind_param('i',$subjectId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc()['image'];
        if (file_exists($uploadDir.$result)) {
            if (!unlink($uploadDir.$result)) {
                http_response_code(500);
                return ['success' => false, 'message' => 'Failed to delete existing file'];
            }
        }

        // Move the file to the upload directory
        if (!move_uploaded_file($fileTmp, $uploadPath)) {
            http_response_code(500);
            return ['success' => false, 'message' => 'Failed to upload image'];
        }

        // Update database with new image filename
        $stmt = $conn->prepare("UPDATE subject SET image = ? WHERE subject_id = ?");
        $stmt->bind_param("si", $newFileName, $subjectId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Subject cover updated successfully'];
        } else {
            http_response_code(500);
            return ['success' => false, 'message' => 'Failed to update subject cover in database'];
        }

        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        return ['success' => false, 'message' => 'Failed to process the cover update'];
    }
}

/**
 * Admin function to get class files
 * 
 * This retrieves files for a specific class using the UnifiedFileManagement
 * 
 * @param int $class_id The class ID to get files for
 * @return array An array of file information
 */
function getAdminClassFiles($class_id) {
    require_once BACKEND.'unified_file_management.php';
    $fileManager = new UnifiedFileManagement();
    
    try {
        return $fileManager->getClassFiles($class_id);
    } catch (Exception $e) {
        log_error("Admin error getting class files: " . $e->getMessage(), "admin");
        return [];
    }
}

/**
 * Get platform-wide statistics for admin reporting
 * 
 * @param string $period Optional period filter (weekly, monthly, yearly)
 * @return array Comprehensive platform statistics
 */
function getPlatformStats($period = 'all') {
    global $conn;
    
    try {
        // Date condition based on period
        $dateCondition = "";
        if ($period === 'weekly') {
            $dateCondition = "AND DATE(created_on) >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)";
        } elseif ($period === 'monthly') {
            $dateCondition = "AND DATE(created_on) >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)";
        } elseif ($period === 'yearly') {
            $dateCondition = "AND DATE(created_on) >= DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)";
        }
        
        // User statistics
        $usersQuery = "SELECT 
            COUNT(CASE WHEN role = 'TECHKID' THEN 1 END) as total_students,
            COUNT(CASE WHEN role = 'TECHGURU' THEN 1 END) as total_tutors,
            COUNT(CASE WHEN role = 'ADMIN' THEN 1 END) as total_admins,
            COUNT(CASE WHEN status = 1 THEN 1 END) as active_users,
            COUNT(CASE WHEN status = 0 THEN 1 END) as inactive_users,
            COUNT(CASE WHEN role = 'TECHKID' AND DATE(created_on) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 END) as new_students,
            COUNT(CASE WHEN role = 'TECHGURU' AND DATE(created_on) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 END) as new_tutors
        FROM users
        WHERE 1=1 " . $dateCondition;
        
        $result = $conn->query($usersQuery);
        $userStats = $result->fetch_assoc();
        
        // Education statistics
        $educationQuery = "SELECT 
            (SELECT COUNT(*) FROM course) as total_courses,
            (SELECT COUNT(*) FROM subject WHERE is_active = 1) as active_subjects,
            (SELECT COUNT(*) FROM class WHERE status = 'active') as active_classes,
            (SELECT COUNT(*) FROM class WHERE status = 'completed') as completed_classes,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'active') as active_enrollments,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'completed') as completed_enrollments,
            (SELECT AVG(rating) FROM users WHERE role = 'TECHGURU' AND rating > 0) as avg_tutor_rating
        FROM dual";
        
        $result = $conn->query($educationQuery);
        $educationStats = $result->fetch_assoc();
        
        // Activity statistics
        $activityQuery = "SELECT 
            COUNT(DISTINCT cs.schedule_id) as total_sessions,
            SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) / 60 as total_teaching_hours,
            COUNT(DISTINCT m.meeting_id) as total_online_meetings,
            COUNT(DISTINCT rv.id) as total_recordings,
            COUNT(DISTINCT sf.rating_id) as total_feedbacks
        FROM class_schedule cs
        LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
        LEFT JOIN recording_visibility rv ON cs.schedule_id = rv.schedule_id
        LEFT JOIN session_feedback sf ON cs.schedule_id = sf.session_id
        WHERE cs.status = 'completed'";
        
        $result = $conn->query($activityQuery);
        $activityStats = $result->fetch_assoc();
        
        // Growth trend over the last 12 months
        $growthQuery = "SELECT 
            DATE_FORMAT(created_on, '%Y-%m') as month,
            COUNT(CASE WHEN role = 'TECHKID' THEN 1 END) as new_students,
            COUNT(CASE WHEN role = 'TECHGURU' THEN 1 END) as new_tutors,
            COUNT(*) as total_new_users
        FROM users
        WHERE created_on >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_on, '%Y-%m')
        ORDER BY month ASC";
        
        $result = $conn->query($growthQuery);
        $growthData = $result->fetch_all(MYSQLI_ASSOC);
        
        // Format months for display
        foreach ($growthData as &$data) {
            $date = DateTime::createFromFormat('Y-m', $data['month']);
            $data['month_display'] = $date->format('M Y');
        }
        
        // Calculate growth percentages
        $totalUsers = $userStats['total_students'] + $userStats['total_tutors'] + $userStats['total_admins'];
        $newUsers = $userStats['new_students'] + $userStats['new_tutors'];
        $monthlyGrowth = $totalUsers > 0 ? round(($newUsers / $totalUsers) * 100, 1) : 0;
        
        // Subject popularity (top 5)
        $subjectPopularityQuery = "SELECT 
            s.subject_name,
            COUNT(DISTINCT e.enrollment_id) as enrollment_count
        FROM subject s
        JOIN class c ON s.subject_id = c.subject_id
        JOIN enrollments e ON c.class_id = e.class_id
        WHERE e.status = 'active'
        GROUP BY s.subject_id
        ORDER BY enrollment_count DESC
        LIMIT 5";
        
        $result = $conn->query($subjectPopularityQuery);
        $popularSubjects = $result->fetch_all(MYSQLI_ASSOC);
        
        // Return combined statistics
        return [
            'user_stats' => array_merge($userStats, [
                'total_users' => $totalUsers,
                'monthly_growth' => $monthlyGrowth
            ]),
            'education_stats' => $educationStats,
            'activity_stats' => $activityStats,
            'growth_data' => $growthData,
            'popular_subjects' => $popularSubjects
        ];
    } catch (Exception $e) {
        log_error("Error getting platform statistics: " . $e->getMessage(), 'admin');
        return [
            'user_stats' => [
                'total_students' => 0,
                'total_tutors' => 0,
                'total_admins' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'new_students' => 0,
                'new_tutors' => 0,
                'total_users' => 0,
                'monthly_growth' => 0
            ],
            'education_stats' => [
                'total_courses' => 0,
                'active_subjects' => 0,
                'active_classes' => 0,
                'completed_classes' => 0,
                'active_enrollments' => 0,
                'completed_enrollments' => 0,
                'avg_tutor_rating' => 0
            ],
            'activity_stats' => [
                'total_sessions' => 0,
                'total_teaching_hours' => 0,
                'total_online_meetings' => 0,
                'total_recordings' => 0,
                'total_feedbacks' => 0
            ],
            'growth_data' => [],
            'popular_subjects' => []
        ];
    }
}

/**
 * Get course performance metrics for admin reports
 * 
 * @return array Data on course performance metrics
 */
function getCoursePerformanceMetrics() {
    global $conn;
    $metrics = [
        'courses' => [],
        'has_data' => false
    ];
    
    try {
        // Get course performance data
        $sql = "
            SELECT 
                c.course_id,
                c.course_name,
                COUNT(DISTINCT s.subject_id) as subject_count,
                COUNT(DISTINCT cl.class_id) as class_count,
                COUNT(DISTINCT e.student_id) as student_count,
                COALESCE(AVG(sp.performance_score), 0) as avg_performance
            FROM course c
            LEFT JOIN subject s ON c.course_id = s.course_id
            LEFT JOIN class cl ON s.subject_id = cl.subject_id
            LEFT JOIN enrollments e ON cl.class_id = e.class_id
            LEFT JOIN student_progress sp ON e.student_id = sp.student_id AND e.class_id = sp.class_id
            GROUP BY c.course_id, c.course_name
            ORDER BY c.course_name
        ";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            log_error("Error in getCoursePerformanceMetrics: " . $conn->error, 'database');
            return $metrics;
        }
        
        if ($result->num_rows > 0) {
            $metrics['has_data'] = true;
            
            while ($row = $result->fetch_assoc()) {
                // Calculate color based on performance
                $performance = floatval($row['avg_performance']);
                $color = '';
                
                if ($performance >= 90) {
                    $color = 'rgba(25, 135, 84, 0.8)'; // Green (Excellent)
                } elseif ($performance >= 70) {
                    $color = 'rgba(13, 110, 253, 0.8)'; // Blue (Good)
                } elseif ($performance >= 50) {
                    $color = 'rgba(255, 193, 7, 0.8)'; // Yellow (Average)
                } else {
                    $color = 'rgba(220, 53, 69, 0.8)'; // Red (Needs Improvement)
                }
                
                $metrics['courses'][] = [
                    'course_id' => $row['course_id'],
                    'course_name' => $row['course_name'],
                    'subject_count' => $row['subject_count'],
                    'class_count' => $row['class_count'],
                    'student_count' => $row['student_count'],
                    'avg_performance' => round($row['avg_performance'], 1),
                    'color' => $color
                ];
            }
        }
        
        return $metrics;
    } catch (Exception $e) {
        log_error("Error in getCoursePerformanceMetrics: " . $e->getMessage(), 'database');
        return $metrics;
    }
}

/**
 * Get user activity timeline for admin reports
 * 
 * @param int $limit Number of months to retrieve
 * @return array Monthly activity data for the specified period
 */
function getUserActivityTimeline($limit = 12) {
    global $conn;
    $timeline = [
        'months' => [],
        'logins' => [],
        'enrollments' => [],
        'completions' => [],
        'has_data' => false
    ];
    
    try {
        $sql = "
            SELECT 
                DATE_FORMAT(date_point, '%b %Y') as month_label,
                DATE_FORMAT(date_point, '%Y-%m') as month_key,
                (
                    SELECT COUNT(*) 
                    FROM users 
                    WHERE DATE_FORMAT(last_login, '%Y-%m') = DATE_FORMAT(date_point, '%Y-%m')
                ) as login_count,
                (
                    SELECT COUNT(*) 
                    FROM enrollments 
                    WHERE DATE_FORMAT(enrollment_date, '%Y-%m') = DATE_FORMAT(date_point, '%Y-%m')
                ) as enrollment_count,
                (
                    SELECT COUNT(*) 
                    FROM enrollments 
                    WHERE DATE_FORMAT(enrollment_date, '%Y-%m') = DATE_FORMAT(date_point, '%Y-%m')
                    AND status = 'completed'
                ) as completion_count
            FROM (
                SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date_point
                FROM (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as a
                CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as b
                CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as c
            ) as dates
            WHERE date_point <= CURDATE() AND date_point >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY month_key
            ORDER BY date_point;
        ";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            log_error("Error preparing statement in getUserActivityTimeline: " . $conn->error, 'database');
            return $timeline;
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $timeline['has_data'] = true;
            
            while ($row = $result->fetch_assoc()) {
                $timeline['months'][] = $row['month_label'];
                $timeline['logins'][] = intval($row['login_count']);
                $timeline['enrollments'][] = intval($row['enrollment_count']);
                $timeline['completions'][] = intval($row['completion_count']);
            }
        }
        
        return $timeline;
    } catch (Exception $e) {
        log_error("Error in getUserActivityTimeline: " . $e->getMessage(), 'database');
        return $timeline;
    }
}

/**
 * Get tutor performance distribution for admin reports
 * 
 * @return array Performance distribution data for tutors
 */
function getTutorPerformanceDistribution() {
    global $conn;
    $distribution = [
        'rating_ranges' => ['4.5-5.0', '4.0-4.4', '3.5-3.9', '3.0-3.4', 'Below 3.0'],
        'tutors' => [0, 0, 0, 0, 0],
        'colors' => [
            'rgba(25, 135, 84, 0.8)',  // Green (Excellent)
            'rgba(13, 110, 253, 0.8)',  // Blue (Very Good)
            'rgba(255, 193, 7, 0.8)',  // Yellow (Good)
            'rgba(255, 136, 0, 0.8)',  // Orange (Average)
            'rgba(220, 53, 69, 0.8)'   // Red (Below Average)
        ],
        'has_data' => false
    ];
    
    try {
        $sql = "
            SELECT 
                CASE
                    WHEN rating >= 4.5 THEN 0
                    WHEN rating >= 4.0 THEN 1
                    WHEN rating >= 3.5 THEN 2
                    WHEN rating >= 3.0 THEN 3
                    ELSE 4
                END as rating_category,
                COUNT(*) as tutor_count
            FROM users
            WHERE role = 'TECHGURU' AND rating > 0
            GROUP BY rating_category
            ORDER BY rating_category
        ";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            log_error("Error in getTutorPerformanceDistribution: " . $conn->error, 'database');
            return $distribution;
        }
        
        $hasValues = false;
        
        while ($row = $result->fetch_assoc()) {
            $category = intval($row['rating_category']);
            $count = intval($row['tutor_count']);
            
            if ($category >= 0 && $category <= 4) {
                $distribution['tutors'][$category] = $count;
                if ($count > 0) {
                    $hasValues = true;
                }
            }
        }
        
        $distribution['has_data'] = $hasValues;
        
        return $distribution;
    } catch (Exception $e) {
        log_error("Error in getTutorPerformanceDistribution: " . $e->getMessage(), 'database');
        return $distribution;
    }
}
/**
 * Get class performance metrics for admin reports
 * 
 * @return array Data on class performance metrics
 */
function getClassPerformanceMetrics() {
    global $conn;
    $metrics = [
        'performance_titles' => [],
        'counts' => [],
        'has_data' => false
    ];

    try {
        $sql = "
            SELECT p.title, COUNT(e.enrollment_id) AS count
            FROM performances p
            LEFT JOIN enrollments e ON p.id = e.performance_id
            GROUP BY p.title
            ORDER BY count DESC
        ";

        $result = $conn->query($sql);

        if (!$result) {
            log_error("Error in getClassPerformanceMetrics: " . $conn->error, 'database');
            return $metrics;
        }

        if ($result->num_rows > 0) {
            $metrics['has_data'] = true;

            while ($row = $result->fetch_assoc()) {
                $metrics['performance_titles'][] = $row['title'];
                $metrics['counts'][] = intval($row['count']);
            }
        }

        return $metrics;
    } catch (Exception $e) {
        log_error("Exception in getClassPerformanceMetrics: " . $e->getMessage(), 'database');
        return $metrics;
    }
}

/**
 * Get attendance distribution counts for admin reports
 * 
 * @return array Attendance status counts
 */
function getAttendanceDistribution() {
    global $conn;
    $distribution = [
        'statuses' => ['present', 'absent', 'late', 'pending'],
        'counts' => [0, 0, 0, 0],
        'has_data' => false
    ];

    try {
        $sql = "
            SELECT status, COUNT(*) as count
            FROM attendance
            GROUP BY status
        ";

        $result = $conn->query($sql);

        if (!$result) {
            log_error("Error in getAttendanceDistribution: " . $conn->error, 'database');
            return $distribution;
        }

        if ($result->num_rows > 0) {
            $distribution['has_data'] = true;

            while ($row = $result->fetch_assoc()) {
                $status = $row['status'];
                $count = intval($row['count']);
                $index = array_search($status, $distribution['statuses']);
                if ($index !== false) {
                    $distribution['counts'][$index] = $count;
                }
            }
        }

        return $distribution;
    } catch (Exception $e) {
        log_error("Exception in getAttendanceDistribution: " . $e->getMessage(), 'database');
        return $distribution;
    }
}

/**
 * Get earnings and transactions analytics for admin reports
 * 
 * @param int $months Number of months to include in timeline (default 12)
 * @return array Analytics data including monthly earnings, transaction counts, and status breakdown
 */
function getTransactionAnalytics($months = 12) {
    global $conn;
    $analytics = [
        'monthly_earnings' => [],
        'monthly_transactions' => [],
        'status_counts' => [],
        'total_earnings' => 0,
        'total_transactions' => 0,
        'has_data' => false
    ];

    try {
        // Get monthly earnings and transaction counts for last $months months
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM transactions
            WHERE status = 'succeeded' AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month ASC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            log_error('Error preparing statement in getTransactionAnalytics: ' . $conn->error, 'database');
            return $analytics;
        }
        $stmt->bind_param('i', $months);
        $stmt->execute();
        $result = $stmt->get_result();

        $monthly_earnings = [];
        $monthly_transactions = [];
        while ($row = $result->fetch_assoc()) {
            $monthly_earnings[$row['month']] = floatval($row['total_amount']);
            $monthly_transactions[$row['month']] = intval($row['transaction_count']);
        }

        // Fill missing months with zero values
        $period = new DatePeriod(
            new DateTime(date('Y-m-01', strtotime("-$months months"))),
            new DateInterval('P1M'),
            new DateTime(date('Y-m-01'))
        );

        $months_labels = [];
        $earnings_data = [];
        $transactions_data = [];
        foreach ($period as $dt) {
            $month_key = $dt->format('Y-m');
            $months_labels[] = $dt->format('M Y');
            $earnings_data[] = $monthly_earnings[$month_key] ?? 0;
            $transactions_data[] = $monthly_transactions[$month_key] ?? 0;
        }

        // Get transaction counts by status
        $status_sql = "
            SELECT status, COUNT(*) as count
            FROM transactions
            GROUP BY status
        ";
        $status_result = $conn->query($status_sql);
        $status_counts = [];
        if ($status_result) {
            while ($row = $status_result->fetch_assoc()) {
                $status_counts[$row['status']] = intval($row['count']);
            }
        }

        // Get total earnings and total transactions
        $total_sql = "
            SELECT 
                COALESCE(SUM(amount), 0) as total_earnings,
                COUNT(*) as total_transactions
            FROM transactions
            WHERE status = 'succeeded'
        ";
        $total_result = $conn->query($total_sql);
        $total_earnings = 0;
        $total_transactions = 0;
        if ($total_result) {
            $total_row = $total_result->fetch_assoc();
            $total_earnings = floatval($total_row['total_earnings']);
            $total_transactions = intval($total_row['total_transactions']);
        }

        $analytics = [
            'months' => $months_labels,
            'monthly_earnings' => $earnings_data,
            'monthly_transactions' => $transactions_data,
            'status_counts' => $status_counts,
            'total_earnings' => $total_earnings,
            'total_transactions' => $total_transactions,
            'has_data' => true
        ];

        return $analytics;
    } catch (Exception $e) {
        log_error('Exception in getTransactionAnalytics: ' . $e->getMessage(), 'database');
        return $analytics;
    }
}
?>
