<?php
require_once __DIR__ . '/../../assets/vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../main.php';

use Google\Client;
use Google\Service\Drive;

class FileManagement {
    private $client;
    private $service;
    private $conn;
    
    // Constants for file limits
    const PERSONAL_STORAGE_LIMIT = 1073741824; // 1GB in bytes
    const CLASS_FILE_LIMIT = 52428800;         // 50MB in bytes

    public function __construct() {
        global $conn;
        $this->conn = $conn;

        try {
            // Initialize Google Client
            $this->client = new Client();
            $this->client->setAuthConfig(__DIR__ . '/../credentials.json');
            $this->client->addScope(Drive::DRIVE_FILE);
            $this->service = new Drive($this->client);
        } catch (Exception $e) {
            log_error($e->getMessage(), "google_drive_init");
            throw new Exception("Failed to initialize Google Drive service");
        }
    }

    /**
     * Get or create a folder in Google Drive
     */
    private function getOrCreateFolder($folderName, $parentId = null) {
        try {
            // Search for existing folder
            $query = "mimeType='application/vnd.google-apps.folder' and name='" . $folderName . "'";
            if ($parentId) {
                $query .= " and '" . $parentId . "' in parents";
            }
            
            $results = $this->service->files->listFiles([
                'q' => $query,
                'spaces' => 'drive'
            ]);

            if (count($results->getFiles()) > 0) {
                return $results->getFiles()[0]->getId();
            }

            // Create new folder if not found
            $folderMetadata = new Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            
            if ($parentId) {
                $folderMetadata->setParents([$parentId]);
            }

            $folder = $this->service->files->create($folderMetadata, [
                'fields' => 'id'
            ]);

            return $folder->getId();
        } catch (Exception $e) {
            log_error($e->getMessage(), "folder_creation");
            throw new Exception("Failed to create/get folder");
        }
    }

    /**
     * Upload a file to Google Drive
     */
    public function uploadFile($file, $userId, $classId = null, $description = '', $requestId = null) {
        try {
            // Validate file size
            $fileSize = $file['size'];
            if ($classId && $fileSize > self::CLASS_FILE_LIMIT) {
                throw new Exception("Class files must be under 50MB");
            }

            // Check personal storage limit if it's a personal file
            if (!$classId) {
                $currentUsage = $this->getStorageInfo($userId)['used'];
                if (($currentUsage + $fileSize) > self::PERSONAL_STORAGE_LIMIT) {
                    throw new Exception("Personal storage limit (1GB) exceeded");
                }
            }

            // Get or create appropriate folder
            $folderId = null;
            if ($classId) {
                // Get class name for folder
                $stmt = $this->conn->prepare("SELECT class_name FROM class WHERE class_id = ?");
                $stmt->bind_param("i", $classId);
                $stmt->execute();
                $className = $stmt->get_result()->fetch_assoc()['class_name'];
                $folderId = $this->getOrCreateFolder($className);
            } else {
                // Personal folder named with user's ID
                $folderId = $this->getOrCreateFolder("user_" . $userId);
            }

            // Upload file to Google Drive
            $fileMetadata = new Drive\DriveFile([
                'name' => $file['name'],
                'parents' => [$folderId]
            ]);

            $content = file_get_contents($file['tmp_name']);
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file['type'],
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);

            // Save to database
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("
                INSERT INTO file_management 
                (class_id, user_id, file_name, file_type, file_size, google_file_id, 
                drive_link, folder_id, description, is_personal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $isPersonal = $classId ? 0 : 1;
            $stmt->bind_param(
                "isssissssi",
                $classId,
                $userId,
                $file['name'],
                $file['type'],
                $fileSize,
                $uploadedFile->getId(),
                $uploadedFile->getWebViewLink(),
                $folderId,
                $description,
                $isPersonal
            );
            $stmt->execute();
            $fileId = $this->conn->insert_id;

            // Update upload request if exists
            if ($requestId) {
                $stmt = $this->conn->prepare("
                    UPDATE file_upload_requests 
                    SET file_id = ?, status = 'completed'
                    WHERE request_id = ? AND student_id = ?
                ");
                $stmt->bind_param("iii", $fileId, $requestId, $userId);
                $stmt->execute();
            }

            $this->conn->commit();
            return $fileId;

        } catch (Exception $e) {
            $this->conn->rollback();
            log_error($e->getMessage(), "file_upload");
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get personal files for a student
     */
    public function getPersonalFiles($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM file_management 
                WHERE user_id = ? AND is_personal = 1
                ORDER BY upload_time DESC
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error($e->getMessage(), "get_personal_files");
            return [];
        }
    }

    /**
     * Get all accessible class files for a student
     */
    public function getAccessibleFiles($userId) {
        try {
            $query = "
                SELECT f.*, c.class_name, CONCAT(u.first_name, ' ', u.last_name) as uploader_name
                FROM file_management f
                JOIN class c ON f.class_id = c.class_id
                JOIN users u ON f.user_id = u.uid
                JOIN enrollments e ON c.class_id = e.class_id
                WHERE e.student_id = ? AND e.status = 'active'
                AND (f.is_visible = 1 OR EXISTS (
                    SELECT 1 FROM file_access fa 
                    WHERE fa.file_id = f.file_id AND fa.enrollment_id = e.enrollment_id
                ))
                ORDER BY c.class_name, f.upload_time DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $userId);
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
            log_error($e->getMessage(), "get_accessible_files");
            return [];
        }
    }

    /**
     * Get upload requests for a student
     */
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
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error($e->getMessage(), "get_upload_requests");
            return [];
        }
    }

    /**
     * Get storage usage information for a student
     */
    public function getStorageInfo($userId) {
        try {
            $query = "
                SELECT COALESCE(SUM(file_size), 0) as total_size
                FROM file_management
                WHERE user_id = ? AND is_personal = 1
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $used = $result['total_size'];
            $limit = self::PERSONAL_STORAGE_LIMIT;
            $percentage = min(round(($used / $limit) * 100, 2), 100);
            
            return [
                'used' => $used,
                'limit' => $limit,
                'percentage' => $percentage
            ];
        } catch (Exception $e) {
            log_error($e->getMessage(), "get_storage_info");
            return [
                'used' => 0,
                'limit' => self::PERSONAL_STORAGE_LIMIT,
                'percentage' => 0
            ];
        }
    }

    /**
     * Delete a file
     */
    public function deleteFile($fileId, $userId) {
        try {
            // Get file info and verify ownership
            $stmt = $this->conn->prepare("
                SELECT google_file_id 
                FROM file_management 
                WHERE file_id = ? AND user_id = ? AND is_personal = 1
            ");
            $stmt->bind_param("ii", $fileId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($file = $result->fetch_assoc()) {
                $this->conn->begin_transaction();
                
                // Delete from Google Drive
                $this->service->files->delete($file['google_file_id']);
                
                // Delete from database
                $stmt = $this->conn->prepare("
                    DELETE FROM file_management 
                    WHERE file_id = ?
                ");
                $stmt->bind_param("i", $fileId);
                $stmt->execute();
                
                $this->conn->commit();
                return true;
            }
            return false;
        } catch (Exception $e) {
            $this->conn->rollback();
            log_error($e->getMessage(), "delete_file");
            throw new Exception("Failed to delete file");
        }
    }
}
