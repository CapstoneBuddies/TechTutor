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
    $userId = isset($_SESSION['user']) ? $_SESSION['user'] : 1;
    
    // Get user's XP info
    $userXPInfo = getUserXPInfo($userId);
    
    // Get user info
    $username = isset($_SESSION['name']) ? $_SESSION['name'] : '';
    
    // Handle friend requests
    $message = '';
    $messageType = '';
    
    // Handle sending friend request
    if (isset($_POST['add_friend'])) {
        $friendEmail = trim($_POST['friend_email']);
        
        if (!empty($friendEmail)) {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute([':email' => $friendEmail]);
                $friendUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($friendUser) {
                    $friendId = $friendUser['id'];
                    
                    // Check if already friends or request pending
                    $checkStmt = $pdo->prepare("SELECT * FROM friends 
                                               WHERE (user_id = :userId AND friend_id = :friendId) 
                                               OR (user_id = :friendId AND friend_id = :userId)");
                    $checkStmt->execute([':userId' => $userId, ':friendId' => $friendId]);
                    $existingRelation = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingRelation) {
                        if ($existingRelation['status'] == 'pending') {
                            $message = 'Friend request already pending.';
                            $messageType = 'warning';
                        } else {
                            $message = 'You are already friends with this user.';
                            $messageType = 'info';
                        }
                    } else {
                        // Send friend request
                        $insertStmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status, created_at) 
                                                    VALUES (:userId, :friendId, 'pending', NOW())");
                        $insertStmt->execute([':userId' => $userId, ':friendId' => $friendId]);
                        
                        $message = 'Friend request sent successfully!';
                        $messageType = 'success';
                    }
                } else {
                    $message = 'User with this email does not exist.';
                    $messageType = 'danger';
                }
            } catch (PDOException $e) {
                $message = 'Error processing request: ' . $e->getMessage();
                $messageType = 'danger';
            }
        } else {
            $message = 'Please enter a valid email address.';
            $messageType = 'warning';
        }
    }
    
    // Handle accepting/rejecting friend requests
    if (isset($_POST['accept_request']) || isset($_POST['reject_request'])) {
        $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        
        if ($requestId > 0) {
            try {
                if (isset($_POST['accept_request'])) {
                    // Accept friend request
                    $updateStmt = $pdo->prepare("UPDATE friends SET status = 'accepted', updated_at = NOW() WHERE id = :requestId AND friend_id = :userId");
                    $updateStmt->execute([':requestId' => $requestId, ':userId' => $userId]);
                    
                    if ($updateStmt->rowCount() > 0) {
                        $message = 'Friend request accepted!';
                        $messageType = 'success';
                    }
                } else {
                    // Reject friend request
                    $deleteStmt = $pdo->prepare("DELETE FROM friends WHERE id = :requestId AND friend_id = :userId");
                    $deleteStmt->execute([':requestId' => $requestId, ':userId' => $userId]);
                    
                    if ($deleteStmt->rowCount() > 0) {
                        $message = 'Friend request rejected.';
                        $messageType = 'info';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Error processing request: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    // Get pending friend requests
    try {
        $pendingStmt = $pdo->prepare("
            SELECT f.id, f.user_id, f.created_at, u.username 
            FROM friends f
            JOIN users u ON f.user_id = u.id
            WHERE f.friend_id = :userId AND f.status = 'pending'
            ORDER BY f.created_at DESC
        ");
        $pendingStmt->execute([':userId' => $userId]);
        $pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $pendingRequests = [];
    }
    
    // Get accepted friends
    try {
        $friendsStmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, ux.level, ux.xp
            FROM friends f
            JOIN users u ON (f.user_id = u.id OR f.friend_id = u.id)
            LEFT JOIN user_xp ux ON u.id = ux.user_id
            WHERE (f.user_id = :userId OR f.friend_id = :userId) 
            AND f.status = 'accepted'
            AND u.id != :userId
            ORDER BY u.username ASC
        ");
        $friendsStmt->execute([':userId' => $userId]);
        $friends = $friendsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $friends = [];
    }
    
    // Get friend suggestions (users who are not yet friends)
    try {
        $suggestionsStmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, ux.level, ux.xp
            FROM users u
            LEFT JOIN user_xp ux ON u.id = ux.user_id
            WHERE u.id != :userId
            AND u.id NOT IN (
                SELECT IF(f.user_id = :userId, f.friend_id, f.user_id)
                FROM friends f
                WHERE (f.user_id = :userId OR f.friend_id = :userId)
            )
            LIMIT 5
        ");
        $suggestionsStmt->execute([':userId' => $userId]);
        $suggestions = $suggestionsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $suggestions = [];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Academy - Friends</title>
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

        /* Friend styles */
        .friend-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background-color: white;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .friend-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .friend-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
            margin-right: 15px;
            font-weight: 500;
            overflow: hidden;
        }
        .friend-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .friend-info {
            flex: 1;
        }
        .friend-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 3px;
        }
        .friend-level {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        .level-badge {
            display: inline-block;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: white;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 8px;
        }
        .friend-actions {
            display: flex;
            gap: 10px;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 0.85rem;
        }
        .btn-play {
            background-color: #28a745;
            color: white;
        }
        .btn-play:hover {
            background-color: #218838;
            color: white;
        }
        .btn-challenge {
            background-color: #fd7e14;
            color: white;
        }
        .btn-challenge:hover {
            background-color: #e56c06;
            color: white;
        }
        .request-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .request-info {
            display: flex;
            align-items: center;
        }
        .request-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #6c757d;
            margin-right: 15px;
            font-weight: 500;
        }
        .request-name {
            font-weight: 600;
        }
        .request-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .request-actions {
            display: flex;
            gap: 10px;
        }
        .search-container {
            margin-bottom: 30px;
        }
        .suggestions-heading {
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 1.3rem;
            color: #495057;
            font-weight: 600;
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
        <a href="../game/friends" class="active">
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
            <h1 class="mb-4">Friends</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- Add Friend -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Add Friend</span>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" class="search-container">
                                <div class="input-group">
                                    <input type="email" name="friend_email" class="form-control" placeholder="Enter friend's email address" required>
                                    <button type="submit" name="add_friend" class="btn btn-primary">
                                        <i class="bi bi-person-plus"></i> Send Request
                                    </button>
                                </div>
                            </form>
                            
                            <p class="text-muted small">Connect with your friends to challenge them, play together, and compare your progress!</p>
                        </div>
                    </div>
                    
                    <!-- Friends List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Your Friends</span>
                            <span class="badge bg-primary"><?php echo count($friends); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (count($friends) > 0): ?>
                                <div class="row">
                                    <?php foreach ($friends as $friend): ?>
                                        <div class="col-md-12">
                                            <div class="friend-card">
                                                <div class="friend-avatar">
                                                    <?php 
                                                        $initials = strtoupper(substr($friend['username'], 0, 1));
                                                        echo $initials;
                                                    ?>
                                                </div>
                                                <div class="friend-info">
                                                    <div class="friend-name"><?php echo htmlspecialchars($friend['username']); ?></div>
                                                    <div class="friend-level">
                                                        <span class="level-badge">Lvl <?php echo $friend['level'] ?? 1; ?></span>
                                                        <?php echo function_exists('getLevelTitle') && isset($friend['level']) ? getLevelTitle($friend['level']) : "Rookie"; ?>
                                                    </div>
                                                </div>
                                                <div class="friend-actions">
                                                    <a href="#" class="btn btn-sm btn-challenge">
                                                        <i class="bi bi-lightning"></i> Challenge
                                                    </a>
                                                    <a href="#" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-chat"></i> Message
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="mt-3 text-muted">You haven't added any friends yet.</p>
                                    <p class="text-muted">Use the form above to connect with other players!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Friend Suggestions -->
                    <?php if (count($suggestions) > 0): ?>
                        <h3 class="suggestions-heading"><i class="bi bi-person-plus"></i> People You May Know</h3>
                        <div class="row">
                            <?php foreach ($suggestions as $suggestion): ?>
                                <div class="col-md-6">
                                    <div class="friend-card">
                                        <div class="friend-avatar">
                                            <?php 
                                                $initials = strtoupper(substr($suggestion['username'], 0, 1));
                                                echo $initials;
                                            ?>
                                        </div>
                                        <div class="friend-info">
                                            <div class="friend-name"><?php echo htmlspecialchars($suggestion['username']); ?></div>
                                            <div class="friend-level">
                                                <span class="level-badge">Lvl <?php echo $suggestion['level'] ?? 1; ?></span>
                                                <?php echo function_exists('getLevelTitle') && isset($suggestion['level']) ? getLevelTitle($suggestion['level']) : "Rookie"; ?>
                                            </div>
                                        </div>
                                        <form method="post" action="">
                                            <input type="hidden" name="friend_email" value="<?php echo $suggestion['email']; ?>">
                                            <button type="submit" name="add_friend" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-person-plus"></i> Add
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <!-- Friend Requests -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Friend Requests</span>
                            <span class="badge bg-danger"><?php echo count($pendingRequests); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (count($pendingRequests) > 0): ?>
                                <?php foreach ($pendingRequests as $request): ?>
                                    <div class="request-item">
                                        <div class="request-info">
                                            <div class="request-avatar">
                                                <?php 
                                                    $initials = strtoupper(substr($request['username'], 0, 1));
                                                    echo $initials;
                                                ?>
                                            </div>
                                            <div>
                                                <div class="request-name"><?php echo htmlspecialchars($request['username']); ?></div>
                                                <div class="request-date"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="request-actions">
                                            <form method="post" action="" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="accept_request" class="btn btn-sm btn-success">
                                                    Accept
                                                </button>
                                            </form>
                                            <form method="post" action="" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="reject_request" class="btn btn-sm btn-outline-danger">
                                                    Decline
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No pending friend requests</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Game Benefits -->
                    <div class="card">
                        <div class="card-header">
                            <span>Why Connect with Friends?</span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="bi bi-lightning-fill text-warning me-2"></i>
                                    <strong>Challenge friends</strong> to coding competitions
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-trophy-fill text-success me-2"></i>
                                    <strong>Earn bonus XP</strong> when playing with friends
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-people-fill text-primary me-2"></i>
                                    <strong>Form teams</strong> for multiplayer challenges
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-graph-up text-info me-2"></i>
                                    <strong>Compare progress</strong> and achievements
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-award-fill text-danger me-2"></i>
                                    <strong>Unlock exclusive badges</strong> for social activities
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 