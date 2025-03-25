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
    <style>
        .class-info-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .info-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            transition: transform 0.2s;
        }
        .info-card:hover {
            transform: translateY(-2px);
        }
        .info-card strong {
            color: var(--bs-primary);
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }
        .info-card p {
            margin: 0;
            font-size: 1rem;
            color: #495057;
        }
        .schedule-table {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,0.2) transparent;
        }
        .schedule-table::-webkit-scrollbar {
            width: 6px;
        }
        .schedule-table::-webkit-scrollbar-track {
            background: transparent;
        }
        .schedule-table::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.2);
            border-radius: 3px;
        }
        .schedule-table th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }
        .student-item {
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: transform 0.2s, background-color 0.2s;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .student-item:hover {
            transform: translateY(-2px);
            background-color: #f8f9fa;
        }
        .student-item img {
            object-fit: cover;
        }
        .student-progress {
            height: 6px;
            margin-top: 0.5rem;
        }
        .material-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: transform 0.2s;
            border: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 0.5rem;
        }
        .material-item:hover {
            transform: translateY(-2px);
            background-color: #f8f9fa;
        }
        .material-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bs-primary);
            color: white;
            border-radius: 0.5rem;
            margin-right: 1rem;
        }
        .quick-action {
            transition: transform 0.2s;
        }
        .quick-action:hover {
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .class-info-grid {
                grid-template-columns: 1fr;
            }
            .student-item {
                margin-bottom: 0.5rem;
            }
        }
    </style>
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
                    <h3 class="mb-4">Class Information</h3>
                    <div class="class-info-grid">
                        <div class="info-card">
                            <strong>Subject</strong>
                            <p><?php echo htmlspecialchars($classDetails['subject_name']); ?></p>
                        </div>
                        <div class="info-card">
                            <strong>Course</strong>
                            <p><?php echo htmlspecialchars($classDetails['course_name']); ?></p>
                        </div>
                        <div class="info-card">
                            <strong>Duration</strong>
                            <p><?php echo date('M d, Y', strtotime($classDetails['start_date'])); ?> - <?php echo date('M d, Y', strtotime($classDetails['end_date'])); ?></p>
                        </div>
                        <div class="info-card">
                            <strong>Class Size</strong>
                            <p>
                                <span class="d-flex align-items-center gap-2">
                                    <span><?php echo isset($classDetails['enrolled_students']) ? $classDetails['enrolled_students'] : 0; ?>/<?php echo $classDetails['class_size'] ? $classDetails['class_size'] : 'Unlimited'; ?> students</span>
                                    <?php if (isset($classDetails['enrolled_students']) && $classDetails['class_size']): ?>
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo ($classDetails['enrolled_students'] / $classDetails['class_size']) * 100; ?>%">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </span>
                            </p>
                        </div>
                        <div class="info-card">
                            <strong>Completion Rate</strong>
                            <p>
                                <span class="d-flex align-items-center gap-2">
                                    <span><?php echo number_format($completion_rate, 1); ?>%</span>
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $completion_rate; ?>%">
                                        </div>
                                    </div>
                                </span>
                            </p>
                        </div>
                        <div class="info-card">
                            <strong>Average Rating</strong>
                            <p class="d-flex align-items-center gap-2">
                                <span><?php echo $rating; ?>/5.0</span>
                                <span class="text-warning">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= round($rating) ? '-fill' : ''; ?> small"></i>
                                    <?php endfor; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h4 class="mb-3">Description</h4>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                    </div>
                </div>

                <!-- Class Schedule -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Class Schedule</h3>
                        <a href="details/schedules?id=<?php echo htmlspecialchars($class_id); ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-calendar-plus"></i> Manage Schedule
                        </a>
                    </div>
                    <div class="schedule-table">
                        <table class="table table-hover align-middle">
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
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-primary">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>
                                            <?php echo date('M d, Y', strtotime($schedule['session_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-primary">
                                                <i class="bi bi-clock"></i>
                                            </div>
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?>
                                        </div>
                                    </td>
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
                                        <?php if ($schedule['status'] === 'confirmed'): ?>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="joinMeeting(<?php echo $schedule['schedule_id']; ?>)"
                                                    data-bs-toggle="tooltip"
                                                    title="Join the virtual classroom">
                                                <i class="bi bi-camera-video me-1"></i> Join Meeting
                                            </button>
                                        <?php elseif ($schedule['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="startSession(<?php echo $schedule['schedule_id']; ?>)"
                                                    data-bs-toggle="tooltip"
                                                    title="Start the virtual classroom">
                                                <i class="bi bi-play-circle me-1"></i> Start Session
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Class Materials -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Class Materials</h3>
                        <button class="btn btn-primary btn-sm" onclick="uploadMaterial()">
                            <i class="bi bi-upload me-1"></i> Upload Material
                        </button>
                    </div>
                    <div class="materials-list">
                        <?php if (empty($files)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                                <p class="mt-3 mb-0">No materials uploaded yet</p>
                                <small class="d-block mt-2">Upload study materials for your students</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                            <div class="material-item">
                                <div class="material-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($file['file_name']); ?></h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Uploaded by <?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?> 
                                            on <?php echo date('M d, Y', strtotime($file['upload_time'])); ?>
                                        </small>
                                        <div class="btn-group">
                                            <a href="download?uuid=<?php echo $file['file_uuid']; ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               data-bs-toggle="tooltip"
                                               title="Download material">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($file['user_id'] === $_SESSION['user']): ?>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteMaterial('<?php echo $file['file_uuid']; ?>')"
                                                    data-bs-toggle="tooltip"
                                                    title="Delete material">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Students List -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Enrolled Students</h3>
                        <span class="badge bg-primary">
                            <?php echo count($students); ?> Students
                        </span>
                    </div>
                    <div class="students-list">
                        <?php if (empty($students)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                <p class="mt-3 mb-0">No students enrolled yet</p>
                                <small class="d-block mt-2">Share your class code to get students enrolled</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                            <div class="student-item">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo IMG . 'users/' . $student['profile_picture']; ?>" 
                                         class="rounded-circle me-3" 
                                         width="40" 
                                         height="40"
                                         alt="<?php echo htmlspecialchars($student['first_name']); ?>'s profile picture">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo $student['completed_sessions']; ?>/<?php echo $student['total_sessions']; ?> sessions
                                            </small>
                                            <small class="text-<?php echo ($student['completed_sessions'] / max($student['total_sessions'], 1) * 100) >= 75 ? 'success' : 'warning'; ?>">
                                                <?php echo round(($student['completed_sessions']/$student['total_sessions']*100)); ?>%
                                            </small>
                                        </div>
                                        <div class="progress student-progress">
                                            <div class="progress-bar bg-<?php echo ($student['completed_sessions']/$student['total_sessions']*100) >= 75 ? 'success' : 'warning'; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($student['completed_sessions']/$student['total_sessions']*100); ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h3 class="mb-4">Quick Actions</h3>
                    <div class="d-grid gap-3">
                        <a href="details/schedules?id=<?php echo htmlspecialchars($class_id); ?>" 
                           class="btn btn-primary quick-action">
                            <i class="bi bi-calendar-check me-2"></i> Manage Schedule
                        </a>
                        <button class="btn btn-info quick-action" onclick="messageStudents()">
                            <i class="bi bi-chat-dots me-2"></i> Message Students
                        </button>
                        <button class="btn btn-success quick-action" onclick="viewAnalytics()">
                            <i class="bi bi-graph-up me-2"></i> View Analytics
                        </button>
                        <button class="btn btn-outline-primary quick-action" onclick="shareClass()">
                            <i class="bi bi-share me-2"></i> Share Class
                        </button>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Initialize upload modal
            window.uploadModal = new bootstrap.Modal(document.getElementById('uploadMaterialModal'));
        });

        function uploadMaterial() {
            window.uploadModal.show();
        }

        function submitMaterial() {
            const file = document.getElementById('materialFile').files[0];
            const description = document.getElementById('materialDescription').value;

            if (!file) {
                showToast('error', 'Please select a file to upload');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('description', description);
            formData.append('class_id', '<?php echo $class_id; ?>');

            showLoading(true);

            fetch(BASE + 'upload-material', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    window.uploadModal.hide();
                    showToast('success', 'Material uploaded successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message || 'Failed to upload material');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'An error occurred while uploading');
            });
        }

        function deleteMaterial(fileUuid) {
            if (!confirm('Are you sure you want to delete this material?')) return;

            showLoading(true);

            fetch(BASE + 'delete-material', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `file_uuid=${fileUuid}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Material deleted successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message || 'Failed to delete material');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'An error occurred while deleting');
            });
        }

        function messageStudents() {
            // TODO: Implement messaging functionality
            showToast('info', 'Messaging feature coming soon');
        }

        function viewAnalytics() {
            window.location.href = `analytics?class_id=<?php echo $class_id; ?>`;
        }

        function shareClass() {
            const classUrl = window.location.href;
            navigator.clipboard.writeText(classUrl).then(() => {
                showToast('success', 'Class link copied to clipboard');
            }).catch(() => {
                showToast('error', 'Failed to copy class link');
            });
        }

        function startSession(scheduleId) {
            if (!confirm("Are you sure you want to start this session?")) return;

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
                    showToast('success', "Meeting room was successfully generated");
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast('error', data.message || "Failed to create meeting room");
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', "Failed to start the meeting. Please try again.");
            });
        }

        function joinMeeting(scheduleId) {
            showLoading(true);

            fetch(BASE+'join-meeting', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `schedule_id=${scheduleId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    window.location.href = data.data.join_url;
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
    </script>
</body>
</html>