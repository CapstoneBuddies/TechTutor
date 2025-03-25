<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../main.php';
require_once __DIR__ . '/../management/file_management.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if file_id is provided
if (!isset($_POST['file_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'File ID is required']);
    exit;
}

try {
    $fileManagement = new FileManagement();
    $fileId = $_POST['file_id'];
    
    // Verify file ownership
    $sql = "SELECT user_id FROM file_management WHERE file_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['user_id'] != $_SESSION['user']) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to delete this file']);
            exit;
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    // Delete the file
    if ($fileManagement->deleteFile($fileId,$_SESSION['user'])) {
        echo json_encode([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete file');
    }
} catch (Exception $e) {
    log_error($e->getMessage(), 'database');
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete file']);
} 