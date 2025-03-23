<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';

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
                                        <li class="breadcrumb-item"><a href="<?php echo BASE; ?>./">My Classes</a></li>
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
                                            $session_date = new DateTime($schedule['session_date']);
                                            $start_time = new DateTime($schedule['start_time']);
                                            $end_time = new DateTime($schedule['end_time']);
                                            $duration = $end_time->diff($start_time)->format('%H:%I');
                                            
                                            $now = new DateTime();
                                            $session_start = new DateTime($schedule['session_date'] . ' ' . $schedule['start_time']);
                                            $session_end = new DateTime($schedule['session_date'] . ' ' . $schedule['end_time']);
                                            
                                            $is_ongoing = $now >= $session_start && $now <= $session_end;
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
                                                    <a href="<?php echo BASE; ?>meeting/join?schedule=<?php echo $schedule['schedule_id']; ?>" 
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
                                                    <button class="btn btn-outline-success btn-sm" disabled>
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Completed
                                                    </button>
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
                            <h2 class="section-title mb-4">Class Resources</h2>
                            <div class="resources-list">
                                <?php if (empty($files)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-folder2-open" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No resources available yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($files as $file): ?>
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
        </style>
    </body>
</html>