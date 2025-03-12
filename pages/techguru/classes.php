<?php 
require_once '../../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Handle class deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];
    if (deleteClass($class_id, $_SESSION['user'])) {
        $_SESSION['success'] = "Class deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete class";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get tutor's classes and stats
$classes = getTechGuruClasses($_SESSION['user']);
$stats = getClassStats($_SESSION['user']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | My Classes</title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>techguru-common.css" rel="stylesheet">
</head>

<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active">My Classes</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">My Classes</h2>
                            <p class="subtitle">Manage your tutoring classes and create new ones</p>
                        </div>
                        <a href="subjects" class="btn btn-primary btn-action">
                            <i class="bi bi-plus-lg"></i>
                            Create Class
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Classes Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <?php if (empty($classes)): ?>
                        <div class="text-center py-5">
                            <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Classes" class="mb-4" style="width: 200px;">
                            <h3>No Classes Created Yet</h3>
                            <p class="text-muted">Start creating your first class by clicking the "Create Class" button above.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Class Name</th>
                                        <th>Schedule</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-book text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($class['subject_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-calendar me-2"></i>
                                                    <?php 
                                                        echo date('M d, Y', strtotime($class['start_date'])) . ' - ' . 
                                                             date('M d, Y', strtotime($class['end_date']));
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-people me-2"></i>
                                                    <?php echo $class['student_count']; ?> / <?php echo $class['class_size']; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $status_class = '';
                                                    if ($class['is_active']) {
                                                        $status_class = 'bg-success';
                                                        $status_text = 'Active';
                                                    } else {
                                                        $status_class = 'bg-secondary';
                                                        $status_text = 'Completed';
                                                    }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="class_details.php?id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="class_edit.php?id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($class['is_active']): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="confirmDelete(<?php echo $class['class_id']; ?>)">
                                                            <i class="bi bi-trash"></i>
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

        <!-- Class Stats -->
        <?php if (!empty($classes)): ?>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Active Classes</h6>
                            <h3 class="mb-0"><?php echo $stats['active_classes'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Total Students</h6>
                            <h3 class="mb-0"><?php echo $stats['total_students'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Total Classes</h6>
                            <h3 class="mb-0"><?php echo $stats['total_classes'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-secondary">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Completed</h6>
                            <h3 class="mb-0"><?php echo $stats['completed_classes'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this class? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        <input type="hidden" name="class_id" id="deleteClassId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(classId) {
            document.getElementById('deleteClassId').value = classId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
