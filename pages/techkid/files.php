<?php 
    require_once '../../backends/main.php';
require_once BACKEND.'file_management.php';

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
                                        <button class="btn btn-warning" onclick="fixPermissions()">
                                            <i class="fas fa-lock-open me-1"></i> Fix File Access
                                        </button>
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
                                <h5 class="mb-0">My Files</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($personalFiles)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                                        <p class="text-muted">No personal files uploaded yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>File Name</th>
                                                    <th>Size</th>
                                                    <th>Upload Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
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
                                        <?php foreach ($classFiles as $className => $files): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#class<?php echo $files[0]['class_id']; ?>">
                                                    <?php echo htmlspecialchars($className); ?>
                                                </button>
                                            </h2>
                                            <div id="class<?php echo $files[0]['class_id']; ?>" 
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
                                                                <?php foreach ($files as $file): ?>
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
                                                                    <td><?php echo htmlspecialchars($file['uploader_name']); ?></td>
                                                                    <td><?php echo date('M d, Y', strtotime($file['upload_time'])); ?></td>
                                                                    <td>
                                                                        <div class="btn-group">
                                                                            <?php if ($file['user_id'] == $_SESSION['user']['uid']): ?>
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
                        </form>
                        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="uploadFile()">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        function showUploadModal() {
            const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            modal.show();
        }

        function uploadFile() {
            const form = document.getElementById('uploadForm');
            const formData = new FormData(form);
            
            // Show loading
            showLoading(true);

            fetch(BASE + 'upload-file', {
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
            form.innerHTML += `<input type="hidden" name="request_id" value="${requestId}">`;
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
    </script>
</body>
</html>