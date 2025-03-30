<?php
/**
 * API endpoint for completing a class
 * Allows TechGuru to manually mark a class as completed
 */

require_once '../main.php';
require_once BACKEND.'class_management.php';

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, "Invalid request method. Only POST is allowed.");
}

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    sendJsonResponse(false, "Unauthorized access. Only TechGuru can complete classes.");
}

// Get class ID from request
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;

if ($class_id <= 0) {
    sendJsonResponse(false, "Invalid class ID provided.");
}

// Call the completeClass function
$result = completeClass($class_id, $_SESSION['user']);

// Process the result
if (is_array($result) && isset($result['success'])) {
    // If the function returned a specific error message
    sendJsonResponse($result['success'], $result['message']);
} elseif ($result === true) {
    // If operation was successful
    sendJsonResponse(true, "Class has been successfully completed.");
} else {
    // If operation failed without specific message
    sendJsonResponse(false, "Failed to complete the class. Please try again later.");
}

/**
 * Send a JSON response
 * 
 * @param bool $success Whether the operation was successful
 * @param string $message Message to display to the user
 * @param array $data Optional additional data
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
} 