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

try {
    $fileManagement = new FileManagement();
    $fixed = $fileManagement->fixFilePermissions($_SESSION['user']);
    
    echo json_encode([
        'success' => true,
        'message' => "Fixed permissions for {$fixed} files",
        'files_fixed' => $fixed
    ]);
} catch (Exception $e) {
    log_error($e->getMessage(), 'database');
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fix permissions']);
} 