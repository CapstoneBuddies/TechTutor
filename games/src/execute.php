<?php
    include 'config.php';
    include 'challenges.php';
    // Include XP manager if file exists
    if (file_exists(__DIR__.'/xp_manager.php')) {
        include 'xp_manager.php';
    }
    global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the submitted code, challenge ID, and language
        $code = isset($_POST['code']) ? $_POST['code'] : '';
        $challengeId = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 1;
        $language = isset($_POST['language']) ? $_POST['language'] : 'php';
        
        // Find the selected challenge
        $selectedChallenge = getChallengeById($challengeId);
        
        // If no challenge found, use a default
        if (!$selectedChallenge && !empty($challenges)) {
            $selectedChallenge = $challenges[0];
        }
        
        $expectedOutput = $selectedChallenge['expected_output'] ?? 'hello world';
        
        // Get current user ID from session or use a default
        $userId = isset($_SESSION['user']) ? $_SESSION['user'] : null;
        
        // If user ID is not set but email is available, try to get user by email
        if (!$userId && isset($_SESSION['email'])) {
            try {
                $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $emailCheck->execute([':email' => $_SESSION['email']]);
                $userResult = $emailCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($userResult) {
                    $userId = $userResult['id'];
                    $_SESSION['user'] = $userId; // Update session with correct user ID
                } else {
                    // Create user if they don't exist but we have their email
                    if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
                        $name = isset($_SESSION['name']) ? $_SESSION['name'] : 
                               (isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? 
                               $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'User');
                        
                        $insertUser = $pdo->prepare("INSERT INTO users (username, email) VALUES (:username, :email)");
                        $insertUser->execute([
                            ':username' => $name,
                            ':email' => $_SESSION['email']
                        ]);
                        
                        $userId = $pdo->lastInsertId();
                        $_SESSION['user'] = $userId; // Update session with new user ID
                        
                        error_log("Created new user with ID: $userId for email: {$_SESSION['email']}");
                    } else {
                        $userId = 1; // Fallback to default if no email
                        error_log("No user found and couldn't create one - using default ID: 1");
                    }
                }
            } catch (PDOException $e) {
                $userId = 1; // Fallback to default
                error_log("Error getting/creating user: " . $e->getMessage());
            }
        } else if (!$userId) {
            $userId = 1; // Fallback to default
        }
        
        $challengeName = $selectedChallenge['name'] ?? 'Challenge';
        
        // Execute the code based on language
        $output = '';
        $executionError = false;
        
        switch ($language) {
            case 'php':
                // Create a temporary file to execute the PHP code
                $tempFile = tempnam(sys_get_temp_dir(), 'php_code');
                file_put_contents($tempFile, '<?php ' . $code . ' ?>');
                
                // Execute the code and capture the output
                ob_start();
                try {
                    include $tempFile;
                } catch (Exception $e) {
                    $output = "Error: " . $e->getMessage();
                    $executionError = true;
                }
                
                if (!$executionError) {
                    $output = ob_get_clean();
                } else {
                    ob_end_clean();
                }
                
                // Clean up the temporary file
                unlink($tempFile);
                break;
                
            case 'javascript':
                // Write JS code to a temp file
                $tempFile = tempnam(sys_get_temp_dir(), 'js_code');
                file_put_contents($tempFile, $code);
                
                // Execute with Node.js if available, or use a fallback message
                $nodeCommand = "node " . escapeshellarg($tempFile) . " 2>&1";
                
                if (function_exists('exec')) {
                    exec($nodeCommand, $outputArray, $returnCode);
                    $output = implode("\n", $outputArray);
                    
                    if ($returnCode !== 0) {
                        $executionError = true;
                    }
                } else {
                    $output = "JavaScript execution not available (Node.js required)";
                    $executionError = true;
                }
                
                // Clean up
                unlink($tempFile);
                break;
                
            case 'python':
                // Write Python code to a temp file
                $tempFile = tempnam(sys_get_temp_dir(), 'py_code');
                file_put_contents($tempFile, $code);
                
                // Execute with Python if available
                $pythonCommand = "python " . escapeshellarg($tempFile) . " 2>&1";
                
                if (function_exists('exec')) {
                    exec($pythonCommand, $outputArray, $returnCode);
                    $output = implode("\n", $outputArray);
                    
                    if ($returnCode !== 0) {
                        $executionError = true;
                    }
                } else {
                    $output = "Python execution not available";
                    $executionError = true;
                }
                
                // Clean up
                unlink($tempFile);
                break;
                
            // For languages that can't be executed directly, simulate execution
            case 'cpp':
            case 'java':
            case 'csharp':
            case 'ruby':
                // Check for expected patterns to simulate execution
                if (stripos($code, $expectedOutput) !== false) {
                    $output = $expectedOutput;
                } else {
                    // Try to extract expected output from print statements
                    $printPatterns = [
                        'cpp' => ['std::cout', 'printf', 'cout'],
                        'java' => ['System.out.println', 'System.out.print'],
                        'csharp' => ['Console.WriteLine', 'Console.Write'],
                        'ruby' => ['puts', 'print']
                    ];
                    
                    $patterns = $printPatterns[$language] ?? [];
                    $foundOutput = false;
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match('/' . preg_quote($pattern, '/') . '\s*\(\s*["\'](.+?)["\']\s*\)/', $code, $matches) ||
                            preg_match('/' . preg_quote($pattern, '/') . '\s*<<\s*["\'](.+?)["\']/', $code, $matches)) {
                            $output = $matches[1];
                            $foundOutput = true;
                            break;
                        }
                    }
                    
                    if (!$foundOutput) {
                        $output = "Execution simulated for " . strtoupper($language);
                    }
                }
                break;
                
            default:
                $output = "Unsupported language: " . htmlspecialchars($language);
                $executionError = true;
        }
        
        // Check if user already has the badge for this challenge
        $alreadyHasBadge = false;
        $badgeName = $selectedChallenge['badge_name'] ?? 'Achievement Badge';
        
        try {
            $badgeCheck = $pdo->prepare("SELECT 1 FROM badges WHERE user_id = :user_id AND badge_name = :badge_name LIMIT 1");
            $badgeCheck->execute([
                ':user_id' => $userId,
                ':badge_name' => $badgeName
            ]);
            
            $alreadyHasBadge = ($badgeCheck->fetchColumn() !== false);
        } catch (PDOException $e) {
            error_log("Badge check error: " . $e->getMessage());
        }

        // Check if the output matches the expected output (case-insensitive)
        $normalizedOutput = strtolower(trim($output));
        $solved = (!$executionError && $normalizedOutput === strtolower(trim($expectedOutput)));
        $result = $solved ? 'Solved' : 'Not Solved';
        
        // Get XP value for the challenge
        $xpEarned = ($solved && !$alreadyHasBadge) ? ($selectedChallenge['xp_value'] ?? 10) : 0;
        
        // Save the result in the database with error handling
        try {
            // Check if xp_earned column exists in game_history table
            $columnExists = false;
            try {
                $checkStmt = $pdo->prepare("SHOW COLUMNS FROM game_history LIKE 'xp_earned'");
                $checkStmt->execute();
                $columnExists = ($checkStmt->rowCount() > 0);
            } catch (PDOException $e) {
                // Column doesn't exist, continue without XP
                $columnExists = false;
            }
            
            if ($columnExists) {
                $stmt = $pdo->prepare("INSERT INTO game_history (user_id, challenge_name, result, xp_earned) VALUES (:user_id, :challenge_name, :result, :xp_earned)");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':challenge_name' => $challengeName,
                    ':result' => $result,
                    ':xp_earned' => $xpEarned
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO game_history (user_id, challenge_name, result) VALUES (:user_id, :challenge_name, :result)");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':challenge_name' => $challengeName,
                    ':result' => $result
                ]);
            }
            
            // Save attempt in session history
            if (!isset($_SESSION['game_history'])) {
                $_SESSION['game_history'] = [];
            }
            $_SESSION['game_history'][] = date('Y-m-d H:i:s');
            
            // Handle XP and level up if challenge was solved
            $xpResult = null;
            $userXPInfo = null;
            
            if ($solved && !$alreadyHasBadge) {
                // First, verify that the user exists in the users table
                try {
                    $userCheck = $pdo->prepare("SELECT 1 FROM users WHERE id = :user_id LIMIT 1");
                    $userCheck->execute([':user_id' => $userId]);
                    $userExists = ($userCheck->fetchColumn() !== false);
                    
                    // Only proceed with XP if user exists
                    if ($userExists && function_exists('addUserXP')) {
                        // Add XP to the user only if they don't already have the badge
                        $xpResult = addUserXP($userId, $xpEarned);
                        
                        // Get user's updated XP and level info
                        $userXPInfo = getUserXPInfo($userId);
                        
                        // Check if the user leveled up
                        if (isset($xpResult['leveled_up']) && $xpResult['leveled_up']) {
                            // Add level title if available
                            if (function_exists('getLevelTitle')) {
                                $xpResult['level_title'] = getLevelTitle($xpResult['new_level']);
                            }
                            
                            // Log the level up
                            try {
                                $logStmt = $pdo->prepare("INSERT INTO level_history (user_id, level, date_achieved) VALUES (:user_id, :level, NOW())");
                                $logStmt->execute([
                                    ':user_id' => $userId,
                                    ':level' => $xpResult['new_level']
                                ]);
                            } catch (PDOException $e) {
                                error_log("Failed to log level up: " . $e->getMessage());
                            }
                        }
                    } else if (!$userExists) {
                        // Log that user doesn't exist
                        error_log("Cannot add XP: User ID $userId does not exist in the users table");
                    }
                } catch (PDOException $e) {
                    error_log("User check error: " . $e->getMessage());
                }
            }
            
            // Handle badge awarding if solved
            if ($solved && !$alreadyHasBadge) {
                if (!isset($_SESSION['badges'])) {
                    $_SESSION['badges'] = [];
                }
                
                // Only add the badge if the user doesn't already have it
                if (!isset($_SESSION['badges'][$badgeName])) {
                    $badgeImage = $selectedChallenge['badge_image'] ?? GAME_IMG.'badges/goodjob.png';
                    $currentDate = date('Y-m-d H:i:s');
                    
                    $_SESSION['badges'][$badgeName] = [
                        'name' => $badgeName,
                        'image' => $badgeImage,
                        'date' => $currentDate
                    ];
                    
                    // Check if earned_at column exists in badges table
                    $badgeColumnName = 'earned_at';
                    try {
                        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM badges LIKE 'earned_at'");
                        $checkStmt->execute();
                        if ($checkStmt->rowCount() == 0) {
                            $badgeColumnName = 'date_earned';
                        }
                    } catch (PDOException $e) {
                        // Default to date_earned if there's an error
                        $badgeColumnName = 'date_earned';
                    }
                    
                    // Save badge to database with appropriate column name
                    $stmt = $pdo->prepare("INSERT INTO badges (user_id, badge_name, badge_image, $badgeColumnName) 
                                          VALUES (:user_id, :badge_name, :badge_image, :earned_at)");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':badge_name' => $badgeName,
                        ':badge_image' => $badgeImage, // Store path instead of binary
                        ':earned_at' => $currentDate
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
            'challenge_id' => $challengeId,
            'xp_earned' => $xpEarned,
            'level_info' => $solved && !$alreadyHasBadge ? $xpResult : null,
            'already_earned' => $alreadyHasBadge,
            'language' => $language,
            'user_xp' => $userXPInfo ?? null
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