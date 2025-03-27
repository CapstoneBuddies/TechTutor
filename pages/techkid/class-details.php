<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'rating_management.php';

    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    // Get class ID from URL parameter
    $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
        <?php include ROOT_PATH . '/components/header.php'; ?>

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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-8">
                    <!-- Class Information -->
                    <div class="content-section mb-4">
                        <div class="class-info-card">
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
                                            <img src="<?php echo USER_IMG . $classDetails['techguru_profile']; ?>" 
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
                                <h3 class="section-title mb-3">Description</h3>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Class Sessions -->
                    <div class="content-section">
                        <div class="class-info-card">
                            <h2 class="section-title mb-4">Class Sessions</h2>
                            <div class="table-responsive">
                                <table class="session-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                            $is_ongoing = ($now >= $session_start && $now <= $session_end) || $meeting_result;
                                            $is_upcoming = $now < $session_start;
                                            $is_completed = $schedule['status'] === 'completed';
                                        ?>
                                        <tr>
                                            <td><?php echo $session_date->format('M d, Y'); ?></td>
                                            <td>
                                                <?php echo $start_time->format('g:i A'); ?> - 
                                                <?php echo $end_time->format('g:i A'); ?>
                                            </td>
                                            <td><?php echo $duration; ?> hrs</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    if ($is_completed) echo 'success';
                                                    elseif ($is_ongoing) echo 'primary';
                                                    elseif ($is_upcoming) echo 'info';
                                                    else echo 'secondary';
                                                ?>">
                                                    <?php 
                                                    if ($is_completed) echo 'Completed';
                                                    elseif ($is_ongoing) echo 'Ongoing';
                                                    elseif ($is_upcoming) echo 'Upcoming';
                                                    else echo 'Missed';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($is_ongoing): ?>
                                                    <a href="#" onclick="joinMeeting(<?php echo $schedule['schedule_id']; ?>)" 
                                                       class="btn btn-success btn-sm">
                                                        <i class="bi bi-camera-video-fill me-1"></i>
                                                        Join Meeting
                                                    </a>
                                                <?php elseif ($is_upcoming): ?>
                                                    <button class="btn btn-outline-primary btn-sm" disabled>
                                                        <i class="bi bi-clock me-1"></i>
                                                        Starts in <?php 
                                                            $diff = $now->diff($session_start);
                                                            if ($diff->days > 0) echo $diff->days . ' days';
                                                            elseif ($diff->h > 0) echo $diff->h . ' hours';
                                                            else echo $diff->i . ' minutes';
                                                        ?>
                                                    </button>
                                                <?php elseif ($is_completed): ?>
                                                    <?php 
                                                        $ratingManager = new RatingManagement();
                                                        $hasRated = $ratingManager->hasStudentRatedSession(
                                                            $schedule['schedule_id'], 
                                                            $_SESSION['user']
                                                        );
                                                    ?>
                                                    <?php if (!$hasRated): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-primary" 
                                                            onclick="showFeedbackModal(<?php echo $schedule['schedule_id']; ?>, <?php echo $classDetails['techguru_id']; ?>)"
                                                            data-toggle="tooltip"
                                                            title="Rate this session">
                                                        <i class="fas fa-star me-1"></i> Give Feedback
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i> Feedback Submitted
                                                    </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        Missed
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
                </div>

                <!-- Right Column -->
                <div class="col-md-4">
                    <!-- Class Resources -->
                    <div class="content-section mb-4">
                        <div class="class-info-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="section-title mb-0">Class Resources</h2>
                                <a href="files?id=<?php echo $class_id; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-folder2"></i> View All Resources
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
                                                    Uploaded by <?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?>
                                                    on <?php echo date('M d, Y', strtotime($file['upload_time'])); ?>
                                                </div>
                                            </div>
                                            <a href="<?php echo BASE . 'uploads/class/' . $file['file_path']; ?>" 
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

                    <!-- Class Recordings -->
                    <div class="content-section mb-4">
                        <div class="class-info-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="section-title mb-0">Class Recordings</h2>
                                <a href="recordings?id=<?php echo $class_id; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-camera-video"></i> View All Recordings
                                </a>
                            </div>
                            <div class="recordings-list">
                                <?php 
                                $recordings = getClassRecordings($class_id, 3); // Get only latest 3 recordings
                                if (empty($recordings)): 
                                ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-camera-video" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No recordings available yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recordings as $recording): ?>
                                        <div class="recording-item d-flex align-items-center p-3 border-bottom">
                                            <div class="flex-shrink-0 me-3">
                                                <i class="bi bi-camera-video-fill text-primary" style="font-size: 1.5rem;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Session on <?php echo date('F d, Y', strtotime($recording['session_date'])); ?></h6>
                                                <p class="text-muted small mb-0">
                                                    Duration: <?php echo $recording['duration']; ?> minutes
                                                    <span class="mx-2">•</span>
                                                    Recorded: <?php echo date('g:i A', strtotime($recording['created_at'])); ?>
                                                </p>
                                            </div>
                                            <a href="recordings?id=<?php echo $class_id; ?>&recording=<?php echo $recording['recording_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-play-fill"></i> Watch
                                            </a>
                                        </div>
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
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="content-section">
                        <div class="class-info-card">
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
            </div>
        </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

        <style>
            .dashboard-content {
                background-color: #F5F5F5;
                min-height: calc(100vh - 60px);
                padding: 1.5rem;
                border-radius: 12px;
            }
            .content-card {
                background: #fff;
                border-radius: 10px;
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
            }
            .progress-bar {
                background-color: var(--bs-primary);
            }
            .resource-item:hover {
                background-color: #f8f9fa;
            }
            .stat-label {
                color: #6c757d;
            }
            .stat-value {
                font-weight: 500;
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
        </style>

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
                                        <i class="fas fa-star"></i>
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

            fetch(BASE + 'submit-feedback', {
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
        function joinMeeting(scheduleId) {
            showLoading(true);

            fetch(BASE+'join-meeting', {
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
        </script>
</body>
</html>