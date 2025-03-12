<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechTutor-1/backends/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechTutor-1/backends/main.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechTutor-1/backends/class_management.php';

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
    <title>TechTutor | Class Meeting</title>
    
    <!-- Favicons -->
    <link href="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/img/stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/css/dashboard.css" rel="stylesheet">
    <link href="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/css/techguru-common.css" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/TechTutor-1/components/header.php'; ?>

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
                                    <li class="breadcrumb-item active">Class Meeting</li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($class['class_name']); ?> - Meeting</h2>
                            <p class="subtitle">Manage your class meeting and attendees</p>
                        </div>
                        <button type="button" class="btn btn-primary btn-action" onclick="startMeeting()">
                            <i class="bi bi-camera-video"></i>
                            Start Meeting
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meeting Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Attendees</h6>
                            <h3 class="mb-0">0</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Duration</h6>
                            <h3 class="mb-0">00:00</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info">
                            <i class="bi bi-hand-index"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Raised Hands</h6>
                            <h3 class="mb-0">0</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0">Chat Messages</h6>
                            <h3 class="mb-0">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendees Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title mb-0">Attendees</h3>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary">
                                <i class="bi bi-mic"></i>
                                Mute All
                            </button>
                            <button class="btn btn-outline-primary">
                                <i class="bi bi-camera-video"></i>
                                Stop All Video
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Join Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <img src="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/img/illustrations/no-data.svg" alt="No Attendees" class="mb-4" style="width: 200px;">
                                        <h3>No Attendees Yet</h3>
                                        <p class="text-muted">Start the meeting to allow students to join.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/TechTutor-1/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function startMeeting() {
            // TODO: Implement meeting functionality
            alert('Meeting functionality will be implemented here');
        }
    </script>
</body>
</html>