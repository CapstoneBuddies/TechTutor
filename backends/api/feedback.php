<?php
/**
 * API endpoint for feedback management
 */
require_once '../main.php';
require_once BACKEND.'rating_management.php';

// Default response
$response = ['success' => false];
// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    $response['error'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

// Get action from query parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

try {
    // Initialize the rating management class
    $ratingManager = new RatingManagement();
    
    switch ($action) {
        case 'update':
            // Required parameters for update
            $rating_id = isset($input['rating_id']) ? intval($input['rating_id']) : 0;
            $rating = isset($input['rating']) ? intval($input['rating']) : 0;
            $feedback = isset($input['feedback']) ? $input['feedback'] : '';
            
            // Validate inputs
            if (empty($rating_id)) {
                throw new Exception('Feedback ID is required');
            }
            
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }
            
            if (empty($feedback)) {
                throw new Exception('Feedback content is required');
            }
            
            // Update the feedback
            $result = $ratingManager->updateFeedback($rating_id, $_SESSION['user'], $rating, $feedback);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            $response = $result;
            break;
            
        case 'archive':
            // Required parameters for archive
            $rating_id = isset($input['rating_id']) ? intval($input['rating_id']) : 0;
            
            // Validate inputs
            if (empty($rating_id)) {
                throw new Exception('Feedback ID is required');
            }
            
            // Archive the feedback
            $result = $ratingManager->archiveFeedback($rating_id, $_SESSION['user']);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            $response = $result;
            break;
            
        case 'unarchive':
            // Required parameters for unarchive
            $rating_id = isset($input['rating_id']) ? intval($input['rating_id']) : 0;
            
            // Validate inputs
            if (empty($rating_id)) {
                throw new Exception('Feedback ID is required');
            }
            
            // Unarchive the feedback
            $result = $ratingManager->unarchiveFeedback($rating_id, $_SESSION['user']);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            $response = $result;
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    log_error("Feedback API error: " . $e->getMessage());
    $response = ['success' => false, 'error' => $e->getMessage()];
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 