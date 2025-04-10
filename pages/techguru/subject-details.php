<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get subject from URL parameter
$subject_name = isset($_GET['subject']) ? $_GET['subject'] : '';

// Get subject details or redirect if invalid
$subjectDetails = getSubjectDetails($subject_name, 'subject_name');
if (!$subjectDetails) {
    header('Location: ./');
    exit();
}

// Get active classes for this subject and tutor
$activeClasses = getActiveClassesForSubject($subjectDetails['subject_id'], $_SESSION['user']);
$title = htmlspecialchars($subjectDetails['subject_name']);
?>

<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .subject-content {
            position: relative;
        }
        .subject-content h4 {
            color: var(--bs-primary);
            font-size: 1.25rem;
            margin-top: 2rem;
            padding-left: 1rem;
            position: relative;
        }
        .subject-content h4:first-child {
            margin-top: 0;
        }
        .subject-content h4::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: var(--bs-primary);
            border-radius: 2px;
        }
        .subject-content p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 0;
        }
        .subject-image {
            border-radius: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .subject-image:hover {
            transform: scale(1.02);
        }
        .stats-content {
            display: grid;
            gap: 1rem;
        }
        .stat-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            transition: transform 0.2s;
        }
        .stat-item:hover {
            transform: translateY(-2px);
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--bs-primary);
        }
        .class-item {
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .class-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .class-item h5 {
            color: var(--bs-primary);
            font-size: 1.1rem;
        }
        .class-item p {
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .class-item i {
            color: var(--bs-primary);
        }
        @media (max-width: 768px) {
            .stats-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="./">Teaching Subjects</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($subjectDetails['subject_name']); ?></li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($subjectDetails['subject_name']); ?></h2>
                            <p class="subtitle">View subject details and create a class</p>
                        </div>
                        <a href="class/create?subject=<?php echo urlencode($subject_name); ?>" class="btn btn-primary btn-action">
                            <i class="bi bi-plus-lg"></i>
                            Create Class
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Subject Overview -->
            <div class="col-md-8">
                <div class="dashboard-card">
                    <h3 class="mb-4">Subject Overview</h3>
                    <div class="subject-content">
                        <h4>Course Description</h4>
                        <p class="mt-3"><?php echo nl2br(htmlspecialchars($subjectDetails['subject_desc'])); ?></p>
                        
                        <h4>Course Category</h4>
                        <p class="mt-3">
                            <span class="fw-medium"><?php echo htmlspecialchars($subjectDetails['course_name']); ?></span>
                            <br>
                            <span class="text-muted"><?php echo htmlspecialchars($subjectDetails['course_desc']); ?></span>
                        </p>

                        <h4>Subject Image</h4>
                        <img src="<?php echo SUBJECT_IMG . $subjectDetails['image']; ?>" 
                             alt="<?php echo htmlspecialchars($subjectDetails['subject_name']); ?>" 
                             class="img-fluid rounded subject-image mt-3" 
                             style="max-width: 300px;">
                    </div>
                </div>
            </div>

            <!-- Subject Stats -->
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h3 class="mb-4">Subject Statistics</h3>
                    <div class="stats-content">
                        <div class="stat-item">
                            <div class="stat-label">Active Classes</div>
                            <div class="stat-value d-flex align-items-center gap-2">
                                <i class="bi bi-mortarboard"></i>
                                <?php echo (int)$subjectDetails['active_classes']; ?>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value d-flex align-items-center gap-2">
                                <i class="bi bi-people"></i>
                                <?php echo (int)$subjectDetails['total_students']; ?>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Average Rating</div>
                            <div class="stat-value d-flex align-items-center gap-2">
                                <?php 
                                $rating = number_format($subjectDetails['average_rating'] ?? 0, 1);
                                ?>
                                <i class="bi bi-star-fill text-warning"></i>
                                <span><?php echo $rating; ?></span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Completion Rate</div>
                            <div class="stat-value d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle"></i>
                                <?php echo number_format($subjectDetails['completion_rate'] ?? 0, 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Your Active Classes -->
                <div class="dashboard-card mt-4">
                    <h3 class="mb-4">Your Active Classes</h3>
                    <div class="active-classes">
                        <?php if (empty($activeClasses)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-calendar2-x" style="font-size: 2.5rem;"></i>
                                <p class="mt-3 mb-0">No active classes</p>
                                <small class="d-block mt-2">Create a new class to get started</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeClasses as $class): ?>
                                <div class="class-item p-3 mb-3 rounded">
                                    <h5 class="mb-2"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                    <p class="d-flex align-items-center gap-2">
                                        <i class="bi bi-clock"></i>
                                        <?php 
                                        $start = new DateTime($class['start_date']);
                                        echo $start->format('D, M j - g:i A'); 
                                        ?>
                                    </p>
                                    <p class="d-flex align-items-center gap-2">
                                        <i class="bi bi-person"></i>
                                        <span><?php echo (int)$class['student_count']; ?> Students</span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    </main> 
    </div> 

    <!-- Scripts -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>
