<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$response = ['success' => false, 'message' => ''];

if (isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $userId = $_SESSION['user'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Upload failed: ' . $file['error'];
        echo json_encode($response);
        exit();
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $response['message'] = 'Invalid file type. Only JPG, JPEG, and PNG files are allowed.';
        echo json_encode($response);
        exit();
    }

    // Get file extension from mime type
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png'
    ];
    $extension = $extensions[$mimeType];

    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'File size must be less than 5MB';
        echo json_encode($response);
        exit();
    }

    // Create directory if it doesn't exist
    $uploadDir = '../assets/img/users/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Remove old profile picture if it exists and is not the default
    $oldPicture = $_SESSION['profile'] ?? '';
    if (!empty($oldPicture) && $oldPicture !== 'default.png' && file_exists($uploadDir . $oldPicture)) {
        unlink($uploadDir . $oldPicture);
    }

    // Set new filename using user ID
    $filename = $userId . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Update database
        global $conn;
        $query = "UPDATE user_details SET profile_picture = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $filename, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['profile'] = $filename;
            $response['success'] = true;
            $response['message'] = 'Profile picture updated successfully';
            $response['filename'] = $filename;
        } else {
            $response['message'] = 'Failed to update database';
            // Remove uploaded file if database update fails
            unlink($targetPath);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Failed to move uploaded file';
    }
} else {
    $response['message'] = 'No file uploaded';
}

echo json_encode($response);
