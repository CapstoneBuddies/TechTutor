<?php
require_once '../../backends/main.php';
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

    $class_id = (int) $data['class_id'];
    $student_id = $_SESSION['user'];

    // Check if class exists and is active
    $stmt = $conn->prepare("SELECT c.class_id, c.class_name, c.class_size, c.is_free, c.price, 
                             u.first_name, u.last_name, u.email, 
                             (SELECT COUNT(*) FROM enrollments WHERE class_id = c.class_id AND status = 'active') as enrolled_count 
                             FROM class c 
                             JOIN users u ON c.tutor_id = u.uid 
                             WHERE c.class_id = ? AND c.status = 'active'");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $class = $stmt->get_result()->fetch_assoc();

    if (!$class) {
        throw new Exception('Class is not available for enrollment.');
    }

    // Check if class size limit is reached
    if ($class['class_size'] && $class['enrolled_count'] >= $class['class_size']) {
        throw new Exception('This class has reached its maximum capacity.');
    }

    // Check if student is already enrolled in this class
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE class_id = ? AND student_id = ? AND status != 'dropped'");
    $stmt->bind_param("ii", $class_id, $student_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('You are already enrolled in this class.');
    }

    // Begin transaction
    $conn->begin_transaction();

    // Enroll the student in the class
    $stmt = $conn->prepare("INSERT INTO enrollments (class_id, student_id, status) VALUES (?, ?, 'active')");
    $stmt->bind_param("ii", $class_id, $student_id);
    $result = $stmt->execute();

    if (!$result) {
        throw new Exception('Failed to enroll in the class. Please try again.');
    }

    // Send notification to student
    sendNotification(
        $student_id, 
        'TECHKID', 
        "You have been enrolled in '{$class['class_name']}'", 
        BASE . "dashboard/s/class", 
        $class_id, 
        'bi-mortarboard', 
        'text-success'
    );

    // Send notification to tutor
    sendNotification(
        null, 
        'TECHGURU', 
        "A new student enrolled in '{$class['class_name']}'", 
        BASE . "dashboard/t/class", 
        $class_id, 
        'bi-person-plus', 
        'text-primary'
    );

    // Send email confirmation to student
    sendEnrollmentEmail($_SESSION['email'], $_SESSION['name'], $class['class_name'], $class['first_name'] . ' ' . $class['last_name']);

    // If class is paid, handle payment (placeholder for future implementation)
    if (!$class['is_free']) {
        // Log this enrollment for payment processing
        log_error("Paid class enrollment: Student ID {$student_id} enrolled in class ID {$class_id} for ₱{$class['price']}", "info");
    }

    // Commit transaction
    $conn->commit();

    log_error("Successful enrollment: Student ID {$student_id} enrolled in class ID {$class_id}", "info");
    echo json_encode(['success' => true, 'message' => 'Successfully enrolled in the class.']);

} catch (Exception $e) {
    // Rollback transaction if error occurs
    if ($conn->errno != 0) {
        $conn->rollback();
    }
    
    log_error("Enrollment Error: " . $e->getMessage(), 'database');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
