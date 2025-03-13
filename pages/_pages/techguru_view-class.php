<?php 
require_once '../../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';
require_once ROOT_PATH . '/backends/class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login.php');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$class_id) {
    header('Location: ' . BASE . 'pages/techguru/techguru_classes.php');
    exit();
}

// Get class details
$class = getClassDetails($class_id, $_SESSION['user_id']);
if (!$class) {
    $_SESSION['error'] = "Class not found or access denied";
    header('Location: ' . BASE . 'pages/techguru/techguru_classes.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | View Class</title>
    
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
                                    <li class="breadcrumb-item"><a href="techguru_classes.php">My Classes</a></li>
                                    <li class="breadcrumb-item active">View Class</li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($class['class_name']); ?></h2>
                            <p class="subtitle"><?php echo htmlspecialchars($class['subject_name']); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="techguru_view-class-meeting.php?id=<?php echo $class_id; ?>" class="btn btn-primary btn-action">
                                <i class="bi bi-camera-video"></i>
                                Start Meeting
                            </a>
                            <a href="techguru_edit-class.php?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                                Edit Class
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Info -->
        <div class="row mt-4">
            <div class="col-md-8">
                <!-- Class Details -->
                <div class="dashboard-card mb-4">
                    <h3 class="card-title">Class Details</h3>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Subject</label>
                                <p class="mb-0"><?php echo htmlspecialchars($class['subject_name']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Class Size</label>
                                <p class="mb-0"><?php echo $class['student_count']; ?> / <?php echo $class['class_size']; ?> students</p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Status</label>
                                <p class="mb-0">
                                    <span class="badge <?php echo $class['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $class['is_active'] ? 'Active' : 'Completed'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Start Date</label>
                                <p class="mb-0"><?php echo date('F j, Y', strtotime($class['start_date'])); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">End Date</label>
                                <p class="mb-0"><?php echo date('F j, Y', strtotime($class['end_date'])); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Schedule</label>
                                <p class="mb-0">
                                    <?php 
                                        $days = json_decode($class['schedule_days']);
                                        echo implode(', ', array_map('ucfirst', $days)) . '<br>';
                                        echo date('g:i A', strtotime($class['start_time'])) . ' - ' . 
                                             date('g:i A', strtotime($class['end_time']));
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Class Description -->
                <div class="dashboard-card mb-4">
                    <h3 class="card-title">Description</h3>
                    <p class="mt-3"><?php echo nl2br(htmlspecialchars($class['description'])); ?></p>
                </div>

                <!-- Class Materials -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title mb-0">Class Materials</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-upload"></i>
                            Upload Material
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Materials" class="mb-4" style="width: 200px;">
                                        <h3>No Materials Yet</h3>
                                        <p class="text-muted">Upload your first class material using the button above.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Students List -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title mb-0">Students</h3>
                        <span class="badge bg-primary"><?php echo $class['student_count']; ?> / <?php echo $class['class_size']; ?></span>
                    </div>
                    
                    <?php if ($class['student_count'] == 0): ?>
                        <div class="text-center py-4">
                            <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Students" class="mb-4" style="width: 150px;">
                            <h4>No Students Yet</h4>
                            <p class="text-muted">Students will appear here once they enroll in your class.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h3 class="card-title mb-4">Quick Actions</h3>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-bell"></i>
                            Send Announcement
                        </button>
                        <button class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-calendar-plus"></i>
                            Schedule Meeting
                        </button>
                        <button class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-file-earmark-text"></i>
                            Create Assignment
                        </button>
                        <?php if ($class['is_active']): ?>
                            <button class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2" 
                                    onclick="confirmDelete(<?php echo $class_id; ?>)">
                                <i class="bi bi-trash"></i>
                                Delete Class
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Upload Material Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm">
                        <div class="mb-3">
                            <label class="form-label">Material Name</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File</label>
                            <input type="file" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="uploadForm" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
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
                    <form method="POST" action="techguru_classes.php">
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
    </script>
</body>
</html>