<?php    
    include 'config.php';
    include 'challenges.php';
    global $pdo;

    // Start or resume session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Handle game actions
    $message = '';
    $gameCompleted = false;

    // Initialize game state if not set
    if (!isset($_SESSION['network_game'])) {
        $_SESSION['network_game'] = [
            'level' => 1,
            'score' => 0,
            'connections' => [],
            'connection_history' => [],
            'devices' => [],
            'completed_levels' => []
        ];
    }

    // Check if user has admin privileges
    $hasPrivilege = isset($_SESSION['role']) && ($_SESSION['role'] === 'TECHGURU' || $_SESSION['role'] === 'ADMIN');

    // Get the maximum level number from the database
    try {
        $maxLevelQuery = $pdo->prepare("
            SELECT COUNT(*) as max_level 
            FROM game_challenges 
            WHERE challenge_type = 'networking'
        ");
        $maxLevelQuery->execute();
        $maxLevelData = $maxLevelQuery->fetch(PDO::FETCH_ASSOC);
        $maxLevel = $maxLevelData['max_level'];
        
        // Check if all levels are completed
        $allLevelsCompleted = false;
        if (isset($_SESSION['network_game']['level']) && $_SESSION['network_game']['level'] > $maxLevel) {
            // Reset to max level and mark as completed
            $_SESSION['network_game']['level'] = $maxLevel;
            $allLevelsCompleted = true;
        }
    } catch (PDOException $e) {
        log_error("Error getting max level: " . $e->getMessage());
        $maxLevel = 3; // Default fallback value
        $allLevelsCompleted = false;
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'select_level':
                    $selectedLevel = intval($_POST['selected_level'] ?? 1);
                    if ($selectedLevel >= 1 && $selectedLevel <= $maxLevel) {
                        $_SESSION['network_game']['level'] = $selectedLevel;
                        // Optionally reset connections and state
                        $_SESSION['network_game']['connections'] = [];
                        $_SESSION['network_game']['connection_history'] = [];
                        $_SESSION['network_game']['devices'] = [];
                    }
                    break;
                
                case 'connect':
                    $source = $_POST['source'] ?? '';
                    $target = $_POST['target'] ?? '';
                    
                    if (!empty($source) && !empty($target)) {
                        // Generate a unique ID for this connection
                        $conn_id = uniqid('conn_');
                        
                        // Add to connections array with ID
                        $_SESSION['network_game']['connections'][] = [
                            'id' => $conn_id,
                            'source' => $source,
                            'target' => $target,
                            'timestamp' => time()
                        ];
                        
                        // Add to history
                        $_SESSION['network_game']['connection_history'][] = [
                            'id' => $conn_id,
                            'action' => 'connect',
                            'source' => $source,
                            'target' => $target,
                            'timestamp' => time()
                        ];
                        
                        $message = "Connected {$source} to {$target}";
                    }
                    break;
                
                case 'disconnect':
                    $conn_id = $_POST['connection_id'] ?? '';
                    
                    if (!empty($conn_id)) {
                        // Find and remove the connection
                        foreach ($_SESSION['network_game']['connections'] as $key => $conn) {
                            if ($conn['id'] === $conn_id) {
                                $source = $conn['source'];
                                $target = $conn['target'];
                                
                                // Remove from connections array
                                unset($_SESSION['network_game']['connections'][$key]);
                                
                                // Reindex array
                                $_SESSION['network_game']['connections'] = array_values($_SESSION['network_game']['connections']);
                                
                                // Add to history
                                $_SESSION['network_game']['connection_history'][] = [
                                    'action' => 'disconnect',
                                    'source' => $source,
                                    'target' => $target,
                                    'timestamp' => time()
                                ];
                                
                                $message = "Disconnected {$source} from {$target}";
                                break;
                            }
                        }
                    }
                    break;
                    
                case 'check_solution':
                    $level = $_SESSION['network_game']['level'];
                    $connections = $_SESSION['network_game']['connections'];

                    // Don't process if all levels are completed
                    if ($level > $maxLevel) {
                        $message = "All levels completed! No more submissions are being recorded.";
                        break;
                    }

                    // Extract just source and target for solution checking
                    $simpleConnections = array_map(function($conn) {
                        return [
                            'source' => $conn['source'],
                            'target' => $conn['target']
                        ];
                    }, $connections);
                    
                    $result = checkNetworkSolution($level, $simpleConnections);
                    if ($result['success']) {
                        $_SESSION['network_game']['completed_levels'][$level] = true;
                        $_SESSION['network_game']['score'] += $result['points'];
                        
                        // Find the challenge ID for this level
                        $challengeId = null;
                        try {
                            $stmt = $pdo->prepare("
                                SELECT challenge_id 
                                FROM `game_challenges` 
                                WHERE challenge_type = 'networking' 
                                ORDER BY challenge_id 
                                LIMIT :offset, 1
                            ");
                            $offset = $level - 1;
                            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                            $stmt->execute();
                            $challengeIdResult = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($challengeIdResult) {
                                $challengeId = $challengeIdResult['challenge_id'];
                            }
                        } catch (PDOException $e) {
                            log_error("Error finding challenge ID for network level $level: " . $e->getMessage());
                        }
                        
                        if ($challengeId) {
                            // Save to game_user_progress
                            try {
                                // Check if there's an existing record
                                $checkStmt = $pdo->prepare("
                                    SELECT `progress_id`, `score` 
                                    FROM `game_user_progress` 
                                    WHERE `user_id` = :user_id AND `challenge_id` = :challenge_id
                                ");
                                $checkStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                                $checkStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
                                $checkStmt->execute();
                                $existingProgress = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($existingProgress) {
                                    // Update only if new score is higher
                                    if ($result['points'] > $existingProgress['score']) {
                                        $updateStmt = $pdo->prepare("
                                            UPDATE `game_user_progress` 
                                            SET `score` = :score, `completed_at` = NOW() 
                                            WHERE `progress_id` = :progress_id
                                        ");
                                        $updateStmt->bindParam(':score', $result['points'], PDO::PARAM_INT);
                                        $updateStmt->bindParam(':progress_id', $existingProgress['progress_id'], PDO::PARAM_INT);
                                        $updateStmt->execute();
                                    }
                                } else {
                                    // Insert new record
                                    $insertStmt = $pdo->prepare("
                                        INSERT INTO `game_user_progress` 
                                        (`user_id`, `challenge_id`, `score`, `time_taken`, `completed_at`) 
                                        VALUES (:user_id, :challenge_id, :score, :time_taken, NOW())
                                    ");
                                    $insertStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                                    $insertStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
                                    $insertStmt->bindParam(':score', $result['points'], PDO::PARAM_INT);
                                    $timeTaken = 0; // Placeholder, you might want to track actual time
                                    $insertStmt->bindParam(':time_taken', $timeTaken, PDO::PARAM_INT);
                                    $insertStmt->execute();
                                }
                                
                                // Check if the user already has a related badge
                                try {
                                    // Get the challenge name for this level to use as badge name
                                    $levelQuery = $pdo->prepare("
                                        SELECT name FROM game_challenges 
                                        WHERE challenge_type = 'networking' 
                                        ORDER BY challenge_id 
                                        LIMIT :offset, 1
                                    ");
                                    $offset = $level - 1;
                                    $levelQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
                                    $levelQuery->execute();
                                    $levelData = $levelQuery->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($levelData) {
                                        $badgeName = $levelData['name'];
                                        
                                        // Find badge ID
                                        $badgeStmt = $pdo->prepare("
                                            SELECT badge_id FROM game_badges WHERE name = :badge_name
                                        ");
                                        $badgeStmt->bindParam(':badge_name', $badgeName);
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
                                                
                                                if ($awardStmt->execute()) {
                                                    // Add to message that badge was earned
                                                    $message .= " You earned the {$badgeName} badge!";
                                                }
                                            }
                                        } else {
                                            // Badge doesn't exist in database, log error
                                            log_error("Badge '{$badgeName}' not found in database");
                                        }
                                    }
                                } catch (PDOException $e) {
                                    log_error("Error getting badge for level {$level}: " . $e->getMessage());
                                }
                            } catch (PDOException $e) {
                                log_error("Error saving network challenge completion: " . $e->getMessage());
                            }
                        }
                        
                        // Add to history
                        $_SESSION['network_game']['connection_history'][] = [
                            'action' => 'level_complete',
                            'level' => $level,
                            'timestamp' => time()
                        ];
                        
                        $message = "Great job! Network configured correctly. +{$result['points']} points";
                        $gameCompleted = true;
                    } else {
                        // Add to history
                        $_SESSION['network_game']['connection_history'][] = [
                            'action' => 'check_failed',
                            'message' => $result['message'],
                            'timestamp' => time()
                        ];
                        
                        $message = "Incorrect network configuration. {$result['message']}";
                    }
                    break;
                    
                case 'next_level':
                    $currentLevel = $_SESSION['network_game']['level'];
                    $_SESSION['network_game']['level'] = $currentLevel + 1;
                    $_SESSION['network_game']['connections'] = [];
                    
                    // Add to history
                    $_SESSION['network_game']['connection_history'][] = [
                        'action' => 'next_level',
                        'from_level' => $currentLevel,
                        'to_level' => $currentLevel + 1,
                        'timestamp' => time()
                    ];
                    
                    $message = "Starting Level " . ($_SESSION['network_game']['level']);
                    break;
                    
                case 'reset_level':
                    // Add to history before clearing connections
                    $_SESSION['network_game']['connection_history'][] = [
                        'action' => 'reset_level',
                        'level' => $_SESSION['network_game']['level'],
                        'timestamp' => time()
                    ];
                    
                    $_SESSION['network_game']['connections'] = [];
                    $message = "Level reset";
                    break;
                    
                case 'reset_game':
                    $_SESSION['network_game'] = [
                        'level' => 1,
                        'score' => 0,
                        'connections' => [],
                        'connection_history' => [
                            [
                                'action' => 'reset_game',
                                'timestamp' => time()
                            ]
                        ],
                        'devices' => [],
                        'completed_levels' => []
                    ];
                    $message = "Game reset to Level 1";
                    break;
                    
                case 'delete_level':
                    $deleteLevelNum = intval($_POST['delete_level_num'] ?? 0);
                    $challengeId = intval($_POST['challenge_id'] ?? 0);
                    if ($deleteLevelNum > 0 && $challengeId > 0) {
                        require_once __DIR__ . '/challenges.php';
                        $challenge = getChallengeById($challengeId);
                        $canDelete = false;
                        if ($challenge) {
                            if ($_SESSION['role'] === 'ADMIN') {
                                $canDelete = true;
                            } elseif ($_SESSION['role'] === 'TECHGURU' && isset($challenge['created_by']) && $challenge['created_by'] == $_SESSION['game']) {
                                $canDelete = true;
                            }
                        }
                        log_error($canDelete);
                        if ($canDelete) {
                            $_POST['challenge_id'] = $challengeId;
                            ob_start();
                            $deleteResponse = ob_get_clean();
                            $deleteResult = json_decode($deleteResponse, true);
                            if ($deleteResult && !empty($deleteResult['success'])) {
                                $message = "Level $deleteLevelNum deleted successfully.";
                                $_SESSION['network_game']['level'] = 1;
                            } else {
                                $message = "Failed to delete level: " . ($deleteResult['message'] ?? 'Unknown error.');
                            }
                        } else {
                            $message = "You do not have permission to delete this level.";
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Game Networking Controller
     * 
     * This file handles multiplayer features, leaderboards, and social aspects
     * of the Code Quest game, integrating with the main game components.
     */
    class GameNetworking {
        private $pdo;
        private $userId;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
            // Get current user ID from session or use a default
            $this->userId = isset($_SESSION['game']) ? $_SESSION['game'] : 1;
        }
        
        /**
         * Get leaderboard data showing top users by challenges completed
         */
        public function getLeaderboard($limit = 10) {
            try {
                $query = "SELECT u.username, 
                          COUNT(DISTINCT gup.challenge_id) as solved_count, 
                          SUM(gup.score) as total_score,
                          (SELECT COUNT(*) FROM game_user_badges gub WHERE gub.user_id = u.id) as badge_count
                          FROM users u
                          LEFT JOIN game_user_progress gup ON u.id = gup.user_id
                          GROUP BY u.id
                          ORDER BY solved_count DESC, total_score DESC
                          LIMIT :limit";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                log_error("Leaderboard error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Get challenge completion statistics
         */
        public function getChallengeStats() {
            try {
                $query = "SELECT gc.name as challenge_name, 
                          COUNT(gup.progress_id) as attempt_count,
                          COUNT(DISTINCT gup.user_id) as unique_users,
                          AVG(gup.score) as avg_score,
                          gdl.name as difficulty
                          FROM game_challenges gc
                          LEFT JOIN game_difficulty_levels gdl ON gc.difficulty_id = gdl.difficulty_id
                          LEFT JOIN game_user_progress gup ON gc.challenge_id = gup.challenge_id
                          WHERE gc.challenge_type = 'networking'
                          GROUP BY gc.challenge_id, gc.name, gdl.name
                          ORDER BY attempt_count DESC";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                log_error("Challenge stats error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Get progress data for a specific user
         */
        public function getUserProgress($userId = null) {
            if (is_null($userId)) {
                $userId = $this->userId;
            }
            
            try {
                // First get all challenges
                $query = "SELECT 
                            gc.challenge_id,
                            gc.name AS challenge_name,
                            gc.challenge_type,
                            gdl.name AS difficulty,
                            gc.xp_value
                          FROM 
                            game_challenges gc
                          JOIN 
                            game_difficulty_levels gdl ON gc.difficulty_id = gdl.difficulty_id
                          WHERE 
                            gc.challenge_type = 'networking'
                          ORDER BY 
                            gc.difficulty_id ASC, 
                            gc.name ASC";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
                $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Now get user progress separately
                $query = "SELECT 
                            challenge_id,
                            score,
                            time_taken,
                            completed_at
                          FROM 
                            game_user_progress
                          WHERE 
                            user_id = :user_id";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Create a lookup array for progress
                $progressLookup = [];
                foreach ($progress as $item) {
                    $progressLookup[$item['challenge_id']] = $item;
                }
                
                // Combine the data
                $result = [];
                foreach ($challenges as $challenge) {
                    $challengeProgress = [
                        'challenge_id' => $challenge['challenge_id'],
                        'challenge_name' => $challenge['challenge_name'],
                        'challenge_type' => $challenge['challenge_type'],
                        'difficulty' => $challenge['difficulty'],
                        'xp_value' => $challenge['xp_value'],
                        'score' => null,
                        'time_taken' => null,
                        'completed_at' => null
                    ];
                    
                    // If we have progress for this challenge, add it
                    if (isset($progressLookup[$challenge['challenge_id']])) {
                        $userProgress = $progressLookup[$challenge['challenge_id']];
                        $challengeProgress['score'] = $userProgress['score'];
                        $challengeProgress['time_taken'] = $userProgress['time_taken'];
                        $challengeProgress['completed_at'] = $userProgress['completed_at'];
                    }
                    
                    $result[] = $challengeProgress;
                }
                
                return $result;
            } catch (PDOException $e) {
                log_error("User progress error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Get badges for a specific user
         */
        public function getUserBadges($userId = null) {
            if (is_null($userId)) {
                $userId = $this->userId;
            }
            
            try {
                $query = "SELECT gb.name as badge_name, gb.image_path as badge_image_path, 
                          gub.earned_at as date_earned, gb.description
                          FROM game_badges gb
                          JOIN game_user_badges gub ON gb.badge_id = gub.badge_id
                          WHERE gub.user_id = :user_id
                          ORDER BY gub.earned_at DESC";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                log_error("User badges error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Add a friend
         */
        public function addFriend($friendName) {
            try {
                // Check if friend exists
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $friendName, PDO::PARAM_STR);
                $stmt->execute();
                
                $friend = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$friend) {
                    return [
                        'success' => false,
                        'message' => 'Friend not found'
                    ];
                }
                
                // In a real implementation, we would add a friendship relation to a database table
                
                return [
                    'success' => true,
                    'message' => 'Friend added successfully (mock)'
                ];
            } catch (PDOException $e) {
                log_error("Add friend error: " . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Database error occurred'
                ];
            }
        }
        
        /**
         * Get friend activity (mock implementation)
         */
        public function getFriendActivity() {
            try {
                // In a real implementation, we would query recent activity of friends
                
                return [
                    [
                        'username' => 'NetworkFriend1',
                        'activity' => 'Completed Network Level 3',
                        'timestamp' => date('Y-m-d H:i:s', time() - 3600)
                    ],
                    [
                        'username' => 'NetworkFriend2',
                        'activity' => 'Earned Network Pro Badge',
                        'timestamp' => date('Y-m-d H:i:s', time() - 7200)
                    ]
                ];
            } catch (PDOException $e) {
                log_error("Friend activity error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Process API requests
         */
        public function handleApiRequest() {
            if (!isset($_REQUEST['action'])) {
                return json_encode(['error' => 'No action specified']);
            }
            
            $action = $_REQUEST['action'];
            $result = [];
            
            switch ($action) {
                case 'leaderboard':
                    $limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 10;
                    $result = $this->getLeaderboard($limit);
                    break;
                    
                case 'challenge_stats':
                    $result = $this->getChallengeStats();
                    break;
                    
                case 'user_progress':
                    $userId = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : null;
                    $result = $this->getUserProgress($userId);
                    break;
                    
                case 'user_badges':
                    $userId = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : null;
                    $result = $this->getUserBadges($userId);
                    break;
                    
                case 'share_achievement':
                    $challengeId = isset($_REQUEST['challenge_id']) ? (int)$_REQUEST['challenge_id'] : 0;
                    $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 'twitter';
                    $result = $this->shareAchievement($challengeId, $platform);
                    break;
                    
                case 'add_friend':
                    $friendId = isset($_REQUEST['friend_id']) ? (int)$_REQUEST['friend_id'] : 0;
                    $result = $this->addFriend($friendId);
                    break;
                    
                case 'friend_activity':
                    $result = $this->getFriendActivity();
                    break;
                    
                default:
                    $result = ['error' => 'Invalid action specified'];
            }
            
            return json_encode($result);
        }
    }

    // Create the networking instance
    $gameNetworking = new GameNetworking($pdo);

    // Handle API requests if needed
    if (isset($_REQUEST['api']) && $_REQUEST['api'] === 'true') {
        header('Content-Type: application/json');
        echo $gameNetworking->handleApiRequest();
        exit;
    }

    // Get data for the UI
    $leaderboard = $gameNetworking->getLeaderboard(5);
    $challengeStats = $gameNetworking->getChallengeStats();
    $userProgress = $gameNetworking->getUserProgress();
    $friendActivity = $gameNetworking->getFriendActivity();

    // Handle share request
    $shareMessage = '';
    if (isset($_POST['share_challenge'])) {
        $challengeId = (int)$_POST['challenge_id'];
        $platform = $_POST['platform'] ?? 'twitter';
        $result = $gameNetworking->shareAchievement($challengeId, $platform);
        $shareMessage = $result['message'];
    }

    // Handle add friend request
    $friendMessage = '';
    if (isset($_POST['add_friend'])) {
        $friendId = (int)$_POST['friend_id'];
        $result = $gameNetworking->addFriend($friendId);
        $friendMessage = $result['message'];
    }

    // Initialize the GameNetworking class if not already done
    if (!isset($gameNetworking)) {
        $gameNetworking = new GameNetworking($pdo);
    }
    
    // Get user progress
    $userProgress = $gameNetworking->getUserProgress();
    
    // Calculate completion percentage
    $completedCount = 0;
    foreach ($userProgress as $challenge) {
        if (!empty($challenge['score'])) {
            $completedCount++;
        }
    }
    $completionPercent = count($userProgress) > 0 ? round(($completedCount / count($userProgress)) * 100) : 0;

    // Get current level data
    $currentLevel = $_SESSION['network_game']['level'];
    $levelData = getNetworkLevel($currentLevel);

    // Get game state
    $score = $_SESSION['network_game']['score'];
    $connections = $_SESSION['network_game']['connections'];
    $devices = $levelData['devices'];

    // Get connection history (limit to last 10 items)
    $connectionHistory = !empty($_SESSION['network_game']['connection_history']) 
        ? array_slice($_SESSION['network_game']['connection_history'], -10) 
        : [];

    // Display completion message if all levels are finished
    if ($allLevelsCompleted) {
        $message = "Congratulations! You have completed all levels.";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Nexus - Gaming Academy</title>
    <!-- Favicons -->
    <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
    <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/fontawesome/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a2e;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Create a flex layout for the game content */
        .game-layout {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        /* Main game content area */
        .game-main-content {
            flex: 1;
            min-width: 0; /* Prevent flex items from overflowing */
        }
        
        /* Sidebar for connection history */
        .game-sidebar {
            width: 300px;
            flex-shrink: 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #0f3460;
            border-radius: 10px;
        }
        .network-area {
            position: relative;
            height: 500px;
            background-color: #16213e;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .control-panel {
            background-color: #16213e;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .device {
            position: absolute;
            width: 60px;
            height: 60px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            cursor: pointer;
            transition: transform 0.2s;
            z-index: 10;
        }
        .device:hover {
            transform: scale(1.1);
        }
        .device.selected {
            box-shadow: 0 0 0 3px #e1b12c, 0 0 10px 3px rgba(225, 177, 44, 0.5);
            border-radius: 30%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 3px #e1b12c, 0 0 10px 3px rgba(225, 177, 44, 0.5); }
            50% { box-shadow: 0 0 0 5px #e1b12c, 0 0 15px 5px rgba(225, 177, 44, 0.5); }
            100% { box-shadow: 0 0 0 3px #e1b12c, 0 0 10px 3px rgba(225, 177, 44, 0.5); }
        }
        .device-info {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 2px 6px;
            border-radius: 3px;
            white-space: nowrap;
        }
        .ip-address {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 2px 6px;
            border-radius: 3px;
            white-space: nowrap;
        }
        .connection-line {
            position: absolute;
            background-color: #3498db;
            height: 3px;
            transform-origin: left center;
            z-index: 5;
        }
        .back-arrow {
            position: fixed;
            top: 20px;
            left: 20px;
            font-size: 24px;
            color: #fff;
            cursor: pointer;
            z-index: 1000;
        }
        .back-arrow:hover {
            color: #0f3460;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .score {
            font-size: 1.2em;
            font-weight: bold;
        }
        .level-title {
            font-size: 1.4em;
            margin: 0;
        }
        .device.router {
            background-image: url('<?php echo GAME_IMG; ?>network/router.png'); 
        }
        .device.computer {
            background-image: url('<?php echo GAME_IMG; ?>network/computer.png');
        }
        .device.laptop {
            background-image: url('<?php echo GAME_IMG; ?>network/laptop.png');
        }
        .device.switch {
            background-image: url('<?php echo GAME_IMG; ?>network/switch.png');
        }
        .device.server {
            background-image: url('<?php echo GAME_IMG; ?>network/server.png');
        }
        .device.modem {
            background-image: url('<?php echo GAME_IMG; ?>network/modem.png');
        }
        .device.printer {
            background-image: url('<?php echo GAME_IMG; ?>network/printer.png');
        }
        .connection-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }
        .level-description {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .btn-primary {
            background-color: #0f3460;
            border-color: #0f3460;
        }
        .btn-primary:hover {
            background-color: #533483;
            border-color: #533483;
        }
        .modal-content {
            background-color: #16213e;
            color: #fff;
        }
        .modal-header {
            border-bottom-color: #333;
        }
        .modal-footer {
            border-top-color: #333;
        }
        
        /* Connection history styles */
        .connection-history {
            background-color: #16213e;
            border-radius: 10px;
            padding: 15px;
            height: 100%;
            overflow-y: auto;
        }
        .connection-history h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.2em;
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 14px;
        }
        .history-item:last-child {
            border-bottom: none;
        }
        .history-item.connect {
            color: #4cd137;
        }
        .history-item.disconnect {
            color: #e84118;
        }
        .history-item .remove-btn {
            background-color: #e84118;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 12px;
            cursor: pointer;
        }
        .history-item .timestamp {
            color: #999;
            font-size: 12px;
        }
        
        /* Connection mode indicator */
        .connection-mode-indicator {
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: rgba(225, 177, 44, 0.2);
            border: 1px solid #e1b12c;
            font-size: 14px;
            display: none;
        }
        .connection-mode-indicator.active {
            display: block;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .connection-form {
                flex-direction: column;
            }
            .connection-form .form-select,
            .connection-form button {
                width: 100%;
                margin-bottom: 10px;
            }
            
            /* Stack layout on mobile */
            .game-layout {
                flex-direction: column;
            }
            
            .game-sidebar {
                width: 100%;
            }
        }
        
        /* Badge styling */
        .badge-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge-card img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .badge-earned {
            background-color: #e8f5e9;
            border-color: #4caf50;
        }
        
        .badge-locked {
            background-color: #f5f5f5;
            opacity: 0.7;
            filter: grayscale(80%);
        }
        
        .badge-earned img {
            filter: drop-shadow(0 0 3px rgba(76, 175, 80, 0.5));
        }
        
        .badge-description {
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .badge-status {
            font-size: 0.8rem;
            font-style: italic;
            color: #757575;
        }
        
        .badge-earned .badge-status {
            color: #2e7d32;
            font-weight: bold;
        }
        
        /* Custom level dropdown styles */
        .level-dropdown {
            max-width: 400px;
        }
        
        .level-dropdown .dropdown-toggle {
            width: 100%;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .level-dropdown .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        
        .level-dropdown .dropdown-menu {
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            padding: 0.5rem 0;
        }
        
        .level-dropdown .dropdown-menu li {
            padding: 0.25rem 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .level-dropdown .dropdown-menu li:last-child {
            border-bottom: none;
        }
        
        .level-dropdown .dropdown-menu li:hover {
            background-color: rgba(50, 151, 168, 1);
        }
        
        .level-dropdown .level-text {
            font-weight: bold;
            padding: 0.375rem 0;
            cursor: pointer;
        }
        
        .level-dropdown .level-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Back Arrow -->
    <a href="./" class="back-arrow">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <div class="game-container">
        <h1 class="text-center mb-4">Network Configuration Game</h1>
        
        <!-- Level dropdown and info section -->
        <div class="level-dropdown">
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="levelDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>Level <?php echo $_SESSION['network_game']['level']; ?></span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="levelDropdownBtn">
                    <?php for ($i = 1; $i <= $maxLevel; $i++):
                        $challenge = $getNetworkLevel[$i] ?? null;
                        $isActive = ($_SESSION['network_game']['level'] == $i);
                    ?>
                    <li onclick="selectLevel(<?= $i ?>)">
                        <span class="level-text" data-level="<?= $i ?>" >
                            Level <?= $i ?>
                        </span>
                        <div class="level-buttons">
                            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                            <form method="get" action="update_challenge.php" style="display:inline;">
                                <input type="hidden" name="challenge_id" value="<?= htmlspecialchars($i) ?>">
                                <button type="submit" class="btn btn-sm btn-warning" title="Update Level <?= $i ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if (($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'TECHGURU')): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete Level <?= $i ?>? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_level">
                                <input type="hidden" name="delete_level_num" value="<?= $i ?>">
                                <input type="hidden" name="challenge_id" value="<?= htmlspecialchars($i) ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Level <?= $i ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>
        
        <!-- Add Challenge Button (if admin) -->
        <?php if ($hasPrivilege): ?>
        <div class="text-center mb-3">
            <button id="add-challenge-btn" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add Challenge
            </button>
        </div>
        <?php endif; ?>
        
        <div class="header">
            <div>
                <h2 class="level-title">Level <?php echo $currentLevel; ?>: <?php echo htmlspecialchars($levelData['title']); ?></h2>
            </div>
            <div class="level-indicator">
                Level: <span class="badge bg-primary"><?php echo $_SESSION['network_game']['level']; ?> of <?php echo $maxLevel; ?></span>
                <?php if ($allLevelsCompleted): ?>
                <span class="badge bg-success ms-2">All Completed!</span>
                <?php endif; ?>
            </div>
            <div class="score">
                Score: <?php echo $score; ?>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Flex layout wrapper -->
        <div class="game-layout">
            <!-- Main game content -->
            <div class="game-main-content">
                <div class="level-description">
                    <?php echo htmlspecialchars($levelData['description']); ?>
                </div>
                
                <!-- Connection mode indicator -->
                <div id="connectionModeIndicator" class="connection-mode-indicator">
                    <i class="fas fa-plug"></i> Connection mode: Click on a second device to connect
                </div>
                
                <div class="network-area" id="networkArea">
                    <?php foreach ($devices as $id => $device): ?>
                        <div class="device <?php echo $device['type']; ?>" 
                             id="<?php echo $id; ?>" 
                             data-device-id="<?php echo $id; ?>"
                             data-device-type="<?php echo $device['type']; ?>"
                             data-ip="<?php echo $device['ip']; ?>"
                             style="left: <?php echo $device['x']; ?>px; top: <?php echo $device['y']; ?>px;">
                            <div class="device-info"><?php echo $id; ?></div>
                            <?php if (!empty($device['ip'])): ?>
                                <div class="ip-address"><?php echo $device['ip']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($connections as $index => $connection): ?>
                        <div class="connection-line" 
                             id="connection-<?php echo $connection['id']; ?>" 
                             data-source="<?php echo $connection['source']; ?>" 
                             data-target="<?php echo $connection['target']; ?>"
                             data-conn-id="<?php echo $connection['id']; ?>"></div>
                    <?php endforeach; ?>
                </div>
                
                <div class="control-panel">
                    <form method="post" id="connectionForm" class="connection-form">
                        <select name="source" id="sourceSelect" class="form-select" required>
                            <option value="">Select source device</option>
                            <?php foreach ($devices as $id => $device): ?>
                                <option value="<?php echo $id; ?>"><?php echo $id; ?> (<?php echo $device['type']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="target" id="targetSelect" class="form-select" required>
                            <option value="">Select target device</option>
                            <?php foreach ($devices as $id => $device): ?>
                                <option value="<?php echo $id; ?>"><?php echo $id; ?> (<?php echo $device['type']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="hidden" name="action" value="connect">
                        <button type="submit" class="btn btn-primary">Connect Devices</button>
                    </form>
                    
                    <div class="d-flex gap-2">
                        <form method="post">
                            <input type="hidden" name="action" value="check_solution">
                            <button type="submit" class="btn btn-success">Check Solution</button>
                        </form>
                        
                        <form method="post">
                            <input type="hidden" name="action" value="reset_level">
                            <button type="submit" class="btn btn-secondary">Reset Level</button>
                        </form>
                    </div>
                </div>
                
                <?php if ($gameCompleted): ?>
                    <div class="text-center mt-3">
                        <form method="post">
                            <input type="hidden" name="action" value="next_level">
                            <button type="submit" class="btn btn-primary">Next Level</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar with connection history -->
            <div class="game-sidebar">
                <!-- Connection History Panel -->
                <div class="connection-history">
                    <h3>Connection History</h3>
                    <?php if (empty($connections)): ?>
                        <div class="history-item">No connections yet. Connect some devices!</div>
                    <?php else: ?>
                        <?php foreach ($connections as $connection): ?>
                            <div class="history-item connect">
                                <div>
                                    Connected <strong><?php echo htmlspecialchars($connection['source']); ?></strong> to 
                                    <strong><?php echo htmlspecialchars($connection['target']); ?></strong>
                                    <span class="timestamp"><?php echo date('H:i:s', $connection['timestamp']); ?></span>
                                </div>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="disconnect">
                                    <input type="hidden" name="connection_id" value="<?php echo $connection['id']; ?>">
                                    <button type="submit" class="remove-btn" title="Remove connection">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Badges section -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Your Badges</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    // Get all available badges
                    $badgesQuery = "
                        SELECT gb.badge_id, gb.name, gb.image_path, gb.description,
                               CASE WHEN gub.user_badge_id IS NOT NULL THEN 1 ELSE 0 END as earned,
                               gub.earned_at
                        FROM game_badges gb
                        LEFT JOIN game_user_badges gub ON gb.badge_id = gub.badge_id AND gub.user_id = :user_id
                        WHERE gb.name IN (
                            SELECT name FROM game_challenges WHERE challenge_type = 'networking'
                        )
                        ORDER BY gb.badge_id ASC
                    ";
                    
                    try {
                        $badgesStmt = $pdo->prepare($badgesQuery);
                        $badgesStmt->bindParam(':user_id', $_SESSION['game'], PDO::PARAM_INT);
                        $badgesStmt->execute();
                        $badges = $badgesStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($badges)) {
                            echo '<div class="col-12"><p>No badges available yet.</p></div>';
                        } else {
                            foreach ($badges as $badge) {
                                // Default image if none specified
                                $imagePath = (!empty($badge['image_path']) && file_exists(GAME_IMG.'badges/'.$badge['image_path'])) ? GAME_IMG.'badges/'.$badge['image_path'] : GAME_IMG.'badges/goodjob.png';
                                
                                // Badge CSS class
                                $badgeClass = $badge['earned'] ? 'badge-earned' : 'badge-locked';
                                
                                // Badge text
                                $earnedText = $badge['earned'] ? 
                                    'Earned on ' . date('M d, Y', strtotime($badge['earned_at'])) : 
                                    'Not yet earned';
                                
                                echo '<div class="col-md-3 col-sm-6 mb-4">';
                                echo '<div class="badge-card ' . $badgeClass . '">';
                                echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($badge['name']) . ' Badge">';
                                echo '<h5>' . htmlspecialchars($badge['name']) . '</h5>';
                                echo '<p class="badge-description">' . htmlspecialchars($badge['description']) . '</p>';
                                echo '<p class="badge-status">' . $earnedText . '</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                    } catch (PDOException $e) {
                        log_error("Error fetching badges: " . $e->getMessage());
                        echo '<div class="col-12"><p>Unable to load badges.</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Level complete modal -->
        <div class="modal fade" id="levelCompleteModal" tabindex="-1" aria-labelledby="levelCompleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="levelCompleteModalLabel">Level Complete!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo $message; ?></p>
                        <?php if ($_SESSION['network_game']['level'] < $maxLevel) { ?>
                            <p>Ready for the next challenge?</p>
                        <?php } else { ?>
                            <p>Congratulations! You have completed all available levels!</p>
                            <div class="alert alert-success mt-3">
                                <strong>All Levels Completed!</strong> You've mastered all the networking challenges.
                            </div>
                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <?php if ($_SESSION['network_game']['level'] < $maxLevel) { ?>
                        <form method="post">
                            <input type="hidden" name="action" value="next_level">
                            <button type="submit" class="btn btn-primary">Next Level</button>
                        </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- All levels complete modal -->
        <div class="modal fade" id="allCompletedModal" tabindex="-1" aria-labelledby="allCompletedModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="allCompletedModalLabel">Congratulations!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-trophy text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-center">You've Completed All Networking Levels!</h4>
                        <p class="text-center mt-3">You've mastered all the networking challenges in our database. Check back later for new challenges!</p>
                        
                        <div class="mt-4">
                            <h5>Your Achievements:</h5>
                            <ul>
                                <li>Completed <strong><?php echo $maxLevel; ?></strong> network challenges</li>
                                <li>Total score: <strong><?php echo $score; ?></strong> points</li>
                                <li>Unlocked all networking badges</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <form method="post">
                            <input type="hidden" name="action" value="reset_game">
                            <button type="submit" class="btn btn-outline-danger">Reset Game</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hidden form for device click connections -->
        <form id="clickConnectionForm" method="post" style="display: none;">
            <input type="hidden" name="action" value="connect">
            <input type="hidden" id="clickSourceDevice" name="source" value="">
            <input type="hidden" id="clickTargetDevice" name="target" value="">
        </form>
        
        <!-- Add Challenge Modal -->
        <?php if ($hasPrivilege): ?>
        <div class="modal fade" id="addChallengeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Network Challenge</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addChallengeForm" method="post" action="add_challenge.php">
                            <input type="hidden" name="challenge_type" value="networking">
                            <div class="mb-3">
                                <label for="challenge_name" class="form-label">Challenge Name</label>
                                <input type="text" class="form-control" id="challenge_name" name="challenge_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="difficulty" class="form-label">Difficulty</label>
                                    <select class="form-select" id="difficulty" name="difficulty" required>
                                        <option value="Easy">Easy</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Hard">Hard</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="xp_value" class="form-label">XP Value</label>
                                    <input type="number" class="form-control" id="xp_value" name="xp_value" min="10" max="500" value="100" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="network_diagram" class="form-label">Network Diagram (JSON)</label>
                                <textarea class="form-control" id="network_diagram" name="network_diagram" rows="5" placeholder='{"nodes":[{"id":"device1","type":"computer","x":100,"y":100},{"id":"router","type":"router","x":300,"y":100}],"connections":[{"source":"device1","target":"router"}]}'></textarea>
                                <small class="form-text text-muted">Define the network topology as JSON with nodes and expected connections</small>
                            </div>
                            <div class="mb-3">
                                <label for="expected_solution" class="form-label">Expected Solution (JSON)</label>
                                <textarea class="form-control" id="expected_solution" name="expected_solution" rows="3" placeholder='[{"source":"device1","target":"router"},{"source":"device2","target":"router"}]'></textarea>
                                <small class="form-text text-muted">Define the expected connections as a JSON array</small>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Challenge</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to handle level selection from dropdown
        function selectLevel(level) {
            // Create and submit a form to select the level
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'select_level';
            
            const levelInput = document.createElement('input');
            levelInput.type = 'hidden';
            levelInput.name = 'selected_level';
            levelInput.value = level;
            
            form.appendChild(actionInput);
            form.appendChild(levelInput);
            document.body.appendChild(form);
            
            form.submit();
        }
        
        // Draw connections between devices
        function drawConnections() {
            const connections = document.querySelectorAll('.connection-line');
            
            connections.forEach(connection => {
                const sourceId = connection.getAttribute('data-source');
                const targetId = connection.getAttribute('data-target');
                
                const sourceElement = document.getElementById(sourceId);
                const targetElement = document.getElementById(targetId);
                
                if (sourceElement && targetElement) {
                    // Calculate positions
                    const sourceRect = sourceElement.getBoundingClientRect();
                    const targetRect = targetElement.getBoundingClientRect();
                    const networkRect = document.getElementById('networkArea').getBoundingClientRect();
                    
                    const sourceX = sourceRect.left + sourceRect.width/2 - networkRect.left;
                    const sourceY = sourceRect.top + sourceRect.height/2 - networkRect.top;
                    const targetX = targetRect.left + targetRect.width/2 - networkRect.left;
                    const targetY = targetRect.top + targetRect.height/2 - networkRect.top;
                    
                    // Calculate distance and angle
                    const distance = Math.sqrt(Math.pow(targetX - sourceX, 2) + Math.pow(targetY - sourceY, 2));
                    const angle = Math.atan2(targetY - sourceY, targetX - sourceX) * 180 / Math.PI;
                    
                    // Set line properties
                    connection.style.width = `${distance}px`;
                    connection.style.left = `${sourceX}px`;
                    connection.style.top = `${sourceY}px`;
                    connection.style.transform = `rotate(${angle}deg)`;
                }
            });
        }
        
        // Initialize click-to-connect functionality
        function initClickToConnect() {
            const devices = document.querySelectorAll('.device');
            const connectionIndicator = document.getElementById('connectionModeIndicator');
            const connectionForm = document.getElementById('clickConnectionForm');
            const sourceInput = document.getElementById('clickSourceDevice');
            const targetInput = document.getElementById('clickTargetDevice');
            
            // Track connection state
            let isConnecting = false;
            let selectedDevice = null;
            
            // Add click handler to each device
            devices.forEach(device => {
                device.addEventListener('click', function() {
                    const deviceId = this.getAttribute('data-device-id');
                    
                    // If not in connection mode, start connection mode
                    if (!isConnecting) {
                        // Clear any previous selections
                        devices.forEach(d => d.classList.remove('selected'));
                        
                        // Mark this device as selected
                        this.classList.add('selected');
                        selectedDevice = deviceId;
                        
                        // Show connection mode indicator
                        connectionIndicator.classList.add('active');
                        
                        // Set as source in the form
                        sourceInput.value = deviceId;
                        
                        // Update dropdown in the manual form for visual consistency
                        const sourceDropdown = document.getElementById('sourceSelect');
                        if (sourceDropdown) {
                            sourceDropdown.value = deviceId;
                        }
                        
                        // Start connection mode
                        isConnecting = true;
                    } 
                    // If we're already in connection mode and user clicks a different device, complete the connection
                    else if (deviceId !== selectedDevice) {
                        // Set as target in the form
                        targetInput.value = deviceId;
                        
                        // Exit connection mode
                        isConnecting = false;
                        connectionIndicator.classList.remove('active');
                        devices.forEach(d => d.classList.remove('selected'));
                        
                        // Submit the connection
                        connectionForm.submit();
                    }
                    // If user clicks the same device again, cancel connection mode
                    else {
                        isConnecting = false;
                        this.classList.remove('selected');
                        connectionIndicator.classList.remove('active');
                    }
                });
            });
            
            // Allow user to cancel connection mode by clicking anywhere in the network area
            const networkArea = document.getElementById('networkArea');
            networkArea.addEventListener('click', function(e) {
                // Only cancel if clicking the background (not a device)
                if (e.target === this && isConnecting) {
                    isConnecting = false;
                    devices.forEach(d => d.classList.remove('selected'));
                    connectionIndicator.classList.remove('active');
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            drawConnections();
            initClickToConnect();
        });
        
        // Redraw connections on window resize
        window.addEventListener('resize', drawConnections);
        
        window.addEventListener('load', function() {
            <?php if ($gameCompleted): ?>
            // Level complete - show modal
            const levelCompleteModal = new bootstrap.Modal(document.getElementById('levelCompleteModal'));
            levelCompleteModal.show();
            <?php endif; ?>
        });
        
        <?php if ($hasPrivilege): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('add-challenge-btn').addEventListener('click', function() {
                var addChallengeModal = new bootstrap.Modal(document.getElementById('addChallengeModal'));
                addChallengeModal.show();
            });
        });
        <?php endif; ?>
        
        // Show all levels complete modal if all levels are completed
        window.addEventListener('load', function() {
            <?php if ($allLevelsCompleted): ?>
            const allCompletedModal = new bootstrap.Modal(document.getElementById('allCompletedModal'));
            allCompletedModal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>