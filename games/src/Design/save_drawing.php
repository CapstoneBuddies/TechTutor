<?php
include 'config.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create uploads directory if it doesn't exist
    if (!file_exists(IMG.'uploads')) {
        mkdir(IMG.'uploads', 0777, true);
    }
    
    $imageData = $_POST['image'];
    $challengeId = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : null;
    
    // Strip the data URI scheme prefix
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $decodedData = base64_decode($imageData);

    // Generate unique filename
    $fileName = 'drawing_' . time() . '.png';
    $filePath = 'uploads/' . $fileName;
    
    // Save the file
    if (file_put_contents($filePath, $decodedData)) {
        $response = ['status' => 'success', 'file' => $fileName];
        
        // If a challenge ID was provided, save to database as a submission
        if ($challengeId) {
            try {
                // Get user ID (placeholder - in a real app, get from session)
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
                
                $stmt = $pdo->prepare("INSERT INTO design_submissions (user_id, challenge_id, image_path, status) VALUES (?, ?, ?, 'submitted')");
                $stmt->execute([$userId, $challengeId, $filePath]);
                
                $response['submission_id'] = $pdo->lastInsertId();
                $response['message'] = 'Your design has been submitted successfully!';
            } catch (PDOException $e) {
                // Log error but don't expose database errors to user
                error_log('Database error: ' . $e->getMessage());
                $response['db_status'] = 'error';
                $response['message'] = 'Your design was saved, but there was an issue with the submission. Try again later.';
            }
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save the file']);
    }
}
?>