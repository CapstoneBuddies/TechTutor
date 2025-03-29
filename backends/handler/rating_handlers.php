<?php
require_once '../main.php';
require_once BACKEND.'rating_management.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

// Get input data (support both GET and POST)
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';
$response = ['success' => false];

// Initialize rating management
$ratingManager = new RatingManagement();

switch ($action) {
    case 'toggle_archive':
        // Check if user is a techguru
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
            $response['error'] = 'Unauthorized access';
            break;
        }

        // Check required parameters
        if (!isset($input['feedback_id']) || !isset($input['archive'])) {
            $response['error'] = 'Missing required parameters';
            break;
        }

        $feedback_id = (int)$input['feedback_id'];
        $archive = (bool)$input['archive'];
        $tutor_id = $_SESSION['user'];

        try {
            // Toggle archive status
            $result = $ratingManager->toggleFeedbackArchiveStatus($feedback_id, $tutor_id, $archive);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Feedback ' . ($archive ? 'archived' : 'unarchived') . ' successfully';
            } else {
                $response['error'] = 'Failed to update archive status';
            }
        } catch (Exception $e) {
            log_error("Error in toggle_archive: " . $e->getMessage(), 'rating');
            $response['error'] = 'An error occurred while updating archive status';
        }
        break;

    default:
        $response['error'] = 'Invalid action';
        break;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 