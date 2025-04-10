<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'game_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()])); 
}

// Get the badge data from the request
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];
$imagePath = $data['image']; // Assuming the image is being passed as a file path
$date = $data['date'];
$userId = 1; // Replace with the actual user ID if you have a user system

// Check if image exists
if (file_exists($imagePath)) {
    // Read the image as binary data
    $imageData = file_get_contents($imagePath);
    
    // Save the badge in the database
    $stmt = $pdo->prepare("INSERT INTO badges (user_id, badge_name, badge_image, earned_at) 
                           VALUES (:user_id, :badge_name, :badge_image, :earned_at)");
    $stmt->execute([
        ':user_id' => $userId,
        ':badge_name' => $name, // badge name
        ':badge_image' => $imageData, // badge image as binary data
        ':earned_at' => $date // date when earned
    ]);

    // Return a success response
    echo json_encode([
        'status' => 'success',
        'badge' => [
            'name' => $name,
            'image' => $imagePath,
            'date' => $date
        ]
    ]);
} else {
    // If the image file doesn't exist
    echo json_encode(['error' => 'Image file not found.']);
}
?>
