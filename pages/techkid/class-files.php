<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'file_management.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';

// Verify session and role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    header("Location: " . BASE . "login");
    exit();
}

// Initialize file management
$fileManager = new FileManagement();

try {
    // Get student's personal files
    $personalFiles = $fileManager->getPersonalFiles($_SESSION['user']);
    
    // Get storage usage
    $storageInfo = $fileManager->getStorageUsage($_SESSION['user']);
    
    } catch (Exception $e) {
    error_log("Error in files page: " . $e->getMessage());
    }

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Get class files organized by folders
$files = $fileManager->getClassFiles($class_id);

$title = "Class Resources - " . htmlspecialchars($classDetails['class_name']);

// Get current folder and file
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$current_file = isset($_GET['file']) ? $_GET['file'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content bg">
            <!-- Header Section -->
            <div class="content-section mb-4">
                <div class="content-card bg-snow">
            <div class="card-body">
                        <nav aria-label="breadcrumb" class="breadcrumb-nav">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                <li class="breadcrumb-item"><a href="details?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                <li class="breadcrumb-item active">Resources</li>
                            </ol>
                        </nav>
                        <h1 class="page-title mb-0">Class Resources</h1>
                </div>
            </div>
        </div>

            <!-- Files Section -->
            <div class="content-section">
                <div class="content-card">
                    <div class="card-body p-0">
                                <?php if (empty($files)): ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-folder2-open" style="font-size: 3rem;"></i>
                                        <p class="mt-3 mb-0">No resources available yet</p>
                                    </div>
                                <?php else: ?>
                            <div class="files-container">
                                <!-- Folder Structure (Left Side) -->
                                <div class="folder-structure">
                                    <div class="folder-header p-3 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-folder2 me-2"></i>
                                            <span class="fw-bold"><?php echo htmlspecialchars($classDetails['class_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="folder-content p-2">
                                        <?php 
                                        $items = $fileManager->getFolderContents($class_id, $current_folder);
                                        
                                        // Show parent folder link if in subfolder
                                        if ($current_folder) {
                                            $parent_folder = dirname($current_folder);
                                            if ($parent_folder == '.') $parent_folder = '';
                                            ?>
                                            <a href="?id=<?php echo $class_id; ?>&folder=<?php echo urlencode($parent_folder); ?>" 
                                               class="folder-item d-flex align-items-center p-2 text-decoration-none">
                                                <i class="bi bi-arrow-up me-2"></i>
                                                <span>Parent Folder</span>
                                            </a>
                                            <?php
                                        }
                                        
                                        // Display folders first
                                        foreach ($items['folders'] as $folder): 
                                            $folder_path = $current_folder ? $current_folder . '/' . $folder : $folder;
                                        ?>
                                            <a href="?id=<?php echo $class_id; ?>&folder=<?php echo urlencode($folder_path); ?>" 
                                               class="folder-item d-flex align-items-center p-2 text-decoration-none">
                                                <i class="bi bi-folder2 me-2"></i>
                                                <span><?php echo htmlspecialchars($folder); ?></span>
                                            </a>
                                        <?php endforeach; ?>

                                        <!-- Display files -->
                                        <?php foreach ($items['files'] as $file): ?>
                                            <a href="?id=<?php echo $class_id; ?>&folder=<?php echo urlencode($current_folder); ?>&file=<?php echo urlencode($file['file_id']); ?>" 
                                               class="file-item d-flex align-items-center p-2 text-decoration-none <?php echo $current_file == $file['file_id'] ? 'active' : ''; ?>"
                                               data-file-id="<?php echo $file['file_id']; ?>">
                                                <i class="bi bi-file-earmark-text me-2"></i>
                                                <span><?php echo htmlspecialchars($file['file_name']); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- File Preview (Right Side) -->
                                <div class="file-preview">
                                    <?php if ($current_file): ?>
                                        <?php 
                                        $file_details = $fileManager->getFileDetails($current_file);
                                        if ($file_details):
                                        ?>
                                            <div class="file-details p-4">
                                                <div class="d-flex justify-content-between align-items-start mb-4">
                                                    <div>
                                                        <h4 class="mb-2"><?php echo htmlspecialchars($file_details['file_name']); ?></h4>
                                                        <p class="text-muted mb-0">
                                                            Uploaded by <?php echo htmlspecialchars($file_details['uploader_name']); ?> on 
                                                            <?php echo date('F d, Y', strtotime($file_details['upload_time'])); ?>
                                                        </p>
                                                        <p class="text-muted mb-0">
                                                            Size: <?php echo formatFileSize($file_details['file_size']); ?>
                                                        </p>
                                                    </div>
                                                    <a href="<?php echo BASE . 'uploads/class/' . $file_details['file_path']; ?>" 
                                                       class="btn btn-primary" 
                                                           download>
                                                        <i class="bi bi-download me-2"></i>
                                                        Download
                                                        </a>
                                                </div>
                                                <?php if ($file_details['description']): ?>
                                                    <div class="file-description">
                                                        <h5>Description</h5>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($file_details['description'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-file-earmark-text" style="font-size: 3rem;"></i>
                                            <p class="mt-3 mb-0">Select a file to view details</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

        <style>
            .dashboard-content {
                background-color: #F5F5F5;
                min-height: calc(100vh - 60px);
                padding: 1.5rem;
                border-radius: 12px;
            }
            .content-card {
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            .page-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0;
            }
            .files-container {
                display: flex;
                min-height: 500px;
            }
            .folder-structure {
                width: 300px;
                border-right: 1px solid #e9ecef;
                overflow-y: auto;
                background: #f8f9fa;
            }
            .folder-content {
                height: calc(100vh - 250px);
                overflow-y: auto;
            }
            .folder-item, .file-item {
                color: #212529;
                border-radius: 4px;
                margin-bottom: 2px;
            }
            .folder-item:hover, .file-item:hover {
                background-color: #e9ecef;
            }
            .file-item.active {
                background-color: var(--bs-primary);
                color: white;
            }
            .file-preview {
                flex: 1;
                overflow-y: auto;
                background: white;
            }
            .file-details {
                max-width: 800px;
                margin: 0 auto;
            }
            .file-description {
                margin-top: 2rem;
                padding-top: 2rem;
                border-top: 1px solid #e9ecef;
            }
            @media (max-width: 768px) {
                .dashboard-content {
                    padding: 1rem;
                }
                .files-container {
                    flex-direction: column;
                }
                .folder-structure {
                    width: 100%;
                    border-right: none;
                    border-bottom: 1px solid #e9ecef;
                }
                .folder-content {
                    height: auto;
                    max-height: 300px;
                }
                .file-preview {
                    display: none;
                }
                .file-item {
                    cursor: pointer;
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // For mobile: Handle file clicks to trigger download
                if (window.innerWidth <= 768) {
                    document.querySelectorAll('.file-item').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            const fileId = this.dataset.fileId;
                            // Trigger download instead of showing preview
                            window.location.href = BASE + 'download-file?id=' + fileId;
                });
            });
        }
            });

            function formatFileSize(bytes) {
                if (bytes >= 1073741824) {
                    return (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                }
                return bytes + ' bytes';
        }
    </script>
</body>
</html>