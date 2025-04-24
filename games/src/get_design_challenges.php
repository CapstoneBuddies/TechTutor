<?php
include 'config.php';
global $pdo;

header('Content-Type: application/json');

try {
    // Get all design challenges
    $stmt = $pdo->query("SELECT * FROM design_challenges ORDER BY difficulty, title");
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON criteria back to arrays
    foreach ($challenges as &$challenge) {
        if (isset($challenge['criteria'])) {
            $challenge['criteria'] = json_decode($challenge['criteria'], true);
        }
    }
    echo json_encode(['status' => 'success', 'challenges' => $challenges]);
} catch (PDOException $e) {
    // Log error but don't expose database errors
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Unable to fetch challenges']);
}
?> 