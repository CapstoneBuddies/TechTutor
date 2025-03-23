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
                            <div class="search-section w-100 w-md-auto">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" placeholder="Search">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section mb-4">
                <div class="content-card bg-snow">
                    <div class="card-body">
                        <h2 class="section-title mb-4">Enrolled Classes</h2>
                        <div class="row g-4">
                            <?php 
                            $enrolled_classes = getStudentClasses($_SESSION['user']);
                            $programming_classes = array_filter($enrolled_classes, function($class) {
                                return strpos(strtolower($class['subject_name']), 'programming') !== false;
                            });
                            
                            if (empty($programming_classes)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-laptop text-muted" style="font-size: 48px;"></i>
                                    <h3 class="mt-3">No Classes Enrolled</h3>
                                    <p class="text-muted mb-4">You haven't enrolled in any programming classes yet.</p>
                                    <a href="enrollments" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                    </a>
                                </div>
                            </div>
                            <?php else:
                                foreach ($programming_classes as $class): 
                            ?>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="class-card h-100">
                                    <div class="card-body">
                                             class="card-img-top rounded mb-3" 
                                        <img src="<?php echo !empty($class['thumbnail']) ? CLASS_IMG . $class['thumbnail'] : CLASS_IMG . 'default.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($class['class_name']); ?>">
                                        <h3 class="h5 mb-3"><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="status-badge enrolled">Enrolled</span>
                                            <a href="class-details.php?id=<?php echo $class['class_id']; ?>" class="stretched-link">
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endforeach;
                            endif;
                            ?>
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
                            <h2 class="section-title mb-4">Available Schedule</h2>
                            <div class="schedule-list">
                                <?php 
                                $schedule = getStudentSchedule($_SESSION['user']);
                                if (!empty($schedule)):
                                    foreach ($schedule as $session): 
                                ?>
                                <div class="schedule-item">
                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                        <div class="tutor-info d-flex align-items-center gap-2">
                                            <img src="<?php echo !empty($session['tutor_avatar']) ? BASE . $session['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                                 class="tutor-avatar rounded-circle" 
                                                 alt="<?php echo htmlspecialchars($session['tutor_name']); ?>"
                                                 width="40" height="40">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($session['tutor_name']); ?></h6>
                                                <div class="rating">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="schedule-info flex-grow-1 text-center text-md-start">
                                            <p class="mb-1">
                                                <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                            </p>
                                            <p class="text-muted mb-0">Incoming class session</p>
                                        </div>
                                        <div class="schedule-action w-100 w-md-auto">
                                            <?php if (strtotime($session['start_time']) <= time() && strtotime($session['end_time']) >= time()): ?>
                                            <button class="btn btn-primary w-100 w-md-auto">Join Class</button>
                                            <?php else: ?>
                                            <button class="btn btn-secondary w-100 w-md-auto" disabled>Not Available</button>
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
                            <?php if (!empty($schedule)): ?>
                            <div class="text-center mt-4">
                                <a href="#" class="text-primary">View All</a>
                            </div>
                            <?php endif; ?>
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
                // Initialize calendar
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
                            return [
                                'title' => $session['title'],
                                'start' => $session['session_date'] . 'T' . $session['start_time'],
                                'end' => $session['session_date'] . 'T' . $session['end_time'],
                                'className' => 'bg-primary'
                            ];
                        }, $schedule) : [];
                        echo json_encode($events);
                    ?>,
                    height: 'auto',
                    dayMaxEvents: true,
                    eventClick: function(info) {
                        // Show event details when clicked
                        const event = info.event;
                        alert(`Class: ${event.title}\nTime: ${event.start.toLocaleTimeString()} - ${event.end.toLocaleTimeString()}`);
                    }
                });
                calendar.render();
            });
        </script>
    </body>
</html>