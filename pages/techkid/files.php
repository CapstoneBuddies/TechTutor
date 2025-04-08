<?php 
    require_once '../../backends/main.php';
require_once BACKEND.'student_management.php';
require_once BACKEND.'unified_file_management.php';

// Verify session and role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    header("Location: " . BASE . "login");
    exit();
}

// Initialize file management
$fileManager = new UnifiedFileManagement();

try {
    // Get student's personal files
    $personalFiles = $fileManager->getPersonalFiles($_SESSION['user']);
    
    // Get student's personal folders
    $personalFolders = $fileManager->getPersonalFolders($_SESSION['user']);
    
    // Get current folder
    $currentFolderId = isset($_GET['folder']) ? (int)$_GET['folder'] : 0;
    $currentFolder = null;
    $breadcrumbs = [];
    
    if ($currentFolderId > 0) {
        $currentFolder = $fileManager->getFolderById($currentFolderId, $_SESSION['user']);
        if ($currentFolder) {
            // Build breadcrumb navigation
            $folderPath = $fileManager->getFolderPath($currentFolderId);
            $breadcrumbs = $folderPath;
            
            // Get files and subfolders for current folder
            $personalFiles = $fileManager->getFolderFiles(0, $currentFolderId);
            $personalFolders = $fileManager->getSubfolders($currentFolderId);
        }
    }
    
    // Get storage usage
    $storageInfo = $fileManager->getStorageInfo($_SESSION['user'])['personal'];
    
    // Get upload requests
    $uploadRequests = $fileManager->getUploadRequests($_SESSION['user']);

    // Get Student's Class Files
    $enrolledClass = getEnrolledClass($_SESSION['user']);
    $classFiles = [];

    foreach($enrolledClass as $classId) {
        $files = $fileManager->getClassFiles($classId);
        if (!empty($files)) {
            // Get class name from the first file
            $className = '';
            $classIdValue = 0;
            if (!empty($files[0]['class_id'])) {
                $classIdValue = $files[0]['class_id'];
                // Query to get class name
                $stmt = $conn->prepare("SELECT class_name FROM class WHERE class_id = ?");
                $stmt->bind_param("i", $classIdValue);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $className = $row['class_name'];
                }
                $stmt->close();
            }
            // Add to classFiles with className as key
            $classFiles[$className] = [
                'class_id' => $classIdValue,
                'files' => $files
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error in files page: " . $e->getMessage());
}

// Helper function to get appropriate Font Awesome icon based on file type
function getFileIcon($fileType) {
    $icons = [
        'image/' => 'fa-image',
        'video/' => 'fa-video',
        'audio/' => 'fa-music',
        'application/pdf' => 'fa-file-pdf',
        'application/msword' => 'fa-file-word',
        'application/vnd.ms-excel' => 'fa-file-excel',
        'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
        'text/plain' => 'fa-file-alt',
        'text/html' => 'fa-file-code',
        'application/zip' => 'fa-file-archive'
    ];
    
    foreach ($icons as $type => $icon) {
        if (strpos($fileType, $type) === 0) {
            return $icon;
        }
    }
    
    return 'fa-file'; // Default icon
}

$title = "My Files";
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

        <!-- Main Dashboard Content -->
        <main class="dashboard-content">
            <div class="container-fluid">
                <!-- Storage Usage Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Storage Usage</h5>
                                    <div class="btn-group">
                                        <button class="btn btn-primary" onclick="showUploadModal()">
                                            <i class="fas fa-upload me-1"></i> Upload File
                                        </button>
                        </div>
                    </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo number_format($storageInfo['percentage'], 2); ?>%"
                                         aria-valuenow="<?php echo number_format($storageInfo['percentage'], 2); ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $storageInfo['percentage']; ?>%
                                    </div>
                    </div>
                                <small class="text-muted mt-2 d-block">
                                    <?php echo formatBytes($storageInfo['used']); ?> used of <?php echo formatBytes($storageInfo['limit']); ?>
                                </small>
                    </div>
                </div>
            </div>
        </div>

                <!-- Upload Requests Section -->
                <?php if (!empty($uploadRequests)): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Upload Requests</h5>
                    </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-items-center">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Request From</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($uploadRequests as $request): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($request['tutor_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['due_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $request['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" 
                                                            onclick="uploadRequestedFile(<?php echo $request['request_id']; ?>)">
                                                        <i class="fas fa-upload me-1"></i> Upload
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Personal Files Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">My Files</h5>
                                    <button class="btn btn-sm btn-success" onclick="showCreateFolderModal()">
                                        <i class="fas fa-folder-plus me-1"></i> Create Folder
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($currentFolderId > 0 && $currentFolder): ?>
                                <!-- Breadcrumb Navigation -->
                                <nav aria-label="breadcrumb" class="mb-3">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="<?php echo BASE; ?>techkid/files">My Files</a>
                                        </li>
                                        <?php foreach ($breadcrumbs as $i => $folder): ?>
                                            <?php if ($i < count($breadcrumbs) - 1): ?>
                                                <li class="breadcrumb-item">
                                                    <a href="<?php echo BASE; ?>techkid/files?folder=<?php echo $folder['folder_id']; ?>">
                                                        <?php echo htmlspecialchars($folder['folder_name']); ?>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="breadcrumb-item active" aria-current="page">
                                                    <?php echo htmlspecialchars($folder['folder_name']); ?>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ol>
                                </nav>
                                <?php endif; ?>
                                
                                <?php if (empty($personalFolders) && empty($personalFiles)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                                        <p class="text-muted">No personal files or folders created yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Size</th>
                                                    <th>Modified</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Folders -->
                                                <?php foreach ($personalFolders as $folder): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-folder me-2 text-warning"></i>
                                                            <a href="<?php echo BASE; ?>techkid/files?folder=<?php echo $folder['folder_id']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($folder['folder_name']); ?>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>—</td>
                                                    <td><?php echo date('M d, Y', strtotime($folder['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm btn-info" 
                                                                    onclick="renameFolder(<?php echo $folder['folder_id']; ?>, '<?php echo htmlspecialchars($folder['folder_name']); ?>')"
                                                                    data-toggle="tooltip" 
                                                                    title="Rename">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" 
                                                                    onclick="deleteFolder(<?php echo $folder['folder_id']; ?>)"
                                                                    data-toggle="tooltip" 
                                                                    title="Delete Folder">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                
                                                <!-- Files -->
                                                <?php foreach ($personalFiles as $file): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas <?php echo getFileIcon($file['file_type']); ?> me-2"></i>
                                                            <?php echo htmlspecialchars($file['file_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo formatBytes($file['file_size']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($file['upload_time'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <?php if ($file['user_id'] == $_SESSION['user']): ?>
                                                            <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-primary"
                                                               data-toggle="tooltip" 
                                                               title="View In Drive">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-info" 
                                                                    onclick="copyLink('<?php echo htmlspecialchars($file['drive_link']); ?>')"
                                                                    data-toggle="tooltip" 
                                                                    title="Copy Link">
                                                                <i class="fas fa-link"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" 
                                                                    onclick="deleteFile(<?php echo $file['file_id']; ?>)"
                                                                    data-toggle="tooltip" 
                                                                    title="Delete File">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-primary me-2"
                                                               data-toggle="tooltip" 
                                                               title="View In Drive">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-info" 
                                                                    onclick="copyLink('<?php echo htmlspecialchars($file['drive_link']); ?>')"
                                                                    data-toggle="tooltip" 
                                                                    title="Copy Link">
                                                                <i class="fas fa-link"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Class Files Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Class Files</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($classFiles)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-books fa-3x mb-3 text-muted"></i>
                                        <p class="text-muted">No class files available yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="accordion" id="classFilesAccordion">
                                        <?php foreach ($classFiles as $className => $classData): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#class<?php echo $classData['class_id']; ?>">
                                                    <?php echo htmlspecialchars($className); ?>
                                                </button>
                                            </h2>
                                            <div id="class<?php echo $classData['class_id']; ?>" 
                                                 class="accordion-collapse collapse">
                                                <div class="accordion-body">
                                                    <div class="table-responsive">
                                                        <table class="table align-items-center">
                                                            <thead>
                                                                <tr>
                                                                    <th>File Name</th>
                                                                    <th>Uploaded By</th>
                                                                    <th>Upload Date</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($classData['files'] as $file): ?>
                                                                <tr>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="fas <?php echo getFileIcon($file['file_type']); ?> me-2"></i>
                                                                            <div>
                                                                                <h6 class="mb-0"><?php echo htmlspecialchars($file['file_name']); ?></h6>
                                                                                <?php if ($file['description']): ?>
                                                                                    <small class="text-muted"><?php echo htmlspecialchars($file['description']); ?></small>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($file['first_name'].' '.$file['last_name']); ?></td>
                                                                    <td><?php echo date('M d, Y', strtotime($file['upload_time'])); ?></td>
                                                                    <td>
                                                                        <div class="btn-group">
                                                                            <?php if ($file['user_id'] == $_SESSION['user']): ?>
                                                                            <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" 
                                                                               target="_blank" 
                                                                               class="btn btn-sm btn-primary"
                                                                               data-toggle="tooltip" 
                                                                               title="View In Drive">
                                                                                <i class="fas fa-external-link-alt"></i>
                                                                            </a>
                                                                            <button class="btn btn-sm btn-info" 
                                                                                    onclick="copyLink('<?php echo htmlspecialchars($file['drive_link']); ?>')"
                                                                                    data-toggle="tooltip" 
                                                                                    title="Copy Link">
                                                                                <i class="fas fa-link"></i>
                                                                            </button>
                                                                            <button class="btn btn-sm btn-danger" 
                                                                                    onclick="deleteFile(<?php echo $file['file_id']; ?>)"
                                                                                    data-toggle="tooltip" 
                                                                                    title="Delete File">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                            <?php else: ?>
                                                                            <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" 
                                                                               target="_blank" 
                                                                               class="btn btn-sm btn-primary me-2"
                                                                               data-toggle="tooltip" 
                                                                               title="View In Drive">
                                                                                <i class="fas fa-external-link-alt"></i>
                                                                            </a>
                                                                            <button class="btn btn-sm btn-info" 
                                                                                    onclick="copyLink('<?php echo htmlspecialchars($file['drive_link']); ?>')"
                                                                                    data-toggle="tooltip" 
                                                                                    title="Copy Link">
                                                                                <i class="fas fa-link"></i>
                                                                            </button>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- END Main Dashboard Content -->

        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select File</label>
                                <input type="file" class="form-control" name="file" required>
                                <small class="text-muted">Maximum file size: 500MB for personal files, 5GB for class files</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <?php if ($currentFolderId > 0): ?>
                            <input type="hidden" name="folder_id" value="<?php echo $currentFolderId; ?>">
                            <?php endif; ?>
                        </form>
                        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="uploadFile()">Upload</button>
                </div>
            </div>
        </div>

        <!-- Create Folder Modal -->
        <div class="modal fade" id="createFolderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Folder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createFolderForm">
                            <div class="mb-3">
                                <label class="form-label">Folder Name</label>
                                <input type="text" class="form-control" name="folder_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <?php if ($currentFolderId > 0): ?>
                            <input type="hidden" name="parent_folder_id" value="<?php echo $currentFolderId; ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="createFolder()">Create</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rename Folder Modal -->
        <div class="modal fade" id="renameFolderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rename Folder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="renameFolderForm">
                            <div class="mb-3">
                                <label class="form-label">New Folder Name</label>
                                <input type="text" class="form-control" name="folder_name" id="renameInput" required>
                                <input type="hidden" name="folder_id" id="renameFolderId">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="renameFolderSubmit()">Rename</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        function showUploadModal() {
            const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            <?php if ($currentFolderId > 0 && $currentFolder): ?>
            document.querySelector('#uploadModal .modal-title').textContent = 'Upload File to "<?php echo htmlspecialchars($currentFolder['folder_name']); ?>"';
            <?php else: ?>
            document.querySelector('#uploadModal .modal-title').textContent = 'Upload File';
            <?php endif; ?>
            modal.show();
        }

        function uploadFile() {
            const form = document.getElementById('uploadForm');
            const formData = new FormData(form);
            
            // Show loading
            showLoading(true);

            fetch(BASE + 'api/materials?action=upload', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'File uploaded successfully');
                    location.reload();
                } else {
                    showToast('error', data.message || 'Failed to upload file');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('error', 'Failed to upload file');
                console.error('Error:', error);
            });
        }

        function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file?')) return;

            showLoading();
            fetch(BASE + 'delete-file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `file_id=${fileId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'File deleted successfully');
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    showToast('error', data.message || 'Failed to delete file');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('error', 'Failed to delete file');
                console.error('Error:', error);
            });
        }

        function uploadRequestedFile(requestId) {
            // Similar to showUploadModal but with request_id
            const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            const form = document.getElementById('uploadForm');
            
            // Reset any previous values
            form.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Select File</label>
                    <input type="file" class="form-control" name="file" required>
                    <small class="text-muted">Maximum file size: 500MB for personal files, 5GB for class files</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (Optional)</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <input type="hidden" name="request_id" value="${requestId}">
                <?php if ($currentFolderId > 0): ?>
                <input type="hidden" name="folder_id" value="<?php echo $currentFolderId; ?>">
                <?php endif; ?>
            `;
            
            document.querySelector('#uploadModal .modal-title').textContent = 'Upload Requested File';
            modal.show();
        }

        function copyLink(link) {
            navigator.clipboard.writeText(link).then(() => {
                showToast('success', 'Link copied to clipboard!');
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = link;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    showToast('success', 'Link copied to clipboard!');
                } catch (err) {
                    showToast('error', 'Failed to copy link');
                }
                document.body.removeChild(textarea);
            });
        }

        // Helper function to format file size
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function fixPermissions() {
            // Show loading state
            Swal.fire({
                title: 'Fixing permissions...',
                text: 'Please wait while we update your file permissions',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make API call
            fetch(BASE+'fix-permissions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload the page to show updated permissions
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.error || 'Failed to fix permissions');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonText: 'OK'
                });
            });
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function showCreateFolderModal() {
            const modal = new bootstrap.Modal(document.getElementById('createFolderModal'));
            modal.show();
        }

        function createFolder() {
            const form = document.getElementById('createFolderForm');
            const formData = new FormData(form);
            
            // Show loading
            showLoading(true);

            fetch(BASE + 'create-folder', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Folder created successfully');
                    location.reload();
                } else {
                    showToast('error', data.message || 'Failed to create folder');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('error', 'Failed to create folder');
                console.error('Error:', error);
            });
        }
        
        function renameFolder(folderId, folderName) {
            // Set the values in the form
            document.getElementById('renameFolderId').value = folderId;
            document.getElementById('renameInput').value = folderName;
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('renameFolderModal'));
            modal.show();
        }
        
        function renameFolderSubmit() {
            const form = document.getElementById('renameFolderForm');
            const formData = new FormData(form);
            
            // Show loading
            showLoading(true);

            fetch(BASE + 'rename-folder', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Folder renamed successfully');
                    location.reload();
                } else {
                    showToast('error', data.message || 'Failed to rename folder');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('error', 'Failed to rename folder');
                console.error('Error:', error);
            });
        }
        
        function deleteFolder(folderId) {
            if (!confirm('Are you sure you want to delete this folder? All files inside will also be deleted.')) return;

            showLoading(true);
            fetch(BASE + 'delete-folder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `folder_id=${folderId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Folder deleted successfully');
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    showToast('error', data.message || 'Failed to delete folder');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('error', 'Failed to delete folder');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>