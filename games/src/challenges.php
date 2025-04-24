<?php
require_once __DIR__.'/config.php';

/**
 * Get all challenges from the database
 * 
 * @return array Array of challenges
 */
function getChallenges($type = null) {
    global $pdo;
    try {
        $sql = "SELECT * FROM `game_challenges` ORDER BY `difficulty_id`, `challenge_id`";
        if ($type !== null) {
            $sql = "SELECT * FROM `game_challenges` WHERE `challenge_type` = :type ORDER BY `difficulty_id`, `challenge_id`";
        }

        $stmt = $pdo->prepare($sql);
        if ($type !== null) {
            $stmt->bindParam(':type', $type);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process each result to decode and merge `content`
        foreach ($results as &$challenge) {
            if (isset($challenge['content'])) {
                $contentData = json_decode($challenge['content'], true);
                if (is_array($contentData)) {
                    $challenge = array_merge($challenge, $contentData);
                }
                unset($challenge['content']);
            }
        }

        return $results;
    } catch (PDOException $e) {
        log_error("Error fetching challenges: " . $e->getMessage());
        return [];
    }
}


/**
 * Get a specific challenge by ID
 * 
 * @param int $id Challenge ID
 * @return array|null Challenge data or null if not found
 */
function getChallengeById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM `game_challenges` WHERE `challenge_id` = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching challenge #$id: " . $e->getMessage());
        return null;
    }
}

/**
 * Record a completed challenge for a user
 * 
 * @param int $userId User ID
 * @param int $challengeId Challenge ID 
 * @param int $score Score earned
 * @param int $timeTaken Time taken in seconds
 * @return bool Success status
 */
function recordCompletedChallenge($userId, $challengeId, $score, $timeTaken) {
    global $pdo;
    try {
        // First check if already completed
        $checkStmt = $pdo->prepare("SELECT `progress_id` FROM `game_user_progress` WHERE `user_id` = :user_id AND `challenge_id` = :challenge_id");
        $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $checkStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Update the existing record if score is better
            $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("UPDATE `game_user_progress` SET `score` = :score, `time_taken` = :time_taken, `completed_at` = NOW() 
                                  WHERE `progress_id` = :id AND `score` < :score");
            $stmt->bindParam(':score', $score, PDO::PARAM_INT);
            $stmt->bindParam(':time_taken', $timeTaken, PDO::PARAM_INT);
            $stmt->bindParam(':id', $existingRecord['progress_id'], PDO::PARAM_INT);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO `game_user_progress` (`user_id`, `challenge_id`, `score`, `time_taken`, `completed_at`) 
                                  VALUES (:user_id, :challenge_id, :score, :time_taken, NOW())");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
            $stmt->bindParam(':score', $score, PDO::PARAM_INT);
            $stmt->bindParam(':time_taken', $timeTaken, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        log_error("Error recording challenge completion: " . $e->getMessage());
        return false;
    }
}

/**
 * Get completed challenges for a user
 * 
 * @param int $userId User ID
 * @return array Array of completed challenges
 */
function getUserCompletedChallenges($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, up.score, up.time_taken, up.completed_at
            FROM `game_user_progress` up
            JOIN `game_challenges` c ON up.challenge_id = c.challenge_id
            WHERE up.user_id = :user_id
            ORDER BY up.completed_at DESC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching user completed challenges: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user progress statistics
 * 
 * @param int $userId User ID
 * @return array User statistics
 */
function getUserStats($userId) {
    global $pdo;
    try {
        // Get total completed challenges
        $completedStmt = $pdo->prepare("
            SELECT COUNT(*) as completed_count, SUM(score) as total_score
            FROM `game_user_progress`
            WHERE `user_id` = :user_id
        ");
        $completedStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $completedStmt->execute();
        $completedData = $completedStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total available challenges
        $totalStmt = $pdo->prepare("SELECT COUNT(*) as total_count FROM `game_challenges` WHERE `is_active` = 1");
        $totalStmt->execute();
        $totalData = $totalStmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate completion percentage
        $completionPercentage = 0;
        if ($totalData['total_count'] > 0) {
            $completionPercentage = round(($completedData['completed_count'] / $totalData['total_count']) * 100);
        }
        
        return [
            'completed_count' => $completedData['completed_count'] ?? 0,
            'total_count' => $totalData['total_count'] ?? 0,
            'total_score' => $completedData['total_score'] ?? 0,
            'completion_percentage' => $completionPercentage
        ];
    } catch (PDOException $e) {
        log_error("Error fetching user stats: " . $e->getMessage());
        return [
            'completed_count' => 0,
            'total_count' => 0,
            'total_score' => 0,
            'completion_percentage' => 0
        ];
    }
}

/**
 * Get network level details
 * 
 * @param int $levelNumber The level number to retrieve
 * @return array Network level details
 */
function getNetworkLevel($levelNumber) {
    global $pdo;

    try {
        // Find the network challenge with the requested level number
        $stmt = $pdo->prepare("
            SELECT * FROM `game_challenges` 
            WHERE `challenge_type` = 'networking'
            ORDER BY `xp_value` ASC
            LIMIT :offset, 1
        ");
        $offset = $levelNumber - 1; // Convert level number to zero-based index
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$challenge) {
            throw new Exception("Network level not found");
        }
        
        // Parse the JSON content field
        $content = json_decode($challenge['content'], true);

        if (!$content) {
            throw new Exception("Invalid challenge content format");
        }
        
        // Construct the level data
        $levelData = [
            'title' => $challenge['name'],
            'description' => $content['description'],
            'devices' => $content['devices'] ?? [],
            'solution' => $content['connections'] ?? [],
            'points' => $content['points'] ?? 100
        ];
        
        return $levelData;
    } catch (Exception $e) {
        log_error("Error retrieving network level #$levelNumber: " . $e->getMessage());
        
        // Fall back to the default levels if database query fails
        return getDefaultNetworkLevel($levelNumber);
    }
}

/**
 * Check if the network solution is correct
 * 
 * @param int $level Level number
 * @param array $connections User-provided connections
 * @return array Result with success status and message
 */
function checkNetworkSolution($level, $connections) {
    $levelData = getNetworkLevel($level);
    $solution = $levelData['solution'];
    
    // Check if all required connections are present
    foreach ($solution as $requiredConnection) {
        $found = false;
        foreach ($connections as $userConnection) {
            if (
                ($userConnection['source'] === $requiredConnection['source'] && 
                 $userConnection['target'] === $requiredConnection['target']) || 
                ($userConnection['source'] === $requiredConnection['target'] && 
                 $userConnection['target'] === $requiredConnection['source'])
            ) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return [
                'success' => false,
                'message' => "Missing connection between {$requiredConnection['source']} and {$requiredConnection['target']}",
                'points' => 0
            ];
        }
    }
    
    // Check if there are any extra connections that shouldn't be there
    foreach ($connections as $userConnection) {
        $valid = false;
        foreach ($solution as $requiredConnection) {
            if (
                ($userConnection['source'] === $requiredConnection['source'] && 
                 $userConnection['target'] === $requiredConnection['target']) || 
                ($userConnection['source'] === $requiredConnection['target'] && 
                 $userConnection['target'] === $requiredConnection['source'])
            ) {
                $valid = true;
                break;
            }
        }
        
        if (!$valid) {
            return [
                'success' => false,
                'message' => "Invalid connection between {$userConnection['source']} and {$userConnection['target']}",
                'points' => 0
            ];
        }
    }
    
    // If we get here, the solution is correct
    return [
        'success' => true,
        'message' => "Network configured correctly!",
        'points' => $levelData['points'] ?? 100
    ];
}

/**
 * Save a new challenge to the database
 * 
 * @param array $challengeData Challenge data including type-specific content
 * @param int $userId ID of the user creating the challenge
 * @return int|bool The new challenge ID or false on error
 */
function saveChallenge($challengeData, $userId) {
    global $pdo;
    
    try {
        // Prepare content based on challenge type
        $content = [];
        
        if ($challengeData['challenge_type'] === 'programming') {
            $content = [
                'starter_code' => $challengeData['starter_code'] ?? '',
                'expected_output' => $challengeData['expected_output'] ?? ''
            ];
        } else if ($challengeData['challenge_type'] === 'networking') {
            $content = [
                'devices' => $challengeData['devices'] ?? [],
                'solution' => $challengeData['solution'] ?? [],
                'points' => $challengeData['points'] ?? 100
            ];
        }
        
        $jsonContent = json_encode($content);
        
        $stmt = $pdo->prepare("
            INSERT INTO `game_challenges` 
            (`challenge_type`, `name`, `description`, `difficulty_id`, `content`, `badge_name`, `badge_image`, `xp_value`, `created_by`)
            VALUES
            (:challenge_type, :name, :description, :difficulty_id, :content, :badge_name, :badge_image, :xp_value, :created_by)
        ");
        
        $stmt->bindParam(':challenge_type', $challengeData['challenge_type']);
        $stmt->bindParam(':name', $challengeData['name']);
        $stmt->bindParam(':description', $challengeData['description']);
        $stmt->bindParam(':difficulty_id', $challengeData['difficulty_id'], PDO::PARAM_INT);
        $stmt->bindParam(':content', $jsonContent);
        $stmt->bindParam(':badge_name', $challengeData['badge_name']);
        $stmt->bindParam(':badge_image', $challengeData['badge_image']);
        $stmt->bindParam(':xp_value', $challengeData['xp_value'], PDO::PARAM_INT);
        $stmt->bindParam(':created_by', $userId, PDO::PARAM_INT);
        
        $stmt->execute();
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        log_error("Error saving new challenge: " . $e->getMessage());
        return false;
    }
}

/**
 * Default network levels as fallback when database is unavailable
 * 
 * @param int $level Level number to retrieve
 * @return array Level data
 */
function getDefaultNetworkLevel($level) {
    $levels = [
        1 => [
            'title' => 'Basic Network Setup',
            'description' => 'Connect the client computers to the router to establish a basic home network.',
            'devices' => [
                'router1' => ['type' => 'router', 'x' => 400, 'y' => 200, 'ip' => '192.168.1.1'],
                'pc1' => ['type' => 'computer', 'x' => 200, 'y' => 100, 'ip' => '192.168.1.2'],
                'pc2' => ['type' => 'computer', 'x' => 200, 'y' => 300, 'ip' => '192.168.1.3'],
                'laptop1' => ['type' => 'laptop', 'x' => 600, 'y' => 100, 'ip' => '192.168.1.4'],
                'laptop2' => ['type' => 'laptop', 'x' => 600, 'y' => 300, 'ip' => '192.168.1.5']
            ],
            'solution' => [
                ['source' => 'pc1', 'target' => 'router1'],
                ['source' => 'pc2', 'target' => 'router1'],
                ['source' => 'laptop1', 'target' => 'router1'],
                ['source' => 'laptop2', 'target' => 'router1']
            ],
            'points' => 100
        ],
        2 => [
            'title' => 'Internet Connection',
            'description' => 'Connect the router to the modem to establish an internet connection for your network.',
            'devices' => [
                'modem' => ['type' => 'modem', 'x' => 400, 'y' => 100, 'ip' => '203.0.113.1'],
                'router1' => ['type' => 'router', 'x' => 400, 'y' => 250, 'ip' => '192.168.1.1'],
                'pc1' => ['type' => 'computer', 'x' => 200, 'y' => 300, 'ip' => '192.168.1.2'],
                'pc2' => ['type' => 'computer', 'x' => 600, 'y' => 300, 'ip' => '192.168.1.3'],
                'server' => ['type' => 'server', 'x' => 400, 'y' => 400, 'ip' => '192.168.1.4']
            ],
            'solution' => [
                ['source' => 'router1', 'target' => 'modem'],
                ['source' => 'pc1', 'target' => 'router1'],
                ['source' => 'pc2', 'target' => 'router1'],
                ['source' => 'server', 'target' => 'router1']
            ],
            'points' => 150
        ],
        3 => [
            'title' => 'Office Network with Switch',
            'description' => 'Create a small office network using a switch to connect multiple computers to a router.',
            'devices' => [
                'router1' => ['type' => 'router', 'x' => 400, 'y' => 100, 'ip' => '192.168.1.1'],
                'switch1' => ['type' => 'switch', 'x' => 400, 'y' => 250, 'ip' => ''],
                'pc1' => ['type' => 'computer', 'x' => 200, 'y' => 350, 'ip' => '192.168.1.2'],
                'pc2' => ['type' => 'computer', 'x' => 350, 'y' => 350, 'ip' => '192.168.1.3'],
                'pc3' => ['type' => 'computer', 'x' => 450, 'y' => 350, 'ip' => '192.168.1.4'],
                'pc4' => ['type' => 'computer', 'x' => 600, 'y' => 350, 'ip' => '192.168.1.5'],
                'printer' => ['type' => 'printer', 'x' => 600, 'y' => 200, 'ip' => '192.168.1.6']
            ],
            'solution' => [
                ['source' => 'switch1', 'target' => 'router1'],
                ['source' => 'pc1', 'target' => 'switch1'],
                ['source' => 'pc2', 'target' => 'switch1'],
                ['source' => 'pc3', 'target' => 'switch1'],
                ['source' => 'pc4', 'target' => 'switch1'],
                ['source' => 'printer', 'target' => 'switch1']
            ],
            'points' => 200
        ]
    ];
    
    return $levels[$level] ?? $levels[1];
}
?> 