<?php
// filepath: /php-game-project/php-game-project/components/header.php

// Get user ID from session
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Get user XP info if the function exists
$userXP = function_exists('getUserXPInfo') ? getUserXPInfo($userId) : null;
?>
<div class="sidebar">
    <h2><i class="bi bi-controller me-2"></i> Gaming Academy</h2>
    
    <a href="<?php echo BASE.'game';?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="bi bi-house-door"></i> Dashboard
    </a>
    
    <div class="game-category">Coding Games</div>
    <a href="ide.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ide.php' ? 'active' : ''; ?>">
        <i class="bi bi-code-slash"></i> Code Quest
    </a>
    
    <div class="game-category">Networking Games</div>
    <a href="game_networking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'game_networking.php' ? 'active' : ''; ?>">
        <i class="bi bi-diagram-3"></i> Network Nexus
    </a>
    
    <div class="game-category">Design Games</div>
    <a href="game_ux.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'game_ux.php' ? 'active' : ''; ?>">
        <i class="bi bi-palette"></i> Design Dynamo
    </a>
    
    <div class="game-category">Community</div>
    <a href="leaderboards.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leaderboards.php' ? 'active' : ''; ?>">
        <i class="bi bi-trophy"></i> Leaderboards
    </a>
    <a href="badges-achievements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'badges-achievements.php' ? 'active' : ''; ?>">
        <i class="bi bi-award"></i> Badges & Achievements
    </a>
    
    <?php if ($userXP): ?>
    <div class="game-category">Your Level</div>
    <div class="p-3 bg-dark rounded mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-white">Level <?php echo $userXP['current_level']; ?></span>
            <span class="badge bg-primary"><?php echo $userXP['total_xp']; ?> XP</span>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" 
                 style="width: <?php echo $userXP['level_progress_percent']; ?>%" 
                 aria-valuenow="<?php echo $userXP['level_progress_percent']; ?>" 
                 aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="mt-1 text-center small text-light">
            <?php echo function_exists('getLevelTitle') ? getLevelTitle($userXP['current_level']) : "Level {$userXP['current_level']}"; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="user-info">
        <img src="https://ui-avatars.com/api/?name=Student&background=random" alt="User">
        <div class="user-details">
            <div class="user-name">Student</div>
            <div class="user-role">Gamer</div>
        </div>
    </div>
</div>
