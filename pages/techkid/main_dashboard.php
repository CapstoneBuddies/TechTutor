<?php 
    require_once 'main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'transactions_management.php';

    
    $enrolled_courses = [];
    $schedules = [];
    $transactions = [];
    $stats = [];
    
    try {
        // Get enrolled courses using centralized function
        $enrolled_courses = getEnrolledCoursesForStudent($_SESSION['user']);
        
        // Get upcoming class schedules using centralized function
        $schedules = getUpcomingClassSchedules($_SESSION['user']);
        
        // Get recent transactions using centralized function
        $transactions = getRecentTransactions($_SESSION['user']);
        
        // Get student learning statistics using centralized function
        $stats = getStudentLearningStats($_SESSION['user']);
        
    } catch (Exception $e) {
        log_error("Dashboard error: " . $e->getMessage(), 2);
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Keep it going, <?php echo explode(' ', $_SESSION['name'])[0]; ?>!</h1>
            <p class="role">TechKid</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Enrolled Courses</h3>
                        <p class="stat-number"><?php echo count($enrolled_courses); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Upcoming Classes</h3>
                        <p class="stat-number"><?php echo count($schedules); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Hours Spent</h3>
                        <p class="stat-number"><?php echo $stats['hours_spent']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Completed Classes</h3>
                        <p class="stat-number"><?php echo $stats['completed_classes']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Sections -->
        <div class="row">
            <!-- Enrolled Courses -->
            <div class="col-md-12">
                <div class="card h-100">
                <div class="card-body">
                <h5 class="card-title section-title mb-4">My Enrolled Courses</h5>
                <div class="row">
                    <?php if (empty($enrolled_courses)): ?>
                    <div class="col-12">
                        <p class="text-muted">No courses enrolled yet</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($enrolled_courses as $course): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <img src="<?php echo $course['image']; ?>" alt="<?php echo $course['name']; ?>" class="img-fluid mb-3">
                                    <h5 class="card-title"><?php echo $course['name']; ?></h5>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted"><?php echo $course['status']; ?></span>
                                        <a href="<?php echo BASE; ?>pages/techkid/course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                            Continue
                                        </a>
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

        <!-- Schedule and Transactions -->
        <div class="row mt-4">
            <!-- Class Schedule -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming Classes</h5>
                        <div class="schedule-list">
                            <?php if (empty($schedules)): ?>
                            <p class="text-muted">No upcoming classes scheduled</p>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                <div class="schedule-item d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="schedule-time"><?php echo $schedule['time']; ?></div>
                                        <div class="schedule-status <?php echo $schedule['active'] ? 'text-success' : 'text-muted'; ?>">
                                            <?php echo $schedule['status']; ?>
                                        </div>
                                    </div>
                                    <button class="btn <?php echo $schedule['active'] ? 'btn-success' : 'btn-outline-primary'; ?> btn-sm">
                                        <?php echo $schedule['active'] ? 'Join Now' : 'View Details'; ?>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Recent Transactions</h5>
                        <div class="transaction-list">
                            <?php if (empty($transactions)): ?>
                            <p class="text-muted">No recent transactions</p>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <div class="transaction-item mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="mb-1"><?php echo $transaction['message']; ?></p>
                                            <small class="text-muted"><?php echo $transaction['date']; ?></small>
                                        </div>
                                        <span class="badge bg-success"><?php echo $transaction['amount']; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>