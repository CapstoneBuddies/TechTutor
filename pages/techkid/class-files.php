<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'unified_file_management.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';

// Verify session and role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    header("Location: " . BASE . "login");
    exit();
}

// Initialize file management
$fileManager = new UnifiedFileManagement();

try {    
    // Get storage usage
    $storageInfo = $fileManager->getStorageInfo($_SESSION['user']);
    
    } catch (Exception $e) {
    log_error("Error in files page: " . $e->getMessage());
    }

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Check if student is enrolled in the class
$check = checkStudentEnrollment($_SESSION['user'], $class_id);
if(!$check) {
    header("location: ".BASE."dashboard/s/enrollments/class?id=".$class_id);
    exit();
}

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
    <!-- Page Loader -->
    <div id="page-loader">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="loading-text">Loading content...</div>
    </div>
    
    <script>
        // This will be automatically hidden when DOMContentLoaded fires in head.php
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize file page specific components
            initializeFilesPage();
        });
        
        function initializeFilesPage() {
            // Initialize any file-specific components here
            setupFolderToggle();
            setupFullscreenToggle();
        }
        
        function setupFolderToggle() {
            // Toggle folder list on mobile
            const toggleFoldersBtn = document.getElementById('toggle-folders');
            const folderList = document.getElementById('folder-list');
            
            if (toggleFoldersBtn && folderList) {
                toggleFoldersBtn.addEventListener('click', function() {
                    folderList.classList.toggle('show-mobile');
                });
            }
        }
        
        function setupFullscreenToggle() {
            // Fullscreen toggle functionality
            const fullscreenBtn = document.getElementById('toggle-fullscreen');
            const viewerContainer = document.getElementById('viewer-container');
            
            if (fullscreenBtn && viewerContainer) {
                fullscreenBtn.addEventListener('click', function() {
                    if (!document.fullscreenElement) {
                        // Enter fullscreen
                        if (viewerContainer.requestFullscreen) {
                            viewerContainer.requestFullscreen();
                        } else if (viewerContainer.webkitRequestFullscreen) { /* Safari */
                            viewerContainer.webkitRequestFullscreen();
                        } else if (viewerContainer.msRequestFullscreen) { /* IE11 */
                            viewerContainer.msRequestFullscreen();
                        }
                        fullscreenBtn.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
                    } else {
                        // Exit fullscreen
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) { /* Safari */
                            document.webkitExitFullscreen();
                        } else if (document.msExitFullscreen) { /* IE11 */
                            document.msExitFullscreen();
                        }
                        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
                    }
                });
                
                // Update button icon when exiting fullscreen by other means (Esc key, etc.)
                document.addEventListener('fullscreenchange', function() {
                    if (!document.fullscreenElement) {
                        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
                    }
                });
            }
        }
    </script>
    
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <div class="dashboard-content bg">
            <!-- Header Section -->
            <div class="content-section mb-4">
                <div class="content-card bg-snow">
            <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div>
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
                            <a href="details?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to Class
                            </a>
                        </div>
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
                                    <div class="folder-header p-3 border-bottom d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-folder2 me-2"></i>
                                            <span class="fw-bold">Folders</span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary d-md-none" id="toggle-folders">
                                            <i class="bi bi-list"></i>
                                        </button>
                                    </div>
                                    <div class="folder-content p-2" id="folder-list">
                                        <?php 
                                        $folders = array_filter($files, function($item) {
                                            return isset($item['folder_id']);
                                        });
                                        
                                        $file_items = array_filter($files, function($item) {
                                            return isset($item['file_id']);
                                        });
                                        
                                        // Show parent folder link if in subfolder
                                        if ($current_folder): 
                                            $parent_folder = dirname($current_folder);
                                            if ($parent_folder == '.') $parent_folder = '';
                                            ?>
                                            <a href="?id=<?php echo $class_id; ?>&folder=<?php echo urlencode($parent_folder); ?>" 
                                               class="folder-item d-flex align-items-center p-2 text-decoration-none">
                                                <i class="bi bi-arrow-up-circle me-2"></i>
                                                <span>Back to Parent</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?id=<?php echo $class_id; ?>" 
                                           class="folder-item d-flex align-items-center p-2 text-decoration-none <?php echo (!$current_folder && !$current_file) ? 'active' : ''; ?>">
                                            <i class="bi bi-house-door me-2"></i>
                                            <span>Root Directory</span>
                                        </a>
                                        
                                        <?php foreach ($folders as $folder): ?>
                                            <a href="?id=<?php echo $class_id; ?>&folder=<?php echo urlencode($folder['folder_id']); ?>" 
                                               class="folder-item d-flex align-items-center p-2 text-decoration-none <?php echo $current_folder == $folder['folder_id'] ? 'active' : ''; ?>">
                                                <i class="bi bi-folder2 me-2"></i>
                                                <span><?php echo htmlspecialchars($folder['folder_name']); ?></span>
                                                <span class="ms-auto badge bg-light text-dark rounded-pill"><?php echo isset($folder['file_count']) ? $folder['file_count'] : 0; ?></span>
                                            </a>
                                        <?php endforeach; ?>

                                        <?php foreach ($file_items as $file): ?>
                                            <a href="?id=<?php echo $class_id; ?>&folder=<?php echo urlencode($current_folder); ?>&file=<?php echo urlencode($file['file_id']); ?>" 
                                               class="file-item d-flex align-items-center p-2 text-decoration-none <?php echo $current_file == $file['file_id'] ? 'active' : ''; ?>"
                                               data-file-id="<?php echo $file['file_id']; ?>">
                                                <?php 
                                                $fileExt = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                                                $iconClass = 'bi-file-earmark-text';
                                                
                                                if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                    $iconClass = 'bi-file-earmark-image';
                                                } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                                    $iconClass = 'bi-file-earmark-word';
                                                } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
                                                    $iconClass = 'bi-file-earmark-excel';
                                                } elseif (in_array($fileExt, ['ppt', 'pptx'])) {
                                                    $iconClass = 'bi-file-earmark-slides';
                                                } elseif ($fileExt == 'pdf') {
                                                    $iconClass = 'bi-file-earmark-pdf';
                                                } elseif (in_array($fileExt, ['mp4', 'avi', 'mov'])) {
                                                    $iconClass = 'bi-file-earmark-play';
                                                }
                                                ?>
                                                <i class="bi <?php echo $iconClass; ?> me-2"></i>
                                                <span class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></span>
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
                                            // Determine file type and set viewer accordingly
                                            $fileExt = strtolower(pathinfo($file_details['file_name'], PATHINFO_EXTENSION));
                                            $isViewable = in_array($fileExt, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif']);
                                        ?>
                                            <div class="file-details p-4">
                                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
                                                    <div>
                                                        <h4 class="mb-2"><?php echo htmlspecialchars($file_details['file_name']); ?></h4>
                                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                                            <span class="badge bg-light text-dark">
                                                                <i class="bi bi-person"></i> 
                                                                <?php echo htmlspecialchars($file_details['uploader_name']); ?>
                                                            </span>
                                                            <span class="badge bg-light text-dark">
                                                                <i class="bi bi-calendar"></i> 
                                                                <?php echo date('M d, Y', strtotime($file_details['upload_time'])); ?>
                                                            </span>
                                                            <span class="badge bg-light text-dark">
                                                                <i class="bi bi-hdd"></i> 
                                                                <?php echo $fileManager->formatFileSize($file_details['file_size']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <a href="https://drive.google.com/uc?export=download&id=<?php echo $file_details['google_file_id']; ?>" 
                                                           class="btn btn-primary" download>
                                                        <i class="bi bi-download me-2"></i>
                                                        Download
                                                        </a>
                                                        <button class="btn btn-outline-secondary" id="toggle-fullscreen">
                                                            <i class="bi bi-arrows-fullscreen"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($file_details['description'])): ?>
                                                    <div class="file-description mb-4">
                                                        <h5 class="border-bottom pb-2 mb-3">Description</h5>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($file_details['description'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="file-viewer-container" id="viewer-container">
                                                    <?php if ($isViewable): ?>
                                                        <iframe class="file-viewer" 
                                                                id="file-viewer"
                                                                src="https://drive.google.com/file/d/<?php echo $file_details['google_file_id']; ?>/preview" 
                                                                allowfullscreen 
                                                                allow="autoplay; encrypted-media; picture-in-picture"
                                                                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-presentation">
                                                        </iframe>
                                                    <?php else: ?>
                                                        <div class="text-center text-muted py-5 bg-light rounded">
                                                            <i class="bi bi-exclamation-circle" style="font-size: 3rem;"></i>
                                                            <p class="mt-3 mb-1">This file type cannot be previewed</p>
                                                            <p class="mb-3">Please download the file to view it</p>
                                                            <a href="https://drive.google.com/uc?export=download&id=<?php echo $file_details['google_file_id']; ?>" 
                                                               class="btn btn-primary" download>
                                                                <i class="bi bi-download me-2"></i>
                                                                Download Now
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
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
</main>

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
            transition: all 0.2s ease;
            }
            .folder-item:hover, .file-item:hover {
                background-color: #e9ecef;
            }
        .folder-item.active, .file-item.active {
                background-color: var(--bs-primary);
                color: white;
            }
        .folder-item.active i, .file-item.active i,
        .folder-item.active .badge, .file-item.active .badge {
            color: white !important;
            }
            .file-preview {
                flex: 1;
                overflow-y: auto;
                background: white;
            }
            .file-details {
            max-width: 900px;
                margin: 0 auto;
            }
            .file-description {
            padding-top: 1rem;
                border-top: 1px solid #e9ecef;
            }
        .file-viewer-container {
            position: relative;
            width: 100%;
            height: 60vh;
            margin: 0 auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .file-viewer {
            width: 100%;
            height: 100%;
            border: none;
        }
        .file-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        /* Fullscreen styles */
        .file-viewer-container:fullscreen {
            padding: 0;
            width: 100vw;
            height: 100vh;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
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
                max-height: 200px;
                    display: none;
                }
            .folder-content.show-mobile {
                display: block;
            }
            .file-preview {
                padding: 1rem;
            }
            .file-viewer-container {
                height: 50vh;
            }
            .file-details {
                padding: 1rem !important;
            }
        }
    </style>
</body>
</html>