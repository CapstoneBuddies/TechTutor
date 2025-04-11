<?php
    include 'config.php';
    include 'challenges.php';
    global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the submitted code and challenge ID
        $code = isset($_POST['code']) ? $_POST['code'] : '';
        $challengeId = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 1;
        
        // Find the selected challenge
        $selectedChallenge = null;
        foreach ($challenges as $challenge) {
            if ($challenge['id'] == $challengeId) {
                $selectedChallenge = $challenge;
                break;
            }
        }
        
        // If no challenge found, use a default
        if (!$selectedChallenge && !empty($challenges)) {
            $selectedChallenge = $challenges[0];
        }
        
        $expectedOutput = $selectedChallenge['expected_output'] ?? 'hello world';
        
        // Create a temporary file to execute the code
        $tempFile = tempnam(sys_get_temp_dir(), 'php_code');
        file_put_contents($tempFile, '<?php ' . $code . ' ?>');
        
        // Execute the code and capture the output
        ob_start();
        include $tempFile;
        $output = ob_get_clean();
        
        // Clean up the temporary file
        unlink($tempFile);
        
        // Check if the output matches the expected output (case-insensitive)
        $normalizedOutput = strtolower(trim($output));
        $solved = $normalizedOutput === strtolower(trim($expectedOutput));
        $result = $solved ? 'Solved' : 'Not Solved';
        
        // Get current user ID from session or use a default
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        $challengeName = $selectedChallenge['name'] ?? 'Challenge';
        
        // Save the result in the database with error handling
        try {
            $stmt = $pdo->prepare("INSERT INTO game_history (user_id, challenge_name, result) VALUES (:user_id, :challenge_name, :result)");
            $stmt->execute([
                ':user_id' => $userId,
                ':challenge_name' => $challengeName,
                ':result' => $result
            ]);
            
            // Save attempt in session history
            if (!isset($_SESSION['game_history'])) {
                $_SESSION['game_history'] = [];
            }
            $_SESSION['game_history'][] = date('Y-m-d H:i:s');
            
            // Handle badge awarding if solved
            if ($solved) {
                if (!isset($_SESSION['badges'])) {
                    $_SESSION['badges'] = [];
                }
                
                $badgeName = $selectedChallenge['badge_name'] ?? 'Achievement Badge';
                
                // Only add the badge if the user doesn't already have it
                if (!isset($_SESSION['badges'][$badgeName])) {
                    $badgeImage = $selectedChallenge['badge_image'] ?? 'assets/badges/goodjob.png';
                    $currentDate = date('Y-m-d H:i:s');
                    
                    $_SESSION['badges'][$badgeName] = [
                        'name' => $badgeName,
                        'image' => $badgeImage,
                        'date' => $currentDate
                    ];
                    
                    // Save badge to database
                    $stmt = $pdo->prepare("INSERT INTO badges (user_id, badge_name, badge_image_path, date_earned) 
                                          VALUES (:user_id, :badge_name, :badge_image, :date_earned)");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':badge_name' => $badgeName,
                        ':badge_image' => $badgeImage, // Store path instead of binary
                        ':date_earned' => $currentDate
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Log database error but don't expose details to the user
            error_log("Database error: " . $e->getMessage());
        }
        
        // Return the result to the frontend
        echo json_encode([
            'output' => $output,
            'solved' => $solved,
            'expected' => $expectedOutput,
            'challenge_id' => $challengeId
        ]);
        
    } catch (Exception $e) {
        // Handle any unexpected errors
        echo json_encode([
            'output' => 'An error occurred while processing your code.',
            'solved' => false,
            'error' => true,
            'message' => $e->getMessage()
        ]);
        
        error_log("Code execution error: " . $e->getMessage());
    }
} else {
    // Handle non-POST requests
    echo json_encode([
        'output' => 'Invalid request method.',
        'solved' => false,
        'error' => true
    ]);
}
?>