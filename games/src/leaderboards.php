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
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/boostrap-icons/bootstrap-icons.css">
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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        .nav-tabs {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-tabs .nav-link:hover {
            color: #495057;
            border-bottom-color: rgba(13, 110, 253, 0.3);
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
        .nav-tabs .nav-link i {
            margin-right: 5px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            font-weight: 500;
            background-color: rgba(0, 0, 0, 0.02);
            color: #6c757d;
            border-bottom-width: 1px;
        }
        .table td {
            vertical-align: middle;
            padding: 1rem 1.5rem;
        }
        .rank-number {
            font-weight: 700;
            text-align: center;
            width: 50px;
            display: inline-block;
        }
        .rank-1, .rank-2, .rank-3 {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
        }
        .rank-1 {
            background-color: gold;
            color: #333;
        }
        .rank-2 {
            background-color: silver;
            color: #333;
        }
        .rank-3 {
            background-color: #cd7f32; /* bronze */
        }
        .user-cell {
            display: flex;
            align-items: center;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .badge-pill {
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: rgba(0, 0, 0, 0.05);
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .back-button:hover {
            color: #0d6efd;
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
            display: block;
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Gaming Academy</h2>
        <a href="<?php echo BASE; ?>">
            <i class="bi bi-house-door-fill"></i>
            Back To Main Dashboard
        </a>
        
        <div class="game-category">Coding Games</div>
        <a href="game/codequest">
            <i class="bi bi-code-square"></i>
            Code Quest
        </a>
        <a href="game/network-nexus">
            <i class="bi bi-diagram-3-fill"></i>
            Network Nexus
        </a>
        <a href="game/design-dynamo">
            <i class="bi bi-palette-fill"></i>
            Design Dynamo
        </a>
        
        <div class="game-category">Community</div>
        <a href="game/leaderboards" class="active">
            <i class="bi bi-trophy-fill"></i>
            Leaderboards
        </a>
        <a href="#">
            <i class="bi bi-people-fill"></i>
            Friends
        </a>
        
        <div class="game-category">Your Profile</div>
        <a href="#">
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
        <div class="page-header">
            <div>
                <a href="<?php echo BASE.'game'; ?>" class="back-button mb-2">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <h1>Leaderboards</h1>
            </div>
        </div>
        
        <!-- Tabs for different leaderboards -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'overall' ? 'active' : ''; ?>" href="?tab=overall">
                    <i class="bi bi-star-fill"></i> Overall
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'codequest' ? 'active' : ''; ?>" href="?tab=codequest">
                    <i class="bi bi-code-square"></i> Code Quest
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'networking' ? 'active' : ''; ?>" href="?tab=networking">
                    <i class="bi bi-diagram-3-fill"></i> Network Nexus
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'design' ? 'active' : ''; ?>" href="?tab=design">
                    <i class="bi bi-palette-fill"></i> Design Dynamo
                </a>
            </li>
        </ul>
        
        <!-- Leaderboard Content based on active tab -->
        <?php if ($activeTab === 'overall'): ?>
            <!-- Overall Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-trophy-fill text-warning me-2"></i> Top Players - All Games
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                <th>Level</th>
                                <th>Challenges Solved</th>
                                <th>Badges</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($overallLeaderboard)): ?>
                                <?php foreach ($overallLeaderboard as $index => $player): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <div class="rank-<?php echo $index + 1; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://via.placeholder.com/40/<?php echo dechex(crc32($player['username'])); ?>?text=<?php echo substr($player['username'], 0, 1); ?>" 
                                                     alt="User" class="user-avatar">
                                                <div>
                                                    <span><?php echo htmlspecialchars($player['username']); ?></span>
                                                    <span class="level-title"><?php echo getLevelTitle($player['user_level']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="level-badge">Lvl <?php echo $player['user_level']; ?></span>
                                            <span class="text-muted small">(<?php echo number_format($player['total_xp']); ?> XP)</span>
                                        </td>
                                        <td><?php echo $player['solved_count']; ?></td>
                                        <td><?php echo $player['badge_count'] ?? 0; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($player['last_active'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No data available yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($activeTab === 'codequest'): ?>
            <!-- Code Quest Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-code-square text-primary me-2"></i> Top Coders - Code Quest
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Coder</th>
                                <th>Level</th>
                                <th>Challenges Solved</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($codeQuestLeaderboard)): ?>
                                <?php foreach ($codeQuestLeaderboard as $index => $player): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <div class="rank-<?php echo $index + 1; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://via.placeholder.com/40/<?php echo dechex(crc32($player['username'])); ?>?text=<?php echo substr($player['username'], 0, 1); ?>" 
                                                     alt="User" class="user-avatar">
                                                <div>
                                                    <span><?php echo htmlspecialchars($player['username']); ?></span>
                                                    <span class="level-title"><?php echo getLevelTitle($player['user_level']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="level-badge">Lvl <?php echo $player['user_level']; ?></span>
                                            <span class="text-muted small">(<?php echo number_format($player['total_xp']); ?> XP)</span>
                                        </td>
                                        <td><?php echo $player['solved_count']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($player['last_active'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No data available yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($activeTab === 'networking'): ?>
            <!-- Network Nexus Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-diagram-3-fill text-success me-2"></i> Top Network Engineers - Network Nexus
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Engineer</th>
                                <th>Level</th>
                                <th>Networks Built</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($networkingLeaderboard)): ?>
                                <?php foreach ($networkingLeaderboard as $index => $player): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <div class="rank-<?php echo $index + 1; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://via.placeholder.com/40/<?php echo dechex(crc32($player['username'])); ?>?text=<?php echo substr($player['username'], 0, 1); ?>" 
                                                     alt="User" class="user-avatar">
                                                <div>
                                                    <span><?php echo htmlspecialchars($player['username']); ?></span>
                                                    <span class="level-title"><?php echo getLevelTitle($player['user_level']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="level-badge">Lvl <?php echo $player['user_level']; ?></span>
                                            <span class="text-muted small">(<?php echo number_format($player['total_xp']); ?> XP)</span>
                                        </td>
                                        <td><?php echo $player['solved_count']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($player['last_active'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No data available yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Design Dynamo Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-palette-fill text-danger me-2"></i> Top Designers - Design Dynamo
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Designer</th>
                                <th>Level</th>
                                <th>Designs Completed</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($designLeaderboard)): ?>
                                <?php foreach ($designLeaderboard as $index => $player): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <div class="rank-<?php echo $index + 1; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="rank-number"><?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="https://via.placeholder.com/40/<?php echo dechex(crc32($player['username'])); ?>?text=<?php echo substr($player['username'], 0, 1); ?>" 
                                                     alt="User" class="user-avatar">
                                                <div>
                                                    <span><?php echo htmlspecialchars($player['username']); ?></span>
                                                    <span class="level-title"><?php echo getLevelTitle($player['user_level']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="level-badge">Lvl <?php echo $player['user_level']; ?></span>
                                            <span class="text-muted small">(<?php echo number_format($player['total_xp']); ?> XP)</span>
                                        </td>
                                        <td><?php echo $player['solved_count']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($player['last_active'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No data available yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Challenge Stats Section -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-bar-chart-fill me-2"></i> Challenge Statistics
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Attempts</th>
                            <th>Solved</th>
                            <th>Success Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($challengeStats)): ?>
                            <?php foreach ($challengeStats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['challenge_name']); ?></td>
                                    <td><?php echo $stat['attempt_count']; ?></td>
                                    <td><?php echo $stat['solved_count']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo min(100, round($stat['success_rate'])); ?>%" 
                                                     aria-valuenow="<?php echo round($stat['success_rate']); ?>" 
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span><?php echo round($stat['success_rate']); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No challenge statistics available yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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
                                <th>Title</th>
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
                                        </td>
                                        <td><?php echo getLevelTitle($user['level']); ?></td>
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
                                    <td colspan="6" class="text-center">No players found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE; ?>assets/vendor/jQuery/jquery-3.6.4.min.js"></script>
</body>
</html>