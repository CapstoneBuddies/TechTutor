<?php 
    require_once '../../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';
    
    // Get enrolled courses
    $enrollments = [];
    $enrolled_courses = [];
    
    // Get available courses
    
    $available = [];
    $available_courses = [];
    
    // Get student stats

    $result = null;
    $stats = null;
    
    $classes = null;
    $upcoming_classes = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Student Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE; ?>/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Keep it going, <?php echo explode(' ', $_SESSION['name'])[0]; ?>!</h1>
            <p class="role">TechKid</p>
            <div class="progress-info">
                <p class="points">You have <?php echo $stats['points']; ?> points</p>
                <p class="next-rank">Earn <?php echo $stats['badges']; ?> more badges to reach Explorer rank</p>
            </div>
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
                        <p class="stat-number"><?php echo count($upcoming_classes); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Badges Earned</h3>
                        <p class="stat-number"><?php echo $stats['badges']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Points</h3>
                        <p class="stat-number"><?php echo $stats['points']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Sections -->
        <div class="row">
            <!-- Enrolled Courses -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title">My Courses</h5>
                            <a href="courses.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="course-list">
                            <?php foreach ($enrolled_courses as $course): ?>
                            <div class="course-item">
                                <div class="course-icon">
                                    <i class="bi bi-book"></i>
                                </div>
                                <div class="course-details">
                                    <h6><?php echo $course['course_name']; ?></h6>
                                    <p class="tutor">with <?php echo $course['tutor_name']; ?></p>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $course['progress']; ?>%" 
                                             aria-valuenow="<?php echo $course['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-success" onclick="continueClass(<?php echo $course['id']; ?>)">
                                    Continue
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Courses -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title">Available Courses</h5>
                            <a href="browse-courses.php" class="btn btn-sm btn-primary">Browse All</a>
                        </div>
                        <div class="course-grid">
                            <?php foreach ($available_courses as $course): ?>
                            <div class="course-card">
                                <div class="course-image">
                                    <img src="<?php echo $course['image']; ?>" alt="<?php echo $course['name']; ?>">
                                </div>
                                <div class="course-info">
                                    <h6><?php echo $course['name']; ?></h6>
                                    <p><?php echo substr($course['description'], 0, 100); ?>...</p>
                                    <button class="btn btn-sm btn-outline-primary" onclick="enrollCourse(<?php echo $course['id']; ?>)">
                                        Enroll Now
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule and Badges -->
        <div class="row">
            <!-- Upcoming Classes -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming Classes</h5>
                        <div class="schedule-list">
                            <?php foreach ($upcoming_classes as $class): ?>
                            <div class="schedule-item">
                                <div class="schedule-time">
                                    <?php echo date('M d, Y', strtotime($class['date'])); ?> at 
                                    <?php echo date('h:i A', strtotime($class['time'])); ?>
                                </div>
                                <div class="schedule-details">
                                    <div class="course-name"><?php echo $class['course_name']; ?></div>
                                    <div class="tutor-name">with <?php echo $class['tutor_name']; ?></div>
                                </div>
                                <button class="btn btn-sm btn-success" onclick="joinClass(<?php echo $class['id']; ?>)">
                                    Join
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($upcoming_classes)): ?>
                            <p class="text-muted">No upcoming classes scheduled</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Badges -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">My Badges</h5>
                        <div class="badge-grid">
                            <?php
                            $badges = [
                                ['name' => 'Quick Learner', 'icon' => 'trophy', 'earned' => true],
                                ['name' => 'Problem Solver', 'icon' => 'puzzle', 'earned' => true],
                                ['name' => 'Team Player', 'icon' => 'people', 'earned' => false],
                                ['name' => 'Code Master', 'icon' => 'code-square', 'earned' => false],
                            ];
                            foreach ($badges as $badge):
                            ?>
                            <div class="badge-item <?php echo $badge['earned'] ? 'earned' : ''; ?>">
                                <div class="badge-icon">
                                    <i class="bi bi-<?php echo $badge['icon']; ?>"></i>
                                </div>
                                <div class="badge-name"><?php echo $badge['name']; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function continueClass(courseId) {
            window.location.href = `course.php?id=${courseId}`;
        }

        function enrollCourse(courseId) {
            window.location.href = `enroll.php?id=${courseId}`;
        }

        function joinClass(classId) {
            window.location.href = `join-class.php?id=${classId}`;
        }
    </script>
</body>
</html>