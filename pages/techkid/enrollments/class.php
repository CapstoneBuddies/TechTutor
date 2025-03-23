<?php 
require_once '../../../backends/main.php';
require_once BACKEND.'student_management.php';
require_once BACKEND.'class_management.php';

if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit;
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$class_id) {
    header('Location: ' . BASE . 'dashboard/s/class');
    exit;
}

// Get class details
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ' . BASE . 'dashboard/s/class');
    exit;
}

// Get class schedules
$schedules = getClassSchedules($class_id);

// Get tutor's rating and stats
$tutor_id = $classDetails['tutor_id'];
$tutor_rating = isset($classDetails['average_rating']) ? number_format($classDetails['average_rating'], 1) : "N/A";
$completion_rate = isset($classDetails['completion_rate']) ? $classDetails['completion_rate'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <link href="<?php echo CSS; ?>techkid-common.css" rel="stylesheet">
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content">
            <div class="container py-4">
                <!-- Back Button and Title Section -->
                <div class="content-section mb-4">
                    <div class="content-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="./" class="btn btn-outline-primary mb-3">
                                        <i class="bi bi-arrow-left"></i> Back to Classes
                                    </a>
                                    <h1 class="page-title mb-0"><?php echo htmlspecialchars($classDetails['class_name']); ?></h1>
                                    <p class="text-muted mb-0">
                                        <?php echo htmlspecialchars($classDetails['subject_name']); ?> | 
                                        <?php echo htmlspecialchars($classDetails['course_name']); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <?php if ($classDetails['is_free']): ?>
                                        <span class="badge bg-success mb-2">Free Class</span>
                                    <?php else: ?>
                                        <span class="badge bg-info mb-2">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Class Information -->
                        <div class="content-section mb-4">
                            <div class="content-card">
                                <div class="card-body">
                                    <h4 class="card-title">Class Information</h4>
                                    <div class="class-info mt-3">
                                        <p><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <p><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($classDetails['start_date'])); ?></p>
                                                <p><strong>End Date:</strong> <?php echo date('F d, Y', strtotime($classDetails['end_date'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Class Size:</strong> <?php echo $classDetails['total_students']; ?>/<?php echo $classDetails['class_size'] ? $classDetails['class_size'] : 'Unlimited'; ?></p>
                                                <p><strong>Status:</strong> <span class="badge bg-<?php echo $classDetails['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($classDetails['status']); ?></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Schedule -->
                        <div class="content-section mb-4">
                            <div class="content-card">
                                <div class="card-body">
                                    <h4 class="card-title">Class Schedule</h4>
                                    <div class="table-responsive mt-3">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($schedules as $schedule): ?>
                                                <tr>
                                                    <td><?php echo date('F d, Y', strtotime($schedule['session_date'])); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $schedule['status'] === 'completed' ? 'success' : ($schedule['status'] === 'ongoing' ? 'warning' : 'info'); ?>">
                                                            <?php echo ucfirst($schedule['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Tutor Information -->
                        <div class="content-section mb-4">
                            <div class="content-card">
                                <div class="card-body">
                                    <h4 class="card-title">About the Tutor</h4>
                                    <div class="tutor-info mt-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="<?php echo USER_IMG . $classDetails['techguru_profile']; ?>" 
                                                 alt="<?php echo htmlspecialchars($classDetails['techguru_name']); ?>" 
                                                 class="rounded-circle me-3" 
                                                 style="width: 64px; height: 64px; object-fit: cover;">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($classDetails['techguru_name']); ?></h5>
                                                <div class="text-warning">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?php echo $i <= $tutor_rating ? '-fill' : ''; ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="text-muted ms-1">(<?php echo $tutor_rating; ?>)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tutor-stats">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="p-3 border rounded text-center">
                                                        <h6 class="mb-1">Students</h6>
                                                        <p class="mb-0 fs-5"><?php echo $classDetails['total_students']; ?></p>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-3 border rounded text-center">
                                                        <h6 class="mb-1">Completion</h6>
                                                        <p class="mb-0 fs-5"><?php echo number_format($completion_rate, 0); ?>%</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollment Section -->
                        <div class="content-section">
                            <div class="content-card">
                                <div class="card-body">
                                    <h4 class="card-title">Enroll in this Class</h4>
                                    <div class="enrollment-info mt-3">
                                        <?php if ($classDetails['is_free']): ?>
                                            <p class="text-success fw-bold mb-3">This is a free class!</p>
                                        <?php else: ?>
                                            <p class="mb-3">
                                                <span class="fs-4 fw-bold">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                                <span class="text-muted">/entire course</span>
                                            </p>
                                        <?php endif; ?>

                                        <div class="d-grid">
                                            <button type="button" class="btn btn-primary btn-lg" onclick="enrollInClass(<?php echo $class_id; ?>)">
                                                Enroll Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            function enrollInClass(classId) {
                if (confirm('Are you sure you want to enroll in this class?')) {
                    fetch(`${BASE}api/enroll-class.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            class_id: classId,
                            selected_sessions: [] // We're enrolling in the entire class, not individual sessions
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Successfully enrolled in the class!');
                            window.location.href = `${BASE}dashboard/s/class`;
                        } else {
                            alert(data.message || 'Failed to enroll in the class. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while enrolling. Please try again.');
                    });
                }
            }
        </script>

        <style>
            .dashboard-content {
                background-color: #f8f9fa;
                min-height: calc(100vh - 60px);
            }
            .content-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                transition: transform 0.2s ease-in-out;
            }
            .content-card:hover {
                transform: translateY(-2px);
            }
            .card-title {
                color: #2c3e50;
                font-weight: 600;
            }
            .tutor-info .bi {
                font-size: 1rem;
            }
            .badge {
                padding: 0.5em 1em;
                font-weight: 500;
            }
            .table {
                margin-bottom: 0;
            }
            .table th {
                font-weight: 600;
                color: #2c3e50;
            }
            .table td {
                vertical-align: middle;
            }
            .enrollment-info .btn-primary {
                padding: 1rem;
                font-weight: 600;
            }
            @media (max-width: 768px) {
                .dashboard-content {
                    padding: 1rem;
                }
            }
        </style>
    </body>
</html> 