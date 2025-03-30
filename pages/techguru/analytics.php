<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'meeting_management.php';
        
    if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHGURU') {
        $_SESSION['msg'] = "Invalid Action";
        log_error("User accessed an invalid page",'security');
        header("location: ".BASE."login");
        exit();
    }
    
    // Get class details or redirect if invalid
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 
                (isset($_GET['id']) ? intval($_GET['id']) : 0);
    
    $classDetails = getClassDetails($class_id, $_SESSION['user']);
    if (!$classDetails) {
        header('Location: ./');
        exit();
    }

    $title = 'Learning Analytics';
    $tutor_id = $_SESSION['user'];
    
    $meeting = new MeetingManagement();

    // Get analytics data from the database
    $analyticsData = $meeting->getMeetingAnalytics($class_id, $tutor_id);
    
    // If fetchMeetingAnalytics hasn't been executed yet, trigger it
    if (empty($analyticsData['activity_data'])) {
        // This ensures we have fresh analytics data
        $meeting->fetchMeetingAnalytics($class_id);
        // Get updated analytics
        $analyticsData = $meeting->getMeetingAnalytics($class_id, $tutor_id);
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .analytics-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }

        .analytics-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .chart-container {
            height: 300px;
            margin: 1rem 0;
        }

        .meeting-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .meeting-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }

        .meeting-item:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
        
    <!-- Main Dashboard Content -->
        <main class="dashboard-content p-4">
            <!-- Header Section -->
            <div class="dashboard-card mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb" class="breadcrumb-nav">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="././">My Classes</a></li>
                                <li class="breadcrumb-item">
                                    <a href="./?id=<?php echo htmlspecialchars($classDetails['class_id']); ?>">
                                        <?php echo htmlspecialchars($classDetails['class_name']); ?>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active">Analytics</li>
                            </ol>
                        </nav>
                        <h2 class="page-header mb-0">Class Analytics</h2>
                        <p class="text-muted">View detailed analytics and insights for your class</p>
                    </div>
                    <div>
                        <a href="./?id=<?php echo $classDetails['class_id'];?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Back to Class
                        </a>
                    </div>
                </div>
            </div>

            <!-- Analytics Grid -->
            <div class="row g-4">
                <!-- Overview Stats -->
                <div class="col-12">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="analytics-card p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-camera-video-fill"></i>
                                    </div>
                                </div>
                                <div class="stat-value" id="total-sessions">
                                    <?php echo number_format($analyticsData['total_sessions'] ?? 0); ?>
                                </div>
                                <div class="stat-label">Total Sessions</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="analytics-card p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-clock-fill"></i>
                                    </div>
                                </div>
                                <div class="stat-value" id="total-hours">
                                    <?php echo number_format($analyticsData['total_hours'] ?? 0, 1); ?>
                                </div>
                                <div class="stat-label">Total Hours</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="analytics-card p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                </div>
                                <div class="stat-value" id="total-participants">
                                    <?php echo number_format($analyticsData['total_participants'] ?? 0); ?>
                                </div>
                                <div class="stat-label">Total Participants</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="analytics-card p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-camera-reels-fill"></i>
                                    </div>
                                </div>
                                <div class="stat-value" id="total-recordings">
                                    <?php echo number_format($analyticsData['total_recordings'] ?? 0); ?>
                                </div>
                                <div class="stat-label">Total Recordings</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Analytics -->
                <div class="col-md-8">
                    <div class="analytics-card p-4">
                        <h5 class="section-title mb-4">Session Activity</h5>
                        <div class="chart-container">
                            <canvas id="sessionActivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Sessions -->
                <div class="col-md-4">
                    <div class="analytics-card p-4">
                        <h5 class="section-title mb-4">Recent Sessions</h5>
                        <div class="meeting-list" id="recent-sessions">
                            <?php if (empty($analyticsData['recent_sessions'])): ?>
                                <p class="text-muted text-center">No recent sessions found</p>
                            <?php else: ?>
                                <?php foreach ($analyticsData['recent_sessions'] as $session): ?>
                                <div class="meeting-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($session['class_name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-date me-1"></i>
                                                <?php 
                                                    $sessionDate = !empty($session['session_date']) ? date('M d, Y', strtotime($session['session_date'])) : 'No date';
                                                    echo $sessionDate; 
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex justify-content-between">
                                        <small class="text-muted">
                                            <i class="bi bi-people me-1"></i>
                                            <?php echo $session['participant_count'] ?? 0; ?> participants
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-clock-history me-1"></i>
                                            <?php 
                                                $duration = isset($session['duration']) ? round($session['duration'] / 60, 1) : 0;
                                                echo $duration;
                                            ?> hours
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Participant Engagement -->
                <div class="col-md-6">
                    <div class="analytics-card p-4">
                        <h5 class="section-title mb-4">Participant Engagement</h5>
                        <div class="chart-container">
                            <canvas id="engagementChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Session Duration Distribution -->
                <div class="col-md-6">
                    <div class="analytics-card p-4">
                        <h5 class="section-title mb-4">Session Duration Distribution</h5>
                        <div class="chart-container">
                            <canvas id="durationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
    </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize charts with data from PHP
                initializeCharts();
            });

            function initializeCharts() {
                const activityData = <?php 
                    $dates = [];
                    $sessionCounts = [];
                    
                    if (isset($analyticsData['activity_data']) && !empty($analyticsData['activity_data'])) {
                        foreach ($analyticsData['activity_data'] as $item) {
                            $dates[] = date('M Y', strtotime($item['month'].'-01'));
                            $sessionCounts[] = $item['meeting_count'];
                        }
                    }
                    
                    echo json_encode([
                        'labels' => $dates,
                        'sessions' => $sessionCounts,
                        'participants' => array_fill(0, count($dates), 0) // Placeholder
                    ]); 
                ?>;
                
                // Session Activity Chart
                new Chart(document.getElementById('sessionActivityChart'), {
                    type: 'line',
                    data: {
                        labels: activityData.labels,
                        datasets: [
                            {
                                label: 'Sessions',
                                data: activityData.sessions,
                                borderColor: '#4e73df',
                                tension: 0.2,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Engagement Chart - Participants per session
                const engagementData = <?php 
                    $months = [];
                    $avgParticipants = [];
                    
                    if (isset($analyticsData['engagement_data']) && !empty($analyticsData['engagement_data'])) {
                        foreach ($analyticsData['engagement_data'] as $item) {
                            $months[] = date('M Y', strtotime($item['month'].'-01'));
                            $avgParticipants[] = $item['avg_participants'];
                        }
                    }
                    
                    echo json_encode([
                        'labels' => $months,
                        'data' => $avgParticipants
                    ]); 
                ?>;
                
                new Chart(document.getElementById('engagementChart'), {
                    type: 'bar',
                    data: {
                        labels: engagementData.labels,
                        datasets: [{
                            label: 'Average Participants',
                            data: engagementData.data,
                            backgroundColor: '#36b9cc'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Average Participants per Session'
                                }
                            }
                        }
                    }
                });

                // Duration Chart - Session duration distribution
                const durationData = <?php 
                    $months = [];
                    $avgDurations = [];
                    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
                    
                    if (isset($analyticsData['duration_data']) && !empty($analyticsData['duration_data'])) {
                        foreach ($analyticsData['duration_data'] as $item) {
                            $months[] = date('M Y', strtotime($item['month'].'-01'));
                            $avgDurations[] = $item['avg_duration_hours'];
                        }
                    }
                    
                    echo json_encode([
                        'labels' => $months,
                        'data' => $avgDurations,
                        'colors' => array_slice($colors, 0, count($months))
                    ]); 
                ?>;
                
                new Chart(document.getElementById('durationChart'), {
                    type: 'line',
                    data: {
                        labels: durationData.labels,
                        datasets: [{
                            label: 'Average Session Duration (hours)',
                            data: durationData.data,
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.2)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            }
                        }
                    }
                });
            }
        </script>
    </body>
</html>