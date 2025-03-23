<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';

    if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit;
    }

    $schedule = [];
    try {
        // Get student's schedule using centralized function
        $schedule = getStudentSchedule($_SESSION['user']);
    } catch (Exception $e) {
        log_error("Schedule page error: " . $e->getMessage(), "database");
    }

    // Group schedule by date
    $grouped_schedule = [];
    if (!empty($schedule)) {
        foreach ($schedule as $session) {
            $date = date('Y-m-d', strtotime($session['session_date']));
            if (!isset($grouped_schedule[$date])) {
                $grouped_schedule[$date] = [];
            }
            $grouped_schedule[$date][] = $session;
        }
    }
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
                            <h1 class="page-title mb-0">My Schedule</h1>
                            <p class="text-muted mb-0">Manage your classes and learning sessions</p>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary active" data-view="calendar">
                                    <i class="bi bi-calendar3 me-2"></i>Calendar
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-view="list">
                                    <i class="bi bi-list-ul me-2"></i>List
                                </button>
                            </div>
                            <button type="button" class="btn btn-primary" id="todayBtn">
                                <i class="bi bi-calendar-check me-2"></i>Today
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Calendar and List View -->
            <div class="col-lg-8">
                <div class="content-section">
                    <div class="content-card bg-snow">
                        <!-- Calendar View -->
                        <div id="calendarView">
                            <div id="calendar"></div>
                        </div>

                        <!-- List View (Initially Hidden) -->
                        <div id="listView" style="display: none;">
                            <div class="card-body p-0">
                                <div class="sticky-top bg-snow px-4 py-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h2 class="section-title mb-0">Schedule List</h2>
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
                                </div>
                                
                                <div class="schedule-list px-4 py-3">
                                    <?php if (empty($grouped_schedule)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar2 text-muted" style="font-size: 48px;"></i>
                                        <h3 class="mt-3">No Sessions Scheduled</h3>
                                        <p class="text-muted mb-4">Check back later for updates</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($grouped_schedule as $date => $sessions): ?>
                                        <div class="date-group mb-4">
                                            <h3 class="date-header h5 mb-3">
                                                <?php echo date('l, F j, Y', strtotime($date)); ?>
                                            </h3>
                                            <?php foreach ($sessions as $session): 
                                                $session_start = strtotime($session['session_date'] . ' ' . $session['start_time']);
                                                $session_end = strtotime($session['session_date'] . ' ' . $session['end_time']);
                                                $current_time = time();
                                                
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
                                            <div class="schedule-item mb-3" data-status="<?php echo strtolower($status); ?>">
                                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                                    <div class="schedule-time text-center px-3 py-2 rounded bg-light">
                                                        <div class="h5 mb-0"><?php echo date('h:i', strtotime($session['start_time'])); ?></div>
                                                        <div class="small text-muted"><?php echo date('A', strtotime($session['start_time'])); ?></div>
                                                    </div>
                                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center flex-grow-1 gap-3">
                                                        <div class="session-info">
                                                            <h4 class="h6 mb-1"><?php echo htmlspecialchars($session['class_name']); ?></h4>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <img src="<?php echo !empty($session['tutor_avatar']) ? USER_IMG . $session['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                                                                     class="rounded-circle" 
                                                                     alt="Tutor"
                                                                     width="24" height="24">
                                                                <span class="text-muted small">
                                                                    <?php echo htmlspecialchars($session['tutor_name']); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="session-duration ms-md-auto text-md-end">
                                                            <p class="mb-1 small">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                                <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                            </p>
                                                            <p class="mb-0 small <?php echo $status_class; ?>">
                                                                <i class="bi bi-circle-fill me-1"></i><?php echo $status; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="session-action">
                                                        <?php if ($status === 'In Progress'): ?>
                                                        <a href="meeting?id=<?php echo $session['schedule_id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="bi bi-camera-video-fill me-1"></i>Join Now
                                                        </a>
                                                        <?php elseif ($status === 'Upcoming'): ?>
                                                        <button class="btn btn-outline-primary btn-sm" disabled>
                                                            Starts in <?php echo human_time_diff($current_time, $session_start); ?>
                                                        </button>
                                                        <?php else: ?>
                                                        <span class="badge bg-success">Completed</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Today's Schedule -->
                <div class="content-section mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <h2 class="section-title mb-4">
                                <i class="bi bi-clock me-2"></i>Today's Schedule
                            </h2>
                            <?php
                            $today = date('Y-m-d');
                            $today_schedule = isset($grouped_schedule[$today]) ? $grouped_schedule[$today] : [];
                            ?>
                            <?php if (empty($today_schedule)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar2-check text-muted" style="font-size: 32px;"></i>
                                <p class="mt-2 mb-0">No sessions scheduled for today</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($today_schedule as $session): 
                                    $session_start = strtotime($session['session_date'] . ' ' . $session['start_time']);
                                    $current_time = time();
                                    $is_active = $current_time >= $session_start && $current_time <= strtotime($session['session_date'] . ' ' . $session['end_time']);
                                ?>
                                <div class="today-session-item mb-3 <?php echo $is_active ? 'active' : ''; ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="session-time text-center">
                                            <div class="h5 mb-0"><?php echo date('h:i', strtotime($session['start_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('A', strtotime($session['start_time'])); ?></div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h4 class="h6 mb-1"><?php echo htmlspecialchars($session['class_name']); ?></h4>
                                            <p class="mb-0 small text-muted">
                                                with <?php echo htmlspecialchars($session['tutor_name']); ?>
                                            </p>
                                        </div>
                                        <?php if ($is_active): ?>
                                        <a href="meeting?id=<?php echo $session['schedule_id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            Join
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="content-section">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <h2 class="section-title mb-4">
                                <i class="bi bi-graph-up me-2"></i>Schedule Stats
                            </h2>
                            <div class="row g-3">
                                <?php
                                $total_sessions = count($schedule);
                                $completed_sessions = count(array_filter($schedule, function($s) {
                                    return strtotime($s['session_date'] . ' ' . $s['end_time']) < time();
                                }));
                                $upcoming_sessions = count(array_filter($schedule, function($s) {
                                    return strtotime($s['session_date'] . ' ' . $s['start_time']) > time();
                                }));
                                ?>
                                <div class="col-6">
                                    <div class="stat-card text-center p-3 rounded bg-light">
                                        <div class="h2 mb-1"><?php echo $total_sessions; ?></div>
                                        <div class="small text-muted">Total Sessions</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card text-center p-3 rounded bg-light">
                                        <div class="h2 mb-1"><?php echo $completed_sessions; ?></div>
                                        <div class="small text-muted">Completed</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card text-center p-3 rounded bg-light">
                                        <div class="h2 mb-1"><?php echo $upcoming_sessions; ?></div>
                                        <div class="small text-muted">Upcoming</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card text-center p-3 rounded bg-light">
                                        <div class="h2 mb-1"><?php echo $total_sessions > 0 ? round(($completed_sessions / $total_sessions) * 100) : 0; ?>%</div>
                                        <div class="small text-muted">Completion</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize calendar
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode(array_map(function($session) {
                    $start = $session['session_date'] . ' ' . $session['start_time'];
                    $end = $session['session_date'] . ' ' . $session['end_time'];
                    $current_time = time();
                    $session_start = strtotime($start);
                    
                    // Determine event color based on status
                    $color = '';
                    if ($current_time > strtotime($end)) {
                        $color = '#198754'; // Completed - Success color
                    } elseif ($current_time >= $session_start && $current_time <= strtotime($end)) {
                        $color = '#0d6efd'; // In Progress - Primary color
                    } else {
                        $color = '#ffc107'; // Upcoming - Warning color
                    }
                    
                    return [
                        'title' => $session['class_name'],
                        'start' => $start,
                        'end' => $end,
                        'color' => $color,
                        'url' => $current_time >= $session_start && $current_time <= strtotime($end) 
                            ? BASE . 'techkid/meeting?id=' . $session['schedule_id']
                            : null,
                        'extendedProps' => [
                            'tutor' => $session['tutor_name'],
                            'status' => $current_time > strtotime($end) ? 'completed' : 
                                      ($current_time >= $session_start ? 'in-progress' : 'upcoming')
                        ]
                    ];
                }, $schedule)); ?>,
                eventDidMount: function(info) {
                    // Add tooltips to events
                    const tooltip = new bootstrap.Tooltip(info.el, {
                        title: `${info.event.title}\nTutor: ${info.event.extendedProps.tutor}`,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            });
            calendar.render();

            // View switching
            const viewBtns = document.querySelectorAll('[data-view]');
            const calendarView = document.getElementById('calendarView');
            const listView = document.getElementById('listView');

            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    viewBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    if (this.dataset.view === 'calendar') {
                        calendarView.style.display = 'block';
                        listView.style.display = 'none';
                        calendar.render(); // Re-render calendar when shown
                    } else {
                        calendarView.style.display = 'none';
                        listView.style.display = 'block';
                    }
                });
            });

            // Today button
            document.getElementById('todayBtn').addEventListener('click', function() {
                calendar.today();
            });

            // Filter functionality
            const filterLinks = document.querySelectorAll('[data-filter]');
            const scheduleItems = document.querySelectorAll('.schedule-item');

            filterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    filterLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    scheduleItems.forEach(item => {
                        if (filter === 'all' || item.dataset.status === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>