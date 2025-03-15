<?php 
    require_once '../../backends/main.php';
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }

    // Get class ID from URL
    $class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    try {
        // Get class details
        $query = "SELECT 
                    c.*,
                    s.subject_name,
                    CONCAT(u.first_name, ' ', u.last_name) as techguru_name,
                    u.email as techguru_email,
                    u.profile_picture as techguru_profile,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.class_id) as enrolled_students
                FROM class c
                LEFT JOIN subject s ON c.subject_id = s.subject_id
                LEFT JOIN users u ON c.tutor_id = u.uid
                WHERE c.class_id = ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $class = $stmt->get_result()->fetch_assoc();
        
        if (!$class) {
            log_error("Class not found: " . $class_id);
            header("Location: " . BASE . "dashboard/classes");
            exit();
        }
    } catch (Exception $e) {
        log_error("Error fetching class details: " . $e->getMessage());
        header("Location: " . BASE . "dashboard/classes");
        exit();
    }

    try {
        // Create enrollments table if it doesn't exist
        $create_enrollments_table = "
            CREATE TABLE IF NOT EXISTS `enrollments` (
                `enrollment_id` INT PRIMARY KEY AUTO_INCREMENT,
                `class_id` INT NOT NULL,
                `student_id` INT NOT NULL,
                `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
                FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(uid) ON DELETE CASCADE,
                UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`)
            )";
        $conn->query($create_enrollments_table);
        
        // Get enrolled students
        $students_query = "SELECT 
                            u.*,
                            e.enrollment_date,
                            e.status as enrollment_status
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.uid
                        WHERE e.class_id = ?
                        ORDER BY e.enrollment_date DESC";

        $stmt = $conn->prepare($students_query);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Error fetching enrolled students: " . $e->getMessage());
        $students = [];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Class Details</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS Files -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <style>
        .class-header {
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .class-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-card {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-card h6 {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .techguru-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .techguru-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
        }

        .student-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .student-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .back-button {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Back Button -->
                    <a href="<?php echo BASE; ?>dashboard/classes" class="btn btn-secondary back-button">
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
                                <p class="mb-0"><?php echo $class['enrolled_students']; ?></p>
                            </div>
                            <div class="info-card">
                                <h6>Created On</h6>
                                <p class="mb-0"><?php echo date('F j, Y', strtotime($class['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- TechGuru Info -->
                    <div class="techguru-card">
                        <img src="<?php echo $class['techguru_profile']; ?>" alt="TechGuru" class="techguru-avatar">
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

    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo JS; ?>dashboard.js"></script>
</body>
</html>