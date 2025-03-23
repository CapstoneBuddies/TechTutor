<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'student_management.php';

// Ensure user is logged in and is a TechKid
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if already enrolled
$enrollment = checkStudentEnrollment($_SESSION['user'], $class_id);
if ($enrollment) {
    header('Location: ' . BASE . 'pages/techkid/class/details?id=' . $enrollment['class_id']);
    exit();
}

// Fetch class details
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Fetch available schedules for enrollment
$schedules = getClassSchedules($class_id);
$title = htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <div class="dashboard-content bg">
                <!-- Header Section -->
                <div class="content-section mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <a href="./" class="btn btn-outline-primary btn-sm mb-2">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <h1 class="page-title mb-1"><?php echo htmlspecialchars($classDetails['class_name']); ?></h1>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="text-muted small">
                                            <strong>Subject:</strong> <?php echo htmlspecialchars($classDetails['subject_name']); ?>
                                        </span>
                                        <span class="text-muted">•</span>
                                        <span class="text-muted small">
                                            <strong>Course:</strong> <?php echo htmlspecialchars($classDetails['course_name']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-start align-items-md-end gap-2">
                                    <?php if ($classDetails['is_free']): ?>
                                        <span class="badge bg-success">Free Class</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-primary" onclick="enrollInClass()">
                                        <i class="bi bi-check-circle"></i> Enroll in Class
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row g-4">
                    <!-- Left Column - Main Content -->
                    <div class="col-12 col-lg-8">
                        <!-- Class Information -->
                        <div class="enrollment-info mb-4">
                            <h2 class="section-title mb-4">Class Information</h2>
                            <div class="class-info">
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>Start Date</strong></p>
                                        <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($classDetails['start_date'])); ?></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>End Date</strong></p>
                                        <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($classDetails['end_date'])); ?></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>Class Size</strong></p>
                                        <p class="text-muted mb-0"><?php echo $classDetails['total_students']; ?>/<?php echo $classDetails['class_size'] ? $classDetails['class_size'] : 'Unlimited'; ?></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>Status</strong></p>
                                        <span class="badge bg-<?php echo $classDetails['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($classDetails['status']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Schedule -->
                        <div class="enrollment-info">
                            <h2 class="section-title mb-4">Class Schedule</h2>
                            <div class="table-responsive">
                                <table class="schedule-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo date('F d, Y', strtotime($schedule['session_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Tutor Info -->
                    <div class="col-12 col-lg-4">
                        <!-- Tutor Details -->
                        <div class="enrollment-info mb-4">
                            <h2 class="section-title mb-4">Instructor</h2>
                            <div class="tutor-profile">
                                <img src="<?php echo !empty($classDetails['tutor_avatar']) ? USER_IMG . $classDetails['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($classDetails['techguru_name']); ?>">
                                <h4><?php echo htmlspecialchars($classDetails['techguru_name']); ?></h4>
                                
                                <!-- Tutor Stats -->
                                <div class="tutor-stats">
                                    <div class="stat-card">
                                        <div class="value"><?php echo number_format($classDetails['average_rating'] ?? 0, 1); ?></div>
                                        <div class="label">Rating</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="value"><?php echo number_format($classDetails['completion_rate'] ?? 0); ?>%</div>
                                        <div class="label">Completion</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Stats -->
                        <div class="enrollment-info">
                            <h2 class="section-title mb-4">Class Stats</h2>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="small text-muted mb-1">Total Students</p>
                                    <h4 class="mb-0"><?php echo $classDetails['total_students']; ?></h4>
                                </div>
                                <div class="h3 text-muted">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            async function enrollInClass() {
                if (!confirm("Are you sure you want to enroll in this class? You will be enrolled in all sessions.")) {
                    return;
                }
                
                showLoading(true);
                
                try {
                    const response = await fetch(`${BASE}api/enroll-class`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            class_id: <?php echo $class_id; ?>
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('success', "Successfully enrolled in the class!");
                        setTimeout(() => location.href = `${BASE}pages/techkid/class/details?id=${<?php echo $class_id; ?>}`, 1500);
                    } else {
                        if (data.already_enrolled) {
                            showToast('info', "You are already enrolled in this class!");
                            setTimeout(() => location.href = `${BASE}pages/techkid/class/details?id=${data.class_id}`, 1500);
                        } else {
                            showToast('error', data.message || "Failed to enroll in the class. Please try again.");
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', "An unexpected error occurred. Please try again.");
                } finally {
                    showLoading(false);
                }
            }
        </script>
    </body>
</html>
