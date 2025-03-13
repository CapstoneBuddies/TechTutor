<?php 
require_once '../../backends/main.php';

// Ensure user is logged in and is an Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid (null tutor_id for admin access)
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Get related data
$schedules = getClassSchedules($class_id);
$students = getClassStudents($class_id);
$files = getClassFiles($class_id);

// Handle class status update
if (isset($_POST['action']) && $_POST['action'] === 'updateStatus') {
    $newStatus = isset($_POST['status']) ? intval($_POST['status']) : 0;
    updateClassStatus($class_id, null, $newStatus);
    header("Location: class-details?id={$class_id}&updated=1");
    exit();
}

// Calculate class statistics
$completion_rate = $classDetails['total_students'] > 0 
    ? ($classDetails['completed_students'] / $classDetails['total_students']) * 100 
    : 0;
$rating = number_format($classDetails['average_rating'] ?? 0, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor Admin | <?php echo htmlspecialchars($classDetails['class_name']); ?></title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>admin-common.css" rel="stylesheet">
</head>

<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Class status updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="classes">All Classes</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($classDetails['class_name']); ?></li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($classDetails['class_name']); ?></h2>
                            <p class="subtitle">
                                <span class="badge <?php echo $classDetails['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $classDetails['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <?php if ($classDetails['is_free']): ?>
                                    <span class="badge bg-info">Free Class</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="updateStatus">
                                <input type="hidden" name="status" value="<?php echo $classDetails['is_active'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn <?php echo $classDetails['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $classDetails['is_active'] ? 'Deactivate Class' : 'Activate Class'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row mt-4">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Class Information -->
                <div class="dashboard-card mb-4">
                    <h3>Class Information</h3>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($classDetails['subject_name']); ?></p>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($classDetails['course_name']); ?></p>
                            <p><strong>Duration:</strong> <?php echo date('M d, Y', strtotime($classDetails['start_date'])); ?> - <?php echo date('M d, Y', strtotime($classDetails['end_date'])); ?></p>
                            <p><strong>Tutor:</strong> <?php echo htmlspecialchars($classDetails['first_name'] . ' ' . $classDetails['last_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Class Size:</strong> <?php echo $classDetails['enrolled_students']; ?>/<?php echo $classDetails['class_size']; ?> students</p>
                            <p><strong>Completion Rate:</strong> <?php echo number_format($completion_rate, 1); ?>%</p>
                            <p><strong>Average Rating:</strong> <?php echo $rating; ?>/5.0</p>
                            <p><strong>Revenue:</strong> ₱<?php echo number_format($classDetails['price'] * $classDetails['enrolled_students'], 2); ?></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h4>Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                    </div>
                </div>

                <!-- Class Schedule -->
                <div class="dashboard-card mb-4">
                    <h3>Class Schedule</h3>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($schedule['session_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            switch($schedule['status']) {
                                                case 'completed': echo 'bg-success'; break;
                                                case 'confirmed': echo 'bg-primary'; break;
                                                case 'canceled': echo 'bg-danger'; break;
                                                default: echo 'bg-warning'; break;
                                            }
                                        ?>">
                                            <?php echo ucfirst($schedule['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewSessionDetails(<?php echo $schedule['schedule_id']; ?>)">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Class Materials -->
                <div class="dashboard-card">
                    <h3>Class Materials</h3>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Uploaded By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                    <td><?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($file['upload_time'])); ?></td>
                                    <td>
                                        <a href="download?uuid=<?php echo $file['file_uuid']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteMaterial('<?php echo $file['file_uuid']; ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Students List -->
                <div class="dashboard-card mb-4">
                    <h3>Enrolled Students</h3>
                    <div class="students-list mt-3">
                        <?php foreach ($students as $student): ?>
                        <div class="student-item d-flex align-items-center mb-3">
                            <img src="<?php echo IMG . 'users/' . $student['profile_picture']; ?>" class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                <small class="text-muted">
                                    Progress: <?php echo $student['completed_sessions']; ?>/<?php echo $student['total_sessions']; ?> sessions
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h3>Admin Actions</h3>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-primary" onclick="editClass()">
                            <i class="bi bi-pencil"></i> Edit Class Details
                        </button>
                        <button class="btn btn-info" onclick="manageEnrollments()">
                            <i class="bi bi-people"></i> Manage Enrollments
                        </button>
                        <button class="btn btn-warning" onclick="reviewReports()">
                            <i class="bi bi-flag"></i> Review Reports
                        </button>
                        <button class="btn btn-success" onclick="viewAnalytics()">
                            <i class="bi bi-graph-up"></i> View Analytics
                        </button>
                        <button class="btn btn-danger" onclick="archiveClass()">
                            <i class="bi bi-archive"></i> Archive Class
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewSessionDetails(scheduleId) {
            // TODO: Implement session details view
            alert('Opening session details...');
        }

        function deleteMaterial(fileUuid) {
            if (confirm('Are you sure you want to delete this material?')) {
                // TODO: Implement delete logic
                alert('Deleting material...');
            }
        }

        function editClass() {
            // TODO: Implement class editing
            alert('Opening class editor...');
        }

        function manageEnrollments() {
            // TODO: Implement enrollment management
            alert('Opening enrollment manager...');
        }

        function reviewReports() {
            // TODO: Implement report review
            alert('Opening reports...');
        }

        function viewAnalytics() {
            // TODO: Implement analytics view
            alert('Opening analytics dashboard...');
        }

        function archiveClass() {
            if (confirm('Are you sure you want to archive this class? This action cannot be undone.')) {
                // TODO: Implement archive logic
                alert('Archiving class...');
            }
        }
    </script>
</body>
</html>