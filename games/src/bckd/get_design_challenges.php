<?php
include 'config.php';
include 'challenges.php';
global $pdo;

// Only set JSON header when accessed directly, not when included
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    header('Content-Type: application/json');
}

function getDesignChallenges() {
    global $pdo;
    
    try {
        // Get all design challenges from the design_challenges table
        // Only use fields that actually exist in the table
        $stmt = $pdo->query("
            SELECT 
                id as challenge_id, 
                'UI' as challenge_type, 
                title, 
                description,
                difficulty, 
                example_image,
                criteria,
                created_at
            FROM `design_challenges` 
            ORDER BY difficulty, title
        ");
        
        $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add default values for fields expected by the frontend
        foreach ($challenges as &$challenge) {
            // Set default values for fields not in the database
            $challenge['xp_value'] = 100; // Default XP value
            
            // Parse JSON fields to arrays where needed
            if (isset($challenge['criteria']) && !is_array($challenge['criteria'])) {
                $challenge['criteria'] = json_decode($challenge['criteria'], true) ?: [];
            }
            
            // Add other expected fields with defaults
            $challenge['requirements'] = [];
            $challenge['time_limit'] = 600; // 10 minutes default
            $challenge['max_score'] = 100;
            $challenge['is_active'] = 1;
        }
        
        return ['status' => 'success', 'challenges' => $challenges];
    } catch (PDOException $e) {
        // Log error using the standard log_error function if available, otherwise just log to error_log
        if (function_exists('log_error')) {
            log_error("Error fetching design challenges: " . $e->getMessage());
        } else {
            error_log("Error fetching design challenges: " . $e->getMessage());
        }
        return ['status' => 'error', 'message' => 'Unable to fetch challenges: ' . $e->getMessage()];
    }
}

// Direct access handling
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    echo json_encode(getDesignChallenges());
}
?>