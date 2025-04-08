<?php
require_once __DIR__.'/../main.php'; 
require_once BACKEND.'unified_file_management.php';

// Ensure user is logged in and is an ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize file manager
$fileManager = new UnifiedFileManagement();

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false];
    
    switch ($action) {
        case 'delete_file':
            $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
            $user_id = $_SESSION['user'];
            
            if (!$file_id) {
                $response = ['success' => false, 'message' => 'File ID is required'];
                break;
            }
            
            try {
                $result = $fileManager->deleteFile($file_id, $user_id);
                $response = [
                    'success' => true, 
                    'message' => 'File deleted successfully'
                ];
                
                // Log the action
                $log_message = "Admin (ID: {$user_id}) deleted file ID: {$file_id}";
                log_error($log_message, "admin");
                
            } catch (Exception $e) {
                $response = [
                    'success' => false, 
                    'message' => $e->getMessage()
                ];
            }
            break;
            
        case 'get_file_details':
            $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
            
            if (!$file_id) {
                $response = ['success' => false, 'message' => 'File ID is required'];
                break;
            }
            
            try {
                $file_details = $fileManager->getFileDetails($file_id);
                if ($file_details) {
                    $response = [
                        'success' => true,
                        'data' => $file_details
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'File not found'
                    ];
                }
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
