<?php
require_once '../backends/main.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($data['class_id']) || !is_numeric($data['class_id'])) {
        throw new Exception('Invalid class ID.');
    }

    if (empty($data['selected_sessions']) || !is_array($data['selected_sessions'])) {
        throw new Exception('No sessions selected.');
    }

    $class_id = (int) $data['class_id'];
    $student_id = $_SESSION['user'];
    $selected_sessions = array_map('intval', $data['selected_sessions']);

    // Check if class exists and is active
    $stmt = $conn->prepare("SELECT class_id FROM class WHERE class_id = ? AND status = 'active'");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $class = $stmt->get_result()->fetch_assoc();

    if (!$class) {
        throw new Exception('Class is not available for enrollment.');
    }

    // Track successful enrollments
    $enrolled_sessions = [];

    foreach ($selected_sessions as $schedule_id) {
        // Check if session exists
        $stmt = $conn->prepare("SELECT session_date, start_time, end_time, status FROM class_schedule WHERE schedule_id = ? AND class_id = ?");
        $stmt->bind_param("ii", $schedule_id, $class_id);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();

        if (!$session) {
            throw new Exception("Invalid session selected.");
        }

        // Check if student is already enrolled in this session
        $stmt = $conn->prepare("SELECT 1 FROM class_schedule WHERE session_date = ? AND start_time = ? AND end_time = ? AND user_id = ? AND role = 'STUDENT'");
        $stmt->bind_param("sssi", $session['session_date'], $session['start_time'], $session['end_time'], $student_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("You are already enrolled in one of the selected sessions.");
        }

        // Enroll the student in the session
        $stmt = $conn->prepare("INSERT INTO class_schedule (class_id, user_id, role, session_date, start_time, end_time, status) 
                                SELECT class_id, ?, 'STUDENT', session_date, start_time, end_time, 'confirmed' 
                                FROM class_schedule WHERE schedule_id = ?");
        $stmt->bind_param("ii", $student_id, $schedule_id);
        $stmt->execute();

        $enrolled_sessions[] = $schedule_id;
    }

    // Get class details for notification
    $stmt = $conn->prepare("SELECT c.class_name, u.first_name, u.last_name, u.email FROM class c 
                            JOIN users u ON c.tutor_id = u.uid WHERE c.class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $classDetails = $stmt->get_result()->fetch_assoc();

    // Send notification to student
    sendNotification($student_id, 'TECHKID', "You have been enrolled in '{$classDetails['class_name']}'", BASE . "dashboard/s/class", $class_id, 'bi-mortarboard', 'text-success');

    // Send notification to tutor
    sendNotification(
        null, 
        'TECHGURU', 
        "A new student enrolled in '{$classDetails['class_name']}'", 
        BASE . "dashboard/t/class", 
        $class_id, 
        'bi-person-plus', 
        'text-primary'
    );

    // Send email confirmation to student
    sendEnrollmentEmail($_SESSION['email'], $_SESSION['name'], $classDetails['class_name'], $classDetails['first_name'] . ' ' . $classDetails['last_name']);

    echo json_encode(['success' => true, 'message' => 'Successfully enrolled in the selected sessions.']);

} catch (Exception $e) {
    log_error("Enrollment Error: " . $e->getMessage(), 'database');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
