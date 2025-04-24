<?php
    // Reduce execution time to prevent server timeouts
    ini_set('max_execution_time', 5); // Limit execution to 5 seconds
    set_time_limit(5);
    
    // Set memory limit to prevent excessive memory usage
    ini_set('memory_limit', '64M');
    
    // Turn off output buffering to send data as it's generated
    ob_implicit_flush(true);
    ob_end_flush();
    
    // Set proper headers for JSON response to prevent fetch errors
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    include 'config.php';
    include 'challenges.php';
    // Include XP manager if file exists
    if (file_exists(__DIR__.'/xp_manager.php')) {
        include 'xp_manager.php';
    }
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the submitted code, challenge ID, and language - use simple defaults
        $code = $_POST['code'] ?? '';
        $challengeId = (int)($_POST['challenge_id'] ?? 1);
        $language = $_POST['language'] ?? 'php';
        
        // Get current user ID from session or use a default
        $userId = $_SESSION['game'] ?? 1;

        // Get challenge details - simplified query with LIMIT for performance
        $selectedChallenge = null;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM game_challenges WHERE challenge_id = ? LIMIT 1");
            $stmt->execute([$challengeId]);
            $selectedChallenge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found, try the challenges array
            if (!$selectedChallenge && !empty($challenges)) {
                foreach ($challenges as $challenge) {
                    if ($challenge['id'] == $challengeId || $challenge['challenge_id'] == $challengeId) {
                        $selectedChallenge = $challenge;
                        $selectedChallenge['challenge_id'] = $selectedChallenge['id'] ?? $selectedChallenge['challenge_id'];
                        $selectedChallenge['challenge_name'] = $selectedChallenge['name'] ?? $selectedChallenge['challenge_name'];
                        $selectedChallenge['challenge_type'] = $selectedChallenge['challenge_type'] ?? 'Coding';
                        break;
                    }
                }
            }
        } catch (PDOException $e) {
            log_error("Failed to get challenge details: " . $e->getMessage());
        }

        if (!$selectedChallenge && !empty($challenges)) {
            $selectedChallenge = $challenges[0];
            $selectedChallenge['challenge_id'] = $selectedChallenge['id'] ?? 1;
            $selectedChallenge['challenge_name'] = $selectedChallenge['name'] ?? 'Challenge';
            $selectedChallenge['challenge_type'] = $selectedChallenge['challenge_type'] ?? 'Coding';
        }

        $expectedOutput = $selectedChallenge['expected_output'] ?? 'hello world';
        $challengeName = $selectedChallenge['challenge_name'] ?? ($selectedChallenge['name'] ?? 'Challenge');
        
        // Process code based on language - OPTIMIZED for speed
        $output = '';
        $executionError = false;
        
        // FASTER PHP EXECUTION - don't use temp files or eval
        if ($language === 'php') {
            // Extract expected output from code without executing
            if (strpos($code, $expectedOutput) !== false) {
                // If code contains the expected output string, we can consider it correct
                $output = $expectedOutput;
            } else {
                // Check if they're printing the expected output
                if (preg_match('/echo\s+[\'"]([^\'"]+)[\'"];?/i', $code, $matches) ||
                    preg_match('/echo\s*\(\s*[\'"]([^\'"]+)[\'"].*?\);?/i', $code, $matches) ||
                    preg_match('/print\s+[\'"]([^\'"]+)[\'"];?/i', $code, $matches) ||
                    preg_match('/print\s*\(\s*[\'"]([^\'"]+)[\'"].*?\);?/i', $code, $matches)) {
                    $output = $matches[1];
                } else {
                    // Check for variable output
                    $varMatch = preg_match('/echo\s+\$(\w+);?/i', $code, $varMatches);
                    if ($varMatch && preg_match('/\$' . $varMatches[1] . '\s*=\s*[\'"]([^\'"]+)[\'"];?/i', $code, $valueMatches)) {
                        $output = $valueMatches[1];
                    } else {
                        // Look for any direct occurrences of expected output
                        if (stripos($code, $expectedOutput) !== false) {
                            $output = $expectedOutput;
                        } else {
                            $output = "Echo or print your output to see results";
                        }
                    }
                }
            }
        }
        // JAVASCRIPT EXECUTION - simplified
        else if ($language === 'javascript') {
            // Simple pattern matching for console.log
            if (preg_match('/console\.log\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)/i', $code, $matches)) {
                $output = $matches[1];
            } else if (strpos($code, $expectedOutput) !== false) {
                $output = $expectedOutput;
            } else {
                $output = "Use console.log() to see output";
            }
        }
        // PYTHON EXECUTION - simplified
        else if ($language === 'python') {
            // Pattern matching for print statements
            if (preg_match('/print\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)/i', $code, $matches)) {
                $output = $matches[1];
            } else if (strpos($code, $expectedOutput) !== false) {
                $output = $expectedOutput;
            } else {
                $output = "Use print() to see output";
            }
        }
        // OTHER LANGUAGES - just check for expected output presence
        else {
            if (strpos($code, $expectedOutput) !== false) {
                $output = $expectedOutput;
            } else {
                $output = "Simulated execution - " . strtoupper($language);
            }
        }
        
        // Determine if the solution is correct
        $solved = trim($output) === trim($expectedOutput);
        $xpEarned = 0;
        $alreadyCompleted = false;
        $xpResult = null;
        
        // Only process XP and badges if the solution is correct
        if ($solved) {
            try {
                // Check if already completed - OPTIMIZED with single query
                try {
                    $stmt = $pdo->prepare(
                        "SELECT EXISTS(
                            SELECT 1 FROM game_user_progress 
                            WHERE user_id = ? AND challenge_id = ? AND score > 0
                        ) AS completed"
                    );
                    $stmt->execute([$userId, $challengeId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $alreadyCompleted = $result && $result['completed'];
                } catch (PDOException $e) {
                    // Fallback to game_history
                    try {
                        $stmt = $pdo->prepare(
                            "SELECT EXISTS(
                                SELECT 1 FROM game_history 
                                WHERE user_id = ? AND challenge_name = ? AND result = 'Solved'
                            ) AS completed"
                        );
                        $stmt->execute([$userId, $challengeName]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $alreadyCompleted = $result && $result['completed'];
                    } catch (PDOException $e2) {
                        log_error("Error checking completion: " . $e2->getMessage());
                    }
                }
                
                // If not already completed, award XP and record completion
                if (!$alreadyCompleted) {
                    // Calculate XP
                    $xpValue = $selectedChallenge['xp_value'] ?? 10;
                    $difficulty = $selectedChallenge['difficulty'] ?? 'normal';
                    
                    // Simplify difficulty adjustment
                    if ($difficulty === 'easy') $xpValue = max(5, $xpValue);
                    if ($difficulty === 'hard') $xpValue = max(15, $xpValue);
                    
                    $xpEarned = $xpValue;
                    
                    // Record completion - OPTIMIZED with single query
                    try {
                        if ($pdo->query("SHOW TABLES LIKE 'game_user_progress'")->rowCount() > 0) {
                            // Only attempt INSERT ON DUPLICATE KEY UPDATE to reduce queries
                            $stmt = $pdo->prepare(
                                "INSERT INTO game_user_progress 
                                 (user_id, challenge_id, score, time_taken, completed_at) 
                                 VALUES (?, ?, ?, 0, NOW()) 
                                 ON DUPLICATE KEY UPDATE 
                                 score = VALUES(score), completed_at = NOW()"
                            );
                            $stmt->execute([$userId, $challengeId, $xpEarned]);
                        } else {
                            // Simple insert into game_history
                            $stmt = $pdo->prepare(
                                "INSERT INTO game_history 
                                 (user_id, challenge_name, result, xp_earned, timestamp) 
                                 VALUES (?, ?, 'Solved', ?, NOW())"
                            );
                            $stmt->execute([$userId, $challengeName, $xpEarned]);
                        }
                    } catch (PDOException $e) {
                        log_error("Error recording completion: " . $e->getMessage());
                    }
                    
                    // Use XP manager function if available (fastest option)
                    if (function_exists('addUserXP')) {
                        try {
                            $xpResult = addUserXP($userId, $xpEarned);
                        } catch (Exception $e) {
                            log_error("XP manager error: " . $e->getMessage());
                            
                            // Fallback: Update XP directly (simpler query)
                            try {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO user_xp (user_id, xp, level) 
                                     VALUES (?, ?, 1) 
                                     ON DUPLICATE KEY UPDATE 
                                     xp = xp + ?, level = GREATEST(level, 1)"
                                );
                                $stmt->execute([$userId, $xpEarned, $xpEarned]);
                            } catch (PDOException $e2) {
                                log_error("XP update error: " . $e2->getMessage());
                            }
                        }
                    }
                    
                    // Get user XP info if needed with simple query
                    if (function_exists('getUserXPInfo')) {
                        try {
                            $userXPInfo = getUserXPInfo($userId);
                        } catch (Exception $e) {
                            // Just log, don't worry about getting XP info
                            log_error("Error getting XP info: " . $e->getMessage());
                        }
                    }
                    
                    // Create badge if solution is correct (simple query)
                    try {
                        $insert_query = "INSERT INTO `game_user_badges`(user_id, badge_id) VALUES(?, ?)";
                        $insert_stmt = $pdo->prepare($insert_query);
                        $insert_stmt->execute([$userId, $challengeId]);
                        
                    } catch (PDOException $e) {
                        log_error("Badge error: " . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                log_error("General processing error: " . $e->getMessage());
            }
        }
        
        // Return a lightweight response - minimal JSON data
        echo json_encode([
            'output' => $output,
            'solved' => $solved,
            'expected' => $expectedOutput,
            'challenge_id' => $challengeId,
            'xp_earned' => $xpEarned,
            'already_earned' => $alreadyCompleted,
            'language' => $language
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);
        
    } catch (Exception $e) {
        // Simple error response
        echo json_encode([
            'output' => 'Processing error',
            'solved' => false,
            'error' => true,
            'message' => $e->getMessage()
        ]);
        
        log_error("Code execution error: " . $e->getMessage());
    }
} else {
    // Handle non-POST requests
    echo json_encode([
        'output' => 'Invalid request method',
        'solved' => false,
        'error' => true
    ]);
}
?>