<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

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
    header('Location: ' . BASE . 'dashboard/t/class');
    exit();
}

// Get tutor's classes and stats
$classes = getTechGuruClasses($_SESSION['user']);
$stats = getClassStats($_SESSION['user']);
$title = 'My Classes';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
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
                        <style>
                            .table-scroll {
                                max-height: 600px;
                                overflow-y: auto;
                                border-radius: 0.5rem;
                                scrollbar-width: thin;
                                scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
                            }
                            .table-scroll::-webkit-scrollbar {
                                width: 6px;
                            }
                            .table-scroll::-webkit-scrollbar-track {
                                background: transparent;
                            }
                            .table-scroll::-webkit-scrollbar-thumb {
                                background-color: rgba(0, 0, 0, 0.2);
                                border-radius: 3px;
                            }
                            .table-scroll thead th {
                                position: sticky;
                                top: 0;
                                background: white;
                                z-index: 1;
                                border-top: none;
                            }
                            .table-scroll tbody tr:first-child td {
                                border-top: none;
                            }
                            @media (max-width: 768px) {
                                .table-scroll {
                                    max-height: 400px;
                                }
                            }
                        </style>
                        <div class="table-responsive table-scroll">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Class Name</th>
                                        <th>Schedule</th>
                                        <th>Students</th>
                                        <th>Progress</th>
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
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img class="thumbnail rounded" src="<?php echo htmlspecialchars(CLASS_IMG.$class['thumbnail']); ?>" alt="<?php echo htmlspecialchars($class['class_name']); ?>" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <div>
                                                        <div><?php echo htmlspecialchars($class['class_name']); ?></div>
                                                        <?php if (isset($class['rating'])): ?>
                                                        <div class="text-warning">
                                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                                <i class="bi bi-star<?php echo $i <= round($class['rating']) ? '-fill' : ''; ?> small"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-calendar me-2"></i>
                                                    <div>
                                                        <div><?php echo date('M d, Y', strtotime($class['start_date'])); ?></div>
                                                        <div class="text-muted small"><?php echo date('M d, Y', strtotime($class['end_date'])); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" data-bs-toggle="tooltip" title="<?php echo $class['student_count']; ?> enrolled out of <?php echo $class['class_size']; ?> slots">
                                                    <i class="bi bi-people me-2"></i>
                                                    <div class="progress w-100" style="height: 6px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo ($class['student_count'] / $class['class_size']) * 100; ?>%"
                                                             aria-valuenow="<?php echo $class['student_count']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="<?php echo $class['class_size']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $completion = isset($class['completed_sessions']) && isset($class['total_sessions']) 
                                                        ? ($class['completed_sessions'] / $class['total_sessions']) * 100 
                                                        : 0;
                                                ?>
                                                <div class="progress" style="height: 6px;" data-bs-toggle="tooltip" 
                                                     title="<?php echo round($completion); ?>% Complete">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $completion; ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch($class['status']) {
                                                        case 'active':
                                                            $status_class = 'bg-success';
                                                            $status_text = 'Active';
                                                            break;
                                                        case 'pending':
                                                            $status_class = 'bg-warning';
                                                            $status_text = 'Pending';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'bg-info';
                                                            $status_text = 'Completed';
                                                            break;
                                                        default:
                                                            $status_class = 'bg-secondary';
                                                            $status_text = 'Draft';
                                                    }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo BASE; ?>dashboard/t/class/details?id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       data-bs-toggle="tooltip"
                                                       title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE; ?>dashboard/t/class/edit?id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Class">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($class['status'] !== 'completed'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger"
                                                                data-bs-toggle="tooltip"
                                                                title="Delete Class"
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
    </main> 
    </div> 

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

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        function confirmDelete(classId) {
            document.getElementById('deleteClassId').value = classId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

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
