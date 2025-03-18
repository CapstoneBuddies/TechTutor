<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php';
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }

    // Get class ID from URL
    $class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($class_id) {
        $class = getClassDetails($class_id);
        $students = getEnrolledStudents($class_id);
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Back Button -->
                    <a href="./" class="btn btn-secondary back-button">
                        <i class="bi bi-arrow-left"></i> Back to Classes
                    </a>

                    <!-- Class Header -->
                    <div class="class-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2><?php echo htmlspecialchars($class['class_name']); ?></h2>
                            <span class="status-badge <?php echo $class['status'] == 'active' ? 'status-active' : 'status-restricted'; ?>">
                                <?php echo ucfirst($class['status']); ?>
                            </span>
                        </div>
                        <div class="class-info">
                            <div class="info-card">
                                <h6>Subject</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($class['subject_name']); ?></p>
                            </div>
                            <div class="info-card">
                                <h6>Students Enrolled</h6>
                                <p class="mb-0"><?php echo $class['total_students']; ?></p>
                            </div>
                            <div class="info-card">
                                <h6>Created On</h6>
                                <p class="mb-0"><?php echo date('F j, Y', strtotime($class['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- TechGuru Info -->
                    <div class="techguru-card">
                        <img src="<?php echo USER_IMG.$class['techguru_profile']; ?>" alt="TechGuru" class="techguru-avatar">
                        <div>
                            <h5 class="mb-1">TechGuru</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($class['techguru_name']); ?></p>
                            <small class="text-muted"><?php echo htmlspecialchars($class['techguru_email']); ?></small>
                        </div>
                    </div>

                    <!-- Enrolled Students -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Enrolled Students</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover student-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Email</th>
                                            <th>Enrollment Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No students enrolled yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo $student['profile_picture']; ?>" alt="Student" class="student-avatar">
                                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td><?php echo date('F j, Y', strtotime($student['enrollment_date'])); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo $student['enrollment_status'] == 'active' ? 'status-active' : 'status-restricted'; ?>">
                                                            <?php echo ucfirst($student['enrollment_status']); ?>
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
            </div>
        </div>
    </main>
    </main> 
    </div> 
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>