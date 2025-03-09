<?php
require_once 'main.php';

if(!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$response = array('success' => false, 'message' => '');
$user_id = $_SESSION['user'];

// Update database to set profile picture to default
$defaultImage = 'default.jpg';
$query = "UPDATE user_details SET profile_picture = ? WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $defaultImage, $user_id);

if($stmt->execute()) {
    // Remove old profile picture if it exists
    $oldPicture = '../assets/img/users/' . $user_id . '.*';
    array_map('unlink', glob($oldPicture));

    // Update session
    $_SESSION['profile'] = BASE . 'assets/img/users/' . $defaultImage;
    $response['success'] = true;
    $response['message'] = 'Profile picture removed successfully';
} else {
    $response['message'] = 'Failed to remove profile picture';
    error_log("Profile picture removal failed: " . $stmt->error);
}

$stmt->close();
echo json_encode($response);
