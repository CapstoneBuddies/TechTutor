<?php    
    include 'src/config.php';
    include 'src/challenges.php';
    include 'src/xp_manager.php';
    global $pdo;

// Check if the user is online
if(!isset($_SESSION['user'])) {
    $_SESSION['Invalid Action'];
    header("Location: login");
    exit; // Add exit after redirect for security
}

// Check if user record exists for gamification
try {
    $query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $pdo->prepare($query);
    $check_stmt->execute([':email' => $_SESSION['email']]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$result) {
        // Create new user record
        $add_user = $pdo->prepare("INSERT INTO users(username, email) VALUES(:username, :email)");
        $username = $_SESSION['first_name'].' '.$_SESSION['last_name'];
        $add_user->execute([':username'=>$username, ':email'=>$_SESSION['email'] ]);
        
        // Initialize XP record for the new user
        $userId = $pdo->lastInsertId();
        $add_xp = $pdo->prepare("INSERT INTO user_xp(user_id, xp, level) VALUES(:user_id, 0, 1)");
        $add_xp->execute([':user_id' => $userId]);
    }
    else {
        $_SESSION['game'] = $result['id'];
    }
} catch (PDOException $e) {
    log_error("Error checking/creating user: " . $e->getMessage());
}

// Fetch game history from the database using game_user_progress instead of game_history
$userId = $_SESSION['game'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            gup.completed_at as date, 
            CASE 
                WHEN gup.score > 0 THEN 'Solved' 
                ELSE 'In Progress' 
            END as result, 
            gc.name as challenge_name 
        FROM game_user_progress gup
        JOIN game_challenges gc ON gup.challenge_id = gc.challenge_id
        WHERE gup.user_id = :user_id 
        ORDER BY gup.completed_at DESC 
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $userId]);
    $gameHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // No need to map status to result as we're directly calculating it in the query
} catch (PDOException $e) {
    log_error("Error fetching game history: " . $e->getMessage());
    $gameHistory = []; // Initialize as empty array on error
}

// Get user XP and level information
$userXPInfo = getUserXPInfo($userId);

// Fetch badges from the db
try {
    $q = "SELECT * FROM badges WHERE user_id = :userId";
    $getBadges = $pdo->prepare($q);
    $getBadges->execute([':userId' => $_SESSION['user']]);
    $result = $getBadges->fetchAll(PDO::FETCH_ASSOC);
    $badges = isset($result) ? $result : [];
} catch (PDOException $e) {
    log_error("Error fetching badges: " . $e->getMessage());
    $badges = [];
}

$gameMessage = isset($_SESSION['game_message']) ? $_SESSION['game_message'] : null;

// Clear the game message after displaying it
unset($_SESSION['game_message']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Gaming Academy</title>
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
        .welcome-message {
            background-color: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
        .list-group-item {
            border: none;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: background-color 0.2s;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        .game-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .game-card {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .game-card .card-img-top {
            height: 180px;
            object-fit: cover;
        }
        .game-card .card-body {
            padding: 20px;
        }
        .game-card .card-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        .game-card .card-text {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .game-card .btn {
            width: 100%;
            padding: 10px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .stat-card .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #0d6efd;
        }
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* XP Progress Bar Styling */
        .xp-progress-container {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .xp-level {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .xp-level-number {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
        }
        
        .xp-level-title {
            color: #6c757d;
        }
        
        .xp-progress {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .xp-bar {
            height: 100%;
            background: linear-gradient(90deg, #4481eb, #04befe);
            border-radius: 5px;
            transition: width 1s ease-in-out;
            position: relative;
        }
        
        .xp-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%
            );
            background-size: 15px 15px;
            animation: xp-bar-animation 1s linear infinite;
        }
        
        @keyframes xp-bar-animation {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 15px 0;
            }
        }
        
        .xp-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Gaming Academy</h2>
        <a href="home" class="active">
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
        <a href="game/leaderboards">
            <i class="bi bi-trophy-fill"></i>
            Leaderboards
        </a>
        <a href="game/friends">
            <i class="bi bi-people-fill"></i>
            Friends
        </a>
        
        <div class="game-category">Your Profile</div>
        <a href="game/badges">
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
        <h1>Welcome to the Gaming Academy</h1>
        
        <div class="welcome-message">
            <h4>Ready to learn while playing?</h4>
            <p>Test your skills, earn badges, and track your progress through interactive coding games.</p>
        </div>
        
        <!-- XP and Level Progress -->
        <div class="xp-progress-container">
            <div class="xp-level">
                <div>
                    <span class="xp-level-number">Level <?php echo $userXPInfo['current_level']; ?></span>
                    <span class="ms-2 xp-level-title"><?php 
                        // Get level title from database instead of hardcoded array
                        echo getLevelTitle($userXPInfo['current_level']);
                    ?></span>
                </div>
                <div>
                    <span class="badge bg-primary">Total XP: <?php echo $userXPInfo['total_xp']; ?></span>
                </div>
            </div>
            <div class="xp-progress">
                <div class="xp-bar" style="width: <?php echo $userXPInfo['level_progress_percent']; ?>%"></div>
            </div>
            <div class="xp-text">
                <span><?php echo $userXPInfo['current_level_xp']; ?> XP</span>
                <span>Next Level: <?php echo $userXPInfo['next_level_xp']; ?> XP</span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Game Cards -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Available Games</span>
                    </div>
                    <div class="card-body">
                        <div class="game-cards">
                            <div class="game-card">
                                <img src="<?php echo GAME_IMG.'code-quest.png'; ?>" class="card-img-top" alt="Code Quest">
                                <div class="card-body">
                                    <h5 class="card-title">Code Quest</h5>
                                    <p class="card-text">Solve coding challenges to improve your programming skills. From beginner to advanced levels!</p>
                                    <a href="game/codequest" class="btn btn-primary">Start Coding</a>
                                </div>
                            </div>
                            
                            <div class="game-card">
                                <img src="<?php echo GAME_IMG.'network-nexus.png'; ?>" class="card-img-top" alt="Network Nexus">
                                <div class="card-body">
                                    <h5 class="card-title">Network Nexus</h5>
                                    <p class="card-text">Build and configure networks to learn fundamental networking concepts in a fun way.</p>
                                    <a href="game/network-nexus" class="btn btn-primary">Start Networking</a>
                                </div>
                            </div>
                            
                            <div class="game-card">
                                <img src="<?php echo GAME_IMG.'design-dynamo.png'; ?>" class="card-img-top" alt="Design Dynamo">
                                <div class="card-body">
                                    <h5 class="card-title">Design Dynamo</h5>
                                    <p class="card-text">Create beautiful user interfaces and learn UX/UI principles through interactive challenges.</p>
                                    <a href="game/design-dynamo" class="btn btn-primary">Start Designing</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game History -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Activity</span>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php 
                            if (!empty($gameHistory)): 
                                foreach ($gameHistory as $history): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($history['challenge_name'] ?? 'Challenge'); ?></strong>
                                            <div class="text-muted small"><?php echo htmlspecialchars($history['date']); ?></div>
                                        </div>
                                        <span class="badge <?php echo ($history['result'] === 'Solved') ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($history['result']); ?>
                                        </span>
                                    </li>
                                <?php endforeach; 
                            else: ?>
                                <li class="list-group-item text-center text-muted">No game history available yet. Start playing!</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>