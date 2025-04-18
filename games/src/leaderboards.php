<?php    
    include 'config.php';
    include 'challenges.php';
    include 'xp_manager.php';
    global $pdo;

    // Start or resume session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Define current tab (default to 'codequest')
    $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'codequest';

    // Get user ID from session
    $userId = isset($_SESSION['user']) ? $_SESSION['user'] : 1;
    
    // Get user's XP info
    $userXPInfo = getUserXPInfo($userId);
    
    // Get all level titles from database
    $levelTitles = getAllLevelTitles();

    // Get leaderboard data for each game
    function getLeaderboard($gameType, $limit = 10) {
        global $pdo;
        
        try {
            $query = "";
            
            switch ($gameType) {
                case 'codequest':
                    // Code Quest leaderboard - based on coding challenges solved
                    $query = "SELECT u.username, COUNT(CASE WHEN gh.result = 'Solved' AND gh.challenge_name LIKE 'Coding%' THEN 1 END) as solved_count, 
                              MAX(gh.date) as last_active,
                              COALESCE(ux.level, 1) as user_level,
                              COALESCE(ux.xp, 0) as total_xp
                              FROM users u
                              LEFT JOIN game_history gh ON u.id = gh.user_id
                              LEFT JOIN user_xp ux ON u.id = ux.user_id
                              GROUP BY u.id
                              HAVING solved_count > 0
                              ORDER BY solved_count DESC, user_level DESC, total_xp DESC, last_active DESC
                              LIMIT :limit";
                    break;
                    
                case 'networking':
                    // Network Nexus leaderboard - based on network levels completed
                    $query = "SELECT u.username, COUNT(CASE WHEN gh.result = 'Solved' AND gh.challenge_name LIKE 'Network%' THEN 1 END) as solved_count, 
                              MAX(gh.date) as last_active,
                              COALESCE(ux.level, 1) as user_level,
                              COALESCE(ux.xp, 0) as total_xp
                              FROM users u
                              LEFT JOIN game_history gh ON u.id = gh.user_id
                              LEFT JOIN user_xp ux ON u.id = ux.user_id
                              GROUP BY u.id
                              HAVING solved_count > 0
                              ORDER BY solved_count DESC, user_level DESC, total_xp DESC, last_active DESC
                              LIMIT :limit";
                    break;
                    
                case 'design':
                    // Design Dynamo leaderboard - based on UX challenges completed
                    $query = "SELECT u.username, COUNT(CASE WHEN gh.result = 'Solved' AND gh.challenge_name LIKE 'Design%' THEN 1 END) as solved_count, 
                              MAX(gh.date) as last_active,
                              COALESCE(ux.level, 1) as user_level,
                              COALESCE(ux.xp, 0) as total_xp
                              FROM users u
                              LEFT JOIN game_history gh ON u.id = gh.user_id
                              LEFT JOIN user_xp ux ON u.id = ux.user_id
                              GROUP BY u.id
                              HAVING solved_count > 0
                              ORDER BY solved_count DESC, user_level DESC, total_xp DESC, last_active DESC
                              LIMIT :limit";
                    break;
                    
                case 'overall':
                default:
                    // Overall leaderboard - based on all challenges
                    $query = "SELECT u.username, COUNT(CASE WHEN gh.result = 'Solved' THEN 1 END) as solved_count, 
                              COUNT(DISTINCT gh.challenge_name) as unique_challenges,
                              (SELECT COUNT(*) FROM badges b WHERE b.user_id = u.id) as badge_count,
                              MAX(gh.date) as last_active,
                              COALESCE(ux.level, 1) as user_level,
                              COALESCE(ux.xp, 0) as total_xp
                              FROM users u
                              LEFT JOIN game_history gh ON u.id = gh.user_id
                              LEFT JOIN user_xp ux ON u.id = ux.user_id
                              GROUP BY u.id
                              HAVING solved_count > 0
                              ORDER BY user_level DESC, total_xp DESC, solved_count DESC, badge_count DESC
                              LIMIT :limit";
            }
            
            if (!empty($query)) {
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("Leaderboard error: " . $e->getMessage());
            return [];
        }
    }

    // Get challenge stats for each game type
    function getChallengeStats($gameType) {
        global $pdo;
        
        try {
            $filter = '';
            switch ($gameType) {
                case 'codequest':
                    $filter = "WHERE challenge_name LIKE 'Coding%'";
                    break;
                case 'networking':
                    $filter = "WHERE challenge_name LIKE 'Network%'";
                    break;
                case 'design':
                    $filter = "WHERE challenge_name LIKE 'Design%'";
                    break;
                default:
                    // All challenges
                    $filter = '';
            }
            
            $query = "SELECT challenge_name, 
                      COUNT(*) as attempt_count,
                      SUM(CASE WHEN result = 'Solved' THEN 1 ELSE 0 END) as solved_count,
                      SUM(CASE WHEN result = 'Solved' THEN xp_earned ELSE 0 END) as total_xp_earned,
                      (SUM(CASE WHEN result = 'Solved' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as success_rate
                      FROM game_history
                      $filter
                      GROUP BY challenge_name
                      HAVING attempt_count > 0
                      ORDER BY attempt_count DESC
                      LIMIT 10";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Challenge stats error: " . $e->getMessage());
            return [];
        }
    }

    // Get top users by level
    $topLevelUsers = getTopUsersByLevel(10);

    // Get data for each leaderboard
    $overallLeaderboard = getLeaderboard('overall');
    $codeQuestLeaderboard = getLeaderboard('codequest');
    $networkingLeaderboard = getLeaderboard('networking');
    $designLeaderboard = getLeaderboard('design');

    // Get challenge stats based on active tab
    $challengeStats = getChallengeStats($activeTab);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Academy - Leaderboards</title>
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
        .badge {
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 6px;
        }

        /* Level badge styles */
        .level-badge {
            display: inline-block;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: white;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .level-title {
            display: inline-block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: 5px;
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
        <a href="../game/leaderboards" class="active">
            <i class="bi bi-trophy-fill"></i>
            Leaderboards
        </a>
        <a href="../game/friends">
            <i class="bi bi-people-fill"></i>
            Friends
        </a>
        
        <div class="game-category">Your Profile</div>
        <a href="../game/badges">
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
            <h1 class="mb-4">Leaderboards</h1>
            
            <!-- Top Level Users -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Top Players by Level</h5>
                </div>
                <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                    <th>Level</th>
                                    <th>Total XP</th>
                                    <th>Progress to Next Level</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php if (!empty($topLevelUsers)): ?>
                                    <?php foreach ($topLevelUsers as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="level-badge">Lvl <?php echo $user['level']; ?></span>
                                                <span class="level-title">
                                                    <?php echo function_exists('getLevelTitle') ? getLevelTitle($user['level']) : "Level {$user['level']}"; ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($user['xp']); ?> XP</td>
                                            <td>
                                                <div class="progress" style="height: 15px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $user['level_progress_percent']; ?>%;" 
                                                         aria-valuenow="<?php echo $user['level_progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $user['level_progress_percent']; ?>%
                                                    </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                        <td colspan="5" class="text-center">No players found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
            
            <!-- Game-specific Leaderboards -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'overall' ? 'active' : ''; ?>" href="?tab=overall">Overall</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'codequest' ? 'active' : ''; ?>" href="?tab=codequest">Code Quest</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'networking' ? 'active' : ''; ?>" href="?tab=networking">Network Nexus</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab === 'design' ? 'active' : ''; ?>" href="?tab=design">Design Dynamo</a>
                </li>
            </ul>
            
            <div class="row">
                <!-- Leaderboard Table -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                <div class="card-header">
                            <h5 class="card-title mb-0">
                                <?php 
                                    switch($activeTab) {
                                        case 'codequest':
                                            echo 'Code Quest Leaderboard';
                                            break;
                                        case 'networking':
                                            echo 'Network Nexus Leaderboard';
                                            break;
                                        case 'design':
                                            echo 'Design Dynamo Leaderboard';
                                            break;
                                        default:
                                            echo 'Overall Leaderboard';
                                    }
                                ?>
                            </h5>
                </div>
                        <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                            <th>Player</th>
                                            <th>Level</th>
                                            <?php if ($activeTab === 'overall'): ?>
                                                <th>Unique Challenges</th>
                                                <th>Badges</th>
                                            <?php else: ?>
                                                <th>Challenges Solved</th>
                                            <?php endif; ?>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                                        <?php 
                                            $leaderboard = [];
                                            switch($activeTab) {
                                                case 'codequest':
                                                    $leaderboard = $codeQuestLeaderboard;
                                                    break;
                                                case 'networking':
                                                    $leaderboard = $networkingLeaderboard;
                                                    break;
                                                case 'design':
                                                    $leaderboard = $designLeaderboard;
                                                    break;
                                                default:
                                                    $leaderboard = $overallLeaderboard;
                                            }
                                        ?>
                                        
                                        <?php if (!empty($leaderboard)): ?>
                                            <?php foreach ($leaderboard as $index => $entry): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($entry['username']); ?></td>
                                                    <td>
                                                        <span class="level-badge">Lvl <?php echo $entry['user_level']; ?></span>
                                                        <span class="level-title">
                                                            <?php echo function_exists('getLevelTitle') ? getLevelTitle($entry['user_level']) : "Level {$entry['user_level']}"; ?>
                                                        </span>
                                                        <div class="small text-muted"><?php echo number_format($entry['total_xp']); ?> XP</div>
                                        </td>
                                                    <?php if ($activeTab === 'overall'): ?>
                                                        <td><?php echo $entry['unique_challenges']; ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $entry['badge_count']; ?></span>
                                        </td>
                                                    <?php else: ?>
                                                        <td><?php echo $entry['solved_count']; ?></td>
                                                    <?php endif; ?>
                                                    <td><?php echo date('M d, Y', strtotime($entry['last_active'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                                <td colspan="5" class="text-center">No leaderboard data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
                                            </div>
                </div>
        
        <!-- Challenge Stats Section -->
                <div class="col-lg-4">
                    <div class="card">
            <div class="card-header">
                            <h5 class="card-title mb-0">Challenge Statistics</h5>
            </div>
                        <div class="card-body">
                            <?php if (!empty($challengeStats)): ?>
            <div class="table-responsive">
                                    <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Attempts</th>
                            <th>Success Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($challengeStats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['challenge_name']); ?></td>
                                    <td><?php echo $stat['attempt_count']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" 
                                                                     style="width: <?php echo round($stat['success_rate']); ?>%;" 
                                                     aria-valuenow="<?php echo round($stat['success_rate']); ?>" 
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                                            <span class="small"><?php echo round($stat['success_rate']); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted">No challenge statistics available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Your Level Card -->
                    <?php if (isset($userXPInfo) && !empty($userXPInfo) && isset($userXPInfo['current_level'])): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Your Current Level</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <h2 class="mb-0">Level <?php echo $userXPInfo['current_level']; ?></h2>
                                <div class="text-muted"><?php echo function_exists('getLevelTitle') ? getLevelTitle($userXPInfo['current_level']) : "Level {$userXPInfo['current_level']}"; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary p-2">Total XP: <?php echo number_format($userXPInfo['total_xp']); ?></span>
                            </div>
                            
                            <div class="progress mb-2" style="height: 15px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $userXPInfo['level_progress_percent']; ?>%;" 
                                     aria-valuenow="<?php echo $userXPInfo['level_progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $userXPInfo['level_progress_percent']; ?>%
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Current: <?php echo number_format($userXPInfo['current_level_xp']); ?> XP</span>
                                <span>Next Level: <?php echo number_format($userXPInfo['next_level_xp']); ?> XP</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>