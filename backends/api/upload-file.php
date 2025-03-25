<?php
require_once '../../backends/main.php';
require_once BACKEND.'file_management.php';

// Verify user is logged in and is a TechKid
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Initialize file management
    $fileManager = new FileManagement();

    log_error(print_r($_FILES,true));

    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['file'];
    $studentId = $_SESSION['user'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
    $classId = isset($_POST['class_id']) ? intval($_POST['class_id']) : null;

    // Upload file using FileManagement class
    $fileId = $fileManager->uploadFile($file, $studentId, $classId, $description, $requestId);

    // Get updated storage info
    $storageInfo = $fileManager->getStorageInfo($studentId);

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'storage' => $storageInfo
    ]);

} catch (Exception $e) {
    log_error("File upload error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
