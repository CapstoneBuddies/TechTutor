<?php
/**
 * Update Challenge Script
 * 
 * This file handles updating an existing challenge in the database
 */

// Include necessary files
include 'config.php';
include 'challenges.php';

// Check if user is logged in with admin privileges
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'TECHGURU' && $_SESSION['role'] !== 'ADMIN')) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Set response header
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate challenge data
$challengeId = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : 0;
$challengeName = isset($_POST['challenge_name']) ? trim($_POST['challenge_name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$starterCode = isset($_POST['starter_code']) ? trim($_POST['starter_code']) : '';
$expectedOutput = isset($_POST['expected_output']) ? trim($_POST['expected_output']) : '';
$difficultyId = isset($_POST['difficulty']) ? intval($_POST['difficulty']) : 1;
$xpValue = isset($_POST['xp_value']) ? intval($_POST['xp_value']) : 100;

// Basic validation
if ($challengeId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid challenge ID'
    ]);
    exit;
}

if (empty($challengeName) || empty($description) || empty($expectedOutput)) {
    echo json_encode([
        'success' => false,
        'message' => 'All required fields must be filled'
    ]);
    exit;
}

// Validate length of description
if (strlen($description) > 500) {
    echo json_encode([
        'success' => false,
        'message' => 'Description cannot exceed 500 characters'
    ]);
    exit;
}

// Check that challenge exists
$existing = getChallengeById($challengeId);
if (!$existing) {
    echo json_encode([
        'success' => false,
        'message' => 'Challenge not found'
    ]);
    exit;
}

// Create content JSON
$content = json_encode([
    'description' => $description,
    'starter_code' => $starterCode,
    'expected_output' => $expectedOutput
]);

try {
    global $pdo;
    
    // Update the challenge
    $stmt = $pdo->prepare("
        UPDATE `game_challenges` 
        SET 
            `name` = :name,
            `content` = :content,
            `difficulty_id` = :difficulty_id,
            `xp_value` = :xp_value,
            `updated_at` = NOW()
        WHERE `challenge_id` = :challenge_id
    ");
    
    $stmt->bindParam(':name', $challengeName);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':difficulty_id', $difficultyId, PDO::PARAM_INT);
    $stmt->bindParam(':xp_value', $xpValue, PDO::PARAM_INT);
    $stmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
    
    $stmt->execute();
    
    // Check if update was successful
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Challenge updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No changes made to the challenge'
        ]);
    }
} catch (PDOException $e) {
    // Log error
    log_error("Error updating challenge: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 