<?php    
    include 'config.php';
    include 'challenges.php';
    global $pdo;

    // Start or resume session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /**
     * Define the network levels and solutions
     */
    function getNetworkLevel($level) {
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

    /**
     * Check if the network solution is correct
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
                    'message' => "Missing connection between {$requiredConnection['source']} and {$requiredConnection['target']}"
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
                    'message' => "Invalid connection between {$userConnection['source']} and {$userConnection['target']}"
                ];
            }
        }
        
        // All connections are correct
        return [
            'success' => true,
            'points' => $levelData['points'],
            'message' => 'Network configured correctly!'
        ];
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
            'devices' => [],
            'completed_levels' => []
        ];
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'connect':
                    $source = $_POST['source'] ?? '';
                    $target = $_POST['target'] ?? '';
                    
                    if (!empty($source) && !empty($target)) {
                        $_SESSION['network_game']['connections'][] = [
                            'source' => $source,
                            'target' => $target
                        ];
                        $message = "Connected {$source} to {$target}";
                    }
                    break;
                    
                case 'check_solution':
                    $level = $_SESSION['network_game']['level'];
                    $connections = $_SESSION['network_game']['connections'];
                    
                    $result = checkNetworkSolution($level, $connections);
                    if ($result['success']) {
                        $_SESSION['network_game']['completed_levels'][$level] = true;
                        $_SESSION['network_game']['score'] += $result['points'];
                        
                        // Save to database
                        try {
                            $stmt = $pdo->prepare("INSERT INTO game_history (user_id, challenge_name, result) 
                                                  VALUES (:user_id, :challenge_name, 'Solved')");
                            $stmt->execute([
                                ':user_id' => $_SESSION['user_id'] ?? 1,
                                ':challenge_name' => "Network Level {$level}"
                            ]);
                        } catch (PDOException $e) {
                            error_log("Database error: " . $e->getMessage());
                        }
                        
                        // Award badge for completing level
                        if (!isset($_SESSION['badges'])) {
                            $_SESSION['badges'] = [];
                        }
                        
                        $badgeName = "Network Level {$level} Master";
                        if (!isset($_SESSION['badges'][$badgeName])) {
                            $_SESSION['badges'][$badgeName] = [
                                'name' => $badgeName,
                                'image' => "assets/badges/network{$level}.png",
                                'date' => date('Y-m-d H:i:s')
                            ];
                        }
                        
                        $message = "Great job! Network configured correctly. +{$result['points']} points";
                        $gameCompleted = true;
                    } else {
                        $message = "Incorrect network configuration. {$result['message']}";
                    }
                    break;
                    
                case 'next_level':
                    $currentLevel = $_SESSION['network_game']['level'];
                    $_SESSION['network_game']['level'] = $currentLevel + 1;
                    $_SESSION['network_game']['connections'] = [];
                    $message = "Starting Level " . ($_SESSION['network_game']['level']);
                    break;
                    
                case 'reset_level':
                    $_SESSION['network_game']['connections'] = [];
                    $message = "Level reset";
                    break;
                    
                case 'reset_game':
                    $_SESSION['network_game'] = [
                        'level' => 1,
                        'score' => 0,
                        'connections' => [],
                        'devices' => [],
                        'completed_levels' => []
                    ];
                    $message = "Game reset to Level 1";
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
            $this->userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        }
        
        /**
         * Get leaderboard data showing top users by challenges completed
         */
        public function getLeaderboard($limit = 10) {
            try {
                $query = "SELECT u.username, COUNT(CASE WHEN gh.result = 'Solved' THEN 1 END) as solved_count, 
                          COUNT(DISTINCT gh.challenge_name) as unique_challenges,
                          (SELECT COUNT(*) FROM badges b WHERE b.user_id = u.id) as badge_count
                          FROM users u
                          LEFT JOIN game_history gh ON u.id = gh.user_id
                          GROUP BY u.id
                          ORDER BY solved_count DESC, unique_challenges DESC
                          LIMIT :limit";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Leaderboard error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Get challenge completion statistics
         */
        public function getChallengeStats() {
            try {
                $query = "SELECT challenge_name, 
                          COUNT(*) as attempt_count,
                          SUM(CASE WHEN result = 'Solved' THEN 1 ELSE 0 END) as solved_count,
                          (SUM(CASE WHEN result = 'Solved' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
                          FROM game_history
                          GROUP BY challenge_name
                          ORDER BY attempt_count DESC";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Challenge stats error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Get user progress across all challenges
         */
        public function getUserProgress($userId = null) {
            if ($userId === null) {
                $userId = $this->userId;
            }
            
            try {
                // Get all challenges completed by the user
                $query = "SELECT challenge_name, result, MAX(date) as last_attempt
                          FROM game_history 
                          WHERE user_id = :user_id
                          GROUP BY challenge_name
                          ORDER BY last_attempt DESC";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                
                $userChallenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Build a progress object with all challenges
                $progress = [];
                foreach ($GLOBALS['challenges'] as $challenge) {
                    $completed = false;
                    $lastAttempt = null;
                    
                    foreach ($userChallenges as $userChallenge) {
                        if ($userChallenge['challenge_name'] === $challenge['name']) {
                            $completed = ($userChallenge['result'] === 'Solved');
                            $lastAttempt = $userChallenge['last_attempt'];
                            break;
                        }
                    }
                    
                    $progress[] = [
                        'id' => $challenge['id'],
                        'name' => $challenge['name'],
                        'completed' => $completed,
                        'last_attempt' => $lastAttempt
                    ];
                }
                
                return $progress;
            } catch (PDOException $e) {
                error_log("User progress error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Get badges earned by a specific user
         */
        public function getUserBadges($userId = null) {
            if ($userId === null) {
                $userId = $this->userId;
            }
            
            try {
                $query = "SELECT badge_name, badge_image_path, date_earned
                          FROM badges
                          WHERE user_id = :user_id
                          ORDER BY date_earned DESC";
                
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("User badges error: " . $e->getMessage());
                return [];
            }
        }
        
        /**
         * Share a challenge completion on social media (mock function)
         */
        public function shareAchievement($challengeId, $platform = 'twitter') {
            // Find the challenge
            $challenge = null;
            foreach ($GLOBALS['challenges'] as $c) {
                if ($c['id'] == $challengeId) {
                    $challenge = $c;
                    break;
                }
            }
            
            if (!$challenge) {
                return [
                    'success' => false,
                    'message' => 'Challenge not found'
                ];
            }
            
            // In a real implementation, this would connect to social media APIs
            // For now, we'll just return a success message
            return [
                'success' => true,
                'message' => "Achievement for '{$challenge['name']}' challenge shared on $platform!",
                'share_url' => "https://codequest.example.com/share?challenge={$challengeId}&platform={$platform}"
            ];
        }
        
        /**
         * Add a friend connection between users (mock function)
         */
        public function addFriend($friendId) {
            try {
                // Since the user_friends table doesn't exist, we'll return a mock success response
                // instead of trying to query a non-existent table
                
                return [
                    'success' => true,
                    'message' => 'Friend added successfully (mock)'
                ];
            } catch (PDOException $e) {
                error_log("Add friend error: " . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Database error occurred'
                ];
            }
        }
        
        /**
         * Get friend activity feed
         */
        public function getFriendActivity() {
            try {
                // Since the user_friends table doesn't exist, we'll return a mock response
                // instead of trying to query a non-existent table
                
                return [
                    [
                        'username' => 'User1',
                        'challenge_name' => 'Hello World',
                        'result' => 'Solved',
                        'date' => date('Y-m-d H:i:s', strtotime('-1 day'))
                    ],
                    [
                        'username' => 'User2',
                        'challenge_name' => 'Factorial Calculator',
                        'result' => 'Solved',
                        'date' => date('Y-m-d H:i:s', strtotime('-2 day'))
                    ]
                ];
            } catch (PDOException $e) {
                error_log("Friend activity error: " . $e->getMessage());
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

    // Calculate completion percentage
    $completedCount = 0;
    foreach ($userProgress as $challenge) {
        if ($challenge['completed']) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Nexus - Gaming Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background-image: url('<?php echo IMG; ?>network/router.png'); 
        }
        .device.computer {
            background-image: url('<?php echo IMG; ?>network/computer.png');
        }
        .device.laptop {
            background-image: url('<?php echo IMG; ?>network/laptop.png');
        }
        .device.switch {
            background-image: url('<?php echo IMG; ?>network/switch.png');
        }
        .device.server {
            background-image: url('<?php echo IMG; ?>network/server.png');
        }
        .device.modem {
            background-image: url('<?php echo IMG; ?>network/modem.png');
        }
        .device.printer {
            background-image: url('<?php echo IMG; ?>network/printer.png');
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
    </style>
</head>
<body>
    <!-- Back Arrow -->
    <a href="./" class="back-arrow">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <div class="game-container">
        <h1 class="text-center mb-4">Network Configuration Game</h1>
        
        <div class="header">
            <div>
                <h2 class="level-title">Level <?php echo $currentLevel; ?>: <?php echo htmlspecialchars($levelData['title']); ?></h2>
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
        
        <div class="level-description">
            <?php echo htmlspecialchars($levelData['description']); ?>
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
                <div class="connection-line" id="connection-<?php echo $index; ?>" 
                     data-source="<?php echo $connection['source']; ?>" 
                     data-target="<?php echo $connection['target']; ?>"></div>
            <?php endforeach; ?>
        </div>
        
        <div class="control-panel">
            <form method="post" class="connection-form">
                <select name="source" class="form-select" required>
                    <option value="">Select source device</option>
                    <?php foreach ($devices as $id => $device): ?>
                        <option value="<?php echo $id; ?>"><?php echo $id; ?> (<?php echo $device['type']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <select name="target" class="form-select" required>
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
    
    <!-- Level Complete Modal -->
    <div class="modal fade" id="levelCompleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Level Complete!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Congratulations! You've successfully completed level <?php echo $currentLevel; ?>.</p>
                    <p>Points earned: <?php echo isset($levelData['points']) ? $levelData['points'] : 0; ?></p>
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <input type="hidden" name="action" value="next_level">
                        <button type="submit" class="btn btn-primary">Next Level</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            drawConnections();
            
            // Show level complete modal if needed
            <?php if ($gameCompleted): ?>
            const levelCompleteModal = new bootstrap.Modal(document.getElementById('levelCompleteModal'));
            levelCompleteModal.show();
            <?php endif; ?>
        });
        
        // Redraw connections on window resize
        window.addEventListener('resize', drawConnections);
    </script>
</body>
</html>