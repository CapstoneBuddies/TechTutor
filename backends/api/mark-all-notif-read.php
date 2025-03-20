<?php
require_once '../main.php';

// Start session if not already started
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) && !isset($data['notifIDs'])) {
        throw new Exception('Missing required parameters');
    }
    
    // Verify user owns these notifications
    if ($data['user_id'] != $_SESSION['user']) {
        throw new Exception('Unauthorized access');
    }
    $ctr = 0;



    foreach ($data['notifIDs'] as $itm) {
        // Use our centralized function to mark all notifications as read
        if(markNotificationsAsRead($itm)) {
            $ctr++;
        }
    }

    if ($ctr !== count($data['notifIDs'])) {
        throw new Exception('Failed to mark all notifications as read');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Use our standard error logging
    log_error("Error in mark-all-notifications-read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while marking notifications as read'
    ]);
}
?>
