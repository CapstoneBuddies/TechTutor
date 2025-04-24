<?php
include 'config.php';
global $pdo;

// Start or resume session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has admin privileges
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'TECHGURU' && $_SESSION['role'] !== 'ADMIN')) {
    // User does not have permission to add challenges
    log_error("Unauthorized attempt to add challenge by user ID: " . ($_SESSION['user'] ?? 'unknown'));
    header("Location: " . BASE);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'status' => 'error',
        'message' => 'An unknown error occurred'
    ];
    
    try {
        // Get common form fields
        $challengeName = trim($_POST['challenge_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $challengeType = trim($_POST['challenge_type'] ?? '');
        $difficulty = trim($_POST['difficulty'] ?? 'medium');
        $xpValue = (int)($_POST['xp_value'] ?? 100);
        
        // Validate required fields
        if (empty($challengeName) || empty($description) || empty($challengeType)) {
            $response['message'] = 'Required fields are missing';
            log_error("Challenge creation failed: Required fields missing");
            echo json_encode($response);
            exit;
        }
        
        // Check description length
        if (strlen($description) > 255) {
            $response['message'] = 'Description exceeds 255 character limit';
            log_error("Challenge creation failed: Description too long");
            echo json_encode($response);
            exit;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get difficulty ID from game_difficulty_levels table
        $difficultyStmt = $pdo->prepare("
            SELECT difficulty_id FROM game_difficulty_levels 
            WHERE name = ?
        ");
        $difficultyStmt->execute([$difficulty]);
        $difficultyResult = $difficultyStmt->fetch(PDO::FETCH_ASSOC);
        
        // If difficulty doesn't exist, create it
        if (!$difficultyResult) {
            $createDifficultyStmt = $pdo->prepare("
                INSERT INTO game_difficulty_levels (name, description) 
                VALUES (?, ?)
            ");
            $createDifficultyStmt->execute([
                $difficulty,
                ucfirst($difficulty) . ' level challenges'
            ]);
            $difficultyId = $pdo->lastInsertId();
        } else {
            $difficultyId = $difficultyResult['difficulty_id'];
        }
        
        // Prepare content data based on challenge type
        $contentData = [];
        $badgeName = '';
        $badgeImage = '';
        
        if ($challengeType === 'Coding' || $challengeType === 'programming') {
            // Standardize challenge type
            $challengeType = 'programming';
            
            $starterCode = $_POST['starter_code'] ?? '';
            $expectedOutput = $_POST['expected_output'] ?? '';
            
            // Language-agnostic content
            $contentData = [
                'starter_code' => $starterCode,
                'expected_output' => $expectedOutput,
                'languages' => ['php', 'javascript', 'python']
            ];
            
            $badgeName = $challengeName;
            $badgeImage = 'programming/' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($challengeName)) . '.png';
        } 
        elseif ($challengeType === 'Networking' || $challengeType === 'networking') {
            // Standardize challenge type
            $challengeType = 'networking';
            
            // For networking challenges, extract devices and connections
            $contentData = [
                'description' => $description,
                'devices' => (object)[],
                'connections' => []
            ];
            
            $badgeName = $challengeName;
            $badgeImage = 'networking/' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($challengeName)) . '.png';
        }
        elseif ($challengeType === 'UI' || $challengeType === 'ui') {
            // Standardize challenge type
            $challengeType = 'ui';
            
            $contentData = [
                'description' => $description,
                'criteria' => $_POST['criteria'] ?? ''
            ];
            
            $badgeName = 'UI: ' . $challengeName;
            $badgeImage = 'ui/' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($challengeName)) . '.png';
        }
        
        // Convert content to JSON
        $jsonContent = json_encode($contentData);
        
        // 1. Insert into game_challenges table using ? positional placeholders
        $stmt = $pdo->prepare("
            INSERT INTO game_challenges 
            (name, challenge_type, difficulty_id, content, badge_name, badge_image, xp_value, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        // Execute with positional parameters
        $stmt->execute([
            $challengeName,
            $challengeType,
            $difficultyId,
            $jsonContent,
            $badgeName,
            $badgeImage,
            $xpValue
        ]);
        
        $challengeId = $pdo->lastInsertId();
        
        // 2. Add to challenge_xp table for XP tracking
        $xpStmt = $pdo->prepare("
            INSERT INTO challenge_xp (challenge_id, xp_value, challenge_type) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE xp_value = ?
        ");
        $xpStmt->execute([
            $challengeId,
            $xpValue,
            $challengeType,
            $xpValue
        ]);
        
        // Commit the transaction
        $pdo->commit();
        
        $response = [
            'status' => 'success',
            'message' => 'Challenge created successfully',
            'challenge_id' => $challengeId
        ];
        
        log_error("Challenge created successfully: ID $challengeId, Name: $challengeName");
        
        // Redirect based on challenge type
        $redirectUrl = BASE . "games/";
        switch ($challengeType) {
            case 'programming':
                $redirectUrl = "codequest?challenge=" . $challengeId;
                break;
            case 'networking':
                $redirectUrl = "network-nexus";
                break;
            case 'ui':
                $redirectUrl = "design-dynamo";
                break;
        }
        
        header("Location: " . $redirectUrl);
        exit;
        
    } catch (PDOException $e) {
        // Roll back the transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        log_error("Error creating challenge: " . $e->getMessage());
        $response['message'] = 'Database error: ' . $e->getMessage();
        
        // If this is an AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode($response);
            exit;
        }
        
        // Otherwise redirect with error parameter
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE) . "?error=" . urlencode($response['message']));
        exit;
    }
    
    // If this is an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode($response);
        exit;
    }
} else {
    // Not a POST request, redirect to home
    header("Location: " . BASE);
    exit;
}
?>
