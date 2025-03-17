<?php
/**
 * API endpoint to mark a single notification as read
 * 
 * This file handles AJAX requests from notifications.js to mark
 * individual notifications as read when clicked.
 */

require_once 'main.php';
require_once 'notifications_management.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate input
if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$notification_id = (int)$_POST['notification_id'];

try {
    // Call the centralized function to mark notification as read
    $success = markNotificationAsRead($notification_id);
    
    if ($success) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
} catch (Exception $e) {
    log_error("Error in mark_notification_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
