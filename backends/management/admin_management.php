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
            COUNT(DISTINCT cs.user_id) as student_count
        FROM course c
        LEFT JOIN subject s ON c.course_id = s.course_id
        LEFT JOIN class cl ON s.subject_id = cl.subject_id
        LEFT JOIN class_schedule cs ON cl.class_id = cs.class_id AND cs.role = 'STUDENT'
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
            COUNT(DISTINCT cs.user_id) as enrolled_students
        FROM class c
        JOIN subject s ON c.subject_id = s.subject_id
        JOIN course co ON s.course_id = co.course_id
        JOIN users u ON c.tutor_id = u.uid
        LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
        WHERE c.class_id = ?
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
            s.is_active,
            c.course_id,
            c.course_name,
            COUNT(DISTINCT cl.tutor_id) as tutor_count,
            COUNT(DISTINCT cs.user_id) as student_count
        FROM subject s
        LEFT JOIN course c ON s.course_id = c.course_id
        LEFT JOIN class cl ON s.subject_id = cl.subject_id
        LEFT JOIN class_schedule cs ON cl.class_id = cs.class_id AND cs.role = 'STUDENT'
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
            WHERE cl.subject_id = ?");
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
                "class-details.php?subject_id={$subject_id}",
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
            (SELECT COUNT(DISTINCT cs.user_id) 
             FROM class_schedule cs 
             WHERE cs.role = 'STUDENT') as enrolled_students";
        
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
 * @return array Operation result with success status and message
 */
function addSubject($courseId, $subjectName, $subjectDesc) {
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
        
        // Log the action
        log_error("New subject added: $subjectName in course ID: $courseId (Subject ID: $subjectId)", "security");
        
        $conn->commit();
        return ['success' => true, 'message' => 'Subject added successfully'];
    } catch (Exception $e) {
        $conn->rollback();
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

?>