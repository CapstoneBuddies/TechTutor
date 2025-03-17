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
function getEnrolledCoursesForStudent($student_id) {
    return [];
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
