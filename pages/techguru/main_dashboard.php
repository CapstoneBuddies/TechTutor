<?php 
    require_once 'main.php';

    // Check if user is logged in
    if (!isset($_SESSION['user'])) {
        header("Location: " . BASE . "login");
        exit();
    }

    $tutor_id = $_SESSION['user'];
    $students_data = getStudentByTutor($tutor_id);
    $classes_data = getTechGuruClasses($tutor_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Dashboard</title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>techguru-dashboard.css" rel="stylesheet">
</head>

<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <div class="row">
            <!-- Welcome Section -->
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="welcome-section">
                        <h2>Hello <span class="text-orange">Mr. <?php echo $_SESSION['last_name']; ?></span></h2>
                        <div class="subtitle">Rating - <i class="bi bi-star-fill"></i> <?php echo $_SESSION['rating']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Students Section -->
                <div class="dashboard-card">
                    <h5 class="card-title">Students</h5>
                    <div class="student-list">
                        <?php if (isset($students_data['count']) && $students_data['count'] > 0): ?>
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th style="min-width: 250px;">Student</th>
                                                <th>Email</th>
                                                <th style="min-width: 200px;">Schedule</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students_data['students'] as $student): ?>
                                                <tr>
                                                    <!-- Student Name & Picture -->
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo USER_IMG . ($student['profile_picture'] ?? 'default.jpg'); ?>" 
                                                                 alt="Student" class="student-avatar">
                                                            <div class="student-name ms-2">
                                                                <?php echo $student['student_first_name'] . ' ' . $student['student_last_name']; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <!-- Email -->
                                                    <td class="text-muted">
                                                        <?php echo $student['email']; ?>
                                                    </td>
                                                    <!-- Schedule -->
                                                    <td>
                                                        <div class="schedule-info">
                                                            <div class="class-name mb-1">
                                                                <?php echo $student['class_name']; ?>
                                                            </div>
                                                            <div class="schedule-details">
                                                                <span class="text-muted">
                                                                    <i class="bi bi-calendar3"></i> 
                                                                    <?php echo date('M j, Y', strtotime($student['session_date'])); ?>
                                                                </span>
                                                                <span class="text-muted ms-2">
                                                                    <i class="bi bi-clock"></i> 
                                                                    <?php echo $student['formatted_start_time'] . ' - ' . $student['formatted_end_time']; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">No students enrolled yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Class Schedule -->
                <div class="dashboard-card">
                    <h5 class="card-title">Class Schedule</h5>
                    <div class="mb-2">Incoming class session</div>
                    <?php if (isset($classes_data['count']) && $classes_data['count'] > 0): ?>
                        <div class="text-muted">No upcoming classes</div>
                    <?php else: ?>
                        <div class="text-muted">No classes scheduled</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Transactions -->
                <div class="dashboard-card">
                    <h5 class="card-title">Transactions</h5>
                    <?php if(null): ?>
                    <div class="d-flex justify-content-between">
                        <div>
                            <div>Payment from Love nikaon naka?</div>
                            <small class="text-muted">Amount: ₱50.00</small>
                        </div>
                        <span class="badge bg-success">Completed</span>
                    </div>
                    <?php else: ?>
                        <div class="text-muted">No transactions yet</div>
                    <?php endif; ?>
                </div>

                <!-- Feedbacks -->
                <div class="dashboard-card">
                    <h5 class="card-title">Feedbacks</h5>
                    <?php 
                    $ratings = getTutorRatings($tutor_id);
                    if (!empty($ratings)): ?>
                        <?php foreach ($ratings as $rating): ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <strong><?php echo $rating['first_name'] . ' ' . $rating['last_name']; ?></strong>
                                    <div class="text-warning">
                                        <?php for ($i = 0; $i < $rating['rating']; $i++): ?>
                                            <i class="bi bi-star-fill"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mb-0 text-muted"><?php echo $rating['comment']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted">No feedbacks yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>