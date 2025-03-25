<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    
    if (!isset($_SESSION)) {
        session_start();
    }

    // Check if user is logged in and is a TECHKID
    if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit;
    }

    $classes = [];
    $active_class = null;
    $unread_notifications = [];

    try {
        // Get student's classes using centralized function
        $classes = getStudentClasses($_SESSION['user']);


        // Get unread notifications for the student
        $unread_notifications = getUserNotifications($_SESSION['user'], $_SESSION['role'], true);
    } catch (Exception $e) {
        log_error("Class page error: " . $e->getMessage(), "database");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content bg">
            <!-- Header Section with Title and Search -->
            <div class="content-section mb-4">
                <div class="content-card bg-snow">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <h1 class="page-title mb-0">Classes</h1>
                            <div class="d-flex flex-column flex-md-row gap-3 w-100 w-md-auto">
                                <div class="search-section flex-grow-1">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-search text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" placeholder="Search">
                        </div>
                                </div>
                                <a href="enrollments" class="btn btn-primary d-flex align-items-center">
                                    <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section mb-4">
                <div class="content-card bg-snow">
                    <div class="card-body p-0">
                        <div class="sticky-top bg-snow px-4 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="section-title mb-0">Enrolled Classes</h2>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                                    <button type="button" class="btn btn-outline-primary" data-filter="active">Active</button>
                                    <button type="button" class="btn btn-outline-primary" data-filter="completed">Completed</button>
                                    <button type="button" class="btn btn-outline-primary" data-filter="dropped">Dropped</button>
                                </div>
                            </div>
                        </div>
                        <div class="enrolled-classes-wrapper px-4 py-3">
                            <div class="row g-4 flex-nowrap overflow-x-auto pb-2" id="classesGrid">
                                <?php if (empty($classes)): ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="bi bi-laptop text-muted" style="font-size: 48px;"></i>
                                        <h3 class="mt-3">No Classes Enrolled</h3>
                                        <p class="text-muted mb-4">Start your learning journey by enrolling in a class.</p>
                                <a href="enrollments" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                                <!-- No Results Message (Hidden by default) -->
                                <div class="col-12" id="noResultsMessage" style="display: none;">
                                    <div class="text-center py-5">
                                        <i class="bi bi-filter text-muted" style="font-size: 48px;"></i>
                                        <h3 class="mt-3">No Classes Found</h3>
                                        <p class="text-muted mb-4">No enrolled classes of this status</p>
                                    </div>
                                </div>
                                <?php foreach ($classes as $class): 
                                    $progress = $class['total_sessions'] > 0 
                                        ? round(($class['completed_sessions'] / $class['total_sessions']) * 100) 
                                        : 0;
                                ?>
                                <div class="col-12 col-sm-6 col-lg-4 class-item" style="min-width: 320px;" 
                                     data-status="<?php echo $class['enrollment_status']; ?>">
                                    <div class="class-card h-100">
                                        <div class="card-body">
                                            <div class="position-relative">
                                                <img src="<?php echo !empty($class['thumbnail']) ? CLASS_IMG . $class['thumbnail'] : CLASS_IMG . 'default.jpg'; ?>" 
                                                     class="card-img-top rounded mb-3" 
                                                     alt="<?php echo htmlspecialchars($class['class_name']); ?>">
                                                <div class="position-absolute top-0 end-0 m-2">
                                                    <?php 
                                                    $status_class = [
                                                        'active' => 'bg-success',
                                                        'completed' => 'bg-primary',
                                                        'dropped' => 'bg-danger'
                                                    ][$class['enrollment_status']];
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($class['enrollment_status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <h3 class="h5 mb-2"><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                            
                                    <div class="d-flex align-items-center mb-3">
                                                <img src="<?php echo !empty($class['tutor_avatar']) ? USER_IMG . $class['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                                                     class="rounded-circle me-2" 
                                             alt="Tutor" 
                                                     width="24" height="24">
                                                <span class="text-muted small">
                                                    <?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?>
                                                </span>
                                            </div>

                                            <div class="progress mb-3" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $progress; ?>%"
                                                     aria-valuenow="<?php echo $progress; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">
                                                    <?php echo $class['completed_sessions']; ?>/<?php echo $class['total_sessions']; ?> sessions
                                                </span>
                                                <a href="class/details?id=<?php echo $class['class_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule and Calendar Section -->
            <div class="row g-4">
                <!-- Available Schedule -->
                <div class="col-12 col-lg-8">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="section-title mb-0">Available Schedule</h2>
                                <div class="dropdown">
                                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-filter me-2"></i>Filter
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item active" href="#" data-filter="all">All Sessions</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="upcoming">Upcoming</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="completed">Completed</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="schedule-list">
                                <?php 
                                $schedule = getStudentSchedule($_SESSION['user']);
                                if (!empty($schedule)):
                                    $current_date = date('Y-m-d');
                                    foreach ($schedule as $session): 
                                        $session_date = date('Y-m-d', strtotime($session['session_date']));
                                        $session_start = strtotime($session['session_date'] . ' ' . $session['start_time']);
                                        $session_end = strtotime($session['session_date'] . ' ' . $session['end_time']);
                                        $current_time = time();
                                        
                                        // Determine session status
                                        $status = '';
                                        $status_class = '';
                                        if ($current_time > $session_end) {
                                            $status = 'Completed';
                                            $status_class = 'text-success';
                                        } elseif ($current_time >= $session_start && $current_time <= $session_end) {
                                            $status = 'In Progress';
                                            $status_class = 'text-primary';
                                        } else {
                                            $status = 'Upcoming';
                                            $status_class = 'text-warning';
                                        }
                                ?>
                                <div class="schedule-item" data-status="<?php echo strtolower($status); ?>">
                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                        <div class="schedule-date text-center px-3 py-2 rounded bg-light">
                                            <div class="h5 mb-0"><?php echo date('d', strtotime($session['session_date'])); ?></div>
                                            <div class="small text-muted"><?php echo date('M', strtotime($session['session_date'])); ?></div>
                                        </div>
                                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center flex-grow-1 gap-3">
                                            <div class="tutor-info d-flex align-items-center gap-2">
                                                <img src="<?php echo !empty($session['tutor_avatar']) ? BASE . 'assets/img/users/' . $session['tutor_avatar'] : BASE . 'assets/img/users/default.jpg'; ?>" 
                                                     class="tutor-avatar rounded-circle" 
                                                     alt="<?php echo htmlspecialchars($session['tutor_name']); ?>"
                                                     width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($session['class_name']); ?></h6>
                                                    <p class="text-muted mb-0 small"><?php echo htmlspecialchars($session['tutor_name']); ?></p>
                                                </div>
                                            </div>
                                            <div class="schedule-info ms-md-auto text-md-end">
                                                <p class="mb-1">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                </p>
                                                <p class="mb-0 small <?php echo $status_class; ?>">
                                                    <i class="bi bi-circle-fill me-1"></i><?php echo $status; ?>
                                                </p>
                            </div>
                        </div>
                                        <div class="schedule-action">
                                            <?php if ($status === 'In Progress'): ?>
                                            <a href="class/meeting?id=<?php echo $session['schedule_id']; ?>" 
                                               class="btn btn-primary">Join Now</a>
                                            <?php elseif ($status === 'Upcoming'): ?>
                                            <button class="btn btn-outline-primary" disabled>
                                                Starts in <?php echo human_time_diff($current_time, $session_start); ?>
                                            </button>
                        <?php else: ?>
                                        <span class="badge bg-success">Completed</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    </div>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 48px;"></i>
                                    <h3 class="h5 mt-3">No Classes Scheduled</h3>
                                    <p class="text-muted mb-4">Ready to start your learning journey?</p>
                                    <a href="enrollments" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="col-12 col-lg-4">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h2 class="section-title mb-4">Calendar</h2>
                            <div id="calendar"></div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Filter functionality for schedule items
                const filterButtons = document.querySelectorAll('.dropdown-item[data-filter]');
                const scheduleItems = document.querySelectorAll('.schedule-item');
                
                filterButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const filter = this.dataset.filter;
                        
                        // Update active state
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Filter schedule items
                        scheduleItems.forEach(item => {
                            if (filter === 'all' || item.dataset.status === filter) {
                                item.style.display = '';
                    } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                });

                // Calendar initialization with custom styling
                const calendarEl = document.getElementById('calendar');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: '',
                        center: 'title',
                        right: 'prev,next'
                    },
                    events: <?php 
                        $events = !empty($schedule) ? array_map(function($session) {
                            $start = $session['session_date'] . 'T' . $session['start_time'];
                            $end = $session['session_date'] . 'T' . $session['end_time'];
                            $current_time = time();
                            $session_start = strtotime($start);
                            
                            // Determine event color based on status
                            $color = '#6c757d'; // Default gray
                            if ($current_time > strtotime($end)) {
                                $color = '#198754'; // Completed - green
                            } elseif ($current_time >= $session_start && $current_time <= strtotime($end)) {
                                $color = '#0d6efd'; // In Progress - blue
                            } elseif ($current_time < $session_start) {
                                $color = '#ffc107'; // Upcoming - yellow
                            }
                            
                            return [
                                'title' => $session['class_name'],
                                'start' => $start,
                                'end' => $end,
                                'backgroundColor' => $color,
                                'borderColor' => $color,
                                'url' => $current_time >= $session_start && $current_time <= strtotime($end) 
                                    ? 'class/meeting?id=' . $session['schedule_id'] 
                                    : 'class/details?id='.$session['class_id'],
                                'description' => 'Tutor: ' . $session['tutor_name']
                            ];
                        }, $schedule) : [];
                        echo json_encode($events);
                    ?>,
                    height: 'auto',
                    dayMaxEvents: true,
                    eventDidMount: function(info) {
                        // Add tooltips to events
                        $(info.el).tooltip({
                            title: info.event.title + '\n' + info.event.extendedProps.description,
                            placement: 'top',
                            trigger: 'hover',
                            container: 'body'
                        });
                    }
                });
                calendar.render();
            });
        </script>

        <style>
            .class-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            }
            .class-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .card-img-top {
                height: 160px;
                width: 100%;
                object-fit: cover;
                border-radius: 8px;
            }
            .progress {
                background-color: #e9ecef;
                border-radius: 3px;
            }
            .progress-bar {
                background-color: var(--bs-primary);
            }
            .btn-group .btn-outline-primary.active {
                background-color: var(--bs-primary);
                color: white;
            }
            @media (max-width: 768px) {
                .btn-group {
                    width: 100%;
                    margin-top: 1rem;
                }
                .btn-group .btn {
                    flex: 1;
                    padding: 0.375rem;
                }
                .card-img-top {
                    height: 140px;
                }
            }
            .schedule-item {
                background: #fff;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: transform 0.2s ease-in-out;
            }
            .schedule-item:hover {
                transform: translateY(-2px);
            }
            .schedule-date {
                min-width: 60px;
            }
            .tutor-avatar {
                object-fit: cover;
            }
            .fc-event {
                cursor: pointer;
            }
            .fc-event-title {
                font-weight: 500;
            }
            .dropdown-item.active {
                background-color: var(--bs-primary);
                color: white;
            }
            @media (max-width: 768px) {
                .schedule-action {
                    width: 100%;
                    margin-top: 1rem;
                }
                .schedule-action .btn {
                    width: 100%;
                }
            }
            .sticky-top {
                z-index: 1020;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .enrolled-classes-wrapper {
                overflow: hidden;
            }
            .overflow-x-auto {
                overflow-x: auto;
                scrollbar-width: thin;
                -ms-overflow-style: none;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }
            .overflow-x-auto::-webkit-scrollbar {
                height: 6px;
            }
            .overflow-x-auto::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }
            .overflow-x-auto::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 3px;
            }
            .overflow-x-auto::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        </style>
</body>
</html>