<?php
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'meeting_management.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'rating_management.php';
    
    // Ensure user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    $tutor_id = $_SESSION['user'];
    
    // Initialize data arrays
    try {
        // Initialize variables with default values
        $rating_data = [
            'average_rating' => 0,
            'total_ratings' => 0
        ];
        $performance_trends = [
            'labels' => [],
            'student_performance' => [],
            'completion_rates' => [],
            'attendance_rates' => []
        ];
        $rating_distribution = [
            'labels' => [],
            'counts' => [],
            'colors' => []
        ];

        // Get teaching statistics
        $teaching_stats = getTechGuruStats($tutor_id);

        // Get class statistics including earnings
        $class_stats = getClassStats($tutor_id);
        
        // Get filter period from GET parameter or default to 'all_time'
        $filter_period = isset($_GET['period']) ? $_GET['period'] : 'all_time';

        // Initialize rating management
        $ratingManager = new RatingManagement();

        // Get rating and feedback data
        $rating_data = getTutorRatingStats($tutor_id) ?: $rating_data;

        // Get teaching performance trends
        $performance_trends = $ratingManager->getTeachingPerformanceTrends($tutor_id) ?: $performance_trends;

        // Get rating distribution
        $rating_distribution = $ratingManager->getRatingDistribution($tutor_id) ?: $rating_distribution;

        // Get earnings and transactions data
        $earnings_data = [
            'labels' => [],
            'earnings' => [],
            'transactions' => []
        ];
        
        // Prepare date filter based on selected period
        $date_filter = '';
        switch($filter_period) {
            case 'current_year':
                $date_filter = 'AND YEAR(t.created_at) = YEAR(CURRENT_DATE())';
                break;
            case 'current_month':
                $date_filter = 'AND YEAR(t.created_at) = YEAR(CURRENT_DATE()) AND MONTH(t.created_at) = MONTH(CURRENT_DATE())';
                break;
            case 'current_week':
                $date_filter = 'AND YEARWEEK(t.created_at) = YEARWEEK(CURRENT_DATE())';
                break;
            default: // all_time
                $date_filter = '';
        }

        // Get earnings data based on selected period
        $sql = "SELECT 
                DATE_FORMAT(t.created_at, '%M %Y') as month,
                SUM(t.amount) as total_earnings,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT tc.class_id) as class_count,
                COUNT(DISTINCT e.student_id) as student_count
                FROM transactions t
                LEFT JOIN transaction_classes tc ON t.transaction_id = tc.transaction_id
                LEFT JOIN enrollments e ON tc.class_id = e.class_id AND e.status = 'active'
                WHERE t.user_id = ? 
                AND t.status = 'succeeded'
                $date_filter
                GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
                ORDER BY t.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $earnings_data['labels'][] = $row['month'];
            $earnings_data['earnings'][] = $row['total_earnings'];
            $earnings_data['transactions'][] = $row['transaction_count'];
        }

        // Calculate statistics based on selected period
        $period_stats_sql = "SELECT 
            SUM(t.amount) as period_earnings,
            COUNT(DISTINCT e.student_id) as period_students,
            COUNT(DISTINCT tc.class_id) as period_classes
            FROM transactions t
            LEFT JOIN transaction_classes tc ON t.transaction_id = tc.transaction_id
            LEFT JOIN enrollments e ON tc.class_id = e.class_id AND e.status = 'active'
            WHERE t.user_id = ? 
            AND t.status = 'succeeded'
            $date_filter";

        $stmt = $conn->prepare($period_stats_sql);
        $stmt->bind_param('i', $tutor_id);
        $stmt->execute();
        $period_stats = $stmt->get_result()->fetch_assoc();

        $total_earnings = $period_stats['period_earnings'] ?? 0;
        $paid_students = $period_stats['period_students'] ?? 0;
        $paid_classes = $period_stats['period_classes'] ?? 0;

        // Get current month earnings for comparison
        $monthly_sql = "SELECT SUM(amount) as monthly_earnings
            FROM transactions 
            WHERE user_id = ? 
            AND status = 'succeeded'
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
            AND MONTH(created_at) = MONTH(CURRENT_DATE())";

        $stmt = $conn->prepare($monthly_sql);
        $stmt->bind_param('i', $tutor_id);
        $stmt->execute();
        $monthly_result = $stmt->get_result()->fetch_assoc();
        $monthly_earnings = $monthly_result['monthly_earnings'] ?? 0;

        // Get class performance data
        $class_performance = getClassPerformanceData($tutor_id);

        // Get student progress data
        $student_progress = getStudentProgressData($tutor_id);

        // Initialize rating management
        $ratingManager = new RatingManagement();

        // Get rating and feedback data
        $rating_data = getTutorRatingStats($tutor_id);

        // Get teaching performance trends
        $performance_trends = $ratingManager->getTeachingPerformanceTrends($tutor_id);

        // Get rating distribution
        $rating_distribution = $ratingManager->getRatingDistribution($tutor_id);

        // Get recent activities
        $recent_activities = getTutorRecentActivities($tutor_id);

    } catch (Exception $e) {
        log_error("Reports page error: " . $e->getMessage(), 2);
    }

    $title = "Teaching Performance Report";
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<body data-base="<?php echo BASE; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="dashboard-content bg">
        <!-- Header Section -->
        <div class="content-section mb-4">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <h1 class="page-title mb-0">Teaching Performance Report</h1>
                            <p class="text-muted">Track your teaching impact and student success</p>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>Print Report
                            </button>
                            <button class="btn btn-primary" onclick="exportReport()">
                                <i class="bi bi-download me-2"></i>Export PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="content-section mb-4">
            <div class="row g-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary-subtle text-primary rounded-3 p-3 me-3">
                                    <i class="bi bi-mortarboard-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Total Classes</h6>
                                    <h3 class="stat-value mb-0"><?php echo $teaching_stats['total_classes']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success-subtle text-success rounded-3 p-3 me-3">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Total Students</h6>
                                    <h3 class="stat-value mb-0"><?php echo $teaching_stats['total_students']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning-subtle text-warning rounded-3 p-3 me-3">
                                    <i class="bi bi-star-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Avg. Rating</h6>
                                    <h3 class="stat-value mb-0"><?php echo number_format($rating_data['average_rating'], 1); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info-subtle text-info rounded-3 p-3 me-3">
                                    <i class="bi bi-clock-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Teaching Hours</h6>
                                    <h3 class="stat-value mb-0"><?php echo number_format($teaching_stats['total_hours'], 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Accounting Analytics -->
                <div class="col-12 mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3>Earnings</h3>
                                <div class="d-flex align-items-center">
                                    <label class="me-2">Filter by:</label>
                                    <select class="form-select" id="earningsFilter" onchange="updateEarningsFilter(this.value)">
                                        <option value="all_time" <?php echo $filter_period === 'all_time' ? 'selected' : ''; ?>>All Time</option>
                                        <option value="current_year" <?php echo $filter_period === 'current_year' ? 'selected' : ''; ?>>Current Year</option>
                                        <option value="current_month" <?php echo $filter_period === 'current_month' ? 'selected' : ''; ?>>Current Month</option>
                                        <option value="current_week" <?php echo $filter_period === 'current_week' ? 'selected' : ''; ?>>Current Week</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-sm-6 col-xl-3">
                                    <div class="d-flex align-items-center p-3 border rounded bg-white">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="stat-icon bg-success-subtle text-success rounded p-3">
                                                <i class="bi bi-currency-dollar fs-4"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1"><?php echo $filter_period === 'all_time' ? 'Total' : ucfirst(str_replace('current_', '', $filter_period)); ?> Earnings</h6>
                                            <h4 class="mb-0">₱<?php echo number_format($total_earnings, 2); ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3">
                                    <div class="d-flex align-items-center p-3 border rounded bg-white">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="stat-icon bg-primary-subtle text-primary rounded p-3">
                                                <i class="bi bi-calendar-check fs-4"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">This Month</h6>
                                            <h4 class="mb-0">₱<?php echo number_format($monthly_earnings, 2); ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3">
                                    <div class="d-flex align-items-center p-3 border rounded bg-white">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="stat-icon bg-info-subtle text-info rounded p-3">
                                                <i class="bi bi-people fs-4"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1"><?php echo $filter_period === 'all_time' ? 'Total' : ucfirst(str_replace('current_', '', $filter_period)); ?> Paid Students</h6>
                                            <h4 class="mb-0"><?php echo $paid_students; ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3">
                                    <div class="d-flex align-items-center p-3 border rounded bg-white">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="stat-icon bg-warning-subtle text-warning rounded p-3">
                                                <i class="bi bi-mortarboard fs-4"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1"><?php echo $filter_period === 'all_time' ? 'Total' : ucfirst(str_replace('current_', '', $filter_period)); ?> Paid Classes</h6>
                                            <h4 class="mb-0"><?php echo $paid_classes; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br/><br/>
                            <div class="row g-4">
                                <!-- Earnings Chart -->
                                <div class="col-md-12 mb-4">
                                    <div class="content-card bg-snow">
                                        <div class="card-body">
                                            <h5 class="section-title mb-3">Earnings & Transactions Overview</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <canvas id="earningsChart"></canvas>
                                                </div>
                                                <div class="col-md-6">
                                                    <canvas id="transactionsChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teaching Performance Section -->
        <div class="content-section mb-4">
            <div class="row">
                <div class="col-md-8">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3">Teaching Performance Trends</h5>
                            <div style="height: 500px; width: 100%; margin: 0 auto;">
                                <canvas id="teachingPerformanceChart"></canvas>
                            </div>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    const performanceCtx = document.getElementById('teachingPerformanceChart').getContext('2d');
                                                    new Chart(performanceCtx, {
                                                        type: 'line',
                                                        data: {
                                                            labels: <?php echo json_encode($performance_trends['labels']); ?>,
                                                            datasets: [{
                                                                label: 'Student Performance',
                                                                data: <?php echo json_encode($performance_trends['student_performance']); ?>,
                                                                borderColor: 'rgb(25, 135, 84)',
                                                                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                                                tension: 0.4
                                                            }, {
                                                                label: 'Completion Rate',
                                                                data: <?php echo json_encode($performance_trends['completion_rates']); ?>,
                                                                borderColor: 'rgb(13, 110, 253)',
                                                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                                                tension: 0.4
                                                            }, {
                                                                label: 'Attendance Rate',
                                                                data: <?php echo json_encode($performance_trends['attendance_rates']); ?>,
                                                                borderColor: 'rgb(255, 193, 7)',
                                                                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                                                tension: 0.4
                                                            }]
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                title: {
                                                                    display: false
                                                                },
                                                                legend: {
                                                                    position: 'bottom'
                                                                }
                                                            },
                                                            scales: {
                                                                y: {
                                                                    beginAtZero: true,
                                                                    max: 100,
                                                                    ticks: {
                                                                        callback: function(value) {
                                                                            return value + '%';
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    });
                                                });
                                            </script>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Rating Distribution -->
                                <div class="col-md-4">
                                    <div class="content-card bg-snow h-100">
                                        <div class="card-body">
                                            <h5 class="section-title mb-3">Rating Distribution</h5>
                                            <div style="height: 600px; width: 100%; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                                <canvas id="ratingDistributionChart"></canvas>
                                            </div>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    // Rating Distribution Chart
                                                    const ratingCtx = document.getElementById('ratingDistributionChart').getContext('2d');
                                                    const ratingData = <?php echo json_encode($rating_distribution['counts']); ?>;
                                                    const hasRatings = ratingData.some(count => count > 0);
                                                    const backgroundColor = ratingData.map((count, index) => 
                                                        count > 0 ? <?php echo json_encode($rating_distribution['colors']); ?>[index] : 'rgba(128, 128, 128, 0.8)'
                                                    );

                                                    new Chart(ratingCtx, {
                                                        type: 'doughnut',
                                                        data: {
                                                            labels: <?php echo json_encode($rating_distribution['labels']); ?>,
                                                            datasets: [{
                                                                data: ratingData,
                                                                backgroundColor: backgroundColor,
                                                                borderWidth: 0
                                                            }]
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                legend: {
                                                                    position: 'bottom',
                                                                    labels: {
                                                                        generateLabels: function(chart) {
                                                                            const data = chart.data;
                                                                            return data.labels.map((label, i) => ({
                                                                                text: `${label} (${data.datasets[0].data[i]})`,
                                                                                fillStyle: <?php echo json_encode($rating_distribution['colors']); ?>[i],
                                                                                hidden: false,
                                                                                index: i
                                                                            }));
                                                                        }
                                                                    }
                                                                }
                                                            },
                                                            cutout: '60%'
                                                        }
                                                    });
                                                });
                                            </script>
                                            </div>
                                        </div>
                                    </div>
                                </div>

        <!-- Performance Charts -->
        <div class="content-section mb-4">
            
        </div>

        <script>
            function updateEarningsFilter(period) {
                window.location.href = window.location.pathname + '?period=' + period;
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Earnings Chart
                const earningsCtx = document.getElementById('earningsChart').getContext('2d');
                new Chart(earningsCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($earnings_data['labels']); ?>,
                        datasets: [{
                            label: 'Total Earnings',
                            data: <?php echo json_encode($earnings_data['earnings']); ?>,
                            borderColor: 'rgb(25, 135, 84)',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: '<?php echo ucwords(str_replace('_', ' ', $filter_period));?> Earnings',
                                font: { size: 16 }
                            },
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Transactions Chart
                const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
                new Chart(transactionsCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($earnings_data['labels']); ?>,
                        datasets: [{
                            label: 'Number of Transactions',
                            data: <?php echo json_encode($earnings_data['transactions']); ?>,
                            backgroundColor: 'rgb(13, 110, 253)',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: '<?php echo ucwords(str_replace('_', ' ', $filter_period));?> Transactions',
                                font: { size: 16 }
                            },
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            });
        </script>

        <!-- Class Performance Table -->
        <div class="content-section mb-4">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="section-title mb-0">
                            <i class="bi bi-collection-play me-2 text-primary"></i>
                            Class Performance
                        </h5>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item active" href="#" data-filter="all">All Classes</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="active">Active</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="completed">Completed</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Subject</th>
                                    <th>Students</th>
                                    <th>Avg. Performance</th>
                                    <th>Completion Rate</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($class_performance)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="bi bi-journal-x text-muted" style="font-size: 48px;"></i>
                                            <h3 class="h5 mt-3">No Class Data Available</h3>
                                            <p class="text-muted">Start teaching classes to see performance metrics.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($class_performance as $class): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo CLASS_IMG . (!empty($class['thumbnail']) ? $class['thumbnail'] : 'default.jpg'); ?>" 
                                                 alt="" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                                <small class="text-muted"><?php echo date('M Y', strtotime($class['start_date'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                    <td><?php echo $class['student_count']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 6px; width: 100px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $class['avg_performance']; ?>%"
                                                 aria-valuenow="<?php echo $class['avg_performance']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?php echo number_format($class['avg_performance'], 1); ?>%</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 6px; width: 100px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $class['completion_rate']; ?>%"
                                                 aria-valuenow="<?php echo $class['completion_rate']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?php echo number_format($class['completion_rate'], 1); ?>%</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><?php echo number_format($class['rating'], 1); ?></span>
                                            <i class="bi bi-star-fill text-warning"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $class['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                            <?php echo ucfirst($class['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Progress -->
        <div class="content-section mb-4">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <h5 class="section-title mb-4">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Student Progress Overview
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Classes Enrolled</th>
                                    <th>Avg. Performance</th>
                                    <th>Attendance Rate</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($student_progress)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="bi bi-people text-muted" style="font-size: 48px;"></i>
                                            <h3 class="h5 mt-3">No Student Data Available</h3>
                                            <p class="text-muted">Student progress will appear here once they enroll in your classes.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($student_progress as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo USER_IMG . (!empty($student['profile_picture']) ? $student['profile_picture'] : 'default.jpg'); ?>" 
                                                 alt="" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($student['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $student['classes_enrolled']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 6px; width: 100px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $student['avg_performance']; ?>%"
                                                 aria-valuenow="<?php echo $student['avg_performance']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?php echo number_format($student['avg_performance'], 1); ?>%</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 6px; width: 100px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $student['attendance_rate']; ?>%"
                                                 aria-valuenow="<?php echo $student['attendance_rate']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?php echo number_format($student['attendance_rate'], 1); ?>%</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($student['last_active'])); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="content-section">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <h5 class="section-title mb-4">
                        <i class="bi bi-activity me-2 text-primary"></i>
                        Recent Teaching Activities
                    </h5>
                    <div class="timeline">
                        <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-4">
                            <div class="empty-state">
                                <i class="bi bi-clock-history text-muted" style="font-size: 48px;"></i>
                                <h3 class="h5 mt-3">No Recent Activities</h3>
                                <p class="text-muted">Your teaching activities will appear here as you conduct classes.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-<?php echo $activity['type_color']; ?>">
                                <i class="bi bi-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <small class="text-muted"><?php echo $activity['timestamp']; ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportReport() {
            // Show loading indicator
            const loading = document.getElementById('loadingIndicator');
            if (loading) loading.classList.remove('d-none');
            
            // Get the dashboard content
            const element = document.querySelector('.dashboard-content');
            
            // Configure html2pdf options
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'teaching_performance_report.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Generate PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // Hide loading indicator when done
                if (loading) loading.classList.add('d-none');
            });
        }
        
        // Initialize charts when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts here
            // This is a placeholder - you'll need to implement the actual chart initialization
            // based on your data structure
        });
    </script>
</body>
</html>
