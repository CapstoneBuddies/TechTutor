<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'unified_file_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id, $_SESSION['user']);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Get current folder ID from URL parameter
$current_folder_id = isset($_GET['folder']) ? intval($_GET['folder']) : 0;

// Get folders and files
$folders = getClassFolders($class_id);
$files = $current_folder_id ? getFolderFiles($current_folder_id) : getClassFiles($class_id);

// Helper function for file icons
function getFileIconClass($fileType) {
    $iconMap = [
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'application/pdf' => 'pdf',
        'application/msword' => 'word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        'application/vnd.ms-excel' => 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'text/plain' => 'text',
        'text/csv' => 'csv',
        'application/json' => 'code',
        'text/html' => 'code',
        'text/css' => 'code',
        'application/javascript' => 'code',
        'video/mp4' => 'play',
        'video/quicktime' => 'play',
        'audio/mpeg' => 'music',
        'audio/mp3' => 'music'
    ];
    
    if (isset($iconMap[$fileType])) {
        return $iconMap[$fileType];
    }
    
    // If file type contains "word", "excel", etc.
    $lowerType = strtolower($fileType);
    if (strpos($lowerType, 'word') !== false) return 'word';
    if (strpos($lowerType, 'excel') !== false || strpos($lowerType, 'sheet') !== false) return 'excel';
    if (strpos($lowerType, 'powerpoint') !== false || strpos($lowerType, 'presentation') !== false) return 'ppt';
    if (strpos($lowerType, 'pdf') !== false) return 'pdf';
    if (strpos($lowerType, 'image') !== false) return 'image';
    if (strpos($lowerType, 'zip') !== false || strpos($lowerType, 'archive') !== false) return 'zip';
    if (strpos($lowerType, 'text') !== false) return 'text';
    if (strpos($lowerType, 'video') !== false) return 'play';
    if (strpos($lowerType, 'audio') !== false) return 'music';
    if (strpos($lowerType, 'code') !== false || strpos($lowerType, 'html') !== false || 
        strpos($lowerType, 'css') !== false || strpos($lowerType, 'javascript') !== false) return 'code';
    
    // Default
    return 'text';
}

// Find current folder details if viewing a folder
$current_folder = null;
if ($current_folder_id) {
    foreach ($folders as $folder) {
        if ($folder['id'] == $current_folder_id) {
            $current_folder = $folder;
            break;
        }
    }
}

$title = "Class Files - " . htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .file-explorer {
            border-radius: 0.5rem;
            border: 1px solid rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .explorer-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem;
        }
        .explorer-content {
            padding: 1rem;
        }
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .folder-item {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            transition: transform 0.2s, background-color 0.2s;
            background-color: #ffffff;
            cursor: pointer;
        }
        .folder-item:hover {
            transform: translateY(-2px);
            background-color: #f8f9fa;
        }
        .folder-icon {
            color: #ffc107;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
        }
        .file-item {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            transition: transform 0.2s, background-color 0.2s;
            background-color: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .file-item:hover {
            transform: translateY(-2px);
            background-color: #f8f9fa;
        }
        .file-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--bs-primary);
        }
        .file-name {
            margin-bottom: 0.25rem;
            font-weight: 500;
            word-break: break-word;
        }
        .file-info {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }
        .file-actions {
            margin-top: auto;
        }
        .folder-path {
            background-color: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .folder-path a {
            color: var(--bs-primary);
            text-decoration: none;
        }
        .folder-path a:hover {
            text-decoration: underline;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .breadcrumb-item.active {
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .folder-grid, .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <!-- Header Section -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="././">My Classes</a></li>
                                        <li class="breadcrumb-item"><a href="./?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                        <li class="breadcrumb-item active">Class Files</li>
                                    </ol>
                                </nav>
                                <h2 class="page-header">
                                    <?php echo htmlspecialchars($classDetails['class_name']); ?> - Files
                                </h2>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="uploadMaterial()">
                                    <i class="bi bi-upload me-1"></i> Upload Material
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Explorer -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="file-explorer">
                        <div class="explorer-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">File Explorer</h4>
                                <?php if ($current_folder): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showNewFolderInput()">
                                    <i class="bi bi-folder-plus me-1"></i> New Folder
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="explorer-content">
                            <!-- Folder Path -->
                            <div class="folder-path">
                                <i class="bi bi-folder me-2"></i>
                                <a href="files?id=<?php echo $class_id; ?>">Root</a>
                                <?php if ($current_folder): ?>
                                <i class="bi bi-chevron-right mx-1"></i>
                                <span><?php echo htmlspecialchars($current_folder['folder_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (empty($folders) && empty($files) && !$current_folder_id): ?>
                            <!-- Empty State -->
                            <div class="empty-state">
                                <i class="bi bi-folder"></i>
                                <h5>No files or folders yet</h5>
                                <p class="mb-3">Upload materials to help your students learn better</p>
                                <button class="btn btn-primary" onclick="uploadMaterial()">
                                    <i class="bi bi-upload me-1"></i> Upload Material
                                </button>
                            </div>
                            <?php else: ?>
                            
                            <?php if (!$current_folder_id && !empty($folders)): ?>
                            <!-- Folders Grid (only in root view) -->
                            <div>
                                <h5>Folders</h5>
                                <div class="folder-grid">
                                    <?php foreach ($folders as $folder): ?>
                                    <div class="folder-item" onclick="navigateToFolder(<?php echo $folder['id']; ?>)">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-folder-fill folder-icon"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($folder['folder_name']); ?></h6>
                                                <small class="text-muted"><?php echo $folder['file_count']; ?> files</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($files)): ?>
                            <!-- Files Grid -->
                            <div>
                                <h5><?php echo $current_folder_id ? 'Folder Contents' : 'Files'; ?></h5>
                                <div class="file-grid">
                                    <?php foreach ($files as $file): ?>
                                    <div class="file-item">
                                        <div class="file-icon">
                                            <i class="bi bi-file-earmark-<?php echo getFileIconClass($file['file_type']); ?>"></i>
                                        </div>
                                        <div class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></div>
                                        <div class="file-info">
                                            <div>Size: <?php echo formatBytes($file['file_size']); ?></div>
                                            <div>Uploaded: <?php echo date('M d, Y', strtotime($file['upload_time'])); ?></div>
                                            <div>By: <?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?></div>
                                        </div>
                                        <div class="file-actions">
                                            <div class="btn-group w-100">
                                                <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                                <?php if ($file['user_id'] === $_SESSION['user']): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="event.stopPropagation(); deleteMaterial('<?php echo $file['file_id']; ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php elseif ($current_folder_id): ?>
                            <!-- Empty Folder State -->
                            <div class="empty-state">
                                <i class="bi bi-folder"></i>
                                <h5>This folder is empty</h5>
                                <p class="mb-3">Upload materials to this folder</p>
                                <button class="btn btn-primary" onclick="uploadMaterial(<?php echo $current_folder_id; ?>)">
                                    <i class="bi bi-upload me-1"></i> Upload Material
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Upload Material Modal -->
        <div class="modal fade" id="uploadMaterialModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="uploadForm">
                            <div class="mb-3">
                                <label class="form-label">Folder</label>
                                <div class="input-group">
                                    <select class="form-select" id="materialFolder">
                                        <option value="">Root Folder</option>
                                        <?php foreach ($folders as $folder): ?>
                                            <option value="<?php echo $folder['id']; ?>" <?php echo ($current_folder_id == $folder['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($folder['folder_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" onclick="showNewFolderInput()">
                                        <i class="bi bi-folder-plus"></i> New
                                    </button>
                                </div>
                            </div>
                            <div id="newFolderInput" class="mb-3 d-none">
                                <label class="form-label">New Folder Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="newFolderName">
                                    <button type="button" class="btn btn-success" onclick="createFolder()">
                                        <i class="bi bi-check-lg"></i> Create
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="hideNewFolderInput()">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File</label>
                                <input type="file" class="form-control" id="materialFile" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="materialDescription" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitMaterial()">Upload</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            let uploadModal;
            let selectedFolderId = <?php echo $current_folder_id ?: 'null'; ?>;

            document.addEventListener('DOMContentLoaded', function() {
                // Initialize modals
                uploadModal = new bootstrap.Modal(document.getElementById('uploadMaterialModal'));
                
                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
            });
            
            function navigateToFolder(folderId) {
                window.location.href = `files?id=<?php echo $class_id; ?>&folder=${folderId}`;
            }

            function uploadMaterial(folderId) {
                if (folderId) {
                    document.getElementById('materialFolder').value = folderId;
                }
                uploadModal.show();
            }

            function showNewFolderInput() {
                document.getElementById('newFolderInput').classList.remove('d-none');
            }

            function hideNewFolderInput() {
                document.getElementById('newFolderInput').classList.add('d-none');
                document.getElementById('newFolderName').value = '';
            }

            function createFolder() {
                const folderName = document.getElementById('newFolderName').value.trim();
                if (!folderName) {
                    showToast('warning', 'Please enter a folder name');
                    return;
                }

                showLoading(true);
                fetch(BASE + 'api/materials?action=create-folder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: <?php echo $class_id; ?>,
                        folder_name: folderName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast('success', 'Folder created successfully');
                        location.reload();
                    } else {
                        showToast('error', 'Failed to create folder');
                        logError(data.message, 'create-folder', 'class-file');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('error', 'Failed to create folder');
                });
            }

            function submitMaterial() {
                const file = document.getElementById('materialFile').files[0];
                const description = document.getElementById('materialDescription').value;
                const folderId = document.getElementById('materialFolder').value;

                if (!file) {
                    showToast('error', 'Please select a file to upload');
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('description', description);
                formData.append('class_id', '<?php echo $class_id; ?>');
                formData.append('folder_id', folderId);
                formData.append('action', 'upload');

                showLoading(true);

                fetch(BASE + 'api/materials?action=upload', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        uploadModal.hide();
                        showToast('success', 'Material uploaded successfully');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('error', 'Failed to upload material');
                        logError(data.message, 'upload', 'class-file');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while uploading');
                });
            }

            function deleteMaterial(fileId) {
                if (!confirm('Are you sure you want to delete this material?')) return;

                showLoading(true);

                fetch(BASE + 'api/materials?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        file_id: fileId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast('success', 'Material deleted successfully');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('error', 'Failed to delete material');
                        logError(data.message, 'delete-material', 'class-file');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while deleting');
                });
            }
        </script>
    </body>
</html>