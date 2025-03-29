<?php 
    require_once '../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';

    // Check if user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    $tutor_id = $_SESSION['user'];
    $students_data = getStudentByTutor($tutor_id);
    $classes_data = getTechGuruClasses($tutor_id);
    $stats = getClassStats($tutor_id);
    $title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        /* Custom Dashboard Styles */
        .dashboard-content {
            padding: 2rem;
            background-color: #f8f9fa;
        }

        .content-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }

        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .rating {
            background: rgba(255, 193, 7, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }

        .session-date {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 0.5rem;
            width: 4rem;
            text-align: center;
        }

        .session-date .month {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 500;
        }

        .session-date .day {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .session-item {
            transition: background-color 0.2s;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .session-item:hover {
            background-color: #f8f9fa;
        }

        .stats-card {
            padding: 1.25rem;
            border-radius: 0.75rem;
            height: 100%;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-icon {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .stats-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .table {
            font-size: 0.9375rem;
        }

        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .student-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            object-fit: cover;
        }

        .feedback-card {
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }

        .feedback-rating {
            color: #ffc107;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content">
            <!-- Header Section -->
            <div class="content-section mb-4">
                <div class="content-card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div>
                                <h1 class="page-title mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h1>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rating d-flex align-items-center">
                                        <i class="bi bi-star-fill text-warning me-2"></i>
                                        <span><?php echo number_format($_SESSION['rating'], 1); ?></span>
                                    </div>
                                    <span class="badge bg-primary px-3 py-2">TechGuru</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="dashboard/t/subjects" class="btn btn-primary d-flex align-items-center gap-2">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Create New Class</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Students Section -->
                    <div class="content-section mb-4">
                        <div class="content-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h2 class="section-title mb-0">
                                        <i class="bi bi-people-fill me-2 text-primary"></i>
                                        My Students
                                    </h2>
                                    <?php if (isset($students_data['count']) && $students_data['count'] > 0): ?>
                                        <a href="dashboard/t/students" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                                            <span>View All</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($students_data['count']) && $students_data['count'] > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="border-0">Student</th>
                                                    <th class="border-0">Class</th>
                                                    <th class="border-0">Next Session</th>
                                                    <th class="border-0">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students_data['students'] as $student): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <img src="<?php echo USER_IMG . ($student['profile_picture'] ?? 'default.jpg'); ?>" 
                                                                     alt="<?php echo htmlspecialchars($student['student_first_name']); ?>" 
                                                                     class="student-avatar">
                                                                <div>
                                                                    <div class="fw-semibold text-dark">
                                                                        <?php echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']); ?>
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        <?php echo htmlspecialchars($student['email']); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="dashboard/t/class/details?id=<?php echo $student['class_id']; ?>" 
                                                               class="text-decoration-none text-primary fw-medium">
                                                                <?php echo htmlspecialchars($student['class_name']); ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <?php if ($student['next_session_date']): ?>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="session-date">
                                                                        <div class="month"><?php echo date('M', strtotime($student['next_session_date'])); ?></div>
                                                                        <div class="day"><?php echo date('d', strtotime($student['next_session_date'])); ?></div>
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        <?php echo $student['next_session_time']; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">No scheduled sessions</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="student-details?id=<?php echo $student['student_id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary d-flex align-items-center gap-2">
                                                                <i class="bi bi-eye"></i>
                                                                <span>View</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                        <h6 class="fw-medium text-muted mb-2">No Students Yet</h6>
                                        <p class="text-muted small mb-0">Create a class to start teaching students</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Class Schedule -->
                    <div class="content-section">
                        <div class="content-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h2 class="section-title mb-0">
                                        <i class="bi bi-calendar3-week me-2 text-primary"></i>
                                        Upcoming Sessions
                                    </h2>
                                    <a href="dashboard/t/students" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                                        <span>View All</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                                
                                <?php if (isset($classes_data) && !empty($classes_data)): ?>
                                    <div class="sessions-list">
                                        <?php foreach ($classes_data as $class): ?>
                                            <?php if (!empty($class['next_session_date']) && $class['next_session_date'] !== 'No scheduled date'): ?>
                                                <div class="session-item d-flex align-items-center p-3">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="session-date">
                                                            <div class="month"><?php echo date('M', strtotime($class['next_session_date'])); ?></div>
                                                            <div class="day"><?php echo date('d', strtotime($class['next_session_date'])); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                                        <p class="text-muted small mb-0">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?php echo $class['next_session_time']; ?>
                                                            <span class="mx-2">•</span>
                                                            <i class="bi bi-people me-1"></i>
                                                            <?php echo $class['student_count']; ?> students
                                                        </p>
                                                    </div>
                                                    <div class="ms-3">
                                                        <?php if ($class['next_session_status'] === 'confirmed'): ?>
                                                            <a href="meeting/host?class=<?php echo $class['class_id']; ?>" 
                                                               class="btn btn-success btn-sm d-flex align-items-center gap-2">
                                                                <i class="bi bi-camera-video-fill"></i>
                                                                <span>Start</span>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-<?php echo $class['next_session_status'] === 'pending' ? 'warning' : 'secondary'; ?>">
                                                                <?php echo ucfirst($class['next_session_status']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="bi bi-calendar3 text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                        <h6 class="fw-medium text-muted mb-2">No Upcoming Sessions</h6>
                                        <p class="text-muted small mb-0">Schedule new sessions with your students</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Quick Stats -->
                    <div class="content-section mb-4">
                        <div class="content-card">
                            <div class="card-body p-4">
                                <h2 class="section-title mb-4">
                                    <i class="bi bi-graph-up me-2 text-primary"></i>
                                    Quick Stats
                                </h2>
                                <div class="row g-3">
                                    <!-- Active Students -->
                                    <div class="col-6">
                                        <div class="stats-card bg-primary bg-opacity-10">
                                            <div class="stats-icon bg-primary bg-opacity-10">
                                                <i class="bi bi-people-fill text-primary"></i>
                                            </div>
                                            <div class="stats-value text-primary">
                                                <?php echo $students_data['count'] ?? 0; ?>
                                            </div>
                                            <div class="stats-label">Active Students</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Active Classes -->
                                    <div class="col-6">
                                        <div class="stats-card bg-success bg-opacity-10">
                                            <div class="stats-icon bg-success bg-opacity-10">
                                                <i class="bi bi-book-fill text-success"></i>
                                            </div>
                                            <div class="stats-value text-success">
                                                <?php 
                                                    $active_classes = array_filter($classes_data, function($class) {
                                                        return $class['status'] === 'active';
                                                    });
                                                    echo count($active_classes);
                                                ?>
                                            </div>
                                            <div class="stats-label">Active Classes</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Completed Sessions -->
                                    <div class="col-6">
                                        <div class="stats-card bg-info bg-opacity-10">
                                            <div class="stats-icon bg-info bg-opacity-10">
                                                <i class="bi bi-check-circle-fill text-info"></i>
                                            </div>
                                            <div class="stats-value text-info">
                                                <?php 
                                                    $completed_sessions = array_reduce($classes_data, function($carry, $class) {
                                                        return $carry + ($class['completed_sessions'] ?? 0);
                                                    }, 0);
                                                    echo $completed_sessions;
                                                ?>
                                            </div>
                                            <div class="stats-label">Completed Sessions</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Average Rating -->
                                    <div class="col-6">
                                        <div class="stats-card bg-warning bg-opacity-10">
                                            <div class="stats-icon bg-warning bg-opacity-10">
                                                <i class="bi bi-star-fill text-warning"></i>
                                            </div>
                                            <div class="stats-value text-warning">
                                                <?php echo number_format($_SESSION['rating'], 1); ?>
                                            </div>
                                            <div class="stats-label">Average Rating</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Feedback -->
                    <div class="content-section">
                        <div class="content-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h2 class="section-title mb-0">
                                        <i class="bi bi-chat-square-quote me-2 text-primary"></i>
                                        Recent Feedback
                                    </h2>
                                    <a href="dashboard/t/class/feedbacks" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                                        <span>View All</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                                <?php if (!empty($students_data['students'])): ?>
                                    <?php 
                                        $feedback_students = array_filter($students_data['students'], function($student) {
                                            return !empty($student['student_feedback']);
                                        });
                                    ?>
                                    <?php if (!empty($feedback_students)): ?>
                                        <?php foreach (array_slice($feedback_students, 0, 3) as $student): ?>
                                            <div class="feedback-card">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <img src="<?php echo USER_IMG . ($student['profile_picture'] ?? 'default.jpg'); ?>" 
                                                         alt="Student" 
                                                         class="rounded-circle"
                                                         width="32"
                                                         height="32">
                                                    <div>
                                                        <div class="fw-medium">
                                                            <?php echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']); ?>
                                                        </div>
                                                        <div class="feedback-rating">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="bi bi-star<?php echo $i <= $student['student_rating'] ? '-fill' : ''; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-muted small mb-0">
                                                    <?php echo htmlspecialchars($student['student_feedback']); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="mb-3">
                                                <i class="bi bi-chat-square text-muted" style="font-size: 2rem;"></i>
                                            </div>
                                            <p class="text-muted small mb-0">No feedback received yet</p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="bi bi-chat-square text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                        <p class="text-muted small mb-0">No feedback available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            // Add any custom JavaScript here
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    </body>
</html>