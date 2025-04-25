<?php    
    include 'config.php';
    include 'challenges.php';
    include 'xp_manager.php';
    global $pdo;

    // Start or resume session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get user ID from session
    $userId = isset($_SESSION['game']) ? $_SESSION['game'] : 1;
    
    // Get user's XP info
    $userXPInfo = getUserXPInfo($userId);
    
    // Get all level titles from database
    $levelTitles = getAllLevelTitles();

    // Active tab (default to 'all')
    $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

    // Function to get user badges
    function getUserBadges($userId) {
        global $pdo;
        
        try {
            // Get the user's current XP level
            $levelStmt = $pdo->prepare("
                SELECT level FROM user_xp WHERE user_id = :user_id
            ");
            $levelStmt->execute([':user_id' => $userId]);
            $userLevel = $levelStmt->fetchColumn() ?: 1; // Default to level 1 if not found
            
            // First get earned badges from game_user_badges table
            $query = "
                SELECT 
                    gb.name as badge_name, 
                    gb.image_path as badge_image, 
                    gb.description,
                    gub.earned_at, 
                    CASE 
                        WHEN gb.name LIKE '%Level%' OR gb.name IN (SELECT badge_name FROM level_definitions)
                        THEN 'level'
                        WHEN gb.name LIKE '%Network%' OR gb.name IN ('Basic Network Setup', 'Internet Connection', 'Office Network with Switch')
                        THEN 'networking'
                        WHEN gb.name LIKE '%Code%' OR gb.name LIKE '%Program%' OR gb.name IN ('Hello World', 'FizzBuzz Master', 'String Reversal')
                        THEN 'coding'
                        WHEN gb.name LIKE '%Design%' OR gb.name LIKE '%UI%' OR gb.name LIKE '%UX%'
                        THEN 'design'
                        ELSE 'achievement'
                    END as badge_category
                FROM game_user_badges gub
                JOIN game_badges gb ON gub.badge_id = gb.badge_id
                WHERE gub.user_id = :user_id
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $earnedBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Then get level-based badges the user should have based on their level
            $levelQuery = "
                SELECT 
                    badge_name,
                    badge_image,
                    CONCAT('Level ', level, ' Badge') as description,
                    NOW() as earned_at,
                    'level' as badge_category
                FROM level_definitions
                WHERE level <= :user_level
            ";
            
            $levelStmt = $pdo->prepare($levelQuery);
            $levelStmt->bindParam(':user_level', $userLevel, PDO::PARAM_INT);
            $levelStmt->execute();
            $levelBadges = $levelStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge the results, avoiding duplicates
            $allBadges = $earnedBadges;
            $badgeNames = array_map(function($badge) {
                return strtolower($badge['badge_name']);
            }, $earnedBadges);
            
            // Add level badges if not already included
            foreach ($levelBadges as $badge) {
                if (!in_array(strtolower($badge['badge_name']), $badgeNames)) {
                    $allBadges[] = $badge;
                }
            }
            
            // Sort by earned_at descending
            usort($allBadges, function($a, $b) {
                return strtotime($b['earned_at']) - strtotime($a['earned_at']);
            });
            
            return $allBadges;
        } catch (PDOException $e) {
            log_error("Error getting user badges: " . $e->getMessage());
            return [];
        }
    }

    // Function to get achievement completion stats
    function getAchievementStats($userId) {
        global $pdo;
        
        try {
            // Check if we're using new schema or old schema
            $hasGameUserProgress = $pdo->query("SHOW TABLES LIKE 'game_user_progress'")->rowCount() > 0;
            $hasGameChallenges = $pdo->query("SHOW TABLES LIKE 'game_challenges'")->rowCount() > 0;
            
            // Get total number of challenges
            $totalChallenges = 0;
            $challengeTypeCounts = [];
            
            if ($hasGameChallenges) {
                // Get column names to ensure we use the right ones
                $columnsStmt = $pdo->query("DESCRIBE game_challenges");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Determine status column name and condition
                $hasStatus = in_array('status', $columns) || in_array('is_active', $columns);
                $statusColumn = in_array('status', $columns) ? 'status' : 'is_active';
                $statusCondition = $hasStatus ? "WHERE $statusColumn = 'active' OR $statusColumn = 1" : "";
                
                // Get type column name
                $typeColumn = in_array('challenge_type', $columns) ? 'challenge_type' : 'type';
                
                // Count total challenges
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM game_challenges $statusCondition");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalChallenges = $result ? $result['total'] : 0;
                
                // Get challenge type counts
                $stmt = $pdo->prepare("SELECT $typeColumn as challenge_type, COUNT(*) as count FROM game_challenges $statusCondition GROUP BY $typeColumn");
                $stmt->execute();
                $challengeTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($challengeTypes as $type) {
                    $challengeTypeCounts[$type['challenge_type']] = $type['count'];
                }
            } else {
                // Use older schema with challenge_xp
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM challenge_xp");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalChallenges = $result ? $result['total'] : 0;
                
                // Get challenge type counts
                $stmt = $pdo->prepare("SELECT challenge_type, COUNT(*) as count FROM challenge_xp GROUP BY challenge_type");
                $stmt->execute();
                $challengeTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($challengeTypes as $type) {
                    $challengeTypeCounts[$type['challenge_type']] = $type['count'];
                }
            }
            
            // Get solved challenges
            $solvedCount = 0;
            $uniqueChallenges = 0;
            
            if ($hasGameUserProgress) {
                // Get the column names first to ensure we use the right ones
                $columnsStmt = $pdo->query("DESCRIBE game_user_progress");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Check for status column
                $hasStatus = in_array('status', $columns);
                if ($hasStatus) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as solved_count,
                        COUNT(DISTINCT challenge_id) as unique_challenges
                        FROM game_user_progress
                        WHERE user_id = :user_id AND status = 'completed'
                    ");
                } else {
                    // Check for alternative status columns
                    $hasCompleted = in_array('completed', $columns) || in_array('is_completed', $columns);
                    $completedColumn = in_array('completed', $columns) ? 'completed' : 'is_completed';
                    
                    if ($hasCompleted) {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as solved_count,
                            COUNT(DISTINCT challenge_id) as unique_challenges
                            FROM game_user_progress
                            WHERE user_id = :user_id AND $completedColumn = 1
                        ");
                    } else {
                        // If no status column exists, count all rows
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as solved_count,
                            COUNT(DISTINCT challenge_id) as unique_challenges
                            FROM game_user_progress
                            WHERE user_id = :user_id
                        ");
                    }
                }
            } else {
                // Use older schema with game_history
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as solved_count,
                    COUNT(DISTINCT challenge_name) as unique_challenges
                    FROM game_history
                    WHERE user_id = :user_id AND result = 'Solved'
                ");
            }
            
            $stmt->execute([':user_id' => $userId]);
            $progressResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progressResult) {
                $solvedCount = $progressResult['solved_count'];
                $uniqueChallenges = $progressResult['unique_challenges'];
            }
            
            // Get total badges
            $stmt = $pdo->prepare("SELECT COUNT(*) as badge_count FROM badges WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $badgeResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get badge counts by type
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE 
                          WHEN badge_name LIKE '%Cod%' OR badge_name LIKE '%Program%' OR 
                               badge_name IN ('Hello World', 'FizzBuzz Master', 'String Wizard', 
                                             'Algorithm Ace', 'Data Dynamo', 'System Sage')
                          THEN 1 END) as coding_badges,
                    COUNT(CASE 
                          WHEN badge_name LIKE '%Network%' OR 
                               badge_name IN ('Network Novice', 'Internet Explorer', 'Network Pro')
                          THEN 1 END) as networking_badges,
                    COUNT(CASE 
                          WHEN badge_name LIKE '%Design%' OR badge_name LIKE '%UI%' OR 
                               badge_name LIKE '%UX%' OR 
                               badge_name IN ('UI Novice', 'UI Designer', 'UI Master', 
                                            'Web Design Star', 'Mobile Design Guru', 'Logo Design Expert')
                          THEN 1 END) as design_badges
                FROM badges
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            $badgeTypeResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate completion percentages
            $challengeCompletion = $totalChallenges > 0 ? ($uniqueChallenges / $totalChallenges) * 100 : 0;
            
            return [
                'total_challenges' => $totalChallenges,
                'solved_challenges' => $uniqueChallenges,
                'challenge_completion' => round($challengeCompletion),
                'total_badges' => $badgeResult['badge_count'],
                'challenge_types' => count($challengeTypeCounts),
                'coding_badges' => $badgeTypeResult['coding_badges'] ?? 0,
                'networking_badges' => $badgeTypeResult['networking_badges'] ?? 0,
                'design_badges' => $badgeTypeResult['design_badges'] ?? 0,
                'coding_challenges' => $challengeTypeCounts['Coding'] ?? ($challengeTypeCounts['coding'] ?? 0),
                'networking_challenges' => $challengeTypeCounts['Networking'] ?? ($challengeTypeCounts['networking'] ?? 0),
                'design_challenges' => $challengeTypeCounts['Design'] ?? ($challengeTypeCounts['design'] ?? ($challengeTypeCounts['UI'] ?? 0)),
            ];
        } catch (PDOException $e) {
            log_error("Error getting achievement stats: " . $e->getMessage());
            return [
                'total_challenges' => 0,
                'solved_challenges' => 0,
                'challenge_completion' => 0,
                'total_badges' => 0,
                'challenge_types' => 0,
                'coding_badges' => 0,
                'networking_badges' => 0,
                'design_badges' => 0,
                'coding_challenges' => 0,
                'networking_challenges' => 0,
                'design_challenges' => 0,
            ];
        }
    }

    // Get user's badges
    $userBadges = getUserBadges($userId);
    
    // Filter badges based on active tab
    $filteredBadges = [];
    foreach ($userBadges as $badge) {
        if ($activeTab === 'all' || $badge['badge_category'] === $activeTab) {
            $filteredBadges[] = $badge;
        }
    }
    
    // Get achievement stats
    $achievementStats = getAchievementStats($userId);
    
    // Get available badges (ones that could potentially be earned)
    $availableBadges = [];

    // Add programming badges based on challenge_xp or game_challenges table
    try {
        // First check if game_challenges table exists
        $tableExists = $pdo->query("SHOW TABLES LIKE 'game_challenges'")->rowCount() > 0;
        
        if ($tableExists) {
            // Get the column names first to ensure we use the right ones
            $columnsStmt = $pdo->query("DESCRIBE game_challenges");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check for key columns
            $hasId = in_array('id', $columns);
            $hasChallengeName = in_array('challenge_name', $columns);
            $hasName = in_array('name', $columns);
            $idColumn = $hasId ? 'id' : 'challenge_id';
            $nameColumn = $hasChallengeName ? 'challenge_name' : ($hasName ? 'name' : 'title');
            
            // Check for status column
            $hasStatus = in_array('status', $columns) || in_array('is_active', $columns);
            $statusColumn = in_array('status', $columns) ? 'status' : 'is_active';
            $statusCondition = $hasStatus ? " WHERE $statusColumn = 'active' OR $statusColumn = 1" : "";
            
            // Use new schema with game_challenges - dynamically building the query based on available columns
            $query = "SELECT $idColumn as id, $nameColumn as name, 
                      " . (in_array('badge_name', $columns) ? "badge_name," : "CONCAT('Badge for ', $nameColumn) as badge_name,") . "
                      " . (in_array('badge_image', $columns) ? "badge_image" : "CONCAT('badges/challenge_', $idColumn, '.png')") . " as image, 
                      " . (in_array('challenge_type', $columns) ? "challenge_type" : "type") . " as challenge_type, 
                      " . (in_array('difficulty', $columns) ? "difficulty" : "'normal'") . " as difficulty, 
                      " . (in_array('xp_value', $columns) ? "xp_value" : "10") . " as xp_value,
                      " . (in_array('description', $columns) ? "description" : "CONCAT('Complete the ', $nameColumn, ' challenge')") . " as description
                FROM game_challenges $statusCondition";
            
            $stmt = $pdo->prepare($query);
        } else {
            // Use older schema with challenge_xp and get names from challenge_details_view if it exists
            $viewExists = $pdo->query("SHOW TABLES LIKE 'challenge_details_view'")->rowCount() > 0;
            
            if ($viewExists) {
                $stmt = $pdo->prepare("
                    SELECT cx.challenge_id as id, cdv.challenge_name as name, 
                           COALESCE(cdv.challenge_name, CONCAT('Challenge #', cx.challenge_id)) as badge_name,
                           CONCAT('badges/challenge_', cx.challenge_id, '.png') as image,
                           cx.challenge_type, cdv.difficulty, cx.xp_value,
                           'Complete this challenge to earn the badge' as description
                    FROM challenge_xp cx
                    LEFT JOIN challenge_details_view cdv ON cx.challenge_id = cdv.challenge_id
                ");
            } else {
                // If view doesn't exist, just use challenge_xp table
                $stmt = $pdo->prepare("
                    SELECT challenge_id as id, 
                           CONCAT('Challenge #', challenge_id) as name,
                           CONCAT('Challenge #', challenge_id) as badge_name,
                           CONCAT('badges/challenge_', challenge_id, '.png') as image,
                           challenge_type, 'normal' as difficulty, xp_value,
                           'Complete this challenge to earn the badge' as description
                    FROM challenge_xp
                ");
            }
        }
        
        $stmt->execute();
        $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($challenges as $challenge) {
            // Map challenge types to game types for filtering
            $gameType = strtolower($challenge['challenge_type']);
            if ($gameType === 'programming') $gameType = 'coding';
            if ($gameType === 'ui') $gameType = 'design';
            
            // Create an object for each badge
            $availableBadges[] = [
                'name' => $challenge['badge_name'] ?? $challenge['name'],
                'image' => $challenge['image'],
                'description' => $challenge['description'] ?? ('Complete ' . $challenge['name'] . ' challenge'),
                'difficulty' => $challenge['difficulty'] ?? 'Unknown',
                'id' => $challenge['id'],
                'game_type' => $gameType
            ];
        }
    } catch (PDOException $e) {
        log_error("Error getting available badges: " . $e->getMessage());
    }
    
    // Add level badges from level_definitions
    try {
        $stmt = $pdo->prepare("SELECT level, badge_name, badge_image, xp_required FROM level_definitions ORDER BY level ASC");
        $stmt->execute();
        $levelBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($levelBadges as $badge) {
            $availableBadges[] = [
                'name' => $badge['badge_name'],
                'image' => $badge['badge_image'],
                'description' => 'Reach level ' . $badge['level'] . ' by earning ' . number_format($badge['xp_required']) . ' XP',
                'difficulty' => 'Level ' . $badge['level'],
                'id' => 'level_' . $badge['level'],
                'game_type' => 'level'
            ];
        }
    } catch (PDOException $e) {
        log_error("Error getting level badges: " . $e->getMessage());
    }
    
    // Add special achievement badges if they're not already included
    $specialBadges = [
        [
            'name' => 'UI Novice', 
            'image' => 'badges/ui/ui_novice.png',
            'description' => 'Complete your first UI design challenge',
            'difficulty' => 'Beginner',
            'game_type' => 'design'
        ],
        [
            'name' => 'Network Novice', 
            'image' => 'badges/networking/network_novice.png',
            'description' => 'Complete your first networking challenge',
            'difficulty' => 'Beginner',
            'game_type' => 'networking'
        ],
        [
            'name' => 'Hello World', 
            'image' => 'badges/programming/hello_world.png',
            'description' => 'Write your first line of code',
            'difficulty' => 'Beginner',
            'game_type' => 'coding'
        ]
    ];
    
    // Add special badges if they don't already exist in the available badges
    foreach ($specialBadges as $badge) {
        $exists = false;
        foreach ($availableBadges as $availableBadge) {
            if ($availableBadge['name'] === $badge['name']) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $availableBadges[] = $badge;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Academy - Badges & Achievements</title>
    <!-- Favicons -->
    <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
    <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: #1e1e1e;
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 20px;
            border-right: 1px solid #444;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #fff;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background-color: #333;
            transform: translateX(5px);
        }
        .sidebar .active {
            background-color: #0d6efd;
            font-weight: 500;
        }
        .sidebar i {
            font-size: 1.2rem;
        }
        .sidebar .game-category {
            margin-top: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #aaa;
            letter-spacing: 1px;
            padding-left: 15px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-top: 1px solid #444;
            margin-top: auto;
        }
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-info .user-details {
            display: flex;
            flex-direction: column;
        }
        .user-info .user-name {
            font-weight: 500;
        }
        .user-info .user-role {
            font-size: 0.8rem;
            color: #aaa;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .main-content h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            font-weight: 600;
        }
        .card-body {
            padding: 20px;
        }

        /* Badge styles */
        .badge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        .badge-item {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .badge-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .badge-category {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 12px;
            background-color: #f8f9fa;
            color: #666;
        }
        .badge-category.level {
            background-color: #e3f2fd;
            color: #0d6efd;
        }
        .badge-category.coding {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .badge-category.networking {
            background-color: #fff3e0;
            color: #e65100;
        }
        .badge-category.design {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        .badge-image {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            object-fit: contain;
        }
        .badge-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        .badge-date {
            font-size: 0.8rem;
            color: #666;
        }
        .badge-locked {
            opacity: 0.5;
            filter: grayscale(1);
        }
        .badge-tooltip {
            display: none;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 0 0 12px 12px;
            font-size: 0.8rem;
        }
        .badge-item:hover .badge-tooltip {
            display: block;
        }
        
        /* Achievement stats */
        .achievement-stat {
            background-color: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .achievement-stat h6 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Gaming Academy</h2>
        <a href="../home">
            <i class="bi bi-house-door-fill"></i>
            Back To Main Dashboard
        </a>
        <a href="<?php echo BASE.'game';?>">
            <i class="bi bi-house-door-fill"></i>
            Back To Game Dashboard
        </a>
        
        <div class="game-category">Coding Games</div>
        <a href="../game/codequest">
            <i class="bi bi-code-square"></i>
            Code Quest
        </a>
        <a href="../game/network-nexus">
            <i class="bi bi-diagram-3-fill"></i>
            Network Nexus
        </a>
        <a href="../game/design-dynamo">
            <i class="bi bi-palette-fill"></i>
            Design Dynamo
        </a>
        
        <div class="game-category">Community</div>
        <a href="../game/leaderboards">
            <i class="bi bi-trophy-fill"></i>
            Leaderboards
        </a>
        <a href="#">
            <i class="bi bi-people-fill"></i>
            Friends
        </a>
        
        <div class="game-category">Your Profile</div>
        <a href="badges" class="active">
            <i class="bi bi-award-fill"></i>
            Badges & Achievements
        </a>
        <a href="#">
            <i class="bi bi-graph-up"></i>
            Your Progress
        </a>
        
        <div class="user-info">
            <img src="<?php echo $_SESSION['profile'] ?>" alt="User Avatar">
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['name']; ?></div>
                <div class="user-role">Level <?php echo $userXPInfo['current_level']; ?> - <?php echo getLevelTitle($userXPInfo['current_level']); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Badges & Achievements</h1>
            
            <?php if($_SESSION['role'] === 'ADMIN'): ?>
            <a href="generate-badges" class="btn btn-lg btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-gear-fill"></i>Badge Manager
            </a>
            <?php endif; ?>
            </div>

            <!-- Achievement Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="achievement-stat">
                        <h6><i class="bi bi-award me-2"></i> Badges Earned</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: <?php echo $achievementStats['total_badges'] > 0 ? 100 : 0; ?>%" 
                                 aria-valuenow="<?php echo $achievementStats['total_badges']; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo count($availableBadges); ?>"></div>
                        </div>
                        <div class="small d-flex justify-content-between">
                            <span class="text-muted"><?php echo $achievementStats['total_badges']; ?> Earned</span>
                            <span class="text-muted"><?php echo count($availableBadges); ?> Total</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="achievement-stat">
                        <h6><i class="bi bi-joystick me-2"></i> Challenges Completed</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $achievementStats['challenge_completion']; ?>%" 
                                 aria-valuenow="<?php echo $achievementStats['solved_challenges']; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $achievementStats['total_challenges']; ?>"></div>
                        </div>
                        <div class="small d-flex justify-content-between">
                            <span class="text-muted"><?php echo $achievementStats['solved_challenges']; ?> Completed</span>
                            <span class="text-muted"><?php echo $achievementStats['total_challenges']; ?> Total</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="achievement-stat">
                        <h6><i class="bi bi-stars me-2"></i> Current Level</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo $userXPInfo ? $userXPInfo['level_progress_percent'] : 0; ?>%" 
                                 aria-valuenow="<?php echo $userXPInfo ? $userXPInfo['level_progress_percent'] : 0; ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="small d-flex justify-content-between">
                            <span class="text-muted">Level <?php echo $userXPInfo ? $userXPInfo['current_level'] : 1; ?></span>
                            <span class="text-muted"><?php echo $userXPInfo ? $userXPInfo['total_xp'] : 0; ?> XP</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'all' ? 'active' : ''; ?>" href="?tab=all">
                        <i class="bi bi-grid-3x3-gap me-1"></i> All Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'level' ? 'active' : ''; ?>" href="?tab=level">
                        <i class="bi bi-stars me-1"></i> Level Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'coding' ? 'active' : ''; ?>" href="?tab=coding">
                        <i class="bi bi-code-slash me-1"></i> Coding Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'networking' ? 'active' : ''; ?>" href="?tab=networking">
                        <i class="bi bi-diagram-3 me-1"></i> Networking Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'design' ? 'active' : ''; ?>" href="?tab=design">
                        <i class="bi bi-palette me-1"></i> Design Badges
                    </a>
                </li>
            </ul>
            
            <!-- Badges Display -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Badges</h5>
                    <span class="badge bg-primary"><?php echo count($filteredBadges); ?> / <?php echo count($availableBadges); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($filteredBadges)): ?>
                        <div class="badge-grid">
                            <?php foreach ($filteredBadges as $badge): ?>
                                <div class="badge-item">
                                    <div class="badge-category <?php echo $badge['badge_category']; ?>">
                                        <?php echo ucfirst($badge['badge_category']); ?>
                                    </div>
                                    <?php 
                                    // Handle badge image path from different sources
                                    $imagePath = '';
                                    if (!empty($badge['badge_image'])) {
                                        // Check if it's a full path or just a filename
                                        if (strpos($badge['badge_image'], '/') === 0 || strpos($badge['badge_image'], 'http') === 0) {
                                            $imagePath = $badge['badge_image']; // Already a full path
                                        } else {
                                            // Check if it's from game_badges or regular badges
                                            $imgFile = GAME_IMG.'badges/'.$badge['badge_image'];
                                            if (file_exists($imgFile)) {
                                                $imagePath = $imgFile;
                                            } else {
                                                // Try level badge path
                                                $imgFile = GAME_IMG.'badges/level/'.$badge['badge_image'];
                                                if (file_exists($imgFile)) {
                                                    $imagePath = $imgFile;
                                                } else {
                                                    // Default fallback
                                                    $imagePath = GAME_IMG.'badges/goodjob.png';
                                                }
                                            }
                                        }
                                    } else {
                                        // No image specified, use default
                                        $imagePath = GAME_IMG.'badges/goodjob.png';
                                    }
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($badge['badge_name']); ?>" class="badge-image">
                                    <div class="badge-name"><?php echo htmlspecialchars($badge['badge_name']); ?></div>
                                    <div class="badge-date">Earned: <?php echo date('M d, Y', strtotime($badge['earned_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-award" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">No badges earned in this category yet.</p>
                            <p class="text-muted">Complete challenges to earn badges!</p>
                            <?php if($_GET['tab'] === 'networking'): ?>
                            <a href="network-nexus" class="btn btn-primary mt-2">Start a Challenge</a>
                            <?php elseif($_GET['tab'] === 'design'): ?>
                            <a href="design-dynamo" class="btn btn-primary mt-2">Start a Challenge</a>
                            <?php else: ?>
                            <a href="codequest" class="btn btn-primary mt-2">Start a Challenge</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Badges -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Available Badges</h5>
                </div>
                <div class="card-body">
                    <div class="badge-grid">
                        <?php 
                            $earnedBadgeNames = array_column($userBadges, 'badge_name');
                            
                            // Filter available badges based on the active tab
                            $availableFilteredBadges = [];
                            foreach ($availableBadges as $badge) {
                                if ($activeTab === 'all' || $badge['game_type'] === $activeTab) {
                                    $availableFilteredBadges[] = $badge;
                                }
                            }
                            
                            foreach ($availableFilteredBadges as $badge): 
                                $isEarned = in_array($badge['name'], $earnedBadgeNames);
                        ?>
                            <div class="badge-item <?php echo $isEarned ? '' : 'badge-locked'; ?>">
                                <div class="badge-category <?php echo $badge['game_type']; ?>">
                                    <?php echo ucfirst($badge['game_type']); ?>
                                </div>
                                <img src="<?php echo file_exists(GAME_IMG.'badges/'.$badge['image']) ? GAME_IMG.'badges/'.$badge['image'] : GAME_IMG.'badges/goodjob.png'; ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="badge-image">
                                <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                <div class="badge-date"><?php echo $badge['difficulty']; ?></div>
                                <div class="badge-tooltip">
                                    <?php echo htmlspecialchars($badge['description']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 