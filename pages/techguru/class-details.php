<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

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
    log_error("I failed here!");
    exit();
}

// Handle class status update
if (isset($_POST['action']) && $_POST['action'] === 'updateStatus') {
    $newStatus = isset($_POST['status']) ? intval($_POST['status']) : 0;
    updateClassStatus($class_id, $_SESSION['user'], $newStatus);
    header("Location: class-details?id={$class_id}&updated=1");
    exit();
}

// Get related data
$schedules = getClassSchedules($class_id,'TECHGURU');
$students = getClassStudents($class_id);
$files = getClassFiles($class_id);

// Calculate class statistics
$completion_rate = $classDetails['total_students'] > 0 
    ? ($classDetails['completed_students'] / $classDetails['total_students']) * 100 
    : 0;
$rating = number_format($classDetails['average_rating'] ?? 0, 1);
$title = htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Class created successfully! Students can now enroll in this class.
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
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($classDetails['class_name']); ?></li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($classDetails['class_name']); ?></h2>
                            <p class="subtitle">
                                <span class="badge <?php 
                                    switch($classDetails['status']) {
                                        case 'active': echo 'bg-success'; break;
                                        case 'inactive': echo 'bg-danger'; break;
                                        default: echo 'bg-warning'; break;
                                    }
                                ?>">
                                    <?php echo ucfirst($classDetails['status']); ?>
                                </span>
                                <?php if ($classDetails['is_free']): ?>
                                    <span class="badge bg-info">Free Class</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                <?php endif; ?>
                            </p>
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
                        </div>
                        <div class="col-md-6">
                            <p><strong>Class Size:</strong> <?php echo isset($classDetails['enrolled_students']) ? $classDetails['enrolled_students'] : 0; ?>/<?php echo $classDetails['class_size'] ? $classDetails['class_size'] : 'Unlimited'; ?> students</p>
                            <p><strong>Completion Rate:</strong> <?php echo number_format($completion_rate, 1); ?>%</p>
                            <p><strong>Average Rating:</strong> <?php echo $rating; ?>/5.0</p>
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
                                        <?php if ($schedule['status'] === 'confirmed'): ?>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="joinMeeting(<?php echo $schedule['schedule_id']; ?>)">
                                             Join Meeting
                                        </button>
                                    </td>
                                        <?php elseif ($schedule['status'] === 'pending'): ?>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="startSession(<?php echo $schedule['schedule_id']; ?>)">
                                             Start Session
                                        </button>
                                    </td>
                                        <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Class Materials -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Class Materials</h3>
                        <button class="btn btn-primary btn-sm" onclick="uploadMaterial()">
                            <i class="bi bi-upload"></i> Upload Material
                        </button>
                    </div>
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
                                        <?php if ($file['user_id'] === $_SESSION['user']): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteMaterial('<?php echo $file['file_uuid']; ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
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
                    <h3>Quick Actions</h3>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-primary" onclick="manageSchedule()">
                            <i class="bi bi-calendar-check"></i> Manage Schedule
                        </button>
                        <button class="btn btn-info" onclick="messageStudents()">
                            <i class="bi bi-chat-dots"></i> Message Students
                        </button>
                        <button class="btn btn-success" onclick="viewAnalytics()">
                            <i class="bi bi-graph-up"></i> View Analytics
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    </main> 
    </div> 

    <!-- Scripts -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        function startSession(scheduleId) {
            if (!confirm("Are you sure you want to start this session?")) return;

            // Show loading indicator
            showLoading(true);

            fetch(BASE+'create-meeting', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `schedule_id=${scheduleId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success',"Meeting room was successfully generated");
                    setTimeout( () => location.reload(), 2000);
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                alert("Failed to start the meeting. Please try again.");
            });
        }
        function joinMeeting(scheduleId) {
            showLoading(true); // Show loading spinner

            fetch(BASE+'join-meeting', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `schedule_id=${scheduleId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false); // Hide loading

                if (data.success) {
                    window.location.href = data.data.join_url; // Redirect to meeting
                } else {
                    showToast('error', data.message || 'Failed to join meeting.');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error joining meeting:', error);
                showToast('error', 'An error occurred. Please try again.');
            });
        }



        function uploadMaterial() {
            // TODO: Implement material upload logic
            alert('Opening upload dialog...');
        }

        function deleteMaterial(fileUuid) {
            if (confirm('Are you sure you want to delete this material?')) {
                // TODO: Implement delete logic
                alert('Deleting material...');
            }
        }

        function manageSchedule() {
            // TODO: Implement schedule management
            alert('Opening schedule manager...');
        }

        function messageStudents() {
            // TODO: Implement messaging
            alert('Opening message composer...');
        }

        function viewAnalytics() {
            // TODO: Implement analytics view
            alert('Opening analytics dashboard...');
        }
    </script>
</body>
</html>