<?php    
    include 'src/config.php';
    include 'src/challenges.php';
    global $pdo;

// Fetch game history from the database
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Use session user ID if available
$stmt = $pdo->prepare("SELECT date, result, challenge_name FROM game_history WHERE user_id = :user_id ORDER BY date DESC LIMIT 10");
$stmt->execute([':user_id' => $userId]);
$gameHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch badges from the session
$badges = isset($_SESSION['badges']) ? $_SESSION['badges'] : [];
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
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Gaming Academy</h2>
        <a href="index.php" class="active">
            <i class="bi bi-house-door-fill"></i>
            Dashboard
        </a>
        
        <div class="game-category">Coding Games</div>
        <a href="codequest">
            <i class="bi bi-code-square"></i>
            Code Quest
        </a>
        <a href="network-nexus">
            <i class="bi bi-diagram-3-fill"></i>
            Network Nexus
        </a>
        <a href="design-dynamo">
            <i class="bi bi-palette-fill"></i>
            Design Dynamo
        </a>
        
        <div class="game-category">Community</div>
        <a href="leaderboards">
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
            <img src="https://via.placeholder.com/40" alt="User Avatar">
            <div class="user-details">
                <div class="user-name">Player</div>
                <div class="user-role">Level 1 Coder</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-message">
            <h1>Welcome to Gaming Academy!</h1>
            <p class="lead">Challenge yourself with our educational games designed to improve your coding, networking, and design skills.</p>
        </div>
        
        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-controller"></i></div>
                <div class="stat-value"><?php echo count($gameHistory); ?></div>
                <div class="stat-label">Games Played</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-award"></i></div>
                <div class="stat-value"><?php echo count($badges); ?></div>
                <div class="stat-label">Badges Earned</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value">
                    <?php 
                        $solvedCount = 0;
                        foreach ($gameHistory as $history) {
                            if ($history['result'] === 'Solved') $solvedCount++;
                        }
                        echo $solvedCount;
                    ?>
                </div>
                <div class="stat-label">Challenges Solved</div>
            </div>
        </div>

        <!-- Display Game Message -->
        <?php if ($gameMessage): ?>
            <div class="alert alert-info mb-4">
                <?php echo htmlspecialchars($gameMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Featured Games -->
        <h2 class="mb-4">Featured Games</h2>
        <div class="game-cards">
            <div class="game-card">
                <img src="https://via.placeholder.com/300x180?text=Code+Quest" class="card-img-top" alt="Code Quest">
                <div class="card-body">
                    <h5 class="card-title">Code Quest</h5>
                    <p class="card-text">Solve coding challenges to improve your programming skills. From beginner to advanced levels!</p>
                    <a href="src/ide.php" class="btn btn-primary">Start Coding</a>
                </div>
            </div>
            
            <div class="game-card">
                <img src="https://via.placeholder.com/300x180?text=Network+Nexus" class="card-img-top" alt="Network Nexus">
                <div class="card-body">
                    <h5 class="card-title">Network Nexus</h5>
                    <p class="card-text">Build and configure networks to learn fundamental networking concepts in a fun way.</p>
                    <a href="src/game_networking.php" class="btn btn-primary">Start Networking</a>
                </div>
            </div>
            
            <div class="game-card">
                <img src="https://via.placeholder.com/300x180?text=Design+Dynamo" class="card-img-top" alt="Design Dynamo">
                <div class="card-body">
                    <h5 class="card-title">Design Dynamo</h5>
                    <p class="card-text">Create beautiful user interfaces and learn UX/UI principles through interactive challenges.</p>
                    <a href="src/game_ux.php" class="btn btn-primary">Start Designing</a>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <!-- Game History -->
            <div class="col-lg-6">
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

            <!-- Badges -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Your Badges</span>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($badges)): ?>
                            <div class="row g-3">
                                <?php foreach ($badges as $badge): ?>
                                    <div class="col-4 text-center">
                                        <img src="<?php echo htmlspecialchars($badge['image'] ?? 'https://via.placeholder.com/80?text=Badge'); ?>" 
                                             alt="Badge" class="img-fluid rounded-circle mb-2" style="width: 80px; height: 80px;">
                                        <div class="small"><?php echo htmlspecialchars($badge['name'] ?? 'Badge'); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-award" style="font-size: 3rem;"></i>
                                <p class="mt-3">No badges earned yet. Complete challenges to earn rewards!</p>
                                <a href="src/ide.php" class="btn btn-outline-primary">Start a Challenge</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>