<?php
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'rating_management.php';
    require_once BACKEND.'transactions_management.php';

    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    // Initialize data arrays
    $learning_stats = [];
    $class_history = [];
    $performance_data = [];
    $attendance_data = [];
    $recent_activities = [];

    try {
        // Fetch comprehensive learning statistics
        $learning_stats = getStudentLearningStats($_SESSION['user']);
        
        // Get class history with detailed progress
        $class_history = getStudentClassHistory($_SESSION['user']);
        
        // Get performance metrics
        $performance_data = getStudentPerformanceMetrics($_SESSION['user']);
        
        // Get attendance records
        $attendance_data = getStudentAttendanceRecords($_SESSION['user']);
        
        // Get recent learning activities
        $recent_activities = getStudentRecentActivities($_SESSION['user']);
        
    } catch (Exception $e) {
        log_error("Reports page error: " . $e->getMessage(), 2);
    }

    $title = "Learning Progress Report";
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
                            <h1 class="page-title mb-0">Learning Progress Report</h1>
                            <p class="text-muted">Track your educational journey and achievements</p>
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
                                    <h3 class="stat-value mb-0"><?php echo $learning_stats['total_classes']; ?></h3>
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
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Learning Hours</h6>
                                    <h3 class="stat-value mb-0"><?php echo $learning_stats['total_hours']; ?></h3>
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
                                    <h6 class="stat-label mb-1">Avg. Performance</h6>
                                    <h3 class="stat-value mb-0"><?php echo number_format($performance_data['average_score'], 1); ?>%</h3>
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
                                    <i class="bi bi-calendar-check fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="stat-label mb-1">Attendance Rate</h6>
                                    <h3 class="stat-value mb-0"><?php echo number_format($attendance_data['attendance_rate'], 1); ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Charts -->
        <div class="content-section mb-4">
            <div class="row g-4">
                <!-- Learning Progress Chart -->
                <div class="col-md-8">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3">Learning Progress</h5>
                            <div style="height: 500px; width: 100%; margin: 0 auto;">
                                <canvas id="learningProgressChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Distribution -->
                <div class="col-md-4">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3">Performance Distribution</h5>
                            <div style="height: 600px; width: 100%; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class History Table -->
        <div class="content-section mb-4">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="section-title mb-0">
                            <i class="bi bi-collection-play me-2 text-primary"></i>
                            Class History
                        </h5>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item active" href="#" data-filter="all">All Classes</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="completed">Completed</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="ongoing">Ongoing</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Subject</th>
                                    <th>Progress</th>
                                    <th>Performance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($class_history)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="bi bi-journal-x text-muted" style="font-size: 48px;"></i>
                                            <h3 class="h5 mt-3">No Class History Available</h3>
                                            <p class="text-muted">Enroll in classes to track your progress over time.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($class_history as $class): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo CLASS_IMG . (!empty($class['thumbnail']) ? $class['thumbnail'] : 'default.jpg'); ?>" 
                                                 alt="" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($class['tutor_name']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $class['progress']; ?>%"
                                                 aria-valuenow="<?php echo $class['progress']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $class['progress']; ?>%</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><?php echo $class['performance']; ?>%</span>
                                            <?php if ($class['performance'] >= 90): ?>
                                                <i class="bi bi-star-fill text-warning"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $class['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                            <?php echo ucfirst($class['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="class-details.php?id=<?php echo $class['class_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
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
                        Recent Activities
                    </h5>
                    <div class="timeline">
                        <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-4">
                            <div class="empty-state">
                                <i class="bi bi-clock-history text-muted" style="font-size: 48px;"></i>
                                <h3 class="h5 mt-3">No Recent Activities</h3>
                                <p class="text-muted">Your learning activities will appear here as you engage with classes.</p>
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

    <!-- Charts.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize charts when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            
            // Add event listeners for filter buttons
            document.querySelectorAll('[data-filter]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all filter items
                    document.querySelectorAll('[data-filter]').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Get filter value
                    const filter = this.dataset.filter;
                    
                    // Apply filter to table rows
                    if (filter === 'all') {
                        document.querySelectorAll('tbody tr').forEach(row => {
                            row.style.display = '';
                        });
                    } else {
                        document.querySelectorAll('tbody tr').forEach(row => {
                            const status = row.querySelector('.badge').textContent.toLowerCase().trim();
                            row.style.display = status === filter ? '' : 'none';
                        });
                    }
                });
            });
        });

        function initializeCharts() {
            // Learning Progress Chart
            const progressCtx = document.getElementById('learningProgressChart').getContext('2d');
            new Chart(progressCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($learning_stats['progress_data'], 'date')); ?>,
                    datasets: [{
                        label: 'Learning Hours',
                        data: <?php echo json_encode(array_column($learning_stats['progress_data'], 'hours')); ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
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

            // Performance Distribution Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            
            // Check if there's any data in the distribution
            const hasPerformanceData = <?php echo json_encode(array_sum($performance_data['distribution']) > 0); ?>;
            
            let performanceData = hasPerformanceData 
                ? <?php echo json_encode($performance_data['distribution']); ?>
                : [25, 25, 25, 25]; // Equal distribution for sample
                
            let backgroundColors = hasPerformanceData
                ? ['#198754', '#0d6efd', '#ffc107', '#dc3545']  // Regular colors
                : ['#dddddd', '#cccccc', '#bbbbbb', '#aaaaaa']; // Gray shades for sample
            
            new Chart(performanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent', 'Good', 'Average', 'Needs Improvement'],
                    datasets: [{
                        data: performanceData,
                        backgroundColor: backgroundColors,
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 10,
                            bottom: 50 // More padding at bottom for legend
                        }
                    },
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            align: 'center',
                            labels: {
                                boxWidth: 20,
                                boxHeight: 20,
                                padding: 20,
                                font: {
                                    size: 14
                                },
                                color: hasPerformanceData ? undefined : '#999999', // Grayed out text for sample
                                usePointStyle: true,
                                pointStyle: 'circle'
                            },
                            maxHeight: 100,
                            display: true
                        },
                        tooltip: {
                            enabled: hasPerformanceData, // Disable tooltips for sample data
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.formattedValue;
                                    let total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                    let percentage = Math.round((context.raw / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // If no performance data, add a "No Data Available" text overlay
            if (!hasPerformanceData) {
                // Add "Sample Data" label
                const chartContainer = document.getElementById('performanceChart').parentNode;
                const noDataLabel = document.createElement('div');
                noDataLabel.style.position = 'absolute';
                noDataLabel.style.top = '50%';
                noDataLabel.style.left = '50%';
                noDataLabel.style.transform = 'translate(-50%, -50%)';
                noDataLabel.style.textAlign = 'center';
                noDataLabel.style.pointerEvents = 'none'; // Don't interfere with chart interactions
                noDataLabel.innerHTML = '<span style="color: #666; font-size: 14px; background: rgba(255,255,255,0.8); padding: 5px 10px; border-radius: 4px;">Sample Data</span>';
                chartContainer.style.position = 'relative';
                chartContainer.appendChild(noDataLabel);
            }
        }

        // Function to export report as PDF
        function exportReport() {
            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'pdf-loading';
            loadingIndicator.innerHTML = `
                <div class="d-flex align-items-center justify-content-center position-fixed top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.5); z-index: 9999;">
                    <div class="bg-white p-4 rounded shadow-lg text-center">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mb-0">Generating PDF report...</p>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
            
            // Convert charts to images
            Promise.all([
                new Promise(resolve => {
                    const learningProgressCanvas = document.getElementById('learningProgressChart');
                    resolve(learningProgressCanvas.toDataURL('image/png'));
                }),
                new Promise(resolve => {
                    const performanceCanvas = document.getElementById('performanceChart');
                    resolve(performanceCanvas.toDataURL('image/png'));
                })
            ]).then(([learningProgressImage, performanceImage]) => {
                // Create form data to send to server
                const formData = new FormData();
                formData.append('action', 'generate_pdf_report');
                formData.append('learning_progress_image', learningProgressImage);
                formData.append('performance_image', performanceImage);
                
                // Send request directly to the API endpoint
                fetch('<?php echo BASE; ?>api/generate-report.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Remove loading indicator
                    document.body.removeChild(loadingIndicator);
                    
                    if (!response.ok) {
                        throw new Error('Failed to generate PDF');
                    }
                    
                    return response.blob();
                })
                .then(blob => {
                    // Create download link for PDF
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'Learning Progress Report_<?php echo htmlspecialchars($_SESSION["name"]); ?>.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => {
                    console.error('Error generating PDF:', error);
                    document.body.removeChild(loadingIndicator);
                    alert('Failed to generate PDF report. Please try again.');
                });
            }).catch(error => {
                console.error('Error processing charts:', error);
                document.body.removeChild(loadingIndicator);
                alert('Failed to process chart images. Please try again.');
            });
        }
    </script>
</body>
</html>
