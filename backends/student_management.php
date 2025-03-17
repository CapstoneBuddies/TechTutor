<?php
/**
 * Get students enrolled with a specific tutor
 */
function getStudentByTutor($tutor_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT u.first_name AS student_first_name, u.last_name AS student_last_name, 
                                  u.profile_picture, u.email, u.status, 
                                  c.class_name, cs.session_date, 
                                  DATE_FORMAT(cs.start_time, '%h:%i %p') AS formatted_start_time, 
                                  DATE_FORMAT(cs.end_time, '%h:%i %p') AS formatted_end_time 
                           FROM users u 
                           JOIN class_schedule cs ON u.uid = cs.user_id 
                           JOIN class c ON cs.class_id = c.class_id 
                           WHERE cs.role = 'STUDENT' AND c.tutor_id = ? 
                           ORDER BY c.class_name, cs.session_date, u.last_name, u.first_name");
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $num_students = count($students);

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

    $stmt = $conn->prepare("SELECT u.first_name, u.last_name, u.profile_picture, u.email, u.status 
                           FROM users u 
                           JOIN class_schedule cs ON u.uid = cs.user_id 
                           WHERE cs.role = 'STUDENT' AND cs.class_id = ?");
    
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
        $stmt = $conn->prepare("SELECT rating_id FROM ratings WHERE student_id = ? AND tutor_id = ?");
        $stmt->bind_param("ii", $student_id, $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return "You have already rated this tutor.";
        }

        // Insert new rating
        $stmt = $conn->prepare("INSERT INTO ratings (student_id, tutor_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $student_id, $tutor_id, $rating, $comment);
        $stmt->execute();

        // Update tutor's average rating
        updateTutorRating($tutor_id);

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
    $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM ratings WHERE tutor_id = ?");
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $average_rating = round($row['avg_rating'], 1); // Round to 1 decimal place

    // Update the tutor's rating in the users table
    $stmt = $conn->prepare("UPDATE users SET rating = ? WHERE uid = ?");
    $stmt->bind_param("di", $average_rating, $tutor_id);
    $stmt->execute();
}

/**
 * Get all ratings for a tutor
 */
function getTutorRatings($tutor_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT r.rating, r.comment, u.first_name, u.last_name
                           FROM ratings r
                           JOIN users u ON r.student_id = u.uid
                           WHERE r.tutor_id = ?
                           ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $tutor_id);
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
        $stmt = $conn->prepare("SELECT 
            s.subject_id, s.subject_name, s.subject_desc, s.image, 
            COUNT(DISTINCT c.class_id) AS class_count, 
            COUNT(DISTINCT cs.user_id) AS student_count
        FROM subject s
        LEFT JOIN class c ON s.subject_id = c.subject_id AND c.is_active = 1
        LEFT JOIN class_schedule cs ON cs.class_id = c.class_id AND cs.role = 'STUDENT'
        WHERE s.is_active = 1 AND s.course_id = ?
        GROUP BY s.subject_id");
        $stmt->bind_param("i", $identifier);
    } else {
        // For getting detailed subject information
        $stmt = $conn->prepare("SELECT s.*, c.course_name, c.course_desc,
            (SELECT COUNT(DISTINCT cl.class_id) 
             FROM class cl 
             WHERE cl.subject_id = s.subject_id AND cl.is_active = TRUE) as active_classes,
            (SELECT COUNT(DISTINCT cs.user_id) 
             FROM class cl 
             JOIN class_schedule cs ON cl.class_id = cs.class_id 
             WHERE cl.subject_id = s.subject_id AND cs.role = 'STUDENT') as total_students,
            (SELECT AVG(r.rating) 
             FROM class cl 
             JOIN ratings r ON cl.tutor_id = r.tutor_id 
             WHERE cl.subject_id = s.subject_id) as average_rating,
            (SELECT COUNT(DISTINCT cs.user_id) * 100.0 / NULLIF(COUNT(DISTINCT cs2.user_id), 0)
             FROM class cl 
             JOIN class_schedule cs ON cl.class_id = cs.class_id AND cs.status = 'completed'
             LEFT JOIN class_schedule cs2 ON cl.class_id = cs2.class_id
             WHERE cl.subject_id = s.subject_id AND cs.role = 'STUDENT') as completion_rate
        FROM subject s
        JOIN course c ON s.course_id = c.course_id
        WHERE s.subject_name = ?");
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
function getEnrolledCoursesForStudent($student_id) {
    return [];
}
function getUpcomingClassSchedules($student_id) {
    return [];
}
function getStudentLearningStats($student_id) {
    return [];
}
function getStudentClasses($student_id) {
    return [];
}
function getCurrentActiveClass($student_id) {
    return [];
}
function getStudentFiles($student_id) {
    return [];
}
function getStudentSchedule($student_id) {
    return [];
}
function getStudentCertificates($student_id) {
    return [];
}

?>
