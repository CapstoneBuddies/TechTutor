<?php 
    require_once 'main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'transactions_management.php';

    
    $enrolled_classes = [];
    $schedules = [];
    $transactions = [];
    $stats = [];
    
    try {
        // Get enrolled classes using centralized function with more details
        $enrolled_classes = getStudentClasses($_SESSION['user']);
        
        // Get upcoming class schedules with additional details
        $schedules = getUpcomingClassSchedules($_SESSION['user']);
        
        // Get recent transactions with more transaction details
        $transactions = getRecentTransactions($_SESSION['user']);
        
        // Get student learning statistics
        $stats = getStudentLearningStats($_SESSION['user']);
        
        // Add progress tracking for enrolled classes
        foreach ($enrolled_classes as &$class) {
            $class['progress'] = getClassProgress($class['class_id'], $_SESSION['user']);
        }
        unset($class);
        
    } catch (Exception $e) {
        log_error("Dashboard error: " . $e->getMessage(), 2);
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>" class="techkid-dashboard">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Dashboard Content -->
    <div class="dashboard-content container-fluid py-4">
        <!-- Welcome Section -->
        <div class="welcome-section mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="display-6 fw-bold mb-1">Keep it going, <?php echo explode(' ', $_SESSION['name'])[0]; ?>! 👋</h1>
                    <p class="text-muted mb-0">Welcome back to your learning journey</p>
                </div>
                <div class="col-auto">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">TechKid</span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-section mb-4">
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary-subtle text-primary rounded-3 p-3 me-3">
                                    <i class="bi bi-book fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Enrolled Classes</h6>
                                    <h3 class="stat-value mb-0"><?php echo count($enrolled_classes); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success-subtle text-success rounded-3 p-3 me-3">
                                    <i class="bi bi-calendar-check fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Upcoming Classes</h6>
                                    <h3 class="stat-value mb-0"><?php echo count($schedules); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning-subtle text-warning rounded-3 p-3 me-3">
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Hours Spent</h6>
                                    <h3 class="stat-value mb-0"><?php echo $stats['hours_spent']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info-subtle text-info rounded-3 p-3 me-3">
                                    <i class="bi bi-mortarboard fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Completed Classes</h6>
                                    <h3 class="stat-value mb-0"><?php echo $stats['completed_classes']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrolled Classes -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title section-title mb-0">
                                <i class="bi bi-collection-play me-2 text-primary"></i>
                                Enrolled Classes
                            </h5>
                            <div>
                                <div class="dropdown d-inline-block me-2">
                                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="filterClasses" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-funnel me-1"></i>Filter
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterClasses">
                                        <li><a class="dropdown-item filter-class active" href="#" data-filter="all">All Classes</a></li>
                                        <li><a class="dropdown-item filter-class" href="#" data-filter="active">Active</a></li>
                                        <li><a class="dropdown-item filter-class" href="#" data-filter="completed">Completed</a></li>
                                        <li><a class="dropdown-item filter-class" href="#" data-filter="pending">Pending</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item filter-class" href="#" data-filter="recent">Recently Added</a></li>
                                    </ul>
                                </div>
                                <a href="<?php echo BASE; ?>dashboard/s/class" class="btn btn-primary btn-sm">
                                    View All <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="row g-4" id="classes-container">
                            <?php if (empty($enrolled_classes)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <div class="empty-state-icon mb-3">
                                        <i class="bi bi-journal-x text-muted" style="font-size: 48px;"></i>
                                    </div>
                                    <h3 class="h5 mb-3">No Classes Enrolled</h3>
                                    <p class="text-muted mb-4">Start your learning journey by enrolling in a class.</p>
                                    <a href="<?php echo BASE; ?>dashboard/s/enrollments" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                                <?php 
                                $displayed_classes = array_slice($enrolled_classes, 0, 3);
                                foreach ($displayed_classes as $class): 
                                ?>
                                <div class="col-md-6 col-lg-4 class-item" data-status="<?php echo $class['enrollment_status']; ?>">
                                    <div class="class-card card h-100 border-0 shadow-sm">
                                        <div class="position-relative">
                                            <img src="<?php echo CLASS_IMG . (!empty($class['thumbnail']) ? $class['thumbnail'] : 'default.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($class['class_name']); ?>"
                                                 class="card-img-top" style="height: 200px; object-fit: cover;">
                                            <span class="badge bg-<?php echo $class['enrollment_status'] === 'active' ? 'success' : ($class['enrollment_status'] === 'completed' ? 'primary' : 'secondary'); ?> position-absolute top-0 end-0 m-3">
                                                <?php echo ucfirst($class['enrollment_status']); ?>
                                            </span>
                                            <!-- Progress indicator -->
                                            <div class="progress position-absolute bottom-0 start-0 end-0 rounded-0" style="height: 5px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $class['progress']; ?>%"
                                                     aria-valuenow="<?php echo $class['progress']; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title mb-2"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                            <div class="mb-3">
                                                <span class="badge bg-primary-subtle text-primary me-2"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($class['course_name']); ?></span>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="<?php echo USER_IMG . ($class['tutor_avatar'] ?? 'default-avatar.png'); ?>" 
                                                     alt="Tutor" class="rounded-circle me-2" 
                                                     style="width: 24px; height: 24px; object-fit: cover;">
                                                <span class="text-muted small"><?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?></span>
                                            </div>
                                            <div class="progress mb-3" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $class['progress']; ?>%" 
                                                     aria-valuenow="<?php echo $class['progress']; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($class['enrollment_date'])); ?>
                                                </small>
                                                <a href="<?php echo BASE; ?>dashboard/s/class/details?id=<?php echo $class['class_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <!-- No classes found for filter message - initially hidden -->
                                <div class="col-12 text-center py-5 no-filtered-classes" style="display: none;">
                                    <div class="empty-state-icon mb-3">
                                        <i class="bi bi-filter-circle text-muted" style="font-size: 48px;"></i>
                                    </div>
                                    <h3 class="h5 mb-3">No <span class="filter-type-text">Active</span> Classes Found</h3>
                                    <p class="text-muted mb-4">You don't have any classes with this status.</p>
                                    <button class="btn btn-outline-primary reset-filter">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Show All Classes
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule and Transactions -->
        <div class="row">
            <!-- Class Schedule -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar-week me-2 text-primary"></i>
                                Upcoming Classes
                            </h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-view="day">Today</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-view="week">Week</button>
                                <button type="button" class="btn btn-outline-primary btn-sm active" data-view="month">Month</button>
                            </div>
                        </div>
                        <div class="schedule-list">
                            <?php if (empty($schedules)): ?>
                            <div class="text-center py-4">
                                <div class="empty-state-icon mb-3">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 32px;"></i>
                                </div>
                                <p class="text-muted">No upcoming classes scheduled</p>
                                <a href="<?php echo BASE; ?>dashboard/s/schedule" class="btn btn-primary btn-sm mt-3">
                                    View Schedule
                                </a>
                            </div>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                <div class="schedule-item card border-0 bg-light mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="d-flex gap-3">
                                                <div class="schedule-time text-center">
                                                    <div class="fs-5 fw-bold text-primary"><?php echo date('H:i', strtotime($schedule['time'])); ?></div>
                                                    <div class="small text-muted"><?php echo date('M d', strtotime($schedule['time'])); ?></div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($schedule['class_name']); ?></h6>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <img src="<?php echo USER_IMG . ($schedule['tutor_avatar'] ?? 'default-avatar.png'); ?>" 
                                                             alt="Tutor" class="rounded-circle me-2" 
                                                             style="width: 20px; height: 20px; object-fit: cover;">
                                                        <span class="text-muted small"><?php echo htmlspecialchars($schedule['tutor_name']); ?></span>
                                                    </div>
                                                    <div class="schedule-status <?php echo $schedule['active'] ? 'text-success' : 'text-muted'; ?>">
                                                        <i class="bi <?php echo $schedule['active'] ? 'bi-circle-fill' : 'bi-circle'; ?> me-1"></i>
                                                        <?php echo $schedule['status']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($schedule['active']): ?>
                                                <a href="<?php echo $schedule['meeting_link']; ?>" class="btn btn-success btn-sm">
                                                    Join Now
                                                </a>
                                                <?php else: ?>
                                                <button class="btn btn-outline-primary btn-sm" onclick="addToCalendar('<?php echo htmlspecialchars(json_encode($schedule)); ?>')">
                                                    <i class="bi bi-calendar-plus me-1"></i>Add
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="<?php echo BASE; ?>dashboard/s/schedule" class="btn btn-link btn-sm text-decoration-none">
                                        View All Schedules <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-receipt me-2 text-primary"></i>
                                Recent Transactions
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-filter="all">All Transactions</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="completed">Completed</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="pending">Pending</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="transaction-list">
                            <?php if (empty($transactions)): ?>
                            <div class="text-center py-4">
                                <div class="empty-state-icon mb-3">
                                    <i class="bi bi-receipt-cutoff text-muted" style="font-size: 32px;"></i>
                                </div>
                                <p class="text-muted">No recent transactions</p>
                                <a href="<?php echo BASE; ?>dashboard/s/transactions" class="btn btn-primary btn-sm mt-3">
                                    View All Transactions
                                </a>
                            </div>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <div class="transaction-item card border-0 bg-light mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($transaction['type']); ?></h6>
                                                <p class="mb-1 text-muted"><?php echo $transaction['message']; ?></p>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?> me-2">
                                                        <?php echo ucfirst($transaction['status']); ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock-history me-1"></i>
                                                        <?php echo $transaction['date']; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fs-5 fw-bold <?php echo $transaction['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $transaction['amount'] >= 0 ? '+' : '-'; ?>₱<?php echo number_format(abs($transaction['amount']), 2); ?>
                                                </div>
                                                <?php if ($transaction['status'] === 'pending'): ?>
                                                <button class="btn btn-link btn-sm text-decoration-none p-0 mt-2" 
                                                        onclick="viewTransaction('<?php echo $transaction['id']; ?>')">
                                                    View Details
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="<?php echo BASE; ?>dashboard/s/transactions" class="btn btn-link btn-sm text-decoration-none">
                                        View All Transactions <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <style>
        .techkid-dashboard {
            background-color: #f8f9fa;
        }
        
        .stat-card {
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .class-card {
            transition: transform 0.2s;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
        }
        
        .empty-state-icon {
            width: 80px;
            height: 80px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .schedule-time {
            min-width: 70px;
        }
        
        .transaction-item {
            transition: transform 0.2s;
        }
        
        .transaction-item:hover {
            transform: translateX(5px);
        }
        
        .progress {
            background-color: rgba(0,0,0,0.1);
        }
        
        .btn-group .btn-outline-primary:not(:last-child) {
            border-right: 1px solid #dee2e6;
        }
        
        .schedule-item, .transaction-item {
            border-radius: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .welcome-section h1 {
                font-size: 1.5rem;
            }
            
            .stat-card .stat-icon {
                width: 50px;
                height: 50px;
            }
            
            .stat-card .stat-value {
                font-size: 1.25rem;
            }
            
            .schedule-time {
                min-width: 60px;
            }
            
            .btn-group {
                display: none;
            }
        }
    </style>

    <script>
    // Add to Calendar functionality
    function addToCalendar(scheduleData) {
        const schedule = JSON.parse(scheduleData);
        // Implement calendar integration
        console.log('Adding to calendar:', schedule);
    }

    // View Transaction Details
    function viewTransaction(transactionId) {
        // Implement transaction details view
        console.log('Viewing transaction:', transactionId);
    }

    // Initialize tooltips and handle class filtering
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Schedule view buttons
        const viewButtons = document.querySelectorAll('[data-view]');
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                viewButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                // Implement view change functionality
                console.log('Changed view to:', this.dataset.view);
            });
        });
        
        // Class filtering functionality
        const filterButtons = document.querySelectorAll('.filter-class');
        const classItems = document.querySelectorAll('.class-item');
        const noFilteredClasses = document.querySelector('.no-filtered-classes');
        const filterTypeText = document.querySelector('.filter-type-text');

        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                let visibleCount = 0;
                
                // Update the filter type text in the no classes message
                if (filterTypeText) {
                    // Capitalize first letter
                    const filterDisplay = filter === 'all' ? 'Any' : filter.charAt(0).toUpperCase() + filter.slice(1);
                    filterTypeText.textContent = filterDisplay;
                }
                
                classItems.forEach(item => {
                    const status = item.dataset.status;
                    
                    if (filter === 'all' || status === filter) {
                        item.style.display = 'block';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Show/hide no filtered classes message
                if (noFilteredClasses) {
                    noFilteredClasses.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            });
        });
        
        // Reset filter button
        const resetButton = document.querySelector('.reset-filter');
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                const allFilter = document.querySelector('.filter-class[data-filter="all"]');
                if (allFilter) {
                    allFilter.click();
                }
            });
        }
        
        // Show active classes by default
        const activeFilter = document.querySelector('.filter-class[data-filter="active"]');
        if (activeFilter) {
            activeFilter.click();
        }
    });
    </script>
</body>
</html>