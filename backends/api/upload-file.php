<?php
require_once '../backends/config.php';
require_once '../backends/db.php';
require_once '../backends/techkid_management.php';

// Verify user is logged in and is a TechKid
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    if (!isset($_POST['courseId'])) {
        throw new Exception('Course ID is required');
    }

    $file = $_FILES['file'];
    $courseId = intval($_POST['courseId']);
    $studentId = $_SESSION['user'];
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $fileType = getFileType(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate file type
    if (!$fileType) {
        throw new Exception('File type not allowed');
    }

    // Check storage quota
    if (!updateStudentStorage($studentId, $fileSize)) {
        throw new Exception('Storage quota exceeded');
    }

    // Generate unique filename
    $uniqueName = uniqid() . '_' . $fileName;
    $targetPath = ROOT_PATH . '/' . STUDENT_FILES . $uniqueName;

    // Move file to storage
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Rollback storage update if file move fails
        updateStudentStorage($studentId, -$fileSize);
        throw new Exception('Failed to save file');
    }

    // Save file record to database
    $db = getConnection();
    $stmt = $db->prepare("INSERT INTO student_files (student_id, course_id, name, type, size, url) 
                         VALUES (?, ?, ?, ?, ?, ?)");
    
    $relativeUrl = STUDENT_FILES . $uniqueName;
    if (!$stmt->execute([$studentId, $courseId, $fileName, $fileType, $fileSize, $relativeUrl])) {
        // Rollback file upload and storage update if database insert fails
        unlink($targetPath);
        updateStudentStorage($studentId, -$fileSize);
        throw new Exception('Failed to save file record');
    }

    // Get updated storage info
    $storageInfo = getStudentFiles($studentId);

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file' => [
            'id' => $db->lastInsertId(),
            'name' => $fileName,
            'type' => $fileType,
            'size' => formatFileSize($fileSize),
            'url' => $relativeUrl
        ],
        'storage' => $storageInfo['storage']
    ]);

} catch (Exception $e) {
    log_error("File upload error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
