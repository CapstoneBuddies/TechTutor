<?php 
    require_once('../../backends/management/main.php');
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'transactions_management.php';

    
    $enrolled_classes = [];
    $schedules = [];
    $transactions = [];
    $stats = [];
    
    try {
        // Get enrolled classes using centralized function
        $enrolled_classes = getStudentClasses($_SESSION['user']);
        
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
                        <h3>Enrolled Classes</h3>
                        <p class="stat-number"><?php echo count($enrolled_classes); ?></p>
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

        <!-- Enrolled Classes -->
        <div class="row">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title section-title mb-0">Enrolled Classes</h5>
                            <a href="<?php echo BASE; ?>dashboard/s/class" class="btn btn-outline-primary btn-sm">
                                View All <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="row g-4">
                            <?php if (empty($enrolled_classes)): ?>
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <i class="bi bi-journal-x text-muted" style="font-size: 48px;"></i>
                                    <h3 class="h5 mt-3">No Classes Enrolled</h3>
                                    <p class="text-muted mb-4">Start your learning journey by enrolling in a class.</p>
                                    <a href="<?php echo BASE; ?>dashboard/s/enrollments" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                                <?php 
                                // Show only the first 3 enrolled classes
                                $displayed_classes = array_slice($enrolled_classes, 0, 3);
                                foreach ($displayed_classes as $class): 
                                ?>
                                <div class="col-md-4">
                                    <div class="class-card h-100">
                                        <div class="card-body">
                                            <div class="position-relative">
                                                <img src="<?php echo CLASS_IMG . (!empty($class['thumbnail']) ? $class['thumbnail'] : 'default.jpg'); ?>" 
                                                     alt="<?php echo htmlspecialchars($class['class_name']); ?>"
                                                     class="card-img-top rounded mb-3">
                                                <span class="badge bg-<?php echo $class['enrollment_status'] === 'active' ? 'success' : ($class['enrollment_status'] === 'completed' ? 'primary' : 'secondary'); ?> position-absolute top-0 end-0 m-2">
                                                    <?php echo ucfirst($class['enrollment_status']); ?>
                                                </span>
                                            </div>
                                            <h6 class="card-title mb-2"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                            <p class="text-muted small mb-3">
                                                <span class="d-block"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                                <span class="d-block"><?php echo htmlspecialchars($class['course_name']); ?></span>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-muted small">
                                                    <div class="mb-1">
                                                        <i class="bi bi-person-circle me-1"></i>
                                                        <?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?>
                                                    </div>
                                                    <div>
                                                        <i class="bi bi-calendar-event me-1"></i>
                                                        <?php echo date('M d, Y', strtotime($class['enrollment_date'])); ?>
                                                    </div>
                                                </div>
                                                <a href="<?php echo BASE; ?>dashboard/s/class/details?id=<?php echo $class['class_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    View Details
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