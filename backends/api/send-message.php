<?php
/**
 * API endpoint for sending messages to students in a class
 */
require_once '../main.php';
require_once BACKEND.'class_management.php';

// Default response
$response = ['success' => false];

// Check if user is logged in and is a TECHGURU
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHGURU') {
    $response['error'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required parameters
$class_id = isset($input['class_id']) ? intval($input['class_id']) : 0;
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$send_email = isset($input['send_email']) ? (bool)$input['send_email'] : false;
$selected_students = isset($input['selected_students']) && is_array($input['selected_students']) 
    ? array_map('intval', $input['selected_students']) 
    : [];

// Validate inputs
if (empty($class_id)) {
    $response['error'] = 'Class ID is required';
    echo json_encode($response);
    exit();
}

if (empty($subject)) {
    $response['error'] = 'Subject is required';
    echo json_encode($response);
    exit();
}

if (empty($message)) {
    $response['error'] = 'Message is required';
    echo json_encode($response);
    exit();
}

try {
    global $conn;
    
    // Verify the tutor owns this class
    $stmt = $conn->prepare("SELECT class_name FROM class WHERE class_id = ? AND tutor_id = ?");
    $stmt->bind_param("ii", $class_id, $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Unauthorized to send messages for this class");
    }
    
    $class = $result->fetch_assoc();
    $class_name = $class['class_name'];
    
    // Get enrolled students
    $students = getEnrolledStudents($class_id);
    
    if (empty($students)) {
        throw new Exception("No students are enrolled in this class");
    }
    
    // Filter students if specific students were selected
    if (!empty($selected_students)) {
        $students = array_filter($students, function($student) use ($selected_students) {
            return in_array($student['uid'], $selected_students);
        });
    }
    
    if (empty($students)) {
        throw new Exception("No selected students found in the class");
    }
    
    $notifications_sent = 0;
    $emails_sent = 0;
    $failed_sends = 0;
    
    foreach ($students as $student) {
        // Use the sendTechGuruMessage function from notifications_management.php
        $result = sendTechGuruMessage(
            $_SESSION['user'], 
            $student['uid'], 
            $class_id, 
            $subject, 
            $message, 
            $send_email
        );
        
        if ($result['success']) {
            $notifications_sent++;
            if ($send_email) {
                $emails_sent++;
            }
        } else {
            $failed_sends++;
            log_error("Failed to send message to student ID {$student['uid']}: {$result['message']}", "messaging");
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Messages sent successfully',
        'data' => [
            'notifications_sent' => $notifications_sent,
            'emails_sent' => $emails_sent,
            'recipients' => count($students),
            'failed' => $failed_sends
        ]
    ];
    
} catch (Exception $e) {
    log_error("Error sending messages: " . $e->getMessage(), "messaging");
    $response['error'] = $e->getMessage();
}

// Send response
header('Content-Type: application/json');
echo json_encode($response);
