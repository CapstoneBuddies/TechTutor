<?php
    require_once '../../backends/main.php';
    require_once BACKEND.'admin_management.php';
    
    // Ensure user is logged in and is an Admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    // Get period filter from query string, default to 'all'
    $period = isset($_GET['period']) ? $_GET['period'] : 'all';
    
    // Get platform statistics based on selected period
    $platform_stats = getPlatformStats($period);
    
    $title = "Platform Performance Report";
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<style>
    /* Modern styling for the reports page */
    :root {
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        --success-gradient: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        --info-gradient: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
        --warning-gradient: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
        --dark-gradient: linear-gradient(135deg, #5a5c69 0%, #373840 100%);
        --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        --card-border-radius: 0.75rem;
    }
    
    .dashboard-content {
        background: #f8f9fc !important;
    }
    
    /* Card styling */
    .content-card {
        border: none !important;
        box-shadow: var(--card-shadow);
        border-radius: var(--card-border-radius);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .content-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
    }
    
    /* Header card styling */
    .header-card {
        background: var(--primary-gradient) !important;
        color: white;
    }
    
    .header-card h1, .header-card p {
        color: white !important;
    }
    
    /* Metric cards */
    .metric-card {
        padding: 1.5rem !important;
        border: none !important;
        border-radius: var(--card-border-radius);
        box-shadow: var(--card-shadow);
        transition: transform 0.2s;
    }
    
    .metric-card:hover {
        transform: translateY(-5px);
    }
    
    .metric-card .metric-icon {
        width: 4rem;
        height: 4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
        border-radius: 50%;
    }
    
    .metric-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    /* Primary Theme */
    .primary-card {
        background: white;
        border-left: 0.25rem solid #4e73df !important;
    }
    
    .primary-card .metric-icon {
        color: white;
        background: var(--primary-gradient);
    }
    
    /* Success Theme */
    .success-card {
        background: white;
        border-left: 0.25rem solid #1cc88a !important;
    }
    
    .success-card .metric-icon {
        color: white;
        background: var(--success-gradient);
    }
    
    /* Info Theme */
    .info-card {
        background: white;
        border-left: 0.25rem solid #36b9cc !important;
    }
    
    .info-card .metric-icon {
        color: white;
        background: var(--info-gradient);
    }
    
    /* Warning Theme */
    .warning-card {
        background: white;
        border-left: 0.25rem solid #f6c23e !important;
    }
    
    .warning-card .metric-icon {
        color: white;
        background: var(--warning-gradient);
    }
    
    /* Section titles */
    .section-title {
        position: relative;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
        font-weight: 700;
        color: #5a5c69;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 50px;
        height: 4px;
        background: var(--primary-gradient);
        border-radius: 2px;
    }
    
    /* Overview cards */
    .overview-card {
        border-radius: var(--card-border-radius);
        box-shadow: var(--card-shadow);
        border: none !important;
        padding: 1.5rem !important;
        background: white !important;
    }
    
    .primary-border-card {
        border-top: 4px solid #4e73df !important;
    }
    
    .success-border-card {
        border-top: 4px solid #1cc88a !important;
    }
    
    .danger-border-card {
        border-top: 4px solid #e74a3b !important;
    }
    
    .info-border-card {
        border-top: 4px solid #36b9cc !important;
    }
    
    /* Custom chart styling */
    canvas {
        max-height: 350px;
    }
    
    /* Button styling */
    .btn-primary, .btn-outline-primary.active {
        background: var(--primary-gradient) !important;
        border: none !important;
    }
    
    .btn-outline-primary {
        border-color: #4e73df !important;
        color: #4e73df;
    }
    
    .btn-outline-primary:hover {
        background: var(--primary-gradient) !important;
        color: white;
    }
    
    /* Improved stats tables */
    .stat-table {
        width: 100%;
    }
    
    .stat-table tr {
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .stat-table tr:last-child {
        border-bottom: none;
    }
    
    .stat-table td {
        padding: 0.75rem 0;
    }
    
    .stat-label {
        color: #858796;
        font-weight: 500;
    }
    
    .stat-value {
        font-weight: 700;
        color: #5a5c69;
    }
    
    /* Custom progress bar styling */
    .progress {
        height: 0.5rem !important;
        border-radius: 1rem !important;
        background-color: #eaecf4 !important;
    }
    
    .progress-bar {
        border-radius: 1rem;
    }
</style>
<body data-base="<?php echo BASE; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="dashboard-content bg">
        <!-- Header Section -->
        <div class="content-section mb-4">
            <div class="content-card bg-primary text-white">
                <div class="card-body py-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <h1 class="page-title mb-0 fs-2">Platform Performance Dashboard</h1>
                            <p class="mb-0 opacity-75">Comprehensive analytics & insights</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group me-2">
                                <a href="?period=all" class="btn btn-sm btn-outline-light <?php echo $period === 'all' ? 'active' : ''; ?>">All Time</a>
                                <a href="?period=yearly" class="btn btn-sm btn-outline-light <?php echo $period === 'yearly' ? 'active' : ''; ?>">This Year</a>
                                <a href="?period=monthly" class="btn btn-sm btn-outline-light <?php echo $period === 'monthly' ? 'active' : ''; ?>">This Month</a>
                                <a href="?period=weekly" class="btn btn-sm btn-outline-light <?php echo $period === 'weekly' ? 'active' : ''; ?>">This Week</a>
                            </div>
                            <button class="btn btn-sm btn-light" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>Print
                            </button>
                            <button class="btn btn-sm btn-light" onclick="exportReport()">
                                <i class="bi bi-download me-2"></i>Export PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Summary -->
        <div class="content-section mb-4">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="metric-card primary-card">
                        <div class="metric-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3><?php echo number_format($platform_stats['user_stats']['total_users']); ?></h3>
                        <p class="text-muted mb-0">Total Users</p>
                        <div class="mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Monthly Growth</small>
                                <small class="text-primary fw-bold">+<?php echo $platform_stats['user_stats']['monthly_growth']; ?>%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: <?php echo min($platform_stats['user_stats']['monthly_growth'] * 5, 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="metric-card success-card">
                        <div class="metric-icon">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <h3><?php echo number_format($platform_stats['education_stats']['active_classes']); ?></h3>
                        <p class="text-muted mb-0">Active Classes</p>
                        <div class="mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Completion Rate</small>
                                <?php 
                                    $completion_rate = $platform_stats['education_stats']['active_classes'] + $platform_stats['education_stats']['completed_classes'] > 0 ? 
                                        round(($platform_stats['education_stats']['completed_classes'] / ($platform_stats['education_stats']['active_classes'] + $platform_stats['education_stats']['completed_classes'])) * 100, 1) : 0;
                                ?>
                                <small class="text-success fw-bold"><?php echo $completion_rate; ?>%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo $completion_rate; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="metric-card warning-card">
                        <div class="metric-icon">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <h3><?php echo number_format($platform_stats['activity_stats']['total_teaching_hours']); ?></h3>
                        <p class="text-muted mb-0">Teaching Hours</p>
                        <div class="mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Avg. Session</small>
                                <?php 
                                    $avgSessionLength = $platform_stats['activity_stats']['total_sessions'] > 0 ? 
                                        round(($platform_stats['activity_stats']['total_teaching_hours'] * 60) / $platform_stats['activity_stats']['total_sessions'], 0) : 0;
                                ?>
                                <small class="text-warning fw-bold"><?php echo $avgSessionLength; ?> min</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: <?php echo min($avgSessionLength / 1.2, 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="metric-card info-card">
                        <div class="metric-icon">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <h3><?php echo number_format($platform_stats['education_stats']['avg_tutor_rating'], 1); ?></h3>
                        <p class="text-muted mb-0">Avg. Tutor Rating</p>
                        <div class="mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Rating Score</small>
                                <small class="text-info fw-bold"><?php echo round($platform_stats['education_stats']['avg_tutor_rating'] * 20, 1); ?>%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: <?php echo $platform_stats['education_stats']['avg_tutor_rating'] * 20; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Growth Chart -->
        <div class="content-section mb-4">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <h5 class="section-title mb-3 text-center">
                        <i class="bi bi-graph-up-arrow me-2 text-primary"></i>
                        User Growth Trends
                    </h5>
                    <p class="text-muted text-center mb-4">User registration trends over the past 12 months</p>
                    <div style="height: 400px; width: 100%;">
                        <canvas id="userGrowthChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Stats (3 columns) -->
        <div class="content-section mb-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">
                                <i class="bi bi-people me-2 text-primary"></i>
                                User Statistics
                            </h5>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Total Users</span>
                                    <span class="stat-value fw-bold"><?php echo number_format($platform_stats['user_stats']['total_users']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">TechKids</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['user_stats']['total_students']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">TechGurus</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['user_stats']['total_tutors']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Admins</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['user_stats']['total_admins']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Active Users</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['user_stats']['active_users']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Monthly Growth</span>
                                    <span class="stat-value text-success">+<?php echo $platform_stats['user_stats']['monthly_growth']; ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">New Students (30 days)</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['user_stats']['new_students']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">New Tutors (30 days)</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['user_stats']['new_tutors']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">
                                <i class="bi bi-book me-2 text-success"></i>
                                Education Statistics
                            </h5>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Total Courses</span>
                                    <span class="stat-value fw-bold"><?php echo number_format($platform_stats['education_stats']['total_courses']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Active Subjects</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['education_stats']['active_subjects']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Active Classes</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['education_stats']['active_classes']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Completed Classes</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['education_stats']['completed_classes']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Active Enrollments</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['education_stats']['active_enrollments']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Completed Enrollments</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['education_stats']['completed_enrollments']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Average Tutor Rating</span>
                                    <div>
                                        <span class="stat-value me-1"><?php echo number_format($platform_stats['education_stats']['avg_tutor_rating'], 1); ?></span>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">
                                <i class="bi bi-activity me-2 text-danger"></i>
                                Activity Statistics
                            </h5>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Total Sessions</span>
                                    <span class="stat-value fw-bold"><?php echo number_format($platform_stats['activity_stats']['total_sessions']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Teaching Hours</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['activity_stats']['total_teaching_hours']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Online Meetings</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['activity_stats']['total_online_meetings']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Recorded Sessions</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['activity_stats']['total_recordings']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Total Feedbacks</span>
                                    <span class="stat-value"><?php echo number_format($platform_stats['activity_stats']['total_feedbacks']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Recording Rate</span>
                                    <?php 
                                        $recordingRate = $platform_stats['activity_stats']['total_online_meetings'] > 0 ? 
                                            round(($platform_stats['activity_stats']['total_recordings'] / $platform_stats['activity_stats']['total_online_meetings']) * 100, 1) : 0;
                                    ?>
                                    <span class="stat-value"><?php echo $recordingRate; ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="stat-label">Avg. Session Length</span>
                                    <?php 
                                        $avgSessionLength = $platform_stats['activity_stats']['total_sessions'] > 0 ? 
                                            round(($platform_stats['activity_stats']['total_teaching_hours'] * 60) / $platform_stats['activity_stats']['total_sessions'], 0) : 0;
                                    ?>
                                    <span class="stat-value"><?php echo $avgSessionLength; ?> min</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribution Charts -->
        <div class="content-section mb-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">User Distribution</h5>
                            <div style="height: 350px; max-width: 450px; margin: 0 auto;">
                                <canvas id="userDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">Popular Subjects</h5>
                            <div style="height: 350px; max-width: 450px; margin: 0 auto;">
                                <canvas id="popularSubjectsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform Overview -->
        <div class="content-section">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <h5 class="section-title mb-4 text-center">Platform Overview</h5>
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="overview-card p-3 mb-4 border-start border-primary border-3 bg-light rounded">
                                        <h6 class="fw-bold mb-3 text-primary">
                                            <i class="bi bi-people me-2"></i>
                                            User Activity
                                        </h6>
                                        <p>
                                            The platform currently has <?php echo number_format($platform_stats['user_stats']['total_users']); ?> registered users,
                                            with <?php echo number_format($platform_stats['user_stats']['active_users']); ?> active users (<?php echo round(($platform_stats['user_stats']['active_users'] / ($platform_stats['user_stats']['total_users'] ?: 1)) * 100, 1); ?>% active rate).
                                            In the last 30 days, we've welcomed <?php echo number_format($platform_stats['user_stats']['new_students']); ?> new students
                                            and <?php echo number_format($platform_stats['user_stats']['new_tutors']); ?> new tutors, representing a
                                            <?php echo $platform_stats['user_stats']['monthly_growth']; ?>% monthly growth rate.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="overview-card p-3 mb-4 border-start border-success border-3 bg-light rounded">
                                        <h6 class="fw-bold mb-3 text-success">
                                            <i class="bi bi-book me-2"></i>
                                            Education Insights
                                        </h6>
                                        <p>
                                            The platform offers <?php echo number_format($platform_stats['education_stats']['total_courses']); ?> courses
                                            with <?php echo number_format($platform_stats['education_stats']['active_subjects']); ?> active subjects.
                                            There are currently <?php echo number_format($platform_stats['education_stats']['active_classes']); ?> active classes
                                            with <?php echo number_format($platform_stats['education_stats']['active_enrollments']); ?> active enrollments.
                                            A total of <?php echo number_format($platform_stats['education_stats']['completed_enrollments']); ?> enrollments have been completed.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="overview-card p-3 mb-4 border-start border-danger border-3 bg-light rounded">
                                        <h6 class="fw-bold mb-3 text-danger">
                                            <i class="bi bi-mortarboard-fill me-2"></i>
                                            Teaching Metrics
                                        </h6>
                                        <p>
                                            Our tutors have conducted <?php echo number_format($platform_stats['activity_stats']['total_sessions']); ?> teaching sessions,
                                            totaling <?php echo number_format($platform_stats['activity_stats']['total_teaching_hours']); ?> hours of instruction.
                                            The average session lasts approximately <?php echo $avgSessionLength; ?> minutes.
                                            Tutors have an average rating of <?php echo number_format($platform_stats['education_stats']['avg_tutor_rating'], 1); ?> stars.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="overview-card p-3 mb-4 border-start border-info border-3 bg-light rounded">
                                        <h6 class="fw-bold mb-3 text-info">
                                            <i class="bi bi-camera-video me-2"></i>
                                            Online Learning
                                        </h6>
                                        <p>
                                            The platform has hosted <?php echo number_format($platform_stats['activity_stats']['total_online_meetings']); ?> online meetings,
                                            with <?php echo number_format($platform_stats['activity_stats']['total_recordings']); ?> recorded sessions
                                            (<?php echo $recordingRate; ?>% recording rate).
                                            Students have provided <?php echo number_format($platform_stats['activity_stats']['total_feedbacks']); ?> feedback submissions
                                            to help improve teaching quality.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Performance Chart -->
        <div class="content-section mb-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">
                                <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                                Class Performance
                            </h5>
                            <p class="text-muted text-center mb-4">Average student performance by class type</p>
                            <div style="height: 300px; width: 100%;">
                                <canvas id="classPerformanceChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="content-card bg-snow h-100">
                        <div class="card-body">
                            <h5 class="section-title mb-3 text-center">
                                <i class="bi bi-pie-chart-fill me-2 text-primary"></i>
                                Attendance Distribution
                            </h5>
                            <p class="text-muted text-center mb-4">Overall attendance status across all classes</p>
                            <div style="height: 300px; width: 100%;">
                                <canvas id="attendanceDistributionChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <!-- Charts.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        function initializeCharts() {
            // User Growth Chart
            const growthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($platform_stats['growth_data'], 'month_display')); ?>,
                    datasets: [{
                        label: 'New Students',
                        data: <?php echo json_encode(array_column($platform_stats['growth_data'], 'new_students')); ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'New Tutors',
                        data: <?php echo json_encode(array_column($platform_stats['growth_data'], 'new_tutors')); ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'User Growth (Last 12 Months)'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'New Users'
                            }
                        }
                    }
                }
            });

            // Popular Subjects Chart
            const subjectsCtx = document.getElementById('popularSubjectsChart').getContext('2d');
            new Chart(subjectsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($platform_stats['popular_subjects'], 'subject_name')); ?>,
                    datasets: [{
                        label: 'Enrollments',
                        data: <?php echo json_encode(array_column($platform_stats['popular_subjects'], 'enrollment_count')); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 206, 86)',
                            'rgb(75, 192, 192)',
                            'rgb(153, 102, 255)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Most Popular Subjects'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Enrollment Count'
                            }
                        }
                    }
                }
            });

            // User Distribution Doughnut Chart
            const distributionCtx = document.getElementById('userDistributionChart').getContext('2d');
            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['TechKids', 'TechGurus', 'Admins', 'Inactive Users'],
                    datasets: [{
                        data: [
                            <?php echo $platform_stats['user_stats']['total_students']; ?>,
                            <?php echo $platform_stats['user_stats']['total_tutors']; ?>,
                            <?php echo $platform_stats['user_stats']['total_admins']; ?>,
                            <?php echo $platform_stats['user_stats']['inactive_users']; ?>
                        ],
                        backgroundColor: [
                            '#0d6efd',
                            '#198754',
                            '#ffc107',
                            '#6c757d'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'User Distribution by Type'
                        }
                    }
                }
            });
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
            
            setTimeout(() => {
                document.body.removeChild(loadingIndicator);
                alert('PDF generation would be implemented using the API endpoint for report generation.');
            }, 1500);
        }
    </script>
</body>
</html>
