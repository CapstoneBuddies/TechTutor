<?php
include 'config.php';
global $pdo;

// Start or resume session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create uploads directory if it doesn't exist
    if (!file_exists(IMG.'uploads')) {
        mkdir(IMG.'uploads', 0777, true);
    }
    
    $imageData = $_POST['image'];
    $challengeId = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : null;
    
    // Strip the data URI scheme prefix
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $decodedData = base64_decode($imageData);

    // Generate unique filename
    $fileName = 'drawing_' . time() . '.png';
    $filePath = IMG.'uploads/' . $fileName;
    
    // Save the file
    if (file_put_contents($filePath, $decodedData)) {
        $response = ['status' => 'success', 'file' => $fileName];
        
        // If a challenge ID was provided, save to game_user_progress as a completion
        if ($challengeId && isset($_SESSION['game'])) {
            try {
                // Get challenge details to determine score
                $scoreStmt = $pdo->prepare("
                    SELECT xp_value 
                    FROM challenge_details_view 
                    WHERE challenge_id = :challenge_id
                ");
                $scoreStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
                $scoreStmt->execute();
                $challengeData = $scoreStmt->fetch(PDO::FETCH_ASSOC);
                $score = $challengeData ? $challengeData['xp_value'] : 100; // Default to 100 if not found
                
                // Check if there's an existing record for this challenge
                $checkStmt = $pdo->prepare("
                    SELECT progress_id, score 
                    FROM game_user_progress 
                    WHERE user_id = :user_id AND challenge_id = :challenge_id
                ");
                $checkStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                $checkStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
                $checkStmt->execute();
                $existingProgress = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingProgress) {
                    // Update only if new score is higher
                    if ($score > $existingProgress['score']) {
                        $updateStmt = $pdo->prepare("
                            UPDATE game_user_progress 
                            SET score = :score, 
                                solution_file = :solution_file, 
                                completed_at = NOW() 
                            WHERE progress_id = :progress_id
                        ");
                        $updateStmt->bindParam(':score', $score, PDO::PARAM_INT);
                        $updateStmt->bindParam(':solution_file', $filePath, PDO::PARAM_STR);
                        $updateStmt->bindParam(':progress_id', $existingProgress['progress_id'], PDO::PARAM_INT);
                        $updateStmt->execute();
                    }
                } else {
                    // Insert new record
                    $insertStmt = $pdo->prepare("
                        INSERT INTO game_user_progress 
                        (user_id, challenge_id, score, solution_file, time_taken, completed_at) 
                        VALUES (:user_id, :challenge_id, :score, :solution_file, :time_taken, NOW())
                    ");
                    $insertStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                    $insertStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
                    $insertStmt->bindParam(':score', $score, PDO::PARAM_INT);
                    $insertStmt->bindParam(':solution_file', $filePath, PDO::PARAM_STR);
                    $timeTaken = 0; // Placeholder - could calculate actual time spent
                    $insertStmt->bindParam(':time_taken', $timeTaken, PDO::PARAM_INT);
                    $insertStmt->execute();
                }
                
                // Check for badge eligibility - award a UI Design badge based on how many challenges completed
                try {
                    $countStmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT gup.challenge_id) as completed_count 
                        FROM game_user_progress gup
                        JOIN challenge_details_view cdv ON gup.challenge_id = cdv.challenge_id
                        WHERE gup.user_id = :user_id 
                        AND cdv.challenge_type = 'UI'
                        AND gup.score > 0
                    ");
                    $countStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                    $countStmt->execute();
                    $completedData = $countStmt->fetch(PDO::FETCH_ASSOC);
                    $completedCount = $completedData['completed_count'] ?? 0;
                    
                    // Determine which badge to award
                    $badgeName = '';
                    if ($completedCount >= 10) {
                        $badgeName = 'UI Master';
                    } elseif ($completedCount >= 5) {
                        $badgeName = 'UI Expert';
                    } elseif ($completedCount >= 3) {
                        $badgeName = 'UI Designer';
                    } elseif ($completedCount >= 1) {
                        $badgeName = 'UI Novice';
                    }
                    
                    if ($badgeName) {
                        // Find badge ID
                        $badgeStmt = $pdo->prepare("
                            SELECT badge_id FROM game_badges WHERE name = :badge_name
                        ");
                        $badgeStmt->bindParam(':badge_name', $badgeName, PDO::PARAM_STR);
                        $badgeStmt->execute();
                        $badgeResult = $badgeStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($badgeResult) {
                            // Check if user already has this badge
                            $userBadgeStmt = $pdo->prepare("
                                SELECT 1 FROM game_user_badges 
                                WHERE user_id = :user_id AND badge_id = :badge_id
                            ");
                            $userBadgeStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                            $userBadgeStmt->bindParam(':badge_id', $badgeResult['badge_id'], PDO::PARAM_INT);
                            $userBadgeStmt->execute();
                            
                            if (!$userBadgeStmt->fetch()) {
                                // Award the badge to the user
                                $awardStmt = $pdo->prepare("
                                    INSERT INTO game_user_badges 
                                    (user_id, badge_id, earned_at) 
                                    VALUES (:user_id, :badge_id, NOW())
                                ");
                                $awardStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                                $awardStmt->bindParam(':badge_id', $badgeResult['badge_id'], PDO::PARAM_INT);
                                $awardStmt->execute();
                                
                                // Add badge earned message to response
                                $response['badge_earned'] = $badgeName;
                            }
                        }
                    }
                } catch (PDOException $e) {
                    log_error("Badge processing error: " . $e->getMessage());
                }
                
                $response['message'] = 'Your design has been submitted successfully!';
            } catch (PDOException $e) {
                // Log error but don't expose database errors to user
                log_error("Database error in save_drawing.php: " . $e->getMessage());
                $response['db_status'] = 'error';
                $response['message'] = 'Your design was saved, but there was an issue with the submission. Try again later.';
            }
        } else {
            $response['message'] = 'Design saved but not submitted (no challenge ID or user not logged in)';
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save the file']);
    }
}
?>