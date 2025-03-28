<?php
require_once __DIR__ . '/../main.php';

class UnifiedFileManagement {
    private $db;
    private $client;
    private $service;
    private $personalLimit = 524288000; // 500MB in bytes
    private $classLimit = 5368709120;   // 5GB in bytes

    /**
     * Constructor
     */
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->initializeGoogleClient();
    }

    /**
     * Initialize Google Drive API client
     */
    private function initializeGoogleClient() {
        try {
            $this->client = new Google_Client();
            $this->client->setAuthConfig(__DIR__ . '/../credentials.json');
            $this->client->addScope(Google_Service_Drive::DRIVE_FILE);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');
            
            $this->service = new Google_Service_Drive($this->client);
        } catch (Exception $e) {
            log_error("Error initializing Google Client: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload a file to Google Drive and record in database
     * 
     * @param array $file The uploaded file ($_FILES array element)
     * @param int $userId User ID who is uploading
     * @param int|null $classId Class ID (if applicable)
     * @param int|null $folderId Folder ID (if applicable)
     * @param string $description File description
     * @param string $visibility File visibility (private, public, class_only, specific_users)
     * @param string $filePurpose File purpose (personal, class_material, assignment, submission)
     * @return int|bool The file ID if successful, false otherwise
     */
    public function uploadFile($file, $userId, $classId = null, $folderId = null, $description = '', $visibility = 'private', $filePurpose = 'personal') {
        try {
            // Validate file size based on purpose
            if ($filePurpose === 'personal' && $file['size'] > $this->personalLimit) {
                throw new Exception("Personal file size exceeds limit of " . formatBytes($this->personalLimit));
            } else if ($file['size'] > $this->classLimit) {
                throw new Exception("File size exceeds limit of " . formatBytes($this->classLimit));
            }

            // Get Google Drive folder
            $googleFolderId = $this->getGoogleFolderId($folderId, $classId, $userId, $filePurpose);
            
            // Upload to Google Drive
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $file['name'],
                'parents' => [$googleFolderId],
                'description' => $description
            ]);

            $content = file_get_contents($file['tmp_name']);
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file['type'],
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);
            
            // Set file permissions based on visibility
            $this->setDrivePermissions($uploadedFile->id, $visibility);
            
            // Generate UUID for file
            $fileUuid = generateUuid();
            
            // Get folder ID if provided
            $dbFolderId = $folderId;
            
            // Insert file into database
            $sql = "INSERT INTO unified_files 
                   (file_uuid, class_id, user_id, folder_id, file_name, file_type, file_size, 
                    google_file_id, drive_link, description, visibility, file_purpose) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "siisssisssss", 
                $fileUuid, $classId, $userId, $dbFolderId, $file['name'], $file['type'], $file['size'],
                $uploadedFile->id, $uploadedFile->webViewLink, $description, $visibility, $filePurpose
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            $fileId = $stmt->insert_id;
            
            // Add to category if specified
            if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
                $categoryId = intval($_POST['category_id']);
                $this->setCategoryForFile($fileId, $categoryId);
            }
            
            return $fileId;
        } catch (Exception $e) {
            log_error("Error in uploadFile: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Set a category for a file
     */
    private function setCategoryForFile($fileId, $categoryId) {
        try {
            // Check if category exists
            $sql = "SELECT category_id FROM file_categories WHERE category_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Category not found");
            }
            
            // Update file's category
            $sql = "UPDATE unified_files SET category_id = ? WHERE file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $categoryId, $fileId);
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error setting category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set Google Drive permissions based on visibility setting
     */
    private function setDrivePermissions($fileId, $visibility) {
        try {
            if ($visibility === 'public' || $visibility === 'class_only') {
                // Anyone with the link can view
                $permission = new Google_Service_Drive_Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                    'allowFileDiscovery' => false
                ]);
                
                $this->service->permissions->create($fileId, $permission);
            }
        } catch (Exception $e) {
            log_error("Error setting Drive permissions: " . $e->getMessage());
        }
    }
    
    /**
     * Get or create appropriate Google Drive folder ID based on context
     */
    private function getGoogleFolderId($folderId, $classId, $userId, $filePurpose) {
        try {
            // If folder ID is provided, get the Google folder ID
            if ($folderId) {
                $sql = "SELECT google_folder_id FROM file_folders WHERE folder_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $folderId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    return $result->fetch_assoc()['google_folder_id'];
                }
            }
            
            // Otherwise create or get appropriate folder based on context
            $rootFolder = $this->getOrCreateRootFolder('TechTutor');
            
            if ($filePurpose === 'personal') {
                // Get user's email for folder name
                $sql = "SELECT email FROM users WHERE uid = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $userEmail = $stmt->get_result()->fetch_assoc()['email'];
                
                $usersFolder = $this->getOrCreateSubFolder($rootFolder, 'Users');
                return $this->getOrCreateSubFolder($usersFolder, $userEmail);
            } else if ($filePurpose === 'class_material' || $filePurpose === 'assignment' || $filePurpose === 'submission') {
                // Get class name
                $sql = "SELECT class_name FROM class WHERE class_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $classId);
                $stmt->execute();
                $className = $stmt->get_result()->fetch_assoc()['class_name'];
                
                $classesFolder = $this->getOrCreateSubFolder($rootFolder, 'Classes');
                $classFolder = $this->getOrCreateSubFolder($classesFolder, $className);
                
                // Add purpose-specific subfolder if needed
                if ($filePurpose === 'class_material') {
                    return $this->getOrCreateSubFolder($classFolder, 'Materials');
                } else if ($filePurpose === 'assignment') {
                    return $this->getOrCreateSubFolder($classFolder, 'Assignments');
                } else if ($filePurpose === 'submission') {
                    return $this->getOrCreateSubFolder($classFolder, 'Submissions');
                }
                
                return $classFolder;
            }
            
            // Default fallback to root folder
            return $rootFolder;
        } catch (Exception $e) {
            log_error("Error in getGoogleFolderId: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get or create root folder in Google Drive
     */
    private function getOrCreateRootFolder($name) {
        try {
            // Search for root folder
            $query = "name = '$name' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $results = $this->service->files->listFiles([
                'q' => $query,
                'spaces' => 'drive'
            ]);

            if (count($results->getFiles()) > 0) {
                return $results->getFiles()[0]->getId();
            }

            // Create root folder if not exists
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $this->service->files->create($folderMetadata, [
                'fields' => 'id'
            ]);

            return $folder->getId();
        } catch (Exception $e) {
            log_error("Error in getOrCreateRootFolder: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get or create subfolder in Google Drive
     */
    private function getOrCreateSubFolder($parentId, $name) {
        try {
            // Search for subfolder
            $query = "name = '$name' and '$parentId' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $results = $this->service->files->listFiles([
                'q' => $query,
                'spaces' => 'drive'
            ]);

            if (count($results->getFiles()) > 0) {
                return $results->getFiles()[0]->getId();
            }

            // Create subfolder if not exists
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => $name,
                'parents' => [$parentId],
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $this->service->files->create($folderMetadata, [
                'fields' => 'id'
            ]);

            return $folder->getId();
        } catch (Exception $e) {
            log_error("Error in getOrCreateSubFolder: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete a file
     * 
     * @param int $fileId File ID
     * @param int $userId User ID attempting to delete
     * @return bool True if successful, false otherwise
     */
    public function deleteFile($fileId, $userId) {
        try {
            // Start transaction
            $this->db->begin_transaction();
            
            // Get file information
            $sql = "SELECT f.*, u.role FROM unified_files f 
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("File not found");
            }
            
            $file = $result->fetch_assoc();
            
            // Check permission - must be owner or admin
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userRole = $stmt->get_result()->fetch_assoc()['role'];
            
            if ($file['user_id'] != $userId && $userRole !== 'ADMIN') {
                // Check if user has edit permissions
                $sql = "SELECT * FROM file_permissions 
                        WHERE file_id = ? AND user_id = ? AND access_type = 'edit'";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $fileId, $userId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("You don't have permission to delete this file");
                }
            }
            
            // Delete from Google Drive
            try {
                $this->service->files->delete($file['google_file_id']);
            } catch (Exception $e) {
                // Log but continue - file might have been deleted outside the system
                log_error("Error deleting from Google Drive: " . $e->getMessage());
            }
            
            // Delete permissions
            $sql = "DELETE FROM file_permissions WHERE file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            
            // Delete file record
            $sql = "DELETE FROM unified_files WHERE file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            log_error("Error in deleteFile: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create a new folder
     * 
     * @param int $userId User creating the folder
     * @param string $folderName Name of the folder
     * @param int|null $classId Class ID (if applicable)
     * @param int|null $parentFolderId Parent folder ID (if applicable)
     * @param string $visibility Folder visibility
     * @return int The new folder ID
     */
    public function createFolder($userId, $folderName, $classId = null, $parentFolderId = null, $visibility = 'private') {
        try {
            // Get parent Google folder ID
            $parentGoogleFolderId = null;
            
            if ($parentFolderId) {
                $sql = "SELECT google_folder_id FROM file_folders WHERE folder_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $parentFolderId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception("Parent folder not found");
                }
                
                $parentGoogleFolderId = $result->fetch_assoc()['google_folder_id'];
            } else {
                // Create appropriate parent folder based on context
                $rootFolder = $this->getOrCreateRootFolder('TechTutor');
                
                if ($classId) {
                    // Get class name
                    $sql = "SELECT class_name FROM class WHERE class_id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $classId);
                    $stmt->execute();
                    $className = $stmt->get_result()->fetch_assoc()['class_name'];
                    
                    $classesFolder = $this->getOrCreateSubFolder($rootFolder, 'Classes');
                    $parentGoogleFolderId = $this->getOrCreateSubFolder($classesFolder, $className);
                } else {
                    // Personal folder - use user's email
                    $sql = "SELECT email FROM users WHERE uid = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $userEmail = $stmt->get_result()->fetch_assoc()['email'];
                    
                    $usersFolder = $this->getOrCreateSubFolder($rootFolder, 'Users');
                    $parentGoogleFolderId = $this->getOrCreateSubFolder($usersFolder, $userEmail);
                }
            }
            
            // Create folder in Google Drive
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => $folderName,
                'parents' => [$parentGoogleFolderId],
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            
            $folder = $this->service->files->create($folderMetadata, [
                'fields' => 'id'
            ]);
            
            // Set folder permissions based on visibility
            if ($visibility === 'public' || $visibility === 'class_only') {
                $permission = new Google_Service_Drive_Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                    'allowFileDiscovery' => false
                ]);
                
                $this->service->permissions->create($folder->getId(), $permission);
            }
            
            // Create folder in database
            $sql = "INSERT INTO file_folders 
                   (class_id, user_id, folder_name, parent_folder_id, 
                   google_folder_id, visibility) 
                   VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "iisiss", 
                $classId, $userId, $folderName, $parentFolderId, 
                $folder->getId(), $visibility
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            return $stmt->insert_id;
        } catch (Exception $e) {
            log_error("Error in createFolder: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Rename a folder
     * 
     * @param int $folderId Folder ID
     * @param string $newName New folder name
     * @param int $userId User attempting the rename
     * @return bool True if successful, false otherwise
     */
    public function renameFolder($folderId, $newName, $userId) {
        try {
            // Get folder info
            $sql = "SELECT f.*, u.role FROM file_folders f 
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $folderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Folder not found");
            }
            
            $folder = $result->fetch_assoc();
            
            // Check permission
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userRole = $stmt->get_result()->fetch_assoc()['role'];
            
            if ($folder['user_id'] != $userId && $userRole !== 'ADMIN') {
                // Check if user has edit permissions
                $sql = "SELECT * FROM file_permissions 
                        WHERE folder_id = ? AND user_id = ? AND access_type = 'edit'";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $folderId, $userId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("You don't have permission to rename this folder");
                }
            }
            
            // Update in Google Drive
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $newName
            ]);
            
            $this->service->files->update($folder['google_folder_id'], $fileMetadata);
            
            // Update in database
            $sql = "UPDATE file_folders SET folder_name = ? WHERE folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $newName, $folderId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error in renameFolder: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete a folder and its contents
     * 
     * @param int $folderId Folder ID
     * @param int $userId User attempting to delete
     * @return bool True if successful, false otherwise
     */
    public function deleteFolder($folderId, $userId) {
        try {
            // Start transaction
            $this->db->begin_transaction();
            
            // Get folder info
            $sql = "SELECT f.*, u.role FROM file_folders f 
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $folderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Folder not found");
            }
            
            $folder = $result->fetch_assoc();
            
            // Check permission
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userRole = $stmt->get_result()->fetch_assoc()['role'];
            
            if ($folder['user_id'] != $userId && $userRole !== 'ADMIN') {
                throw new Exception("You don't have permission to delete this folder");
            }
            
            // Delete from Google Drive
            try {
                $this->service->files->delete($folder['google_folder_id']);
            } catch (Exception $e) {
                // Log but continue - folder might have been deleted outside the system
                log_error("Error deleting folder from Google Drive: " . $e->getMessage());
            }
            
            // Delete all files in this folder
            $sql = "SELECT file_id FROM unified_files WHERE folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $folderId);
            $stmt->execute();
            $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($files as $file) {
                // Skip Google Drive deletion as folder deletion already handled it
                $sql = "DELETE FROM file_permissions WHERE file_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $file['file_id']);
                $stmt->execute();
                
                $sql = "DELETE FROM unified_files WHERE file_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $file['file_id']);
                $stmt->execute();
            }
            
            // Delete subfolders recursively
            $sql = "SELECT folder_id FROM file_folders WHERE parent_folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $folderId);
            $stmt->execute();
            $subfolders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($subfolders as $subfolder) {
                $this->deleteFolder($subfolder['folder_id'], $userId);
            }
            
            // Delete permissions for this folder
            $sql = "DELETE FROM file_permissions WHERE folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $folderId);
            $stmt->execute();
            
            // Delete folder itself
            $sql = "DELETE FROM file_folders WHERE folder_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $folderId);
            $stmt->execute();
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            log_error("Error in deleteFolder: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get storage information for a user
     * 
     * @param int $userId The user ID to get storage info for
     * @return array Storage information
     */
    public function getStorageInfo($userId) {
        try {
            // Get total size of personal files
            $sql = "SELECT SUM(file_size) as total_personal_size 
                   FROM unified_files 
                   WHERE user_id = ? AND file_purpose = 'personal'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $personalSize = $row['total_personal_size'] ? $row['total_personal_size'] : 0;
            
            // Get total size of class files
            $sql = "SELECT SUM(file_size) as total_class_size 
                   FROM unified_files 
                   WHERE user_id = ? AND file_purpose != 'personal'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $classSize = $row['total_class_size'] ? $row['total_class_size'] : 0;
            
            // Calculate percentages
            $personalPercentage = ($personalSize / $this->personalLimit) * 100;
            $classPercentage = ($classSize / $this->classLimit) * 100;
            
            return [
                'personal' => [
                    'used' => $personalSize,
                    'total' => $this->personalLimit,
                    'used_formatted' => formatBytes($personalSize),
                    'total_formatted' => formatBytes($this->personalLimit),
                    'percentage' => round($personalPercentage, 2)
                ],
                'class' => [
                    'used' => $classSize,
                    'total' => $this->classLimit,
                    'used_formatted' => formatBytes($classSize),
                    'total_formatted' => formatBytes($this->classLimit),
                    'percentage' => round($classPercentage, 2)
                ]
            ];
        } catch (Exception $e) {
            log_error("Error in getStorageInfo: " . $e->getMessage());
            return [
                'personal' => [
                    'used' => 0,
                    'total' => $this->personalLimit,
                    'used_formatted' => '0 B',
                    'total_formatted' => formatBytes($this->personalLimit),
                    'percentage' => 0
                ],
                'class' => [
                    'used' => 0,
                    'total' => $this->classLimit,
                    'used_formatted' => '0 B',
                    'total_formatted' => formatBytes($this->classLimit),
                    'percentage' => 0
                ]
            ];
        }
    }
    
    /**
     * Get personal files for a user
     * 
     * @param int $userId User ID to get files for
     * @return array Array of file information
     */
    public function getPersonalFiles($userId) {
        try {
            $sql = "SELECT f.*, c.category_name,
                           ff.folder_name, ff.folder_id,
                           u.first_name, u.last_name, u.email
                    FROM unified_files f
                    LEFT JOIN file_categories c ON f.category_id = c.category_id
                    LEFT JOIN file_folders ff ON f.folder_id = ff.folder_id
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.user_id = ? AND f.file_purpose = 'personal'
                    ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getPersonalFiles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a file request (from TechGuru to TechKid)
     * 
     * @param int $classId Class ID
     * @param int $requesterId TechGuru user ID
     * @param int $recipientId TechKid user ID
     * @param string $requestTitle Request title
     * @param string $description Request description
     * @param string $dueDate Due date (YYYY-MM-DD HH:MM:SS format)
     * @return int|bool Request ID if successful, false otherwise
     */
    public function createFileRequest($classId, $requesterId, $recipientId, $requestTitle, $description, $dueDate) {
        try {
            // Verify class exists
            $sql = "SELECT class_id FROM class WHERE class_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Class not found");
            }
            
            // Verify requester is a TechGuru
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $requesterId);
            $stmt->execute();
            $requesterRole = $stmt->get_result()->fetch_assoc()['role'];
            if ($requesterRole !== 'TECHGURU') {
                throw new Exception("Only teachers can create file requests");
            }
            
            // Verify recipient is a TechKid
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $recipientId);
            $stmt->execute();
            $recipientRole = $stmt->get_result()->fetch_assoc()['role'];
            if ($recipientRole !== 'TECHKID') {
                throw new Exception("File requests can only be sent to students");
            }
            
            // Verify recipient is enrolled in the class
            $sql = "SELECT enrollment_id FROM enrollments 
                    WHERE class_id = ? AND student_id = ? AND status IN ('active', 'pending')";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $classId, $recipientId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Student is not enrolled in this class");
            }
            
            // Create file request
            $sql = "INSERT INTO file_requests 
                   (class_id, requester_id, recipient_id, request_title, description, due_date)
                   VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iiisss", $classId, $requesterId, $recipientId, $requestTitle, $description, $dueDate);
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            $requestId = $stmt->insert_id;
            
            // Send notification to the recipient
            $sql = "INSERT INTO notifications 
                   (recipient_id, class_id, message, link, icon, icon_color) 
                   VALUES (?, ?, ?, ?, ?, ?)";
            
            $message = "You have a new file request: $requestTitle";
            $link = "student/class.php?id=$classId&tab=assignments";
            $icon = "bi-file-earmark-arrow-up";
            $iconColor = "primary";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "iissss", 
                $recipientId, $classId, $message, $link, $icon, $iconColor
            );
            $stmt->execute();
            
            return $requestId;
        } catch (Exception $e) {
            log_error("Error in createFileRequest: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Submit a file to a request (from TechKid to TechGuru)
     * 
     * @param int $requestId Request ID
     * @param int $fileId File ID
     * @param int $userId User ID submitting the file
     * @return bool True if successful, false otherwise
     */
    public function submitFileToRequest($requestId, $fileId, $userId) {
        try {
            // Verify request exists and is pending
            $sql = "SELECT r.*, f.file_id as existing_file_id
                    FROM file_requests r
                    LEFT JOIN unified_files f ON r.response_file_id = f.file_id
                    WHERE r.request_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("File request not found");
            }
            
            $request = $result->fetch_assoc();
            
            if ($request['status'] !== 'pending') {
                throw new Exception("This request has already been " . $request['status']);
            }
            
            // If there's already a file, delete it first
            if ($request['existing_file_id']) {
                $this->deleteFile($request['existing_file_id'], $userId);
            }
            
            // Update request status
            $sql = "UPDATE file_requests 
                   SET response_file_id = ?, status = 'submitted', updated_at = NOW()
                   WHERE request_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $fileId, $requestId);
            
            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }
            
            // Send notification to the requester
            $sql = "INSERT INTO notifications 
                   (recipient_id, class_id, message, link, icon, icon_color) 
                   VALUES (?, ?, ?, ?, ?, ?)";
            
            $message = "A student has submitted a file for your request";
            $link = "tutor/class.php?id=" . $request['class_id'] . "&tab=submissions";
            $icon = "bi-file-earmark-check";
            $iconColor = "success";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "iissss", 
                $request['requester_id'], $request['class_id'], $message, $link, $icon, $iconColor
            );
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            log_error("Error in submitFileToRequest: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get files for a specific class
     * 
     * @param int $classId Class ID
     * @param string|null $filePurpose File purpose filter (class_material, assignment, submission)
     * @return array Array of file information
     */
    public function getClassFiles($classId, $filePurpose = null) {
        try {
            $whereClause = "f.class_id = ?";
            $params = [$classId];
            $types = "i";
            
            if ($filePurpose) {
                $whereClause .= " AND f.file_purpose = ?";
                $params[] = $filePurpose;
                $types .= "s";
            }
            
            $sql = "SELECT f.*, c.category_name,
                           ff.folder_name, ff.folder_id,
                           u.first_name, u.last_name, u.email
                    FROM unified_files f
                    LEFT JOIN file_categories c ON f.category_id = c.category_id
                    LEFT JOIN file_folders ff ON f.folder_id = ff.folder_id
                    JOIN users u ON f.user_id = u.uid
                    WHERE $whereClause
                    ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getClassFiles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get files in a specific folder
     * 
     * @param int $classId Class ID
     * @param int $folderId Folder ID
     * @return array Array of file information
     */
    public function getFolderFiles($classId, $folderId) {
        try {
            $sql = "SELECT f.*, c.category_name,
                           ff.folder_name, ff.folder_id,
                           u.first_name, u.last_name, u.email
                    FROM unified_files f
                    LEFT JOIN file_categories c ON f.category_id = c.category_id
                    LEFT JOIN file_folders ff ON f.folder_id = ff.folder_id
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.class_id = ? AND f.folder_id = ?
                    ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $classId, $folderId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getFolderFiles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all files accessible to a user
     * 
     * @param int $userId User ID
     * @return array Array of file information
     */
    public function getUserAccessibleFiles($userId) {
        try {
            // Files owned by user, visible to user through permissions, or public
            $sql = "SELECT f.*, c.category_name,
                           ff.folder_name, ff.folder_id,
                           u.first_name, u.last_name, u.email
                    FROM unified_files f
                    LEFT JOIN file_categories c ON f.category_id = c.category_id
                    LEFT JOIN file_folders ff ON f.folder_id = ff.folder_id
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.user_id = ? 
                       OR f.visibility = 'public'
                       OR EXISTS (
                          SELECT 1 FROM file_permissions p
                          WHERE p.file_id = f.file_id
                            AND p.user_id = ?
                       )
                       OR EXISTS (
                          SELECT 1 FROM class cl
                          JOIN enrollments e ON cl.class_id = e.class_id
                          WHERE f.class_id = cl.class_id
                            AND e.student_id = ?
                            AND f.visibility = 'class_only'
                       )
                    ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iii", $userId, $userId, $userId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getUserAccessibleFiles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remove a permission
     * 
     * @param int $permissionId Permission ID to remove
     * @param int $userId User ID attempting to remove permission
     * @return bool True if successful, false otherwise
     */
    public function removePermission($permissionId, $userId) {
        try {
            // Get permission info
            $sql = "SELECT p.*, uf.user_id as file_owner, ff.user_id as folder_owner 
                    FROM file_permissions p
                    LEFT JOIN unified_files uf ON p.file_id = uf.file_id
                    LEFT JOIN file_folders ff ON p.folder_id = ff.folder_id
                    WHERE p.permission_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $permissionId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Permission not found");
            }
            
            $permission = $result->fetch_assoc();
            
            // Check if user has right to remove this permission
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userRole = $stmt->get_result()->fetch_assoc()['role'];
            
            $isOwner = ($permission['file_owner'] == $userId || $permission['folder_owner'] == $userId);
            $isAdmin = ($userRole === 'ADMIN');
            $isGranter = ($permission['granted_by'] == $userId);
            
            if (!$isOwner && !$isAdmin && !$isGranter) {
                throw new Exception("You don't have permission to remove this access");
            }
            
            // Delete the permission
            $sql = "DELETE FROM file_permissions WHERE permission_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $permissionId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error in removePermission: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add permission for a user to access a file or folder
     * 
     * @param int|null $fileId File ID (if applicable)
     * @param int|null $folderId Folder ID (if applicable)
     * @param int $userId User ID to grant access to
     * @param string $accessType Access type (view, edit, owner)
     * @param int $grantedBy User ID of the person granting access
     * @return bool True if successful, false otherwise
     */
    public function addPermission($fileId, $folderId, $userId, $accessType = 'view', $grantedBy) {
        try {
            // Validate input
            if (!$fileId && !$folderId) {
                throw new Exception("Either file ID or folder ID must be provided");
            }
            
            if ($fileId) {
                // Check if file exists and if granter has permission
                $sql = "SELECT f.* FROM unified_files f 
                        WHERE f.file_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $fileId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("File not found");
                }
                
                // Check if user already has permission
                $sql = "SELECT * FROM file_permissions 
                        WHERE file_id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $fileId, $userId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    // Update existing permission
                    $sql = "UPDATE file_permissions 
                            SET access_type = ? 
                            WHERE file_id = ? AND user_id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("sii", $accessType, $fileId, $userId);
                } else {
                    // Add new permission
                    $sql = "INSERT INTO file_permissions 
                           (file_id, user_id, access_type, granted_by) 
                           VALUES (?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("iisi", $fileId, $userId, $accessType, $grantedBy);
                }
            } else {
                // Check if folder exists
                $sql = "SELECT f.* FROM file_folders f 
                        WHERE f.folder_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $folderId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("Folder not found");
                }
                
                // Check if user already has permission
                $sql = "SELECT * FROM file_permissions 
                        WHERE folder_id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $folderId, $userId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    // Update existing permission
                    $sql = "UPDATE file_permissions 
                            SET access_type = ? 
                            WHERE folder_id = ? AND user_id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("sii", $accessType, $folderId, $userId);
                } else {
                    // Add new permission
                    $sql = "INSERT INTO file_permissions 
                           (folder_id, user_id, access_type, granted_by) 
                           VALUES (?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("iisi", $folderId, $userId, $accessType, $grantedBy);
                }
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error in addPermission: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Search for files based on criteria
     * 
     * @param int $userId User ID searching
     * @param string $searchTerm Search term
     * @param array $filters Optional filters (category, file type, date range)
     * @return array Array of file information
     */
    public function searchFiles($userId, $searchTerm, $filters = []) {
        try {
            // Base query for files accessible to user
            $sql = "SELECT f.*, c.category_name,
                           ff.folder_name, ff.folder_id,
                           u.first_name, u.last_name, u.email
                    FROM unified_files f
                    LEFT JOIN file_categories c ON f.category_id = c.category_id
                    LEFT JOIN file_folders ff ON f.folder_id = ff.folder_id
                    JOIN users u ON f.user_id = u.uid
                    WHERE (f.user_id = ? 
                       OR f.visibility = 'public'
                       OR EXISTS (
                          SELECT 1 FROM file_permissions p
                          WHERE p.file_id = f.file_id
                            AND p.user_id = ?
                       )
                       OR EXISTS (
                          SELECT 1 FROM class cl
                          JOIN enrollments e ON cl.class_id = e.class_id
                          WHERE f.class_id = cl.class_id
                            AND e.student_id = ?
                            AND f.visibility = 'class_only'
                       ))";
            
            $params = [$userId, $userId, $userId];
            $types = "iii";
            
            // Add search term condition
            if (!empty($searchTerm)) {
                $sql .= " AND (f.file_name LIKE ? OR f.description LIKE ?)";
                $searchParam = "%" . $searchTerm . "%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "ss";
            }
            
            // Add category filter
            if (!empty($filters['category_id'])) {
                $sql .= " AND f.category_id = ?";
                $params[] = $filters['category_id'];
                $types .= "i";
            }
            
            // Add file type filter
            if (!empty($filters['file_type'])) {
                $sql .= " AND f.file_type = ?";
                $params[] = $filters['file_type'];
                $types .= "s";
            }
            
            // Add date range filter
            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                $sql .= " AND f.upload_time BETWEEN ? AND ?";
                $params[] = $filters['date_from'] . " 00:00:00";
                $params[] = $filters['date_to'] . " 23:59:59";
                $types .= "ss";
            }
            
            // Add sort order
            $sql .= " ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in searchFiles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get file categories
     * 
     * @return array Array of file categories
     */
    public function getFileCategories() {
        try {
            $sql = "SELECT * FROM file_categories ORDER BY category_name ASC";
            $result = $this->db->query($sql);
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getFileCategories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add a new file category
     * 
     * @param string $categoryName Category name
     * @param string $description Optional description
     * @return int|bool New category ID or false on failure
     */
    public function addFileCategory($categoryName, $description = '') {
        try {
            $sql = "INSERT INTO file_categories (category_name, description) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ss", $categoryName, $description);
            
            if ($stmt->execute()) {
                return $this->db->insert_id;
            }
            return false;
        } catch (Exception $e) {
            log_error("Error in addFileCategory: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a file's information
     * 
     * @param int $fileId File ID
     * @param int $userId User ID attempting the update
     * @param array $data Data to update (file_name, description, category_id, visibility)
     * @return bool True if successful, false otherwise
     */
    public function updateFile($fileId, $userId, $data) {
        try {
            // Check if user is owner of the file
            $sql = "SELECT user_id FROM unified_files WHERE file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("File not found");
            }
            
            $file = $result->fetch_assoc();
            
            // Check if user has right to update this file
            $sql = "SELECT role FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userRole = $stmt->get_result()->fetch_assoc()['role'];
            
            $isOwner = ($file['user_id'] == $userId);
            $isAdmin = ($userRole === 'ADMIN');
            
            if (!$isOwner && !$isAdmin) {
                throw new Exception("You don't have permission to update this file");
            }
            
            // Build the update query based on provided data
            $updateFields = [];
            $params = [];
            $types = "";
            
            if (isset($data['file_name'])) {
                $updateFields[] = "file_name = ?";
                $params[] = $data['file_name'];
                $types .= "s";
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $data['description'];
                $types .= "s";
            }
            
            if (isset($data['category_id'])) {
                $updateFields[] = "category_id = ?";
                $params[] = $data['category_id'];
                $types .= "i";
            }
            
            if (isset($data['visibility'])) {
                $updateFields[] = "visibility = ?";
                $params[] = $data['visibility'];
                $types .= "s";
            }
            
            if (empty($updateFields)) {
                return true; // Nothing to update
            }
            
            $sql = "UPDATE unified_files SET " . implode(", ", $updateFields) . " WHERE file_id = ?";
            $params[] = $fileId;
            $types .= "i";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error in updateFile: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add tags to a file
     * 
     * @param int $fileId File ID
     * @param int $userId User ID attempting to add tags
     * @param array $tags Array of tag names
     * @return bool True if successful, false otherwise
     */
    public function addFileTags($fileId, $userId, $tags) {
        try {
            // Check if user has permission to tag this file
            $sql = "SELECT f.user_id, f.visibility, c.class_id 
                    FROM unified_files f
                    LEFT JOIN class c ON f.class_id = c.class_id
                    WHERE f.file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("File not found");
            }
            
            $file = $result->fetch_assoc();
            
            // Check user permission
            $isOwner = ($file['user_id'] == $userId);
            if (!$isOwner) {
                // Check if user has access through class or permissions
                $sql = "SELECT role FROM users WHERE uid = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $userRole = $stmt->get_result()->fetch_assoc()['role'];
                
                $isAdmin = ($userRole === 'ADMIN');
                
                if (!$isAdmin) {
                    // Check for permissions
                    $sql = "SELECT * FROM file_permissions 
                            WHERE file_id = ? AND user_id = ? AND access_type IN ('edit', 'owner')";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $fileId, $userId);
                    $stmt->execute();
                    
                    $hasPermission = ($stmt->get_result()->num_rows > 0);
                    
                    if (!$hasPermission && $file['class_id']) {
                        // Check if user is a teacher for this class
                        $sql = "SELECT * FROM class WHERE class_id = ? AND teacher_id = ?";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("ii", $file['class_id'], $userId);
                        $stmt->execute();
                        
                        $isTeacher = ($stmt->get_result()->num_rows > 0);
                        
                        if (!$isTeacher) {
                            throw new Exception("You don't have permission to tag this file");
                        }
                    } elseif (!$hasPermission) {
                        throw new Exception("You don't have permission to tag this file");
                    }
                }
            }
            
            // Process tags
            $this->db->begin_transaction();
            
            foreach ($tags as $tagName) {
                // Normalize tag
                $tagName = trim(strtolower($tagName));
                if (empty($tagName)) continue;
                
                // Check if tag exists
                $sql = "SELECT tag_id FROM file_tags WHERE tag_name = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("s", $tagName);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $tagId = $result->fetch_assoc()['tag_id'];
                } else {
                    // Create new tag
                    $sql = "INSERT INTO file_tags (tag_name) VALUES (?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("s", $tagName);
                    $stmt->execute();
                    $tagId = $this->db->insert_id;
                }
                
                // Associate tag with file (avoid duplicates)
                $sql = "INSERT IGNORE INTO file_tag_map (file_id, tag_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $fileId, $tagId);
                $stmt->execute();
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            log_error("Error in addFileTags: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Remove a tag from a file
     * 
     * @param int $fileId File ID
     * @param int $tagId Tag ID
     * @param int $userId User ID attempting to remove the tag
     * @return bool True if successful, false otherwise
     */
    public function removeFileTag($fileId, $tagId, $userId) {
        try {
            // Check if user has permission to modify this file
            $sql = "SELECT f.user_id 
                    FROM unified_files f
                    WHERE f.file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("File not found");
            }
            
            $file = $result->fetch_assoc();
            
            // Check permission (using same logic as addFileTags)
            $isOwner = ($file['user_id'] == $userId);
            if (!$isOwner) {
                // Check if user is admin
                $sql = "SELECT role FROM users WHERE uid = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $userRole = $stmt->get_result()->fetch_assoc()['role'];
                
                $isAdmin = ($userRole === 'ADMIN');
                
                if (!$isAdmin) {
                    throw new Exception("You don't have permission to remove tags from this file");
                }
            }
            
            // Remove the tag association
            $sql = "DELETE FROM file_tag_map WHERE file_id = ? AND tag_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $fileId, $tagId);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            log_error("Error in removeFileTag: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get tags for a file
     * 
     * @param int $fileId File ID
     * @return array Array of tags
     */
    public function getFileTags($fileId) {
        try {
            $sql = "SELECT t.* 
                    FROM file_tags t
                    JOIN file_tag_map m ON t.tag_id = m.tag_id
                    WHERE m.file_id = ?
                    ORDER BY t.tag_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getFileTags: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Find files by tag
     * 
     * @param int $userId User ID searching
     * @param string $tagName Tag name to search for
     * @return array Array of files with this tag
     */
    public function findFilesByTag($userId, $tagName) {
        try {
            // Base query for files accessible to user with the specified tag
            $sql = "SELECT f.*, c.category_name,
                           ff.folder_name, ff.folder_id,
                           u.first_name, u.last_name, u.email
                    FROM unified_files f
                    LEFT JOIN file_categories c ON f.category_id = c.category_id
                    LEFT JOIN file_folders ff ON f.folder_id = ff.folder_id
                    JOIN users u ON f.user_id = u.uid
                    JOIN file_tag_map m ON f.file_id = m.file_id
                    JOIN file_tags t ON m.tag_id = t.tag_id
                    WHERE t.tag_name = ? 
                      AND (f.user_id = ? 
                         OR f.visibility = 'public'
                         OR EXISTS (
                            SELECT 1 FROM file_permissions p
                            WHERE p.file_id = f.file_id
                              AND p.user_id = ?
                         )
                         OR EXISTS (
                            SELECT 1 FROM class cl
                            JOIN enrollments e ON cl.class_id = e.class_id
                            WHERE f.class_id = cl.class_id
                              AND e.student_id = ?
                              AND f.visibility = 'class_only'
                         ))
                    ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("siii", $tagName, $userId, $userId, $userId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in findFilesByTag: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get popular tags (most used tags)
     * 
     * @param int $limit Limit the number of tags returned
     * @return array Array of tags with usage counts
     */
    public function getPopularTags($limit = 20) {
        try {
            $sql = "SELECT t.tag_id, t.tag_name, COUNT(m.file_id) as usage_count
                    FROM file_tags t
                    JOIN file_tag_map m ON t.tag_id = m.tag_id
                    GROUP BY t.tag_id
                    ORDER BY usage_count DESC, t.tag_name ASC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getPopularTags: " . $e->getMessage());
            return [];
        }
    }
    public function getFileDetails($fileId) {
        try {
        // Prepare the SQL query to fetch the file details
        $query = "SELECT 
                    CONCAT(u.first_name,' ',u.last_name) AS uploader_name,
                    uf.file_id, 
                    uf.file_name, 
                    uf.file_type, 
                    uf.file_size, 
                    uf.upload_time, 
                    uf.google_file_id,
                    uf.drive_link, 
                    uf.description, 
                    uf.visibility, 
                    uf.file_purpose, 
                    fc.category_name AS file_category 
                  FROM unified_files uf 
                  LEFT JOIN file_categories fc ON uf.category_id = fc.category_id
                  LEFT JOIN users u ON uf.user_id = u.uid
                  WHERE uf.file_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $fileId);
        
        // Execute the query
        $stmt->execute();

        // Fetch the result
        return $stmt->get_result()->fetch_assoc();
        } 
        catch (Exception $e) {
            log_error("Error in getPopularTags: " . $e->getMessage());
            return [];
        }
    }
    public function formatFileSize($fileSize) {
        if ($fileSize < 1024) {
            return $fileSize . ' bytes';
        } elseif ($fileSize < 1048576) {
            return round($fileSize / 1024, 2) . ' KB';
        } elseif ($fileSize < 1073741824) {
            return round($fileSize / 1048576, 2) . ' MB';
        } else {
            return round($fileSize / 1073741824, 2) . ' GB';
        }
    }
    public function extractFileIdFromDriveLink($url) {
        // Regular expression to extract file ID from Google Drive URL
        preg_match('/\/d\/([a-zA-Z0-9_-]+)\//', $url, $matches);
        
        return isset($matches[1]) ? "https://drive.google.com/uc?export=download&id=".$matches[1] : null;
    }
}