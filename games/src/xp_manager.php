<?php
// XP Manager - Handles user XP and level calculations
include_once 'config.php';

/**
 * Add XP to a user and update their level if needed
 * 
 * @param int $userId The user ID
 * @param int $xpAmount The amount of XP to add
 * @return array Information about the XP update and level up if applicable
 */
function addUserXP($userId, $xpAmount) {
    global $pdo;
    
    try {
        // Check if user already has an XP record
        $stmt = $pdo->prepare("SELECT id, xp, level FROM user_xp WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $userXP = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $oldLevel = 1;
        $newXp = $xpAmount;
        
        // If user has XP record, update it
        if ($userXP) {
            $oldLevel = $userXP['level'];
            $newXp = $userXP['xp'] + $xpAmount;
            
            $stmt = $pdo->prepare("UPDATE user_xp SET xp = :xp WHERE id = :id");
            $stmt->execute([
                ':xp' => $newXp,
                ':id' => $userXP['id']
            ]);
        } 
        // Otherwise create a new record
        else {
            $stmt = $pdo->prepare("INSERT INTO user_xp (user_id, xp, level) VALUES (:user_id, :xp, 1)");
            $stmt->execute([
                ':user_id' => $userId,
                ':xp' => $xpAmount
            ]);
        }
        
        // Check if user should level up
        $newLevel = calculateUserLevel($newXp);
        $leveledUp = false;
        $levelUpInfo = null;
        
        // If level changed, update it
        if ($newLevel > $oldLevel) {
            $stmt = $pdo->prepare("UPDATE user_xp SET level = :level WHERE user_id = :user_id");
            $stmt->execute([
                ':level' => $newLevel,
                ':user_id' => $userId
            ]);
            
            $leveledUp = true;
            
            // Get level up badge info
            $stmt = $pdo->prepare("SELECT badge_name, badge_image FROM level_definitions WHERE level = :level");
            $stmt->execute([':level' => $newLevel]);
            $levelUpInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Award level badge if it exists
            if ($levelUpInfo && !empty($levelUpInfo['badge_name'])) {
                awardLevelBadge($userId, $newLevel, $levelUpInfo['badge_name'], $levelUpInfo['badge_image']);
            }
        }
        
        return [
            'success' => true,
            'xp_earned' => $xpAmount,
            'total_xp' => $newXp,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'leveled_up' => $leveledUp,
            'level_info' => $levelUpInfo
        ];
    } catch (PDOException $e) {
        error_log("XP error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => "Could not update XP: " . $e->getMessage()
        ];
    }
}

/**
 * Calculate user level based on total XP
 * 
 * @param int $totalXP The total XP the user has
 * @return int The level the user should be based on XP
 */
function calculateUserLevel($totalXP) {
    global $pdo;
    
    try {
        // Get the highest level the user qualifies for
        $stmt = $pdo->prepare("SELECT MAX(level) as max_level FROM level_definitions WHERE xp_required <= :xp");
        $stmt->execute([':xp' => $totalXP]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['max_level'] ? $result['max_level'] : 1;
    } catch (PDOException $e) {
        error_log("Level calculation error: " . $e->getMessage());
        return 1; // Default to level 1 if there's an error
    }
}

/**
 * Get a user's current XP and level information
 * 
 * @param int $userId The user ID
 * @return array The user's XP and level information
 */
function getUserXPInfo($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_level_view
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        // If no record exists, return default values
        return [
            'user_id' => $userId,
            'total_xp' => 0,
            'current_level' => 1,
            'current_level_xp' => 0,
            'next_level_xp' => 100,
            'level_progress_percent' => 0
        ];
    } catch (PDOException $e) {
        error_log("XP info error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get the title/badge name for a specific level
 * 
 * @param int $level The level number
 * @return string The title for the level
 */
function getLevelTitle($level) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT badge_name FROM level_definitions WHERE level = :level");
        $stmt->execute([':level' => $level]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['badge_name']) {
            return $result['badge_name'];
        }
        
        // Default level titles if not found in database
        $defaultTitles = [
            1 => 'Newbie',
            2 => 'Junior Coder',
            3 => 'Debug Detective'
        ];
        
        return isset($defaultTitles[$level]) ? $defaultTitles[$level] : "Level $level";
    } catch (PDOException $e) {
        error_log("Level title error: " . $e->getMessage());
        return "Level $level"; // Default to "Level X" if there's an error
    }
}

/**
 * Get all level titles from the database
 * 
 * @return array Associative array of level numbers to level titles
 */
function getAllLevelTitles() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT level, badge_name FROM level_definitions ORDER BY level ASC");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $titles = [];
        foreach ($results as $row) {
            $titles[$row['level']] = $row['badge_name'];
        }
        
        return $titles;
    } catch (PDOException $e) {
        error_log("Get all level titles error: " . $e->getMessage());
        
        // Return default titles if database query fails
        return [
            1 => 'Newbie',
            2 => 'Junior Coder',
            3 => 'Debug Detective',
            4 => 'Script Sorcerer',
            5 => 'Code Crusader'
        ];
    }
}

/**
 * Award a level badge to the user
 * 
 * @param int $userId The user ID
 * @param int $level The level reached
 * @param string $badgeName The name of the badge
 * @param string $badgeImage Path to the badge image
 * @return bool Whether the badge was awarded successfully
 */
function awardLevelBadge($userId, $level, $badgeName, $badgeImage) {
    global $pdo;
    
    try {
        // Check if user already has this badge
        $stmt = $pdo->prepare("SELECT id FROM badges WHERE user_id = :user_id AND badge_name = :badge_name");
        $stmt->execute([
            ':user_id' => $userId,
            ':badge_name' => $badgeName
        ]);
        
        if (!$stmt->fetch()) {
            // Add the badge
            $stmt = $pdo->prepare("INSERT INTO badges (user_id, badge_name, badge_image) VALUES (:user_id, :badge_name, :badge_image)");
            $stmt->execute([
                ':user_id' => $userId,
                ':badge_name' => $badgeName,
                ':badge_image' => $badgeImage
            ]);
            
            return true;
        }
        
        return false; // Badge already exists
    } catch (PDOException $e) {
        error_log("Badge award error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get XP value for a specific challenge ID
 * 
 * @param int $challengeId The challenge ID
 * @return int The XP value for the challenge
 */
function getChallengeXP($challengeId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT xp_value FROM challenge_xp WHERE challenge_id = :challenge_id");
        $stmt->execute([':challenge_id' => $challengeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['xp_value'] : 10; // Default to 10 XP if not found
    } catch (PDOException $e) {
        error_log("Challenge XP error: " . $e->getMessage());
        return 10; // Default to 10 XP if there's an error
    }
}

/**
 * Get the top users ranked by level and XP
 * 
 * @param int $limit The number of users to return
 * @return array List of top users by level and XP
 */
function getTopUsersByLevel($limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.username, 
                COALESCE(ux.level, 1) as level, 
                COALESCE(ux.xp, 0) as xp,
                ulv.level_progress_percent
            FROM users u
            LEFT JOIN user_xp ux ON u.id = ux.user_id
            LEFT JOIN user_level_view ulv ON u.id = ulv.user_id
            ORDER BY level DESC, xp DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Top users error: " . $e->getMessage());
        return [];
    }
}
?> 