<?php
/**
 * API endpoint for dropping a class (unenroll)
 */
require_once '../main.php';
require_once BACKEND.'student_management.php';

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
$reason = isset($input['reason']) ? trim($input['reason']) : '';

// Validate inputs
if (empty($class_id)) {
    $response['error'] = 'Class ID is required';
    echo json_encode($response);
    exit();
}

try {
    // Call the dropClass function from student_management.php
    $result = dropClass($_SESSION['user'], $class_id, $reason);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    $response = $result;
    
} catch (Exception $e) {
    log_error("Error dropping class: " . $e->getMessage(), "enrollment");
    $response['error'] = $e->getMessage();
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 