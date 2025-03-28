<?php
require_once __DIR__ . '/../main.php';
require_once BACKEND . 'class_management.php';
require_once BACKEND . 'management/unified_file_management.php';

// Initialize file management
$fileManager = new UnifiedFileManagement();

// Check authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        // File upload
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Validate request
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file'];
            $classId = isset($_POST['class_id']) ? intval($_POST['class_id']) : null;
            $folderId = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
            $categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'private';
            $filePurpose = isset($_POST['file_purpose']) ? $_POST['file_purpose'] : 'personal';
            
            // Optional tags
            $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
            
            // Upload file
            $result = $fileManager->uploadFile(
                $file,
                $_SESSION['user'],
                $description,
                $visibility,
                $filePurpose,
                $classId,
                $folderId,
                $categoryId
            );
            
            if ($result && !empty($tags)) {
                // Add tags to the file
                $fileManager->addFileTags($result, $_SESSION['user'], $tags);
            }
            
            echo json_encode([
                'success' => (bool)$result, 
                'message' => $result ? 'File uploaded successfully' : 'Failed to upload file',
                'file_id' => $result
            ]);
            break;
            
        // File deletion
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['file_id'])) {
                throw new Exception('Missing file ID');
            }
            
            $fileId = intval($data['file_id']);
            
            // Delete file
            $result = $fileManager->deleteFile($fileId, $_SESSION['user']);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'File deleted successfully' : 'Failed to delete file'
            ]);
            break;
            
        // Create folder
        case 'create-folder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['folder_name'])) {
                throw new Exception('Missing folder name');
            }
            
            $folderName = trim($data['folder_name']);
            $classId = isset($data['class_id']) ? intval($data['class_id']) : null;
            $parentFolderId = isset($data['parent_folder_id']) ? intval($data['parent_folder_id']) : null;
            
            // Create folder
            $result = $fileManager->createFolder(
                $folderName, 
                $_SESSION['user'], 
                $classId,
                $parentFolderId
            );
            
            echo json_encode([
                'success' => (bool)$result, 
                'message' => $result ? 'Folder created successfully' : 'Failed to create folder',
                'folder_id' => $result
            ]);
            break;
            
        // Rename folder
        case 'rename-folder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['folder_id']) || !isset($data['folder_name'])) {
                throw new Exception('Missing required fields');
            }
            
            $folderId = intval($data['folder_id']);
            $folderName = trim($data['folder_name']);
            
            // Rename folder
            $result = $fileManager->renameFolder($folderId, $_SESSION['user'], $folderName);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Folder renamed successfully' : 'Failed to rename folder'
            ]);
            break;
            
        // Delete folder
        case 'delete-folder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['folder_id'])) {
                throw new Exception('Missing folder ID');
            }
            
            $folderId = intval($data['folder_id']);
            
            // Delete folder
            $result = $fileManager->deleteFolder($folderId, $_SESSION['user']);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Folder deleted successfully' : 'Failed to delete folder'
            ]);
            break;
            
        // Get files for a class
        case 'get-class-files':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            if (!isset($_GET['class_id'])) {
                throw new Exception('Missing class ID');
            }
            
            $classId = intval($_GET['class_id']);
            $filePurpose = isset($_GET['file_purpose']) ? $_GET['file_purpose'] : null;
            
            // Get class files
            $files = $fileManager->getClassFiles($classId, $filePurpose);
            
            echo json_encode([
                'success' => true, 
                'files' => $files
            ]);
            break;
            
        // Get files in a folder
        case 'get-folder-files':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            if (!isset($_GET['folder_id']) || !isset($_GET['class_id'])) {
                throw new Exception('Missing folder ID or class ID');
            }
            
            $folderId = intval($_GET['folder_id']);
            $classId = intval($_GET['class_id']);
            
            // Get folder files
            $files = $fileManager->getFolderFiles($classId, $folderId);
            
            echo json_encode([
                'success' => true, 
                'files' => $files
            ]);
            break;
            
        // Get personal files
        case 'get-personal-files':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            // Get personal files
            $files = $fileManager->getPersonalFiles($_SESSION['user']);
            
            echo json_encode([
                'success' => true, 
                'files' => $files
            ]);
            break;
            
        // Get all accessible files
        case 'get-accessible-files':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            // Get accessible files
            $files = $fileManager->getUserAccessibleFiles($_SESSION['user']);
            
            echo json_encode([
                'success' => true, 
                'files' => $files
            ]);
            break;
            
        // Search files
        case 'search-files':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
            $filters = [];
            
            if (isset($_GET['category_id'])) {
                $filters['category_id'] = intval($_GET['category_id']);
            }
            
            if (isset($_GET['file_type'])) {
                $filters['file_type'] = $_GET['file_type'];
            }
            
            if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
                $filters['date_from'] = $_GET['date_from'];
                $filters['date_to'] = $_GET['date_to'];
            }
            
            // Search files
            $files = $fileManager->searchFiles($_SESSION['user'], $searchTerm, $filters);
            
            echo json_encode([
                'success' => true, 
                'files' => $files
            ]);
            break;
            
        // Add tags to a file
        case 'add-tags':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['file_id']) || !isset($data['tags'])) {
                throw new Exception('Missing required fields');
            }
            
            $fileId = intval($data['file_id']);
            $tags = is_array($data['tags']) ? $data['tags'] : [$data['tags']];
            
            // Add tags
            $result = $fileManager->addFileTags($fileId, $_SESSION['user'], $tags);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Tags added successfully' : 'Failed to add tags'
            ]);
            break;
            
        // Remove a tag from a file
        case 'remove-tag':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['file_id']) || !isset($data['tag_id'])) {
                throw new Exception('Missing required fields');
            }
            
            $fileId = intval($data['file_id']);
            $tagId = intval($data['tag_id']);
            
            // Remove tag
            $result = $fileManager->removeFileTag($fileId, $tagId, $_SESSION['user']);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Tag removed successfully' : 'Failed to remove tag'
            ]);
            break;
            
        // Get tags for a file
        case 'get-file-tags':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            if (!isset($_GET['file_id'])) {
                throw new Exception('Missing file ID');
            }
            
            $fileId = intval($_GET['file_id']);
            
            // Get tags
            $tags = $fileManager->getFileTags($fileId);
            
            echo json_encode([
                'success' => true, 
                'tags' => $tags
            ]);
            break;
            
        // Find files by tag
        case 'find-by-tag':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            if (!isset($_GET['tag'])) {
                throw new Exception('Missing tag');
            }
            
            $tagName = $_GET['tag'];
            
            // Find files by tag
            $files = $fileManager->findFilesByTag($_SESSION['user'], $tagName);
            
            echo json_encode([
                'success' => true, 
                'files' => $files
            ]);
            break;
            
        // Get popular tags
        case 'get-popular-tags':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            // Get popular tags
            $tags = $fileManager->getPopularTags($limit);
            
            echo json_encode([
                'success' => true, 
                'tags' => $tags
            ]);
            break;
            
        // Get storage info
        case 'get-storage-info':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            // Get storage info
            $storageInfo = $fileManager->getStorageInfo($_SESSION['user']);
            
            echo json_encode([
                'success' => true, 
                'storage_info' => $storageInfo
            ]);
            break;
            
        // Create a file request
        case 'create-file-request':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['class_id']) || !isset($data['recipient_id']) || !isset($data['request_title']) || !isset($data['due_date'])) {
                throw new Exception('Missing required fields');
            }
            
            $classId = intval($data['class_id']);
            $recipientId = intval($data['recipient_id']);
            $requestTitle = $data['request_title'];
            $description = isset($data['description']) ? $data['description'] : '';
            $dueDate = $data['due_date'];
            
            // Create file request
            $result = $fileManager->createFileRequest(
                $classId,
                $_SESSION['user'],
                $recipientId,
                $requestTitle,
                $description,
                $dueDate
            );
            
            echo json_encode([
                'success' => (bool)$result, 
                'message' => $result ? 'File request created successfully' : 'Failed to create file request',
                'request_id' => $result
            ]);
            break;
            
        // Submit file to a request
        case 'submit-file-to-request':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['request_id']) || !isset($data['file_id'])) {
                throw new Exception('Missing required fields');
            }
            
            $requestId = intval($data['request_id']);
            $fileId = intval($data['file_id']);
            
            // Submit file to request
            $result = $fileManager->submitFileToRequest($requestId, $fileId, $_SESSION['user']);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'File submitted successfully' : 'Failed to submit file'
            ]);
            break;
            
        // Add permission to a file
        case 'add-permission':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if ((!isset($data['file_id']) && !isset($data['folder_id'])) || !isset($data['user_id'])) {
                throw new Exception('Missing required fields');
            }
            
            $fileId = isset($data['file_id']) ? intval($data['file_id']) : null;
            $folderId = isset($data['folder_id']) ? intval($data['folder_id']) : null;
            $userId = intval($data['user_id']);
            $accessType = isset($data['access_type']) ? $data['access_type'] : 'view';
            
            // Add permission
            $result = $fileManager->addPermission($fileId, $folderId, $userId, $accessType, $_SESSION['user']);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Permission added successfully' : 'Failed to add permission'
            ]);
            break;
            
        // Remove permission
        case 'remove-permission':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['permission_id'])) {
                throw new Exception('Missing permission ID');
            }
            
            $permissionId = intval($data['permission_id']);
            
            // Remove permission
            $result = $fileManager->removePermission($permissionId, $_SESSION['user']);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Permission removed successfully' : 'Failed to remove permission'
            ]);
            break;
            
        // Update file information
        case 'update-file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['file_id'])) {
                throw new Exception('Missing file ID');
            }
            
            $fileId = intval($data['file_id']);
            $updateData = [];
            
            if (isset($data['file_name'])) {
                $updateData['file_name'] = $data['file_name'];
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            
            if (isset($data['category_id'])) {
                $updateData['category_id'] = intval($data['category_id']);
            }
            
            if (isset($data['visibility'])) {
                $updateData['visibility'] = $data['visibility'];
            }
            
            // Update file
            $result = $fileManager->updateFile($fileId, $_SESSION['user'], $updateData);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'File updated successfully' : 'Failed to update file'
            ]);
            break;
            
        // Get file categories
        case 'get-categories':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method');
            }
            
            // Get categories
            $categories = $fileManager->getFileCategories();
            
            echo json_encode([
                'success' => true, 
                'categories' => $categories
            ]);
            break;
            
        // Add file category
        case 'add-category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['category_name'])) {
                throw new Exception('Missing category name');
            }
            
            $categoryName = $data['category_name'];
            $description = isset($data['description']) ? $data['description'] : '';
            
            // Add category
            $result = $fileManager->addFileCategory($categoryName, $description);
            
            echo json_encode([
                'success' => (bool)$result, 
                'message' => $result ? 'Category added successfully' : 'Failed to add category',
                'category_id' => $result
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    log_error($e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 