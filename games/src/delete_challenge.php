<?php
    include 'config.php';
    global $pdo;

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Check if user is logged in and has admin permissions
    if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || 
        ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'TECHGURU')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Get challenge ID from POST data
    $challengeId = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 0;
    
    if ($challengeId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid challenge ID']);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Delete related records from game_user_progress
        $stmt = $pdo->prepare("DELETE FROM game_user_progress WHERE challenge_id = ?");
        $stmt->execute([$challengeId]);
        
        // 2. Delete related records from game_user_badges (if any)
        $stmt = $pdo->prepare("DELETE FROM game_user_badges WHERE badge_id = ?");
        $stmt->execute([$challengeId]);
        
        // 3. Finally delete the challenge itself
        $stmt = $pdo->prepare("DELETE FROM game_challenges WHERE challenge_id = ?");
        $stmt->execute([$challengeId]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Challenge deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        log_error("Error deleting challenge: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete challenge. Error has been logged.'
        ]);
    }
?>
