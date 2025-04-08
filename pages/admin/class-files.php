<?php
require_once '../../backends/main.php';
require_once BACKEND.'admin_management.php';
require_once BACKEND.'unified_file_management.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: user-logout');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$class_id) {
    header('Location: ./');
    exit();
}

// Get class details
$query = "SELECT c.*, 
                 CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                 (SELECT COUNT(*) FROM enrollments WHERE class_id = c.class_id) as enrollment_count
          FROM class c
          LEFT JOIN users u ON c.tutor_id = u.uid
          WHERE c.class_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ./');
    exit();
}

$class = $result->fetch_assoc();

// Get class files
$class_files = getAdminClassFiles($class_id);

// Initialize file manager
$fileManager = new UnifiedFileManagement();

// Handle form submission for file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'upload_file' && isset($_FILES['file'])) {
        try {
            $file = $_FILES['file'];
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'class_material';
            
            // Upload file
            $fileManager->uploadFile(
                $file,
                $_SESSION['user'],
                $class_id,
                null, // folder ID
                $description,
                'class_only', // visibility
                $purpose
            );
            
            $_SESSION['success'] = "File uploaded successfully";
            header("Location: class-files.php?id=$class_id");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error uploading file: " . $e->getMessage();
        }
    }
}

$title = $class['class_name']." Files";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../components/head.php'; ?>
    <style>
        .file-card {
            transition: all 0.3s ease;
        }
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .file-icon {
            font-size: 2.5rem;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include '../../components/header.php'; ?>
    
    <main class="container py-4">
        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-1">
                                        <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="./">Classes</a></li>
                                        <li class="breadcrumb-item"><a href="./?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($class['class_name']); ?></a></li>
                                        <li class="breadcrumb-item active">Class Files</li>
                                    </ol>
                                </nav>
                                <h2 class="page-header mb-0">Class Files</h2>
                                <p class="text-muted">Manage Files for <?php echo htmlspecialchars($class['class_name']); ?></p>
                            </div>
                            <div>
                                <a href="./?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Class Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column align-items-center text-center">
                        <div class="stat-icon bg-primary text-white mb-2">
                            <i class="bi bi-file-earmark"></i>
                        </div>
                        <h6 class="card-subtitle mb-1 text-muted">Total Files</h6>
                        <h2 class="card-title mb-0"><?php echo count($class_files); ?></h2>
                    </div>
                </div>
            </div>
            <?php 
                $materialCount = 0;
                $assignmentCount = 0;
                $submissionCount = 0;
                
                foreach ($class_files as $file) {
                    if ($file['file_purpose'] === 'class_material') {
                        $materialCount++;
                    } elseif ($file['file_purpose'] === 'assignment') {
                        $assignmentCount++;
                    } elseif ($file['file_purpose'] === 'submission') {
                        $submissionCount++;
                    }
                }
            ?>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column align-items-center text-center">
                        <div class="stat-icon bg-info text-white mb-2">
                            <i class="bi bi-book"></i>
                        </div>
                        <h6 class="card-subtitle mb-1 text-muted">Class Materials</h6>
                        <h2 class="card-title mb-0"><?php echo $materialCount; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column align-items-center text-center">
                        <div class="stat-icon bg-success text-white mb-2">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h6 class="card-subtitle mb-1 text-muted">Assignments</h6>
                        <h2 class="card-title mb-0"><?php echo $assignmentCount; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column align-items-center text-center">
                        <div class="stat-icon bg-warning text-white mb-2">
                            <i class="bi bi-upload"></i>
                        </div>
                        <h6 class="card-subtitle mb-1 text-muted">Submissions</h6>
                        <h2 class="card-title mb-0"><?php echo $submissionCount; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload New File -->
        <div class="card mb-4 mt-4">
            <?php if(!($class['status'] === 'completed') ): ?>
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-upload"></i> Upload New File</h3>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#uploadForm" aria-expanded="false" aria-controls="uploadForm">
                    <i class="bi bi-plus-circle"></i> Add File
                </button>
            </div>
            <?php endif; ?>
            <div class="collapse" id="uploadForm">
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_file">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="file" class="form-label">File</label>
                                <input type="file" class="form-control" id="file" name="file" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="purpose" class="form-label">File Purpose</label>
                                <select class="form-select" id="purpose" name="purpose" required>
                                    <option value="class_material">Class Material</option>
                                    <option value="assignment">Assignment</option>
                                    <option value="submission">Submission</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload File
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Files Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-file-earmark"></i> Class Files</h3>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" id="viewModeToggle">
                        <i class="bi bi-grid"></i> Toggle View
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($class_files)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i> No files have been uploaded for this class yet.
                    </div>
                <?php else: ?>
                    <!-- Table View (default) -->
                    <div class="table-responsive" id="tableView">
                        <table class="table table-hover" id="filesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploaded By</th>
                                    <th>Upload Date</th>
                                    <th>Purpose</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_files as $file): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($file['file_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php 
                                            // Display file type icon based on MIME type
                                            $icon = 'file';
                                            if (strpos($file['file_type'], 'image') !== false) {
                                                $icon = 'file-image';
                                            } elseif (strpos($file['file_type'], 'pdf') !== false) {
                                                $icon = 'file-pdf';
                                            } elseif (strpos($file['file_type'], 'word') !== false || strpos($file['file_type'], 'document') !== false) {
                                                $icon = 'file-word';
                                            } elseif (strpos($file['file_type'], 'excel') !== false || strpos($file['file_type'], 'sheet') !== false) {
                                                $icon = 'file-excel';
                                            } elseif (strpos($file['file_type'], 'video') !== false) {
                                                $icon = 'file-video';
                                            } elseif (strpos($file['file_type'], 'audio') !== false) {
                                                $icon = 'file-audio';
                                            } elseif (strpos($file['file_type'], 'zip') !== false || strpos($file['file_type'], 'rar') !== false) {
                                                $icon = 'file-archive';
                                            } elseif (strpos($file['file_type'], 'text') !== false) {
                                                $icon = 'file-text';
                                            }
                                            ?>
                                            <i class="bi bi-<?php echo $icon; ?> me-1"></i>
                                            <?php echo htmlspecialchars($file['file_type']); ?>
                                        </td>
                                        <td><?php echo $fileManager->formatFileSize($file['file_size']); ?></td>
                                        <td><?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($file['upload_time'])); ?></td>
                                        <td>
                                            <?php 
                                            $purpose_badge = 'secondary';
                                            switch ($file['file_purpose']) {
                                                case 'class_material':
                                                    $purpose_badge = 'info';
                                                    $purpose_text = 'Class Material';
                                                    break;
                                                case 'assignment':
                                                    $purpose_badge = 'primary';
                                                    $purpose_text = 'Assignment';
                                                    break;
                                                case 'submission':
                                                    $purpose_badge = 'success';
                                                    $purpose_text = 'Submission';
                                                    break;
                                                default:
                                                    $purpose_text = ucfirst($file['file_purpose']);
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $purpose_badge; ?>">
                                                <?php echo $purpose_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" target="_blank" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($fileManager->extractFileIdFromDriveLink($file['drive_link'])): ?>
                                                <a href="<?php echo $fileManager->extractFileIdFromDriveLink($file['drive_link']); ?>" class="btn btn-outline-success" data-bs-toggle="tooltip" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-danger delete-file-btn" data-file-id="<?php echo $file['file_id']; ?>" data-file-name="<?php echo htmlspecialchars($file['file_name']); ?>" data-bs-toggle="tooltip" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Grid View (hidden by default) -->
                    <div class="row g-3" id="gridView" style="display: none;">
                        <?php foreach ($class_files as $file): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="card h-100 file-card">
                                    <div class="card-body">
                                        <?php 
                                        // Display file type icon based on MIME type
                                        $icon = 'file';
                                        if (strpos($file['file_type'], 'image') !== false) {
                                            $icon = 'file-image';
                                        } elseif (strpos($file['file_type'], 'pdf') !== false) {
                                            $icon = 'file-pdf';
                                        } elseif (strpos($file['file_type'], 'word') !== false || strpos($file['file_type'], 'document') !== false) {
                                            $icon = 'file-word';
                                        } elseif (strpos($file['file_type'], 'excel') !== false || strpos($file['file_type'], 'sheet') !== false) {
                                            $icon = 'file-excel';
                                        } elseif (strpos($file['file_type'], 'video') !== false) {
                                            $icon = 'file-video';
                                        } elseif (strpos($file['file_type'], 'audio') !== false) {
                                            $icon = 'file-audio';
                                        } elseif (strpos($file['file_type'], 'zip') !== false || strpos($file['file_type'], 'rar') !== false) {
                                            $icon = 'file-archive';
                                        } elseif (strpos($file['file_type'], 'text') !== false) {
                                            $icon = 'file-text';
                                        }
                                        
                                        $purpose_badge = 'secondary';
                                        switch ($file['file_purpose']) {
                                            case 'class_material':
                                                $purpose_badge = 'info';
                                                $purpose_text = 'Class Material';
                                                break;
                                            case 'assignment':
                                                $purpose_badge = 'primary';
                                                $purpose_text = 'Assignment';
                                                break;
                                            case 'submission':
                                                $purpose_badge = 'success';
                                                $purpose_text = 'Submission';
                                                break;
                                            default:
                                                $purpose_text = ucfirst($file['file_purpose']);
                                        }
                                        ?>
                                        <div class="text-center mb-3">
                                            <i class="bi bi-<?php echo $icon; ?> file-icon text-primary"></i>
                                        </div>
                                        <h5 class="card-title text-truncate">
                                            <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($file['file_name']); ?>
                                            </a>
                                        </h5>
                                        <div class="mb-2">
                                            <span class="badge bg-<?php echo $purpose_badge; ?>">
                                                <?php echo $purpose_text; ?>
                                            </span>
                                            <small class="text-muted ms-2"><?php echo $fileManager->formatFileSize($file['file_size']); ?></small>
                                        </div>
                                        <p class="card-text small text-muted">
                                            Uploaded by <?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?><br>
                                            on <?php echo date('M d, Y g:i A', strtotime($file['upload_time'])); ?>
                                        </p>
                                        <div class="mt-auto pt-2 border-top">
                                            <div class="btn-group w-100" role="group">
                                                <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <?php if ($fileManager->extractFileIdFromDriveLink($file['drive_link'])): ?>
                                                <a href="<?php echo $fileManager->extractFileIdFromDriveLink($file['drive_link']); ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger delete-file-btn" data-file-id="<?php echo $file['file_id']; ?>" data-file-name="<?php echo htmlspecialchars($file['file_name']); ?>" data-bs-toggle="tooltip" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
        
    <!-- Delete File Confirmation Modal -->
    <div class="modal fade" id="deleteFileModal" tabindex="-1" aria-labelledby="deleteFileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteFileModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the file: <span id="fileNameToDelete" class="fw-bold"></span>?</p>
                    <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteFile">Delete</button>
                </div>
            </div>
        </div>
    </div>
        
    <?php include '../../components/footer.php'; ?>

    <!-- Page specific scripts -->
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            // Initialize DataTable
            $('#filesTable').DataTable({
                responsive: true,
                order: [[4, 'desc']] // Sort by upload date desc
            });
            
            // Toggle view between table and grid
            $('#viewModeToggle').click(function() {
                if ($('#tableView').is(':visible')) {
                    $('#tableView').hide();
                    $('#gridView').show();
                    $(this).html('<i class="bi bi-table"></i> Table View');
                } else {
                    $('#gridView').hide();
                    $('#tableView').show();
                    $(this).html('<i class="bi bi-grid"></i> Grid View');
                }
            });
            
            // Delete file button click
            $('.delete-file-btn').click(function() {
                const fileId = $(this).data('file-id');
                const fileName = $(this).data('file-name');
                
                $('#fileNameToDelete').text(fileName);
                $('#confirmDeleteFile').data('file-id', fileId);
                
                $('#deleteFileModal').modal('show');
            });
            
            // Confirm delete button click
            $('#confirmDeleteFile').click(function() {
                const fileId = $(this).data('file-id');
                
                // Send AJAX request to delete the file
                $.ajax({
                    url: '../../backends/admin/file_management.php',
                    type: 'POST',
                    data: {
                        action: 'delete_file',
                        file_id: fileId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message and reload page
                            alert('File deleted successfully');
                            location.reload();
                        } else {
                            // Show error message
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while trying to delete the file');
                    },
                    complete: function() {
                        $('#deleteFileModal').modal('hide');
                    }
                });
            });
        });
    </script>
</body>
</html>