<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'rating_management.php'; 
    require_once BACKEND.'meeting_management.php';

    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    // Get class ID from URL parameter
    $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Check if student is enrolled in the class
    $check = checkStudentEnrollment($_SESSION['user'], $class_id);
    if(!$check) {
        header("location: ".BASE."dashboard/s/enrollments/class?id=".$class_id);
        exit();
    }

    // Initialize MeetingManagement class
    $meetingManager = new MeetingManagement();

    // Get class details
    $classDetails = getClassDetails($class_id);
    if (!$classDetails) {
        header('Location: ./');
        exit();
    }
    
    // Get class schedules and files
    $schedules = getClassSchedules($class_id);
    $files = getClassFiles($class_id);

    // Calculate progress
    $total_sessions = count($schedules);
    $completed_sessions = count(array_filter($schedules, function($schedule) {
        return $schedule['status'] == 'completed';
    }));
    $progress_percentage = $total_sessions > 0 ? ($completed_sessions / $total_sessions) * 100 : 0;

    $title = htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <!-- Page Loader -->
        <div id="page-loader">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="loading-text">Loading content...</div>
        </div>
        
        <script>
            // Show loading screen at the start of page load
            // This will be automatically hidden when DOMContentLoaded fires
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize any page-specific components after page loads
                initializePage();
            });
            
            function initializePage() {
                // Any class-details specific initialization can go here
                console.log('Class details page initialized');
            }
            
            // Use showLoading for AJAX operations
            function joinMeeting(scheduleId) {
                showLoading(true);

                fetch(`${BASE}api/meeting?action=join-meeting`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        schedule_id: scheduleId,
                        role: '<?php echo $_SESSION['role']; ?>'
                    })
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

            // Function to send a message to the TechGuru
            function sendMessageToTechGuru() {
                const subject = document.getElementById('messageSubject').value.trim();
                const message = document.getElementById('messageContent').value.trim();
                
                if (!subject) {
                    showToast('error', 'Please enter a subject for your message');
                    return;
                }
                
                if (!message) {
                    showToast('error', 'Please enter a message');
                    return;
                }
                
                showLoading(true);
                
                fetch(`${BASE}api/student-message`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: <?php echo $class_id; ?>,
                        subject: subject,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast('success', 'Message sent successfully');
                        bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
                        document.getElementById('messageForm').reset();
                    } else {
                        showToast('error', data.message || 'Failed to send message');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error sending message:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }

            // Function to drop a class
            function dropClass() {
                // Show confirmation dialog
                if (!confirm('Are you sure you want to drop this class? This action cannot be undone.')) {
                    return;
                }
                
                // Ask for a reason (optional)
                const reason = prompt('Please provide a reason for dropping this class (optional):');
                
                showLoading(true);
                
                fetch(`${BASE}api/drop-class`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: <?php echo $class_id; ?>,
                        reason: reason || ''
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast('success', data.message || 'Class dropped successfully');
                        // Redirect to classes page after a short delay
                        setTimeout(() => {
                            window.location.href = './'
                        }, 1500);
                    } else {
                        showToast('error', data.error || 'Failed to drop class');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error dropping class:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }
        </script>
        
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <div class="dashboard-content bg">
                <!-- Header Section -->
                <div class="content-section mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                        <ol class="breadcrumb mb-2">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($classDetails['class_name']); ?></li>
                                        </ol>
                                    </nav>
                                    <h1 class="page-title mb-1"><?php echo htmlspecialchars($classDetails['class_name']); ?></h1>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge bg-<?php echo $classDetails['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($classDetails['status']); ?>
                                        </span>
                                        <?php if ($classDetails['is_free']): ?>
                                            <span class="badge bg-info">Free Class</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="./" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Classes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row g-4">
                    <!-- Left Column -->
                    <div class="col-md-8">
                        <!-- Class Information -->
                        <div class="content-section mb-4">
                            <div class="content-card bg-snow">
                                <div class="card-body">
                                    <h2 class="section-title mb-4">Class Information</h2>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($classDetails['subject_name']); ?></p>
                                            <p><strong>Course:</strong> <?php echo htmlspecialchars($classDetails['course_name']); ?></p>
                                            <p><strong>Duration:</strong> <?php echo date('M d, Y', strtotime($classDetails['start_date'])); ?> - <?php echo date('M d, Y', strtotime($classDetails['end_date'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p>
                                                <strong>Tutor:</strong> 
                                                <span class="d-inline-flex align-items-center">
                                                    <img src="<?php echo !empty($classDetails['techguru_profile']) ? USER_IMG . $classDetails['techguru_profile'] : USER_IMG . 'default.jpg'; ?>" 
                                                         class="rounded-circle me-2" 
                                                         width="24" 
                                                         height="24"
                                                         alt="Tutor">
                                                    <?php echo htmlspecialchars($classDetails['techguru_name']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Your Progress:</strong> <?php echo $completed_sessions; ?>/<?php echo $total_sessions; ?> sessions</p>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $progress_percentage; ?>%"
                                                     aria-valuenow="<?php echo $progress_percentage; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <h3 class="h5 mb-3">Description</h3>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Sessions -->
                        <div class="content-section">
                            <div class="content-card bg-snow">
                                <div class="card-body">
                                    <h2 class="section-title mb-4">Class Sessions</h2>
                                    <div class="session-list">
                                        <?php if (empty($schedules)): ?>
                                            <div class="text-center py-5">
                                                <i class="bi bi-calendar-x text-muted" style="font-size: 48px;"></i>
                                                <h3 class="h5 mt-3">No Sessions Scheduled</h3>
                                                <p class="text-muted mb-0">There are no sessions scheduled for this class yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($schedules as $schedule): ?>
                                            <?php 
                                                // Check if meeting is running from the meetings table
                                                $stmt = $conn->prepare("
                                                    SELECT is_running 
                                                    FROM meetings 
                                                    WHERE schedule_id = ? 
                                                    ORDER BY created_at DESC 
                                                    LIMIT 1
                                                ");
                                                $stmt->bind_param("i", $schedule['schedule_id']);
                                                $stmt->execute();
                                                $meeting_result = $stmt->get_result()->fetch_assoc();

                                                $session_date = new DateTime($schedule['session_date']);
                                                $start_time = new DateTime($schedule['start_time']);
                                                $end_time = new DateTime($schedule['end_time']);
                                                $duration = $end_time->diff($start_time)->format('%H:%I');
                                                
                                                $now = new DateTime();
                                                $session_start = new DateTime($schedule['session_date'] . ' ' . $schedule['start_time']);
                                                $session_end = new DateTime($schedule['session_date'] . ' ' . $schedule['end_time']);

                                                // First check if there's an active meeting
                                                $is_meeting_active = $meeting_result && $meeting_result['is_running'];
                                                // Determine status based on database status first
                                                switch($schedule['status']) {
                                                    case 'completed':
                                                        $status_label = 'Completed';
                                                        $status_class = 'success';
                                                        $icon_class = 'bi-check-circle';
                                                        break;
                                                        
                                                    case 'canceled':
                                                        $status_label = 'Canceled';
                                                        $status_class = 'danger';
                                                        $icon_class = 'bi-x-circle';
                                                        break;

                                                    case 'confirmed':
                                                        // For confirmed sessions, check if it's ongoing
                                                        if ($is_meeting_active || ($now >= $session_start && $now <= $session_end)) {
                                                            $status_label = 'Ongoing';
                                                            $status_class = 'primary';
                                                            $icon_class = 'bi-broadcast';
                                                        }
                                                        else {
                                                            $status_label = 'Pending';
                                                            $status_class = 'warning';
                                                            $icon_class = 'bi-hourglass-split';
                                                        }
                                                        break;
                                                        
                                                    case 'pending':
                                                        if ($now <= $session_start) {
                                                            $status_label = 'Upcoming';
                                                            $status_class = 'info';
                                                            $icon_class = 'bi-clock';
                                                        } 
                                                        break;
                                                    default:
                                                            $status_label = 'Missed';
                                                            $status_class = 'secondary';
                                                            $icon_class = 'bi-x-circle';
                                                            break;
                                                }

                                                // Set boolean flags for UI control
                                                $is_ongoing = $status_label === 'Ongoing';
                                                $is_upcoming = $status_label === 'Upcoming';
                                                $is_completed = $status_label === 'Completed';
                                            ?>
                                            <div class="session-item mb-3">
                                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                                    <div class="session-date text-center px-3 py-2 rounded bg-light">
                                                        <div class="h5 mb-0"><?php echo $session_date->format('d'); ?></div>
                                                        <div class="small text-muted"><?php echo $session_date->format('M'); ?></div>
                                                    </div>
                                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center flex-grow-1 gap-3">
                                                        <div class="session-info">
                                                            <h6 class="mb-1"><?php echo $session_date->format('l, F d, Y'); ?></h6>
                                                            <p class="text-muted mb-0">
                                                                <i class="bi bi-clock me-1"></i> 
                                                                <?php echo $start_time->format('g:i A'); ?> - <?php echo $end_time->format('g:i A'); ?> 
                                                                <span class="ms-2 text-muted">•</span>
                                                                <span class="ms-2"><?php echo $duration; ?> hrs</span>
                                                            </p>
                                                        </div>
                                                        <div class="ms-md-auto d-flex align-items-center">
                                                            <span class="badge bg-<?php echo $status_class; ?> me-3">
                                                                <i class="bi <?php echo $icon_class; ?> me-1"></i> <?php echo $status_label; ?>
                                                            </span>
                                                            
                                                            <?php if ($is_ongoing): ?>
                                                                <?php
                                                                    // Check if the meeting exists in the database
                                                                    $meeting_stmt = $conn->prepare("
                                                                        SELECT meeting_uid 
                                                                        FROM meetings 
                                                                        WHERE schedule_id = ? 
                                                                        ORDER BY meeting_id DESC 
                                                                        LIMIT 1
                                                                    ");
                                                                    $meeting_stmt->bind_param("i", $schedule['schedule_id']);
                                                                    $meeting_stmt->execute();
                                                                    $meeting_result = $meeting_stmt->get_result();
                                                                    
                                                                    if ($meeting_result->num_rows > 0) {
                                                                        $meeting_data = $meeting_result->fetch_assoc();
                                                                        $meeting_uid = $meeting_data['meeting_uid'];
                                                                        
                                                                        // Check if the meeting is actually running
                                                                        $is_meeting_running = $meetingManager->isMeetingRunning($meeting_uid);
                                                                    } else {
                                                                        $is_meeting_running = false;
                                                                    }
                                                                ?>
                                                                
                                                                <?php if ($is_meeting_running): ?>
                                                                    <button onclick="joinMeeting(<?php echo $schedule['schedule_id']; ?>)" 
                                                                        class="btn btn-success btn-sm">
                                                                        <i class="bi bi-camera-video-fill me-1"></i> 
                                                                        <?php if(isset($_GET['ended']) && $_GET['ended'] == $schedule['schedule_id']): ?>
                                                                        Rejoin Now
                                                                        <?php else: ?>
                                                                        Join Now
                                                                        <?php endif; ?>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                                                        <i class="bi bi-hourglass-split me-1"></i> Waiting for meeting to start
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php elseif ($is_upcoming): ?>
                                                                <button class="btn btn-outline-primary btn-sm" disabled>
                                                                    <i class="bi bi-clock me-1"></i>
                                                                    <?php 
                                                                        $diff = $now->diff($session_start);
                                                                        if ($diff->days > 0) echo $diff->days . ' days';
                                                                        elseif ($diff->h > 0) echo $diff->h . ' hours';
                                                                        else echo $diff->i . ' minutes';
                                                                    ?> left
                                                                </button>
                                                            <?php elseif ($is_completed): ?>
                                                                <?php 
                                                                    $ratingManager = new RatingManagement();
                                                                    $hasRated = $ratingManager->hasStudentRatedSession(
                                                                        $schedule['schedule_id'], 
                                                                        $_SESSION['user']
                                                                    );
                                                                ?>
                                                                <div class="d-flex gap-2">
                                                                    <?php 
                                                                    $time_limit = isWithin24Hours($schedule['schedule_id']);
                                                                    if (!$hasRated && (!empty($time_limit) && $time_limit)): 
                                                                    ?>
                                                                        <button type="button" 
                                                                                class="btn btn-sm btn-primary" 
                                                                                onclick="showFeedbackModal(<?php echo $schedule['schedule_id']; ?>, <?php echo $classDetails['techguru_id']; ?>)">
                                                                            <i class="bi bi-star me-1"></i> Give Feedback
                                                                        </button>
                                                                    <?php elseif ($hasRated): ?>
                                                                        <span class="badge bg-success">
                                                                            <i class="bi bi-check-circle me-1"></i> Feedback Submitted
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">
                                                                            <i class="bi bi-clock me-1"></i> Feedback Period Expired
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php 
                                                                    // Check if recording exists
                                                                    $rec_stmt = $conn->prepare("SELECT recording_id FROM recording_visibility WHERE schedule_id = ? AND is_visible = 1");
                                                                    $rec_stmt->bind_param("i", $schedule['schedule_id']);
                                                                    $rec_stmt->execute();
                                                                    $recording = $rec_stmt->get_result()->fetch_assoc();
                                                                    if ($recording): 
                                                                    ?>
                                                                        <a href="#"
                                                                            onclick="window.open('recordings?id=<?php echo $class_id; ?>&recording=<?php echo $recording['recording_id']; ?>', '_blank')"
                                                                           class="btn btn-outline-secondary btn-sm">
                                                                            <i class="bi bi-play-circle me-1"></i> Watch Recording
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php elseif ($status_label === 'Pending'): ?>
                                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                                    <i class="bi bi-info-circle me-1"></i> Waiting for the Session to Start
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                                    <i class="bi bi-x-circle me-1"></i> Missed
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
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-4">
                        <!-- Quick Actions Card -->
                        <div class="content-section mb-4">
                            <div class="content-card bg-snow">
                                <div class="card-body">
                                    <h2 class="section-title mb-3">Quick Actions</h2>
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#messageModal">
                                            <i class="bi bi-chat-left-text"></i> Message TechGuru
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="dropClass()">
                                            <i class="bi bi-box-arrow-right"></i> Drop Class
                                        </button>
                                        <a href="details/feedbacks?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-star"></i> View Feedbacks
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Resources -->
                        <div class="content-section mb-4">
                            <div class="content-card bg-snow">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h2 class="section-title mb-0">Class Resources</h2>
                                        <a href="files?id=<?php echo $class_id; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-folder2"></i> View All
                                        </a>
                                    </div>
                                    <div class="resources-list">
                                        <?php if (empty($files)): ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-folder2-open" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">No resources available yet</p>
                                            </div>
                                        <?php else: ?>
                                            <?php 
                                            // Show only the first 3 files
                                            $displayFiles = array_slice($files, 0, 3); 
                                            foreach ($displayFiles as $file): 
                                            ?>
                                                <div class="resource-item">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                    <div class="resource-info">
                                                        <div class="resource-name"><?php echo htmlspecialchars($file['file_name']); ?></div>
                                                        <div class="resource-meta">
                                                            <?php echo date('M d, Y', strtotime($file['upload_time'])); ?>
                                                        </div>
                                                    </div>
                                                    <a href="https://drive.google.com/uc?export=download&id=<?php echo $file['google_file_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       download>
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($files) > 3): ?>
                                                <div class="text-center mt-3">
                                                    <a href="files?id=<?php echo $class_id; ?>" class="text-primary">
                                                        View <?php echo count($files) - 3; ?> more files...
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="content-section mb-4">
                            <div class="content-card bg-snow">
                                <div class="card-body">
                                    <h2 class="section-title mb-4">Quick Stats</h2>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="stat-card">
                                                <div class="value"><?php echo $completed_sessions; ?></div>
                                                <div class="label">Completed Sessions</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-card">
                                                <div class="value"><?php echo round($progress_percentage); ?>%</div>
                                                <div class="label">Completion Rate</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Class Recordings -->
                        <div class="content-section">
                            <div class="content-card bg-snow">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h2 class="section-title mb-0">Recordings</h2>
                                        <a href="recordings?id=<?php echo $class_id; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-camera-video"></i> View All
                                        </a>
                                    </div>
                                    <div class="recordings-list">
                                        <?php 
                                        $recordings = getClassRecordings($class_id); // Get only latest 3 recordings
                                        if(!empty($recordings)): 
                                        ?>
                                            <?php foreach ($recordings['recordings'] as $recording): ?>
                                                <?php if($recording['is_visible']): ?>
                                                <div class="recording-item d-flex align-items-center p-3 border-bottom">
                                                    <div class="flex-shrink-0 me-3">
                                                        <i class="bi bi-camera-video-fill text-primary" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Session on <?php echo date('F d, Y', strtotime($recording['session_date'])); ?></h6>
                                                        <p class="text-muted small mb-0">
                                                            Duration: <?php echo $recording['duration']; ?> minutes
                                                        </p>
                                                    </div>
                                                    <a href="#" onclick="window.open('recordings?id=<?php echo $class_id; ?>&recording=<?php echo $recording['recordID']; ?>', '_blank')"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-play-fill"></i> Watch
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php 
                                            $total_recordings = getClassRecordingsCount($class_id);
                                            if ($total_recordings > 3): 
                                            ?>
                                                <div class="text-center mt-3">
                                                    <a href="recordings?id=<?php echo $class_id; ?>" class="text-primary">
                                                        View <?php echo $total_recordings - 3; ?> more recordings...
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="bi bi-camera-video" style="font-size: 2rem;"></i>
                                                    <p class="mt-2 mb-0">No recordings available for viewing</p>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-camera-video" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">There is no recorded meeting yet</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Message Modal -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalLabel">Message to TechGuru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="messageForm">
                            <div class="mb-3">
                                <label for="messageSubject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="messageSubject" required>
                            </div>
                            <div class="mb-3">
                                <label for="messageContent" class="form-label">Message</label>
                                <textarea class="form-control" id="messageContent" rows="6" required></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="sendMessageToTechGuru()">Send Message</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <!-- Feedback Modal -->
        <div class="modal fade" id="feedbackModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Session Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="feedbackForm">
                            <input type="hidden" name="session_id" id="session_id">
                            <input type="hidden" name="tutor_id" id="tutor_id">
                            
                            <div class="mb-3 text-center">
                                <label class="form-label d-block">Rate this session</label>
                                <div class="rating-stars">
                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                                    <label for="star<?php echo $i; ?>">
                                        <i class="bi bi-star-fill"></i>
                                    </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Your Feedback</label>
                                <textarea class="form-control" name="feedback" rows="4" 
                                        placeholder="Share your experience about this session..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitFeedback()">Submit Feedback</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function showFeedbackModal(sessionId, tutorId) {
            document.getElementById('session_id').value = sessionId;
            document.getElementById('tutor_id').value = tutorId;
            const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            modal.show();
        }

        function submitFeedback() {
            const form = document.getElementById('feedbackForm');
            const formData = new FormData(form);
            
            // Validate rating
            if (!formData.get('rating')) {
                showToast('error', 'Please select a rating');
                return;
            }

            // Show loading
            showLoading(true);

            fetch(`${BASE}api/submit-feedback`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', 'Thank you for your feedback!');
                    bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message || 'Failed to submit feedback');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('error', 'Failed to submit feedback');
                console.error('Error:', error);
            });
        }
        </script>

        <style>
            .dashboard-content {
                padding: 1.5rem;
            }
            .content-section {
                margin-bottom: 1.5rem;
            }
            .content-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            .card-body {
                padding: 1.5rem;
            }
            .page-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0;
            }
            .section-title {
                font-size: 1.25rem;
                font-weight: 600;
                margin: 0;
            }
            .progress {
                background-color: #e9ecef;
                border-radius: 3px;
                height: 6px;
            }
            .progress-bar {
                background-color: var(--bs-primary);
            }
            .session-item {
                background: #fff;
                border-radius: 8px;
                padding: 1rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: transform 0.2s ease-in-out;
            }
            .session-item:hover {
                transform: translateY(-2px);
            }
            .session-date {
                min-width: 60px;
            }
            .session-info {
                flex: 1;
            }
            .resource-item {
                display: flex;
                align-items: center;
                padding: 0.75rem;
                border-radius: 8px;
                margin-bottom: 0.5rem;
                background-color: #f8f9fa;
                transition: all 0.2s ease;
            }
            .resource-item:hover {
                background-color: #e9ecef;
            }
            .resource-item i {
                font-size: 1.5rem;
                color: var(--bs-primary);
                margin-right: 1rem;
            }
            .resource-info {
                flex-grow: 1;
                overflow: hidden;
            }
            .resource-name {
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 200px;
            }
            .resource-meta {
                font-size: 0.75rem;
                color: #6c757d;
            }
            .recording-item {
                border-radius: 8px;
                margin-bottom: 0.5rem;
                transition: all 0.2s ease;
            }
            .recording-item:hover {
                background-color: #f8f9fa;
            }
            .stat-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1rem;
                text-align: center;
            }
            .stat-card .value {
                font-size: 1.5rem;
                font-weight: bold;
                color: var(--bs-primary);
            }
            .stat-card .label {
                font-size: 0.9rem;
                color: #6c757d;
            }
            .session-info h6 {
                font-weight: 600;
            }
            .rating-stars {
                display: inline-flex;
                flex-direction: row-reverse;
                gap: 0.25rem;
            }
            .rating-stars input {
                display: none;
            }
            .rating-stars label {
                cursor: pointer;
                color: #ddd;
                font-size: 1.5rem;
            }
            .rating-stars label:hover,
            .rating-stars label:hover ~ label,
            .rating-stars input:checked ~ label {
                color: #ffd700;
            }
            @media (max-width: 768px) {
                .dashboard-content {
                    padding: 1rem;
                }
                .card-body {
                    padding: 1rem;
                }
                .page-title {
                    font-size: 1.25rem;
                }
                .section-title {
                    font-size: 1.1rem;
                }
                .session-item {
                    padding: 0.75rem;
                }
                .session-info {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }
                .session-item .ms-md-auto {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.5rem;
                    margin-top: 0.5rem;
                    width: 100%;
                }
                .session-item .badge {
                    margin-bottom: 0.5rem;
                }
                .session-item .d-flex.gap-2 {
                    flex-direction: column;
                    width: 100%;
                }
                .session-item .btn {
                    width: 100%;
                    margin-right: 0 !important;
                }
            }
        </style>
    </body>
</html>