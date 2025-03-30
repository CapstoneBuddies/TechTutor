<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'unified_file_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Process form submission for completing class (backward compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'completeClass') {
    require_once BACKEND.'class_management.php';
    $result = completeClass($class_id, $_SESSION['user']);
    
    if (is_array($result) && isset($result['success']) && !$result['success']) {
        header('Location: details?id=' . $class_id . '&error=completion_failed&message=' . urlencode($result['message']));
    } else {
        header('Location: details?id=' . $class_id . '&completed=1');
    }
    exit();
}

$status = getClassStatus($class_id);
log_error($status);

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id, $_SESSION['user']);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Get related data
$schedules = getClassSchedules($class_id);
$students = getClassStudents($class_id);
$files = getClassFiles($class_id);
$folders = getClassFolders($class_id);

// Calculate class statistics
$completion_rate = $classDetails['total_students'] > 0 
    ? ($classDetails['completed_students'] / $classDetails['total_students']) * 100 
    : 0;
$rating = number_format($classDetails['average_rating'] ?? 0, 1);
$title = htmlspecialchars($classDetails['class_name']);

// Add this function to the PHP section at the top of the file
function getFileIconClass($fileType) {
    $iconMap = [
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'application/pdf' => 'pdf',
        'application/msword' => 'word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        'application/vnd.ms-excel' => 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'text/plain' => 'text',
        'text/csv' => 'csv',
        'application/json' => 'code',
        'text/html' => 'code',
        'text/css' => 'code',
        'application/javascript' => 'code',
        'video/mp4' => 'play',
        'video/quicktime' => 'play',
        'audio/mpeg' => 'music',
        'audio/mp3' => 'music'
    ];
    
    if (isset($iconMap[$fileType])) {
        return $iconMap[$fileType];
    }
    
    // If file type contains "word", "excel", etc.
    $lowerType = strtolower($fileType);
    if (strpos($lowerType, 'word') !== false) return 'word';
    if (strpos($lowerType, 'excel') !== false || strpos($lowerType, 'sheet') !== false) return 'excel';
    if (strpos($lowerType, 'powerpoint') !== false || strpos($lowerType, 'presentation') !== false) return 'ppt';
    if (strpos($lowerType, 'pdf') !== false) return 'pdf';
    if (strpos($lowerType, 'image') !== false) return 'image';
    if (strpos($lowerType, 'zip') !== false || strpos($lowerType, 'archive') !== false) return 'zip';
    if (strpos($lowerType, 'text') !== false) return 'text';
    if (strpos($lowerType, 'video') !== false) return 'play';
    if (strpos($lowerType, 'audio') !== false) return 'music';
    if (strpos($lowerType, 'code') !== false || strpos($lowerType, 'html') !== false || 
        strpos($lowerType, 'css') !== false || strpos($lowerType, 'javascript') !== false) return 'code';
    
    // Default
    return 'text';
}
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

        <?php if (isset($_GET['completed']) && $_GET['completed'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> The class has been marked as completed and all students have been notified.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 'completion_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : 'Failed to complete the class. Please try again later.'; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php 
        // Show info alert about early completion if class is active and started but hasn't reached the 10-day mark yet
        $tenDaysAfterStart = date('Y-m-d', strtotime($classDetails['start_date'] . ' + 10 days'));
        $today = date('Y-m-d');
        $hasStarted = $today >= $classDetails['start_date'];
        
        if ($classDetails['status'] === 'active' && $hasStarted && $today < $tenDaysAfterStart): 
        ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            This class will be eligible for early completion on <strong><?php echo date('M d, Y', strtotime($tenDaysAfterStart)); ?></strong> (10 days after start date). The class will be automatically marked as completed one day after its end date.
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
                        <?php if($status != 'completed'): ?>
                        <a href="details/schedules?id=<?php echo htmlspecialchars($class_id); ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-calendar-plus"></i> Manage Schedule
                        </a>
                        <?php endif; ?>
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
                                            <div class="d-flex gap-2">
                                                <?php if(isset($_GET['ended'])): ?>
                                                <a href="#" onclick="joinMeeting(<?php echo $schedule['schedule_id']; ?>)" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="bi bi-camera-video-fill me-1"></i>
                                                    Rejoin Meeting
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="endMeeting(<?php echo $schedule['schedule_id']; ?>)">
                                                    <i class="bi bi-stop-circle-fill me-1"></i>
                                                    End Meeting
                                                </button>
                                                <?php else: ?>
                                                <a href="#" onclick="joinMeeting(<?php echo $schedule['schedule_id']; ?>)" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="bi bi-camera-video-fill me-1"></i>
                                             Join Meeting
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif($schedule['status'] === 'pending'): ?>
                                             <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-primary" 
                                                    onclick="startSession(<?php echo $schedule['schedule_id']; ?>)"
                                                    data-bs-toggle="tooltip"
                                                    title="Start the virtual classroom">
                                                <i class="bi bi-play-circle me-1"></i> Start Session
                                        </button>
                                            </div>
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
                        <div class="d-flex gap-2">
                            <a href="details/files?id=<?php echo $class_id; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-folder me-1"></i> View All Files
                            </a>
                            <?php if($status != 'completed'): ?>
                            <button class="btn btn-primary btn-sm" onclick="uploadMaterial()">
                                <i class="bi bi-upload me-1"></i> Upload Material
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="materials-list">
                        <?php if (empty($files) && empty($folders)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                                <p class="mt-3 mb-0">No materials uploaded yet</p>
                                <small class="d-block mt-2">Upload study materials for your students</small>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($folders)): ?>
                                <div class="folders mb-4">
                                    <h6 class="text-muted mb-3">Folders</h6>
                                    <div class="row g-3">
                                        <?php foreach ($folders as $folder): ?>
                                            <div class="col-md-6">
                                                <div class="folder-item d-flex align-items-center p-3 border rounded">
                                                    <i class="bi bi-folder-fill text-warning me-3 fs-4"></i>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($folder['folder_name']); ?></h6>
                                                        <small class="text-muted"><?php echo $folder['file_count']; ?> files</small>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-link text-dark" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="renameFolder(<?php echo $folder['id']; ?>, '<?php echo htmlspecialchars($folder['folder_name']); ?>')">
                                                                    <i class="bi bi-pencil me-2"></i> Rename
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#" onclick="deleteFolder(<?php echo $folder['id']; ?>)">
                                                                    <i class="bi bi-trash me-2"></i> Delete
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($files)): ?>
                                <div class="files">
                                    <h6 class="text-muted mb-3">Files</h6>
                                <?php foreach ($files as $file): ?>
                                        <div class="material-item">
                                            <div class="material-icon">
                                                <i class="bi bi-file-earmark-<?php echo getFileIconClass($file['file_type']); ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($file['file_name']); ?></h6>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?php if (isset($file['id']) && $file['id']): ?>
                                                            <i class="bi bi-folder me-1"></i> <?php echo htmlspecialchars($file['folder_name'] ?? 'Folder'); ?> •
                                                        <?php endif; ?>
                                                        <?php echo formatBytes($file['file_size']); ?> •
                                                        Uploaded by <?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?> 
                                                        on <?php echo date('M d, Y', strtotime($file['upload_time'])); ?>
                                                    </small>
                                                    <div class="btn-group">
                                                        <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           target="_blank"
                                                           data-bs-toggle="tooltip"
                                                           title="View/Download material">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <?php if ($file['user_id'] === $_SESSION['user']): ?>
                                                            <button class="btn btn-sm btn-outline-danger"
                                                                    onclick="deleteMaterial('<?php echo $file['file_id']; ?>')"
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
                                </div>
                            <?php endif; ?>
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
                                                <?php echo $student['completed_sessions']; ?>/<?php echo $student['all_sessions']; ?> sessions
                                            </small>
                                            <small class="text-<?php echo $student['attendance_rate'] >= 75 ? 'success' : 'warning'; ?>">
                                                <?php echo $student['attendance_rate']; ?>%
                                </small>
                                        </div>
                                        <div class="progress student-progress">
                                            <div class="progress-bar bg-<?php echo $student['attendance_rate'] >= 75 ? 'success' : 'warning'; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $student['attendance_rate']; ?>%">
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
                        <?php if($status !== 'completed'): ?>
                        <a href="details/schedules?id=<?php echo htmlspecialchars($class_id); ?>" 
                           class="btn btn-primary quick-action">
                            <i class="bi bi-calendar-check me-2"></i> Manage Schedule
                        </a>
                        <button class="btn btn-info quick-action" onclick="messageStudents()">
                            <i class="bi bi-chat-dots me-2"></i> Message Students
                        </button>
                        <?php 
                        // Show Complete Class button if:
                        // 1. Class status is active, AND
                        // 2. Class has been active for at least 10 days since start date
                        $tenDaysAfterStart = date('Y-m-d', strtotime($classDetails['start_date'] . ' + 10 days'));
                        $today = date('Y-m-d');

                        log_error($classDetails['status']);

                        if (in_array($classDetails['status'], ['active','completed']) && ($today >= $tenDaysAfterStart || $today >= $classDetails['end_date']) ): 
                        ?>
                        <button type="button" class="btn btn-dark quick-action w-100" onclick="completeClass(<?php echo $class_id; ?>)">
                            <i class="bi bi-check-circle me-2"></i> Complete Class
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-primary quick-action" onclick="showShareModal()">
                            <i class="bi bi-share me-2"></i> Share & Invite
                        </button>

                        <?php endif; ?>
                        
                        <button class="btn btn-success quick-action" onclick="viewAnalytics()">
                            <i class="bi bi-graph-up me-2"></i> View Analytics
                        </button>
                        <a href="details/recordings?id=<?php echo htmlspecialchars($class_id); ?>" 
                           class="btn btn-warning quick-action">
                            <i class="bi bi-camera-reels me-2"></i> View Recordings
                        </a>
                        <a href="details/feedbacks?id=<?php echo htmlspecialchars($class_id); ?>"
                           class="btn btn-secondary quick-action">
                            <i class="bi bi-star me-2"></i> View All Feedbacks
                        </a>
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
                            <label class="form-label">Folder</label>
                            <div class="input-group">
                                <select class="form-select" id="materialFolder">
                                    <option value="">Root Folder</option>
                                    <?php foreach ($folders as $folder): ?>
                                        <option value="<?php echo $folder['id']; ?>">
                                            <?php echo htmlspecialchars($folder['folder_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="showNewFolderInput()">
                                    <i class="bi bi-folder-plus"></i> New
                                </button>
                            </div>
                        </div>
                        <div id="newFolderInput" class="mb-3 d-none">
                            <label class="form-label">New Folder Name</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newFolderName">
                                <button type="button" class="btn btn-success" onclick="createFolder()">
                                    <i class="bi bi-check-lg"></i> Create
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="hideNewFolderInput()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
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

    <!-- Share and Invite Modal -->
    <div class="modal fade" id="shareInviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share & Invite Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Share Link Section -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Share Class Link</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="classLink" readonly>
                            <button class="btn btn-outline-primary" onclick="copyClassLink()">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Invite Students Section -->
                    <div>
                        <label class="form-label fw-bold">Invite Students</label>
                        <div class="mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="studentSearch" 
                                   placeholder="Type student email (min. 4 characters)"
                                   oninput="searchStudents(this.value)">
                        </div>
                        <div id="studentResults" class="list-group mb-3" style="max-height: 200px; overflow-y: auto;">
                            <!-- Search results will be populated here -->
                        </div>
                        <div id="selectedStudents" class="mb-3">
                            <!-- Selected students will be shown here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="sendInvites()">Send Invites</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Students Modal -->
    <div class="modal fade" id="messageStudentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="messageForm">
                        <div class="mb-3">
                            <label class="form-label">To:</label>
                            <select class="form-select" id="messageRecipients" multiple>
                                <option value="all">All Students</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['uid']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' <' . $student['email'] . '>'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject:</label>
                            <input type="text" class="form-control" id="messageSubject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message:</label>
                            <div id="messageEditor" style="height: 200px;"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="sendMessage()">Send Message</button>
                </div>
            </div>
        </div>
    </div> 

    <!-- Scripts -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        let selectedStudents = new Set();
        let shareInviteModal;
        let messageModal;
        let quill;

        // Wait for Select2 to load
        function initializeSelect2() {
            if (typeof $.fn.select2 === 'undefined') {
                setTimeout(initializeSelect2, 100);
                return;
            }
            
            // Initialize select2 for recipients
            $('#messageRecipients').select2({
                placeholder: 'Select recipients',
                width: '100%',
                dropdownParent: $('#messageStudentsModal') // This ensures proper modal display
            });
        }

         document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Initialize modals
            window.uploadModal = new bootstrap.Modal(document.getElementById('uploadMaterialModal'));
            shareInviteModal = new bootstrap.Modal(document.getElementById('shareInviteModal'));
            messageModal = new bootstrap.Modal(document.getElementById('messageStudentsModal'));

            // Set class link in share modal
            document.getElementById('classLink').value = window.location.href;

            // Initialize Quill editor with MutationObserver
            const editorContainer = document.getElementById('messageEditor');
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && !quill) {
                        initQuillEditor();
                    }
                });
            });

            observer.observe(editorContainer, {
                childList: true,
                subtree: true
            });

            function initQuillEditor() {
                quill = new Quill('#messageEditor', {
                    theme: 'snow',
                    placeholder: 'Write your message here...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    }
                });
            }

            // Initial Quill initialization
            initQuillEditor();

            // Initialize Select2 after libraries are loaded
            initializeSelect2();
        });

            function uploadMaterial() {
            window.uploadModal.show();
        }

        function submitMaterial() {
            const file = document.getElementById('materialFile').files[0];
            const description = document.getElementById('materialDescription').value;
            const folderId = document.getElementById('materialFolder').value;

            if (!file) {
                showToast('error', 'Please select a file to upload');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('description', description);
            formData.append('class_id', '<?php echo $class_id; ?>');
            formData.append('id', folderId);
            formData.append('action', 'upload');

            showLoading(true);

            fetch(BASE + 'api/materials?action=upload', {
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

            function deleteMaterial(fileId) {
            if (!confirm('Are you sure you want to delete this material?')) return;

            showLoading(true);

            fetch(BASE + 'api/materials?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    file_id: fileId
                })
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
            // Reset form
            document.getElementById('messageSubject').value = '';
            quill.setContents([]);
            $('#messageRecipients').val('all').trigger('change');
            messageModal.show();
        }

        function sendMessage() {
            // Get selected recipients
            let recipients = $('#messageRecipients').val();
            if (!recipients || recipients.length === 0) {
                showToast('warning', 'Please select at least one recipient');
                return;
            }

            // Get subject
            let subject = $('#messageSubject').val().trim();
            if (!subject) {
                showToast('warning', 'Please enter a subject');
                return;
            }

            // Get message content from Quill editor
            let messageContent = quill.root.innerHTML.trim();
            if (!messageContent || messageContent === '<p><br></p>') {
                showToast('warning', 'Please enter a message');
                return;
            }

            // Handle "All Students" option
            let selectedStudents = [];
            let allStudents = false;
            
            if (recipients.includes('all')) {
                allStudents = true;
            } else {
                selectedStudents = recipients;
            }
            
            // Check if email notifications should be sent
            let sendEmail = confirm('Would you like to send email notifications to the students as well?');
            let recipientCount = allStudents ? 'all students' : `${selectedStudents.length} student(s)`;
            
            // Display loading indicator
            $('#messageModalFooter').html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Sending...
                </button>
            `);

            // Send message
            fetch(BASE + 'api/send-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    class_id: <?php echo $class_id; ?>,
                    selected_students: selectedStudents,
                    all_students: allStudents,
                    subject: subject,
                    message: messageContent,
                    send_email: sendEmail
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset form
                    $('#messageRecipients').val(null).trigger('change');
                    $('#messageSubject').val('');
                    quill.root.innerHTML = '';
                    
                    // Close modal
                    messageModal.hide();
                    
                    // Show success message
                    showToast('success', `Message sent successfully to ${recipientCount}${sendEmail ? ' (with email notifications)' : ''}`);
                } else {
                    // Show error
                    showToast('error', data.message || 'Failed to send message');
                    
                    // Reset footer
                    $('#messageModalFooter').html(`
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="sendMessage()">Send Message</button>
                    `);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showToast('error', 'An error occurred while sending the message');
                
                // Reset footer
                $('#messageModalFooter').html(`
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="sendMessage()">Send Message</button>
                `);
            });
        }

        function viewAnalytics() {
            window.location.href = `details/analytics?class_id=<?php echo $class_id; ?>`;
        }

        function showShareModal() {
            document.getElementById('studentSearch').value = '';
            document.getElementById('studentResults').innerHTML = '';
            document.getElementById('selectedStudents').innerHTML = '';
            selectedStudents.clear();
            shareInviteModal.show();
        }

        function copyClassLink() {
            const classLink = document.getElementById('classLink');
            classLink.select();
            document.execCommand('copy');
            showToast('success', 'Class link copied to clipboard');
        }

        function searchStudents(query) {
            if (query.length < 4) {
                document.getElementById('studentResults').innerHTML = '';
                return;
            }

            fetch(BASE + 'api/search-students', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    query: query,
                    class_id: <?php echo $class_id; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('studentResults');
                resultsDiv.innerHTML = '';

                if (data.students && data.students.length > 0) {
                    data.students.forEach(student => {
                        if (!selectedStudents.has(student.user_id)) {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <img src="${BASE}assets/img/users/${student.profile_picture}" 
                                         class="rounded-circle me-2" 
                                         width="32" 
                                         height="32">
                                    <div>
                                        <div>${student.first_name} ${student.last_name}</div>
                                        <small class="text-muted">${student.email}</small>
                                    </div>
                                </div>
                            `;
                            item.onclick = (e) => {
                                e.preventDefault();
                                selectStudent(student);
                            };
                            resultsDiv.appendChild(item);
                        }
                    });
                } else {
                    resultsDiv.innerHTML = '<div class="text-center p-3 text-muted">No students found</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to search students');
            });
        }

        function selectStudent(student) {
            if (selectedStudents.has(student.user_id)) return;

            selectedStudents.add(student.user_id);
            console.log(selectedStudents);
            const selectedDiv = document.getElementById('selectedStudents');
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary me-2 mb-2';
            badge.innerHTML = `
                ${student.first_name} ${student.last_name}
                <button type="button" class="btn-close btn-close-white ms-2" 
                        onclick="removeStudent('${student.user_id}', this.parentElement)"></button>
            `;
            selectedDiv.appendChild(badge);
            document.getElementById('studentResults').innerHTML = '';
            document.getElementById('studentSearch').value = '';
        }

        function removeStudent(userId, element) {
            selectedStudents.delete(userId);
            element.remove();
        }

        function sendInvites() {
            if (selectedStudents.size === 0) {
                showToast('warning', 'Please select at least one student to invite');
                return;
            }

            showLoading(true);
            fetch(BASE + 'api/invite-students', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    class_id: <?php echo $class_id; ?>,
                    student_ids: Array.from(selectedStudents)
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Invitations sent successfully');
                    shareInviteModal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message || 'Failed to send invitations');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'Failed to send invitations');
            });
        }

        function startSession(scheduleId) {
            if (!confirm("Are you sure you want to start this session?")) return;

            showLoading(true);

            fetch(BASE+'api/meeting?action=create-meeting', {
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

            fetch(BASE+'api/meeting?action=join-meeting', {
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
        function endMeeting(scheduleId) {
            if (!confirm('Are you sure you want to end this meeting?')) {
                return;
            }
            showLoading(true);
            fetch(BASE + 'api/meeting?action=end-meeting', {
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
                    showToast('success', 'Meeting ended successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message || 'Failed to end meeting');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'Failed to end meeting');
            });
        }

        function showNewFolderInput() {
            document.getElementById('newFolderInput').classList.remove('d-none');
        }

        function hideNewFolderInput() {
            document.getElementById('newFolderInput').classList.add('d-none');
            document.getElementById('newFolderName').value = '';
        }

        function createFolder() {
            const folderName = document.getElementById('newFolderName').value.trim();
            if (!folderName) {
                showToast('warning', 'Please enter a folder name');
                return;
            }

            showLoading(true);
            fetch(BASE + 'api/materials?action=create-folder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    class_id: <?php echo $class_id; ?>,
                    folder_name: folderName
                })
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
                console.error('Error:', error);
                showToast('error', 'Failed to create folder');
            });
        }

        function renameFolder(folderId, currentName) {
            const newName = prompt('Enter new folder name:', currentName);
            if (!newName || newName === currentName) return;

            showLoading(true);
            fetch(BASE + 'api/materials?action=rename-folder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: folderId,
                    folder_name: newName
                })
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
                console.error('Error:', error);
                showToast('error', 'Failed to rename folder');
            });
        }

        function deleteFolder(folderId) {
            if (!confirm('Are you sure you want to delete this folder and all its contents? This action cannot be undone.')) return;

            showLoading(true);
            fetch(BASE + 'api/materials?action=delete-folder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folder_id: folderId
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Folder deleted successfully');
                    location.reload();
                } else {
                    showToast('error', data.message || 'Failed to delete folder');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'Failed to delete folder');
            });
        }

        function completeClass(classId) {
            if (!confirm('Are you sure you want to mark this class as completed early?\n\nThis will:\n- Change the class status to "completed"\n- Update all student enrollments to "completed"\n- Send notifications to all students\n\nThis action cannot be undone.')) {
                return;
            }

            showLoading(true);
            fetch(BASE + 'api/class-completion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `class_id=${classId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', data.message || 'Class has been successfully completed.');
                    // Redirect with success parameter
                    window.location.href = `details?id=${classId}&completed=1`;
                } else {
                    showToast('error', data.message || 'Failed to complete the class. Please try again later.');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'An error occurred while processing your request.');
            });
        }
    </script>
</body>
</html>