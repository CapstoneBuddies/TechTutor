<?php
/**
 * API endpoint for students to send messages to their TechGuru
 */
require_once '../main.php';

// Default response
$response = ['success' => false];

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
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
    // Use the sendTechKidMessage function from notifications_management.php
    $result = sendTechKidMessage($_SESSION['user'], $class_id, $subject, $message);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    $response = $result;
    
} catch (Exception $e) {
    log_error("Error sending student message: " . $e->getMessage(), "messaging");
    $response['error'] = $e->getMessage();
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 