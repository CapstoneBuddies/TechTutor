<?php
require_once '../../backends/main.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get last notification ID from request
$last_id = isset($_POST['last_id']) ? intval($_POST['last_id']) : 0;

// Check for new notifications
$result = checkNewNotifications($_SESSION['user_id'], $last_id);

// Send response
header('Content-Type: application/json');
echo json_encode($result); 