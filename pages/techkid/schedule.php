<?php 
    require_once '../../backends/main.php';
    require_once ROOT_PATH.'/backends/student_management.php';

    $schedule = [];
    try {
        // Get student's schedule using centralized function
        $schedule = getStudentSchedule($_SESSION['user']);
    } catch (Exception $e) {
        log_error("Schedule page error: " . $e->getMessage(), "database");
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="page-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>My Schedule</h1>
            <p>View and manage your upcoming classes and sessions</p>
        </div>

        <!-- Calendar View -->
        <div class="row">
            <div class="col-md-8">
                <div class="content-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="section-title mb-0">
                                <i class="bi bi-calendar-week"></i>
                                Calendar
                            </h2>
                            <div class="btn-group">
                                <button class="btn btn-outline" onclick="changeView('month')">Month</button>
                                <button class="btn btn-outline" onclick="changeView('week')">Week</button>
                                <button class="btn btn-outline" onclick="changeView('day')">Day</button>
                            </div>
                        </div>
                        <div id="calendar"></div>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <h2 class="section-title">
                    <i class="bi bi-clock"></i>
                    Today's Schedule
                </h2>
                <?php if (empty($schedule['today'])): ?>
                <div class="content-card">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-calendar2-check text-muted" style="font-size: 48px;"></i>
                        <h3 class="mt-3">No Classes Today</h3>
                        <p class="text-muted">Enjoy your free time!</p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($schedule['today'] as $class): ?>
                    <div class="content-card schedule-card mb-3">
                        <div class="card-body">
                            <div class="time-indicator">
                                <div class="time">
                                    <?php echo date('h:i A', strtotime($class['start_time'])); ?>
                                </div>
                                <div class="duration">
                                    <?php echo $class['duration']; ?> mins
                                </div>
                            </div>
                            <div class="class-info">
                                <h3 class="class-title"><?php echo $class['title']; ?></h3>
                                <div class="tutor-info">
                                    <img src="<?php echo $class['tutor_avatar']; ?>" alt="Tutor" class="tutor-avatar">
                                    <span class="tutor-name">with <?php echo $class['tutor_name']; ?></span>
                                </div>
                                <div class="class-topic">
                                    <strong>Topic:</strong> <?php echo $class['topic']; ?>
                                </div>
                            </div>
                            <div class="class-actions">
                                <?php if (strtotime($class['start_time']) <= time() && time() <= strtotime($class['end_time'])): ?>
                                <a href="<?php echo $class['meeting_url']; ?>" class="btn btn-primary">
                                    <i class="bi bi-camera-video-fill me-2"></i>Join Now
                                </a>
                                <?php else: ?>
                                <button class="btn btn-outline" onclick="showClassDetails('<?php echo $class['id']; ?>')">
                                    View Details
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Upcoming Classes -->
            <div class="col-md-4">
                <h2 class="section-title">
                    <i class="bi bi-calendar-check"></i>
                    Upcoming Classes
                </h2>

                <?php if (empty($schedule['upcoming'])): ?>
                <div class="content-card">
                    <div class="card-body text-center py-4">
                        <p class="text-muted">No upcoming classes scheduled</p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($schedule['upcoming'] as $class): ?>
                    <div class="content-card schedule-card mb-3">
                        <div class="card-body">
                            <div class="schedule-date">
                                <?php echo date('l, M d', strtotime($class['start_time'])); ?>
                            </div>
                            <div class="schedule-time">
                                <?php echo date('h:i A', strtotime($class['start_time'])); ?> - 
                                <?php echo date('h:i A', strtotime($class['end_time'])); ?>
                            </div>
                            <h3 class="class-title"><?php echo $class['title']; ?></h3>
                            <div class="tutor-info">
                                <img src="<?php echo $class['tutor_avatar']; ?>" alt="Tutor" class="tutor-avatar">
                                <span class="tutor-name"><?php echo $class['tutor_name']; ?></span>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-outline btn-sm w-100" onclick="showClassDetails('<?php echo $class['id']; ?>')">
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Quick Links -->
                <h2 class="section-title mt-4">
                    <i class="bi bi-link-45deg"></i>
                    Quick Links
                </h2>
                <div class="content-card">
                    <div class="list-group list-group-flush">
                        <a href="<?php echo BASE; ?>techkid/class" class="list-group-item list-group-item-action">
                            <i class="bi bi-camera-video me-2"></i>
                            My Classes
                        </a>
                        <a href="<?php echo BASE; ?>techkid/files" class="list-group-item list-group-item-action">
                            <i class="bi bi-folder2 me-2"></i>
                            Learning Materials
                        </a>
                        <a href="<?php echo BASE; ?>techkid/certificates" class="list-group-item list-group-item-action">
                            <i class="bi bi-award me-2"></i>
                            Certificates
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            initializeCalendar();
        });

        function initializeCalendar() {
            // Implementation for calendar initialization
            console.log('Initializing calendar');
            // You can implement a calendar library like FullCalendar here
        }

        function changeView(view) {
            // Implementation for changing calendar view
            console.log('Changing view to:', view);
        }

        function showClassDetails(classId) {
            // Implementation for showing class details
            console.log('Showing details for class:', classId);
        }
    </script>
</body>
</html>