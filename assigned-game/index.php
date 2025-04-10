<?php 
session_start();

// Database connection
$host = 'localhost';
$dbname = 'game_db'; // Replace with your database name
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch game history from the database
$userId = 1; // Replace with the actual user ID if you have a user system
$stmt = $pdo->prepare("SELECT date, result FROM game_history WHERE user_id = :user_id ORDER BY date DESC");
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
    <title>Game Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles2.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #1e1e1e;
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 20px;
            border-right: 1px solid #444;
        }
        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #fff;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background-color: #444;
        }
        .sidebar .active {
            background-color: #0d6efd;
        }
        .sidebar i {
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .main-content h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .badge {
            font-size: 0.875rem;
        }
        .list-group-item {
            border: none;
            padding: 15px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Game Dashboard</h2>
        <a href="#" class="active">
            <i class="bi bi-house-door-fill"></i>
            Home
        </a>
       
        <a href="#">
            <i class="bi bi-clock-history"></i>
            Game History
        </a>
        <a href="#">
            <i class="bi bi-award-fill"></i>
            Badges
        </a>
        
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Welcome to the Game!</h1>
        <p>Are you ready to test your skills? Click the button below to start the game!</p>
        <form method="post" action="/Game/assigned-game/src/ide.php">
            <button type="submit" name="start_game" class="btn btn-primary btn-lg">Start Game</button>
        </form>

        <!-- Display Game Message -->
        <?php if ($gameMessage): ?>
            <div class="alert alert-info mt-4">
                <?php echo htmlspecialchars($gameMessage); ?>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <!-- Game History -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Game History</h5>
                        <ul class="list-group">
                            <?php 
                            if (!empty($gameHistory)): 
                                $recentHistory = array_slice($gameHistory, 0, 10); // Limit to 10 recent results
                                foreach ($recentHistory as $history): ?>
                                    <li class="list-group-item">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($history['result']); ?></span>
                                        <span class="text-muted ms-2"><?php echo htmlspecialchars($history['date']); ?></span>
                                    </li>
                                <?php endforeach; 
                            else: ?>
                                <li class="list-group-item text-muted">No game history available.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Badges -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Badges</h5>
                        <ul class="list-group">
                            <?php if (!empty($badges)): ?>
                                <?php foreach ($badges as $badge): ?>
                                    <li class="list-group-item d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($badge['image']); ?>" alt="Badge" style="width: 50px; height: 50px; margin-right: 10px;">

                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No badges earned yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>