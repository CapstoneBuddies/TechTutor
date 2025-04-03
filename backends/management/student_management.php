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
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT 
                cs.schedule_id,
                cs.class_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.status as schedule_status,
                c.class_name,
                c.thumbnail,
                CONCAT(cs.session_date, ' ', cs.start_time) as datetime,
                CONCAT(
                    DATE_FORMAT(cs.start_time, '%H:%i'), 
                    ' - ', 
                    DATE_FORMAT(cs.end_time, '%H:%i')
                ) as time,
                TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) AS duration_minutes,
                u.first_name AS tutor_first_name,
                u.last_name AS tutor_last_name,
                CONCAT(u.first_name, ' ', u.last_name) AS tutor_name,
                u.profile_picture AS tutor_avatar,
                CASE 
                    WHEN cs.session_date = CURDATE() AND 
                         TIME(NOW()) BETWEEN cs.start_time AND cs.end_time 
                    THEN 1 
                    ELSE 0 
                END as active,
                CASE 
                    WHEN cs.session_date = CURDATE() AND 
                         TIME(NOW()) BETWEEN cs.start_time AND cs.end_time 
                    THEN 'Live Now'
                    WHEN cs.session_date = CURDATE() 
                    THEN 'Today'
                    WHEN cs.session_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) 
                    THEN 'Tomorrow'
                    ELSE DATE_FORMAT(cs.session_date, '%a, %d %b')
                END as status
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN class_schedule cs ON c.class_id = cs.class_id
            JOIN users u ON c.tutor_id = u.uid
            LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
            WHERE e.student_id = ? 
            AND e.status = 'active'
            AND CONCAT(cs.session_date, ' ', cs.end_time) >= NOW()
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT 5
        ");
        
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        log_error("Error fetching upcoming schedules: " . $e->getMessage(), 'database');
        return [];
    }
}

function getStudentLearningStats($student_id) {
    global $conn;
    
    try {
        // Query to get statistics about classes and time spent
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.class_id) AS enrolled_classes,
                COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.class_id END) AS completed_classes,
                COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.attendance_id END) AS sessions_attended,
                COALESCE(SUM(
                    CASE WHEN a.status = 'present' 
                    THEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) 
                    ELSE 0 END
                ), 0) AS total_minutes
            FROM enrollments e
            LEFT JOIN class_schedule cs ON e.class_id = cs.class_id
            LEFT JOIN attendance a ON cs.schedule_id = a.schedule_id AND a.student_id = e.student_id
            WHERE e.student_id = ?
        ");
        
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Calculate hours rounded to 1 decimal place
        $hours_spent = round($result['total_minutes'] / 60, 1);
        
        return [
            'enrolled_classes' => $result['enrolled_classes'],
            'completed_classes' => $result['completed_classes'],
            'sessions_attended' => $result['sessions_attended'],
            'hours_spent' => $hours_spent,
            'total_minutes' => $result['total_minutes']
        ];
        
    } catch (Exception $e) {
        log_error("Error getting student learning stats: " . $e->getMessage(), 'database');
        return [
            'enrolled_classes' => 0,
            'completed_classes' => 0,
            'sessions_attended' => 0,
            'hours_spent' => 0,
            'total_minutes' => 0
        ];
    }
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

function getStudentSchedule($student_id, $include_completed = false) {
    global $conn;

    try {
        $condition = $include_completed ? "IN ('active','completed') " : "= 'active' AND cs.session_date >= CURDATE()";
        
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
                e.status as enrollment_status,
                CASE 
                    WHEN cs.session_date < CURDATE() OR 
                         (cs.session_date = CURDATE() AND cs.end_time < CURRENT_TIME() OR e.status = 'completed') 
                    THEN 'completed'
                    WHEN cs.session_date = CURDATE() AND 
                         CURRENT_TIME() BETWEEN cs.start_time AND cs.end_time 
                    THEN 'in_progress'
                    ELSE 'upcoming'
                END as session_status
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN class_schedule cs ON c.class_id = cs.class_id
            JOIN subject s ON c.subject_id = s.subject_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE e.student_id = ? 
            AND e.status 
            $condition
            ORDER BY cs.session_date ASC, cs.start_time ASC
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
            AND e.status NOT IN ('dropped', 'pending')
        ");
        
        $stmt->bind_param("ii", $student_id, $class_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        log_error("Error checking enrollment: " . $e->getMessage(), "student");
        return null;
    }
}

/**
 * Check if a student has been invited to a class
 * @param int $student_id The student's ID
 * @param int $class_id The class ID
 * @return array|null Returns invitation details if invited, null otherwise
 */
function checkPendingInvitation($student_id, $class_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                e.enrollment_id,
                e.enrollment_date,
                e.status,
                c.class_name,
                c.tutor_id,
                c.class_id,
                CONCAT(u.first_name, ' ', u.last_name) as tutor_name
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE e.student_id = ? 
            AND e.class_id = ?
            AND e.status = 'pending'
        ");
        
        $stmt->bind_param("ii", $student_id, $class_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        log_error("Error checking pending invitation: " . $e->getMessage(), "student");
        return null;
    }
}
/**
 * This function will return if the current date is within 24hr from the session completion date
 * @param int $scheduleId The class' session id
 * @return true|false if current datetime is within the specified limit
 */
function isWithin24Hours($scheduleId) {
    global $conn;

    $stmt = $conn->prepare("SELECT status_changed_at FROM class_schedule WHERE schedule_id = ? LIMIT 1");
    $stmt->bind_param('i',$scheduleId);
    $stmt->execute();

    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        $completionDateTime  = new DateTime($row['status_changed_at']);
        $completionDateTime->add(new DateInterval('PT24H'));
        $currentDateTime = new DateTime();
    
        if($currentDateTime < $completionDateTime) {
            return true;
        }
        return false;
    }
    return null;
}

/**
 * Drop a class (unenroll a student from a class)
 * 
 * @param int $student_id The ID of the student dropping the class
 * @param int $class_id The ID of the class to drop
 * @param string $reason Optional reason for dropping the class
 * @return array Status and message
 */
function dropClass($student_id, $class_id, $reason = '') {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Verify the student is enrolled in this class
        $stmt = $conn->prepare("
            SELECT e.enrollment_id, e.status, c.class_name, c.tutor_id, 
                   CONCAT(u.first_name, ' ', u.last_name) AS tutor_name
            FROM enrollments e
            JOIN class c ON e.class_id = c.class_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE e.class_id = ? AND e.student_id = ? AND e.status = 'active'
        ");
        $stmt->bind_param("ii", $class_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false, 
                'message' => 'You are not actively enrolled in this class or the class does not exist'
            ];
        }
        
        $enrollment = $result->fetch_assoc();
        
        // Get student name
        $stmt = $conn->prepare("
            SELECT CONCAT(first_name, ' ', last_name) AS student_name, email
            FROM users
            WHERE uid = ?
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        // Update enrollment status to 'dropped'
        $stmt = $conn->prepare("
            UPDATE enrollments
            SET status = 'dropped',enrollment_date = NOW(), message = ?
            WHERE enrollment_id = ?
        ");
        $notes = empty($reason) ? 'Student initiated drop' : 'Reason: ' . $reason;
        $stmt->bind_param("si", $notes, $enrollment['enrollment_id']);
        $stmt->execute();

        // Send notification to the tutor
        $notification_message = "<strong>{$student['student_name']}</strong> has dropped the class <strong>{$enrollment['class_name']}</strong>.";
        if (!empty($reason)) {
            $notification_message .= "<br><strong>Reason:</strong> " . htmlspecialchars($reason);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                recipient_id, recipient_role, class_id, message, icon, icon_color
            ) VALUES (?, 'TECHGURU', ?, ?, 'bi-person-dash-fill', 'text-danger')
        ");
        $stmt->bind_param("iis", $enrollment['tutor_id'], $class_id, $notification_message);
        $stmt->execute();
        
        // Try to send an email notification to the tutor
        try {
            // Get tutor email
            $stmt = $conn->prepare("SELECT email FROM users WHERE uid = ?");
            $stmt->bind_param("i", $enrollment['tutor_id']);
            $stmt->execute();
            $tutor_email = $stmt->get_result()->fetch_assoc()['email'];
            
            $mail = getMailerInstance();
            $mail->addAddress($tutor_email, $enrollment['tutor_name']);
            $mail->Subject = "Student Dropped Class - {$enrollment['class_name']}";
            
            $email_body = '
            <div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                <div style="background: #dc3545; padding: 20px; text-align: center; color: white;">
                    <h2 style="margin: 0; font-size: 22px;">Student Dropped Class</h2>
                </div>
                <div style="padding: 20px; background: #f9f9f9;">
                    <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($enrollment['tutor_name']) . '</strong>,</p>
                    <p style="font-size: 16px; color: #555;">
                        <strong>' . htmlspecialchars($student['student_name']) . '</strong> has dropped your class <strong>' . htmlspecialchars($enrollment['class_name']) . '</strong>.
                    </p>';
            
            if (!empty($reason)) {
                $email_body .= '
                    <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #dc3545; background: #f0f0f0;">
                        <h3 style="margin-top: 0; color: #444; font-size: 18px;">Reason for Dropping:</h3>
                        <div style="color: #555; font-size: 16px;">
                            ' . htmlspecialchars($reason) . '
                        </div>
                    </div>';
            }
            
            $email_body .= '
                    <p style="font-size: 15px; color: #666;">
                        You can view this update in your TechTutor dashboard.
                    </p>
                    <div style="text-align: center; margin: 25px 0 15px;">
                        <a href="' . BASE . 'dashboard" 
                           style="background: #0dcaf0; color: white; padding: 10px 20px; text-decoration: none; font-size: 16px; border-radius: 5px;">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
                <div style="background: #ddd; padding: 10px; text-align: center; font-size: 14px; color: #666;">
                    <p style="margin: 5px 0;">Best regards,<br><strong>The TechTutor Team</strong></p>
                </div>
            </div>';
            
            $mail->Body = $email_body;
            $mail->send();
        } catch (Exception $e) {
            // Log email error but continue with the process
            log_error("Failed to send class drop email to tutor: " . $e->getMessage(), "mail");
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'You have successfully dropped the class',
            'data' => [
                'class_name' => $enrollment['class_name']
            ]
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        log_error("Error dropping class: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to drop class: ' . $e->getMessage()];
    }
}
/**
 * This function will get all of the student's enrolled class
 * @param int $studentId The ID of the student to be used as reference
 * @return $classIds[], will return all of the enrolled class Ids
*/
function getEnrolledClass($studentId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT class_id FROM enrollments WHERE student_id = ? AND status IN ('active', 'completed')");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return array_column($result, 'class_id');
}

/**
 * This function will get all of the student's enrolled class
 * @param int $classId The ID of the class to be used as reference
 * @return $students[], will return all students not enrolled to the class
*/
function getAvailableStudentsForClass($classId) {
    global $conn;
    $stmt = $conn->prepare("SELECT CONCAT(u.first_name,' ',u.last_name) AS name, u.uid AS student_id, u.email FROM users u WHERE role = 'TECHKID' AND u.uid NOT IN ( SELECT student_id FROM enrollments WHERE class_id = ? AND status != 'dropped')");
    $stmt->bind_param('i',$classId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
