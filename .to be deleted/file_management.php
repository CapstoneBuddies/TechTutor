<?php
require_once __DIR__ . '/../main.php';

class FileManagement {
    private $db;
    private $client;
    private $service;
    private $personalLimit = 524288000; // 500MB in bytes
    private $classLimit = 5368709120; // 5GB in bytes

    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->initializeGoogleClient();
    }

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

    public function uploadFile($file, $classId, $userId, $description = '', $isPersonal = false) {
        try {
            // Validate file size
            if ($isPersonal && $file['size'] > $this->personalLimit) {
                throw new Exception("Personal file size exceeds 500MB limit");
            }
            if (!$isPersonal && $file['size'] > $this->classLimit) {
                throw new Exception("Class file size exceeds 5GB limit");
            }

            // Get or create folder
            $folderId = $this->getOrCreateFolder($classId, $userId, $isPersonal);
            
            // Upload to Google Drive
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $file['name'],
                'parents' => [$folderId],
                'description' => $description
            ]);

            $content = file_get_contents($file['tmp_name']);
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file['type'],
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);

            // Set file permissions to "Anyone with the link can view"
            $permission = new Google_Service_Drive_Permission([
                'type' => 'anyone',
                'role' => 'reader',
                'allowFileDiscovery' => false
            ]);
            
            $this->service->permissions->create($uploadedFile->id, $permission);

            // Save to database
            $sql = "INSERT INTO file_management 
                    (class_id, user_id, file_name, file_type, file_size, 
                     google_file_id, drive_link, folder_id, description, is_personal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iisssssssi", 
                $classId, $userId, $file['name'], $file['type'], 
                $file['size'], $uploadedFile->id, $uploadedFile->webViewLink,
                $folderId, $description, $isPersonal
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error in uploadFile: " . $e->getMessage(),'database');
            throw $e;
        }
    }

    private function getOrCreateFolder($classId, $userId, $isPersonal) {
        try {
            // Get user's email
            $sql = "SELECT email FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userEmail = $stmt->get_result()->fetch_assoc()['email'];

            // Get class name
            $sql = "SELECT class_name FROM class WHERE class_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $className = $stmt->get_result()->fetch_assoc()['class_name'];

            // Check if root folders exist
            $rootFolder = $this->getOrCreateRootFolder('TechTutor');
            $classFolder = $this->getOrCreateSubFolder($rootFolder, 'Class');
            $usersFolder = $this->getOrCreateSubFolder($rootFolder, 'Users');

            if ($isPersonal) {
                // Create user's personal folder
                $userFolder = $this->getOrCreateSubFolder($usersFolder, $userEmail);
                // Set folder permissions
                $this->setFolderPermissions($userFolder);
                return $userFolder;
            } else {
                // Create class folder
                $classSpecificFolder = $this->getOrCreateSubFolder($classFolder, $className);
                // Set folder permissions
                $this->setFolderPermissions($classSpecificFolder);
                return $classSpecificFolder;
            }
        } catch (Exception $e) {
            log_error("Error in getOrCreateFolder: " . $e->getMessage(),'database');
            throw $e;
        }
    }

    private function setFolderPermissions($folderId) {
        try {
            // First, check if permission already exists
            $permissions = $this->service->permissions->listPermissions($folderId);
            foreach ($permissions as $permission) {
                if ($permission->type === 'anyone' && $permission->role === 'reader') {
                    return; // Permission already exists
                }
            }

            // Set folder permissions to "Anyone with the link can view"
            $permission = new Google_Service_Drive_Permission([
                'type' => 'anyone',
                'role' => 'reader',
                'allowFileDiscovery' => false
            ]);
            
            $this->service->permissions->create($folderId, $permission, [
                'supportsAllDrives' => true,
                'sendNotificationEmail' => false
            ]);
        } catch (Exception $e) {
            log_error("Error setting folder permissions: " . $e->getMessage(),'database');
        }
    }

    private function getOrCreateRootFolder($name) {
        try {
            // Search for root folder
            $query = "name = '$name' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $results = $this->service->files->listFiles([
                'q' => $query,
                'spaces' => 'drive'
            ]);

            if ($results->getFiles()) {
                $folderId = $results->getFiles()[0]->getId();
                $this->setFolderPermissions($folderId);
                return $folderId;
            }

            // Create root folder if not exists
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $this->service->files->create($folderMetadata, [
                'fields' => 'id'
            ]);

            $this->setFolderPermissions($folder->getId());
            return $folder->getId();
        } catch (Exception $e) {
            log_error("Error in getOrCreateRootFolder: " . $e->getMessage(),'database');
            throw $e;
        }
    }

    private function getOrCreateSubFolder($parentId, $name) {
        try {
            // Search for subfolder
            $query = "name = '$name' and '$parentId' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $results = $this->service->files->listFiles([
                'q' => $query,
                'spaces' => 'drive'
            ]);

            if ($results->getFiles()) {
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
            log_error("Error in getOrCreateSubFolder: " . $e->getMessage(),'database');
            throw $e;
        }
    }

    public function getPersonalFiles($userId) {
        try {
            // Get user's email
            $sql = "SELECT email FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userEmail = $stmt->get_result()->fetch_assoc()['email'];

            $sql = "SELECT f.*, u.email 
                    FROM file_management f
                    JOIN users u ON f.user_id = u.uid
                    WHERE f.user_id = ? AND f.is_personal = 1 
                    AND u.email = ?
                    ORDER BY f.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("is", $userId, $userEmail);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getPersonalFiles: " . $e->getMessage(),'database');
            return false;
        }
    }

    public function getAccessibleFiles($userId) {
        try {
            // Get user's email
            $sql = "SELECT email FROM users WHERE uid = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userEmail = $stmt->get_result()->fetch_assoc()['email'];

            $query = "
                SELECT f.*, c.class_name, CONCAT(u.first_name, ' ', u.last_name) as uploader_name
                FROM file_management f
                JOIN class c ON f.class_id = c.class_id
                JOIN users u ON f.user_id = u.uid
                JOIN enrollments e ON c.class_id = e.class_id
                WHERE e.student_id = ? AND e.status = 'active'
                AND (
                    (f.is_personal = 1 AND u.email = ?) OR
                    (f.is_personal = 0 AND f.is_visible = 1)
                )
                ORDER BY c.class_name, f.upload_time DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("is", $userId, $userEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $files = [];
            while ($row = $result->fetch_assoc()) {
                $className = $row['class_name'];
                if (!isset($files[$className])) {
                    $files[$className] = [];
                }
                $files[$className][] = $row;
            }
            
            return $files;
        } catch (Exception $e) {
            log_error("Error in getAccessibleFiles: " . $e->getMessage(),'database');
            return [];
        }
    }

    public function getUploadRequests($userId) {
        try {
            $query = "
                SELECT r.*, c.class_name, 
                       CONCAT(u.first_name, ' ', u.last_name) as tutor_name
                FROM file_upload_requests r
                JOIN class c ON r.class_id = c.class_id
                JOIN users u ON r.tutor_id = u.uid
                WHERE r.student_id = ? AND r.status = 'pending'
                ORDER BY r.due_date ASC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getUploadRequests: " . $e->getMessage(),'database');
            return [];
        }
    }

    public function getStorageInfo($userId) {
        try {
            $query = "
                SELECT COALESCE(SUM(file_size), 0) as total_size
                FROM file_management 
                WHERE user_id = ? AND is_personal = 1
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $used = $result['total_size'];
            $limit = $this->personalLimit;
            $percentage = min(round(($used / $limit) * 100, 2), 100);
            
            return [
                'used' => $used,
                'limit' => $limit,
                'percentage' => $percentage
            ];
        } catch (Exception $e) {
            log_error("Error in getStorageInfo: " . $e->getMessage(),'database');
            return [
                'used' => 0,
                'limit' => $this->personalLimit,
                'percentage' => 0
            ];
        }
    }

    public function getClassFiles($classId) {
        try {
            $sql = "SELECT fm.*, u.full_name as uploader_name 
                    FROM file_management fm 
                    JOIN users u ON fm.user_id = u.uid 
                    WHERE fm.class_id = ? AND fm.is_personal = 0 
                    ORDER BY fm.upload_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error in getClassFiles: " . $e->getMessage(),'database');
            return false;
        }
    }

    public function deleteFile($fileId, $userId) {
        try {
            // Get file info
            $sql = "SELECT * FROM file_management WHERE file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $file = $stmt->get_result()->fetch_assoc();

            if (!$file) {
                throw new Exception("File not found");
            }

            // Check permission
            if ($file['user_id'] != $userId) {
                throw new Exception("Unauthorized");
            }

            // Delete from Google Drive
            $this->service->files->delete($file['google_file_id']);

            // Delete from database
            $sql = "DELETE FROM file_management WHERE file_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $fileId);
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Error in deleteFile: " . $e->getMessage(),'database');
            throw $e;
        }
    }

    public function getStorageUsage($userId) {
        try {
            $sql = "SELECT SUM(file_size) as total_size 
                    FROM file_management 
                    WHERE user_id = ? AND is_personal = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return [
                'used' => $result['total_size'] ?? 0,
                'limit' => $this->personalLimit,
                'percentage' => ($this->personalLimit > 0) 
                    ? (($result['total_size'] ?? 0) / $this->personalLimit) * 100 : 0
            ];
        } catch (Exception $e) {
            log_error("Error in getStorageUsage: " . $e->getMessage(),'database');
            return false;
        }
    }

    public function fixFilePermissions($userId) {
        try {
            // Get all files for the user
            $sql = "SELECT google_file_id FROM file_management WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $fixed = 0;
            while ($row = $result->fetch_assoc()) {
                try {
                    // Check if permission already exists
                    $permissions = $this->service->permissions->listPermissions($row['google_file_id']);
                    $hasPublicAccess = false;
                    foreach ($permissions as $permission) {
                        if ($permission->type === 'anyone' && $permission->role === 'reader') {
                            $hasPublicAccess = true;
                            break;
                        }
                    }

                    if (!$hasPublicAccess) {
                        // Add public access permission
                        $permission = new Google_Service_Drive_Permission([
                            'type' => 'anyone',
                            'role' => 'reader',
                            'allowFileDiscovery' => false
                        ]);
                        
                        $this->service->permissions->create($row['google_file_id'], $permission, [
                            'supportsAllDrives' => true,
                            'sendNotificationEmail' => false
                        ]);
                        $fixed++;
                    }
                } catch (Exception $e) {
                    log_error("Error fixing permissions for file {$row['google_file_id']}: " . $e->getMessage(), 'database');
                    continue;
                }
            }
            
            return $fixed;
        } catch (Exception $e) {
            log_error("Error in fixFilePermissions: " . $e->getMessage(), 'database');
            throw $e;
        }
    }
}

/**
 * Format bytes into a human-readable string.
 *
 * @param int $bytes The number of bytes.
 * @param int $precision The number of decimal places to include.
 * @return string The formatted string.
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    if ($bytes < 1) return '0 B';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
}

/**
 * Get the file icon based on the file type.
 *
 * @param string $fileType The MIME type of the file.
 * @return string The corresponding icon class or image URL.
 */
function getFileIcon($fileType) {
    $iconMap = [
        'image/jpeg' => 'icon-image',
        'image/png' => 'icon-image',
        'application/pdf' => 'icon-pdf',
        'application/msword' => 'icon-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'icon-word',
        'application/vnd.ms-excel' => 'icon-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'icon-excel',
        'application/zip' => 'icon-zip',
        'text/plain' => 'icon-text',
        // Add more mappings as needed
    ];

    return isset($iconMap[$fileType]) ? $iconMap[$fileType] : 'icon-file'; // Default icon
}

/**
 * Get the standardized file type based on extension
 * @param string $extension File extension
 * @return string|false Standardized file type or false if not allowed
 */
function getFileType($extension) {
    $allowedTypes = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Text
        'txt' => 'text/plain',
        // Archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed'
    ];
    
    $extension = strtolower($extension);
    return isset($allowedTypes[$extension]) ? $allowedTypes[$extension] : false;
}

/**
 * Update student's storage usage
 * @param int $studentId Student ID
 * @param int $bytes Number of bytes to add (positive) or remove (negative)
 * @return bool Whether the update was successful
 */
function updateStudentStorage($studentId, $bytes) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Get current storage usage
        $stmt = $conn->prepare("SELECT storage_used FROM student_storage WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Create new storage record if doesn't exist
            $stmt = $conn->prepare("INSERT INTO student_storage (student_id, storage_used) VALUES (?, ?)");
            $stmt->bind_param("ii", $studentId, $bytes);
            $stmt->execute();
        } else {
            // Update existing storage record
            $currentUsage = $result->fetch_assoc()['storage_used'];
            $newUsage = $currentUsage + $bytes;
            
            // Check if new usage would exceed limit
            if ($newUsage > FileManagement::PERSONAL_STORAGE_LIMIT) {
                throw new Exception("Storage limit exceeded");
            }
            
            $stmt = $conn->prepare("UPDATE student_storage SET storage_used = ? WHERE student_id = ?");
            $stmt->bind_param("ii", $newUsage, $studentId);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        log_error($e->getMessage(),'database');
        return false;
    }
}

/**
 * Change permission level on a file/folder
 * 
 * @param string $fileId The ID of the file/folder
 * @param string $email The email address to set the permission for
 * @param string $role The role to assign ('reader', 'writer', 'commenter')
 * @return void
 */
function changePermission($fileId, $email, $role) {
    try {
        // Define the permission for the email and the role
        $permission = new Google_Service_Drive_Permission([
            'type' => 'user',   // Type can be 'user', 'group', 'domain', or 'anyone'
            'role' => $role,    // 'reader', 'writer', 'commenter'
            'emailAddress' => $email,  // The user's email
        ]);
        
        // Create or update the permission
        $this->service->permissions->create($fileId, $permission);
        echo "Permission updated successfully!";
    } catch (Exception $e) {
        echo 'Error updating permission: ' . $e->getMessage();
    }
}

/**
 * Remove permission for a user
 * 
 * @param string $fileId The ID of the file/folder
 * @param string $permissionId The ID of the permission to delete
 * @return void
 */
function removePermission($fileId, $permissionId) {
    try {
        $this->service->permissions->delete($fileId, $permissionId);
        echo "Permission removed successfully!";
    } catch (Exception $e) {
        echo 'Error removing permission: ' . $e->getMessage();
    }
}
