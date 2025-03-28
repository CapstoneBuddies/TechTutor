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
    $classDetails = getClassDetails($_GET['class_id'], $_SESSION['user']);
    if (!$classDetails) {
        header('Location: ./');
        exit();
    }

    $title = 'Learning Analytics';
    $meeting = new MeetingManagement();
    $tutor_id = $_SESSION['user'];
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
                                <div class="stat-value" id="total-sessions">-</div>
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
                                <div class="stat-value" id="total-hours">-</div>
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
                                <div class="stat-value" id="total-participants">-</div>
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
                                <div class="stat-value" id="total-recordings">-</div>
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
                            <!-- Will be populated by JavaScript -->
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
                // Fetch analytics data
                fetchAnalyticsData();
                
                // Initialize charts
                initializeCharts();
            });

            function fetchAnalyticsData() {
                // Fetch data using AJAX
                fetch(`${BASE}api/meeting?action=get_analytics&tutor_id=<?php echo $tutor_id; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        updateDashboardStats(data);
                        updateCharts(data);
                        updateRecentSessions(data.recent_sessions);
                    })
                    .catch(error => {
                        console.error('Error fetching analytics:', error);
                    });
            }

            function updateDashboardStats(data) {
                document.getElementById('total-sessions').textContent = data.total_sessions || 0;
                document.getElementById('total-hours').textContent = data.total_hours || 0;
                document.getElementById('total-participants').textContent = data.total_participants || 0;
                document.getElementById('total-recordings').textContent = data.total_recordings || 0;
            }

            function updateRecentSessions(sessions) {
                const container = document.getElementById('recent-sessions');
                container.innerHTML = '';

                if (!sessions || sessions.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center">No recent sessions found</p>';
                    return;
                }

                sessions.forEach(session => {
                    const item = document.createElement('div');
                    item.className = 'meeting-item';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${session.name}</h6>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    ${new Date(session.start_time).toLocaleDateString()}
                                </small>
                            </div>
                            <span class="badge bg-${session.status === 'completed' ? 'success' : 'primary'}">
                                ${session.status}
                            </span>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-people me-1"></i>
                                ${session.participants} participants
                            </small>
                        </div>
                    `;
                    container.appendChild(item);
                });
            }

            function initializeCharts() {
                // Session Activity Chart
                new Chart(document.getElementById('sessionActivityChart'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Sessions',
                            data: [],
                            borderColor: '#4e73df',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Engagement Chart
                new Chart(document.getElementById('engagementChart'), {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Engagement',
                            data: [],
                            backgroundColor: '#36b9cc'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Duration Chart
                new Chart(document.getElementById('durationChart'), {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            data: [],
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            function updateCharts(data) {
                // Update Session Activity Chart
                const activityChart = Chart.getChart('sessionActivityChart');
                if (activityChart && data.session_activity) {
                    activityChart.data.labels = data.session_activity.labels;
                    activityChart.data.datasets[0].data = data.session_activity.data;
                    activityChart.update();
                }

                // Update Engagement Chart
                const engagementChart = Chart.getChart('engagementChart');
                if (engagementChart && data.engagement_data) {
                    engagementChart.data.labels = data.engagement_data.labels;
                    engagementChart.data.datasets[0].data = data.engagement_data.data;
                    engagementChart.update();
                }

                // Update Duration Distribution Chart
                const durationChart = Chart.getChart('durationChart');
                if (durationChart && data.duration_distribution) {
                    durationChart.data.labels = data.duration_distribution.labels;
                    durationChart.data.datasets[0].data = data.duration_distribution.data;
                    durationChart.update();
                }
            }
        </script>
    </body>
</html>