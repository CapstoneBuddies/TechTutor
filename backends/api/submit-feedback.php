<?php
require_once '../main.php';
require_once BACKEND . 'rating_management.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate required fields
$sessionId = $_POST['session_id'] ?? null;
$tutorId = $_POST['tutor_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = $_POST['feedback'] ?? '';

if (!$sessionId || !$tutorId || !$rating) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate rating value
if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit;
}

try {
    $ratingManager = new RatingManagement();
    $ratingManager->submitSessionRating(
        $sessionId,
        $_SESSION['user'],
        $rating,
        $feedback,
        $tutorId
    );

    echo json_encode([
        'success' => true,
        'message' => 'Feedback submitted successfully'
    ]);
} catch (Exception $e) {
    log_error($e->getMessage(), 'database');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 