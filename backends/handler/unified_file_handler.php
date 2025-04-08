<?php
require_once __DIR__ . '/../main.php';

// Set header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Log for debugging
log_error("Unified File Handler: $method, $action", "info");

// Load FileManagement class
require_once BACKEND . 'unified_file_management.php';
$fileManager = new UnifiedFileManagement();

log_error($action);

try {
    // Handle different actions
    switch ($action) {
        case 'upload': 
            handleFileUpload($fileManager);
            break;
            
        case 'delete':
            handleFileDeletion($fileManager);
            break;
            
        case 'create-folder':
            handleFolderCreation($fileManager);
            break;
            
        case 'rename-folder':
            handleFolderRename($fileManager);
            break;
            
        case 'delete-folder':
            handleFolderDeletion($fileManager);
            break;
            
        case 'file-request':
            handleFileRequest($fileManager);
            break;
            
        case 'submit-file-request':
            handleRequestSubmission($fileManager);
            break;
            
        case 'get-files':
            handleGetFiles($fileManager);
            break;
            
        case 'add-permission':
            handleAddPermission($fileManager);
            break;
            
        case 'remove-permission':
            handleRemovePermission($fileManager);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    log_error("Error in unified file handler: " . $e->getMessage());
    http_response_code(400);
    $response['message'] = $e->getMessage();
    echo json_encode($response);
}

/**
 * Handle file upload for both TechGuru and TechKid
 */
function handleFileUpload($fileManager) {
    global $response;
    
    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['file'];
    $userId = $_SESSION['user'];
    $role = $_SESSION['role'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $classId = isset($_POST['class_id']) ? intval($_POST['class_id']) : null;
    $folderId = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
    $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'private';
    $filePurpose = 'personal';
    
    // Determine file purpose based on role and other parameters
    if ($role === 'TECHGURU' && $classId) {
        $filePurpose = 'class_material';
    } elseif ($role === 'TECHKID' && isset($_POST['request_id'])) {
        $filePurpose = 'submission';
        $requestId = intval($_POST['request_id']);
    } elseif ($classId) {
        $filePurpose = 'assignment';
    }
    
    // Upload file
    $fileId = $fileManager->uploadFile(
        $file, 
        $userId, 
        $classId, 
        $folderId, 
        $description, 
        $visibility, 
        $filePurpose
    );
    
    // Handle request submission if applicable
    if ($filePurpose === 'submission' && isset($requestId)) {
        $fileManager->submitFileToRequest($requestId, $fileId);
    }
    
    // Get storage info for response
    $storageInfo = $fileManager->getStorageInfo($userId);
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'File uploaded successfully',
        'file_id' => $fileId,
        'storage' => $storageInfo
    ];
    
    echo json_encode($response);
}

/**
 * Handle file deletion
 */
function handleFileDeletion($fileManager) {
    global $response;
    
    // Get file ID from POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = isset($input['file_id']) ? intval($input['file_id']) : 
              (isset($_POST['file_id']) ? intval($_POST['file_id']) : null);
    
    if (!$fileId) {
        throw new Exception('File ID is required');
    }
    
    // Delete file
    if ($fileManager->deleteFile($fileId, $_SESSION['user'])) {
        $response = [
            'success' => true,
            'message' => 'File deleted successfully'
        ];
    } else {
        throw new Exception('Failed to delete file');
    }
    
    echo json_encode($response);
}

/**
 * Handle folder creation
 */
function handleFolderCreation($fileManager) {
    global $response;
    
    // Get folder data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $classId = isset($input['class_id']) ? intval($input['class_id']) : 
               (isset($_POST['class_id']) ? intval($_POST['class_id']) : null);
    
    $folderName = isset($input['folder_name']) ? trim($input['folder_name']) : 
                  (isset($_POST['folder_name']) ? trim($_POST['folder_name']) : null);
    
    $parentFolderId = isset($input['parent_folder_id']) ? intval($input['parent_folder_id']) : 
                      (isset($_POST['parent_folder_id']) ? intval($_POST['parent_folder_id']) : null);
    
    $visibility = isset($input['visibility']) ? $input['visibility'] : 
                 (isset($_POST['visibility']) ? $_POST['visibility'] : 'private');
    
    if (!$folderName) {
        throw new Exception('Folder name is required');
    }
    
    // Create folder
    $folderId = $fileManager->createFolder( 
        $_SESSION['user'],
        $folderName,
        $classId,
        $parentFolderId,
        $visibility
    );
    
    $response = [
        'success' => true,
        'message' => 'Folder created successfully',
        'folder_id' => $folderId
    ];
    
    echo json_encode($response);
}

/**
 * Handle folder rename
 */
function handleFolderRename($fileManager) {
    global $response;
    
    // Get folder data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $folderId = isset($input['folder_id']) ? intval($input['folder_id']) : 
                (isset($_POST['folder_id']) ? intval($_POST['folder_id']) : null);
    
    $folderName = isset($input['folder_name']) ? trim($input['folder_name']) : 
                  (isset($_POST['folder_name']) ? trim($_POST['folder_name']) : null);
    
    if (!$folderId || !$folderName) {
        throw new Exception('Folder ID and name are required');
    }
    
    // Rename folder
    if ($fileManager->renameFolder($folderId, $folderName, $_SESSION['user'])) {
        $response = [
            'success' => true,
            'message' => 'Folder renamed successfully'
        ];
    } else {
        throw new Exception('Failed to rename folder');
    }
    
    echo json_encode($response);
}

/**
 * Handle folder deletion
 */
function handleFolderDeletion($fileManager) {
    global $response;
    
    // Get folder ID
    $input = json_decode(file_get_contents('php://input'), true);
    $folderId = isset($input['folder_id']) ? intval($input['folder_id']) : 
                (isset($_POST['folder_id']) ? intval($_POST['folder_id']) : null);
    
    if (!$folderId) {
        throw new Exception('Folder ID is required');
    }
    
    // Delete folder
    if ($fileManager->deleteFolder($folderId, $_SESSION['user'])) {
        $response = [
            'success' => true,
            'message' => 'Folder deleted successfully'
        ];
    } else {
        throw new Exception('Failed to delete folder');
    }
    
    echo json_encode($response);
}

/**
 * Handle file request creation (for TechGuru to request files from TechKid)
 */
function handleFileRequest($fileManager) {
    global $response;
    
    // Ensure user is a TechGuru
    if ($_SESSION['role'] !== 'TECHGURU') {
        throw new Exception('Only tutors can create file requests');
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $classId = isset($input['class_id']) ? intval($input['class_id']) : null;
    $recipientId = isset($input['recipient_id']) ? intval($input['recipient_id']) : null;
    $requestTitle = isset($input['request_title']) ? trim($input['request_title']) : null;
    $description = isset($input['description']) ? trim($input['description']) : '';
    $dueDate = isset($input['due_date']) ? $input['due_date'] : null;
    
    if (!$classId || !$recipientId || !$requestTitle || !$dueDate) {
        throw new Exception('Missing required fields');
    }
    
    // Create file request
    $requestId = $fileManager->createFileRequest(
        $classId,
        $_SESSION['user'],
        $recipientId,
        $requestTitle,
        $description,
        $dueDate
    );
    
    $response = [
        'success' => true,
        'message' => 'File request created successfully',
        'request_id' => $requestId
    ];
    
    echo json_encode($response);
}

/**
 * Handle file request submission (for TechKid to submit files to TechGuru)
 */
function handleRequestSubmission($fileManager) {
    global $response;
    
    // Ensure user is a TechKid
    if ($_SESSION['role'] !== 'TECHKID') {
        throw new Exception('Only students can submit to file requests');
    }
    
    // Get submission data
    $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
    $fileId = isset($_POST['file_id']) ? intval($_POST['file_id']) : null;
    
    if (!$requestId) {
        throw new Exception('Request ID is required');
    }
    
    // If no file ID is provided, it should have been uploaded in this request
    if (!$fileId && isset($_FILES['file'])) {
        // Process will continue in handleFileUpload which already handles request submissions
        return;
    } else if (!$fileId) {
        throw new Exception('No file provided for submission');
    }
    
    // Submit file to request
    if ($fileManager->submitFileToRequest($requestId, $fileId, $_SESSION['user'])) {
        $response = [
            'success' => true,
            'message' => 'File submitted successfully'
        ];
    } else {
        throw new Exception('Failed to submit file');
    }
    
    echo json_encode($response);
}

/**
 * Handle file retrieval (get files based on different criteria)
 */
function handleGetFiles($fileManager) {
    global $response;
    
    // Get parameters
    $userId = $_SESSION['user'];
    $classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
    $folderId = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : null;
    $filePurpose = isset($_GET['purpose']) ? $_GET['purpose'] : null;
    $isPersonal = isset($_GET['personal']) ? filter_var($_GET['personal'], FILTER_VALIDATE_BOOLEAN) : false;
    
    // Get files based on parameters
    $files = [];
    
    if ($isPersonal) {
        $files = $fileManager->getPersonalFiles($userId);
    } else if ($classId && $folderId) {
        $files = $fileManager->getFolderFiles($classId, $folderId);
    } else if ($classId) {
        $files = $fileManager->getClassFiles($classId, $filePurpose);
    } else {
        $files = $fileManager->getUserAccessibleFiles($userId);
    }
    
    $response = [
        'success' => true,
        'files' => $files
    ];
    
    echo json_encode($response);
}

/**
 * Handle adding permissions to a file or folder
 */
function handleAddPermission($fileManager) {
    global $response;
    
    // Get parameters
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fileId = isset($input['file_id']) ? intval($input['file_id']) : null;
    $folderId = isset($input['folder_id']) ? intval($input['folder_id']) : null;
    $userId = isset($input['user_id']) ? intval($input['user_id']) : null;
    $accessType = isset($input['access_type']) ? $input['access_type'] : 'view';
    
    if ((!$fileId && !$folderId) || !$userId) {
        throw new Exception('File/Folder ID and User ID are required');
    }
    
    // Add permission
    if ($fileManager->addPermission($fileId, $folderId, $userId, $accessType, $_SESSION['user'])) {
        $response = [
            'success' => true,
            'message' => 'Permission added successfully'
        ];
    } else {
        throw new Exception('Failed to add permission');
    }
    
    echo json_encode($response);
}

/**
 * Handle removing permissions from a file or folder
 */
function handleRemovePermission($fileManager) {
    global $response;
    
    // Get parameters
    $input = json_decode(file_get_contents('php://input'), true);
    
    $permissionId = isset($input['permission_id']) ? intval($input['permission_id']) : null;
    
    if (!$permissionId) {
        throw new Exception('Permission ID is required');
    }
    
    // Remove permission
    if ($fileManager->removePermission($permissionId, $_SESSION['user'])) {
        $response = [
            'success' => true,
            'message' => 'Permission removed successfully'
        ];
    } else {
        throw new Exception('Failed to remove permission');
    }
    
    echo json_encode($response);
} 