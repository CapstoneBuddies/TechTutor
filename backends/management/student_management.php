<?php
/**
 * Get students enrolled with a specific tutor
 * Returns detailed information about students including their progress and upcoming sessions
 */
function getStudentByTutor($tutor_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            u.uid AS student_id,
            u.first_name AS student_first_name, 
            u.last_name AS student_last_name,
            COALESCE(u.profile_picture, '') as profile_picture,
            COALESCE(u.email, '') as email,
            COALESCE(u.status, 'inactive') as status,
            c.class_id,
            c.class_name,
            COALESCE(c.thumbnail, '') AS class_thumbnail,
            e.enrollment_date,
            COALESCE(e.status, 'pending') AS enrollment_status,
            COALESCE((
                SELECT COUNT(*) 
                FROM class_schedule cs 
                JOIN attendance a ON cs.schedule_id = a.schedule_id
                WHERE cs.class_id = c.class_id 
                AND a.student_id = u.uid 
                AND cs.status = 'completed'
                AND a.status = 'present'
            ), 0) as completed_sessions,
            COALESCE((
                SELECT COUNT(*) 
                FROM class_schedule cs 
                WHERE cs.class_id = c.class_id
            ), 0) as total_sessions,
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
            ) as next_session_status,
            COALESCE((
                SELECT sf.rating 
                FROM session_feedback sf 
                WHERE sf.student_id = u.uid 
                AND sf.tutor_id = c.tutor_id 
                ORDER BY sf.created_at DESC 
                LIMIT 1
            ), 0) as student_rating,
            COALESCE((
                SELECT sf.feedback 
                FROM session_feedback sf 
                WHERE sf.student_id = u.uid 
                AND sf.tutor_id = c.tutor_id 
                ORDER BY sf.created_at DESC 
                LIMIT 1
            ), '') as student_feedback
        FROM users u 
        JOIN enrollments e ON u.uid = e.student_id
        JOIN class c ON e.class_id = c.class_id 
        WHERE c.tutor_id = ? 
        AND e.status = 'active'
        GROUP BY u.uid, c.class_id
        ORDER BY u.last_name, u.first_name, c.class_name");
        
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $num_students = count($students);

    // Process the results to calculate additional metrics
    foreach ($students as &$student) {
        // Calculate progress percentage
        $student['progress'] = $student['total_sessions'] > 0 
            ? round(($student['completed_sessions'] / $student['total_sessions']) * 100) 
            : 0;
            
        // Format next session info
        if (!empty($student['next_session_date'])) {
            $student['next_session_date'] = date('M d, Y', strtotime($student['next_session_date']));
            $student['session_date'] = $student['next_session_date']; // For backward compatibility
            $student['session_time'] = $student['next_session_time']; // For backward compatibility
        } else {
            $student['next_session_date'] = 'No scheduled date';
            $student['session_date'] = 'No scheduled date'; // For backward compatibility
            $student['next_session_time'] = 'No scheduled time';
            $student['session_time'] = 'No scheduled time'; // For backward compatibility
        }

        // Ensure all fields have default values
        $student = array_merge([
            'profile_picture' => '',
            'email' => '',
            'status' => 'inactive',
            'class_thumbnail' => '',
            'enrollment_status' => 'pending',
            'completed_sessions' => 0,
            'total_sessions' => 0,
            'student_rating' => 0,
            'student_feedback' => '',
            'progress' => 0,
            'next_session_status' => 'pending'
        ], $student);
    }

    return [
        'count' => $num_students,
        'students' => $students
    ];
}

/**
 * Get students enrolled in a specific class
 */
function getStudentByClass($class_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT u.first_name, u.last_name, u.profile_picture, u.email, u.status,
                                  e.enrollment_date, e.status as enrollment_status
                           FROM users u 
                           JOIN enrollments e ON u.uid = e.student_id
                           WHERE e.class_id = ?");
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $num_students = $result->num_rows;
    $students = $result->fetch_all(MYSQLI_ASSOC);

    return [
        'count' => $num_students,
        'students' => $students
    ];
}

/**
 * Submit a rating for a tutor
 */
function submitRating($student_id, $tutor_id, $rating, $comment = null) {
    global $conn;

    try {
        // Check if student has already rated the tutor
        $stmt = $conn->prepare("SELECT rating_id FROM session_feedback WHERE student_id = ? AND tutor_id = ?");
        $stmt->bind_param("ii", $student_id, $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return "You have already rated this tutor.";
        }

        // Insert new rating
        $stmt = $conn->prepare("INSERT INTO session_feedback (student_id, tutor_id, rating, feedback, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $student_id, $tutor_id, $rating, $comment);
        $stmt->execute();

        // The trigger will automatically update the tutor's rating
        return "Thank you for your feedback!";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Update a tutor's average rating
 */
function updateTutorRating($tutor_id) {
    global $conn;

    // Calculate new average rating
    $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) as rating_count 
                           FROM session_feedback 
                           WHERE tutor_id = ?");
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $average_rating = round($row['avg_rating'], 1); // Round to 1 decimal place
    $rating_count = $row['rating_count'];

    // Update the tutor's rating in the users table
    $stmt = $conn->prepare("UPDATE users SET rating = ?, rating_count = ? WHERE uid = ?");
    $stmt->bind_param("dii", $average_rating, $rating_count, $tutor_id);
    $stmt->execute();
}

/**
 * Get all ratings for a tutor
 */
function getTutorRatings($tutor_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT sf.rating, sf.feedback as comment, 
                                  u.first_name, u.last_name,
                                  sf.created_at
                           FROM session_feedback sf
                           JOIN users u ON sf.student_id = u.uid
                           WHERE sf.tutor_id = ?
                           ORDER BY sf.created_at DESC");
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllCourses() {
    global $conn;
    
    try {
        $query = "SELECT 
                    c.*, 
                    (SELECT COUNT(*) FROM subject s 
                     WHERE s.course_id = c.course_id AND s.is_active = 1) as subject_count
                  FROM course c 
                  ORDER BY c.course_name";
        
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error getting courses: " . $e->getMessage(), 'database');
        return [];
    }
}

function getUpcomingClassSchedules($student_id) {
    return [];
}

function getStudentLearningStats($student_id) {
    return [
        'hours_spent' => 0,
        'completed_classes' => 0
    ];
}

function getStudentClasses($student_id) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT 
                c.class_id,
                c.class_name,
                c.class_desc,
                c.thumbnail,
                c.status as class_status,
                s.subject_name,
                s.subject_id,
                co.course_name,
                co.course_id,
                u.first_name AS tutor_first_name,
                u.last_name AS tutor_last_name,
                u.profile_picture AS tutor_avatar,
                e.status as enrollment_status,
                e.enrollment_date,
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
                ) as total_sessions
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN subject s ON c.subject_id = s.subject_id
            JOIN course co ON s.course_id = co.course_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE e.student_id = ?
            ORDER BY e.enrollment_date DESC
        ");

        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Error fetching student classes: " . $e->getMessage(), 'database');
        return [];
    }
}

function getCurrentActiveClass() {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT 
                c.class_id,
                c.class_name,
                c.class_desc AS description,
                c.thumbnail,
                c.status,
                c.is_free,
                c.price,
                s.subject_id,
                s.subject_name,
                co.course_id,
                co.course_name,
                u.first_name AS tutor_first_name,
                u.last_name AS tutor_last_name,
                u.profile_picture AS tutor_avatar,
                (
                    SELECT COUNT(*) 
                    FROM enrollments e
                    WHERE e.class_id = c.class_id 
                    AND e.status = 'active'
                ) AS enrolled_students
            FROM class c
            JOIN subject s ON c.subject_id = s.subject_id
            JOIN course co ON s.course_id = co.course_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE c.status = 'active'
            ORDER BY c.created_at DESC
        ");

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Error fetching active classes: " . $e->getMessage(), 'database');
        return [];
    }
}

function getStudentFiles($student_id) {
    return [];
}

function getStudentSchedule($student_id) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT 
                cs.schedule_id,
                cs.class_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) AS duration,
                cs.status as schedule_status,
                c.class_name,
                c.thumbnail,
                s.subject_name,
                CONCAT(u.first_name,' ',u.last_name) AS tutor_name,
                u.profile_picture AS tutor_avatar,
                e.status as enrollment_status
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN class_schedule cs ON c.class_id = cs.class_id
            JOIN subject s ON c.subject_id = s.subject_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE e.student_id = ? 
            AND e.status = 'active'
            AND cs.session_date >= CURDATE()
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT 10
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Error fetching student schedule: " . $e->getMessage(), 'database');
        return [];
    }
}

function getStudentCertificates($student_id) {
    require_once BACKEND . 'certificate_management.php';
    return getStudentCertificatesDetails($student_id);
}

/**
 * Get all courses with their active subjects
 * @return array Array of courses with their subjects
 */
function getCoursesWithSubjects() {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.course_id,
                c.course_name,
                s.subject_id,
                s.subject_name
            FROM course c
            LEFT JOIN subject s ON c.course_id = s.course_id
            WHERE s.is_active = 1
            ORDER BY c.course_name, s.subject_name
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];
        
        while ($row = $result->fetch_assoc()) {
            if (!isset($courses[$row['course_id']])) {
                $courses[$row['course_id']] = [
                    'name' => $row['course_name'],
                    'subjects' => []
                ];
            }
            if ($row['subject_id']) {
                $courses[$row['course_id']]['subjects'][] = [
                    'id' => $row['subject_id'],
                    'name' => $row['subject_name']
                ];
            }
        }
        
        return $courses;
    } catch (Exception $e) {
        log_error($e->getMessage());
        return [];
    }
}
function getEnrolledSubjectsForStudent($studentId) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                c.course_name,
                e.enrollment_date,
                e.status
            FROM enrollments e
            JOIN class cl ON e.class_id = cl.class_id
            JOIN subject s ON cl.subject_id = s.subject_id
            JOIN course c ON s.course_id = c.course_id
            WHERE e.student_id = ?
            ORDER BY e.enrollment_date DESC
        ");
        $stmt->execute([$studentId]);
        $result = $stmt->get_result();
        
        $enrolledSubjects = [];
        while ($row = $result->fetch_assoc()) {
            $enrolledSubjects[] = [
                'subject_id' => $row['subject_id'],
                'subject_name' => $row['subject_name'],
                'subject_desc' => $row['subject_desc'],
                'image' => $row['image'],
                'is_active' => $row['is_active'],
                'course_name' => $row['course_name'],
                'enrollment_date' => $row['enrollment_date'],
                'status' => $row['status']
            ];
        }
        
        return $enrolledSubjects;
    } catch (Exception $e) {
        log_error($e->getMessage());
        return [];
    }
}

/**
 * Get meeting details for a student's class session
 * @param int $schedule_id The schedule ID
 * @param int $student_id The student's ID
 * @return array|null Meeting details or null if not found
 */
function getStudentMeetingDetails($schedule_id, $student_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                cs.schedule_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.status as schedule_status,
                c.class_id,
                c.class_name,
                c.thumbnail,
                s.subject_name,
                CONCAT(u.first_name,' ',u.last_name) AS tutor_name,
                u.profile_picture AS tutor_avatar,
                m.meeting_uid,
                m.attendee_pw,
                e.status as enrollment_status
            FROM class_schedule cs
            JOIN class c ON cs.class_id = c.class_id
            JOIN subject s ON c.subject_id = s.subject_id
            JOIN users u ON c.tutor_id = u.uid
            JOIN enrollments e ON c.class_id = e.class_id AND e.student_id = ?
            LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
            WHERE cs.schedule_id = ?
            AND e.status = 'active'
        ");
        
        $stmt->bind_param("ii", $student_id, $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        log_error("Error getting meeting details: " . $e->getMessage(), "student");
        return null;
    }
}

/**
 * Get student's active class with next session
 * @param int $student_id The student's ID
 * @return array|null Active class details or null if not found
 */
function getStudentActiveClass($student_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.class_id,
                c.class_name,
                c.thumbnail,
                CONCAT(u.first_name, ' ', u.last_name) as tutor_name,
                u.profile_picture as tutor_avatar,
                MIN(cs.session_date) as next_session_date,
                MIN(cs.start_time) as next_session_time
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN users u ON c.tutor_id = u.uid
            LEFT JOIN class_schedule cs ON c.class_id = cs.class_id
            WHERE e.student_id = ?
            AND e.status = 'active'
            AND cs.session_date >= CURDATE()
            AND cs.status != 'completed'
            GROUP BY c.class_id
            ORDER BY next_session_date ASC, next_session_time ASC
            LIMIT 1
        ");
        
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        log_error("Error getting active class: " . $e->getMessage(), "student");
        return null;
    }
}

/**
 * Get student's upcoming schedule
 * @param int $student_id The student's ID
 * @return array List of upcoming sessions
 */
function getStudentUpcomingSchedule($student_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                cs.schedule_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.status,
                c.class_id,
                c.class_name,
                c.thumbnail,
                CONCAT(u.first_name, ' ', u.last_name) as tutor_name,
                u.profile_picture as tutor_avatar
            FROM class_schedule cs
            JOIN class c ON cs.class_id = c.class_id
            JOIN users u ON c.tutor_id = u.uid
            JOIN enrollments e ON c.class_id = e.class_id
            WHERE e.student_id = ?
            AND e.status = 'active'
            AND cs.session_date >= CURDATE()
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT 10
        ");
        
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error getting upcoming schedule: " . $e->getMessage(), "student");
        return [];
    }
}

/**
 * Check if a student is already enrolled in a class
 * @param int $student_id The student's ID
 * @param int $class_id The class ID
 * @return array|null Returns enrollment details if enrolled, null otherwise
 */
function checkStudentEnrollment($student_id, $class_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                e.enrollment_id,
                e.status,
                c.class_name,
                c.class_id
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            WHERE e.student_id = ? 
            AND e.class_id = ?
            AND e.status != 'dropped'
        ");
        
        $stmt->bind_param("ii", $student_id, $class_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        log_error("Error checking enrollment: " . $e->getMessage(), "student");
        return null;
    }
}

?>
