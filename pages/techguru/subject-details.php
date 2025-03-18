<?php 
require_once '../../backends/main.php';
require_once ROOT_PATH.'/backends/class_management.php';

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
                    <h3>Subject Overview</h3>
                    <div class="subject-content mt-4">
                        <h4>Course Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($subjectDetails['subject_desc'])); ?></p>
                        
                        <h4 class="mt-4">Course Category</h4>
                        <p><?php echo htmlspecialchars($subjectDetails['course_name']); ?> - <?php echo htmlspecialchars($subjectDetails['course_desc']); ?></p>

                        <h4 class="mt-4">Subject Image</h4>
                        <img src="<?php echo SUBJECT_IMG . $subjectDetails['image']; ?>" alt="<?php echo htmlspecialchars($subjectDetails['subject_name']); ?>" class="img-fluid rounded" style="max-width: 300px;">
                    </div>
                </div>
            </div>

            <!-- Subject Stats -->
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h3>Subject Statistics</h3>
                    <div class="stats-content mt-4">
                        <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-label">Active Classes</span>
                            <span class="stat-value"><?php echo (int)$subjectDetails['active_classes']; ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-label">Total Students</span>
                            <span class="stat-value"><?php echo (int)$subjectDetails['total_students']; ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-label">Average Rating</span>
                            <span class="stat-value">
                                <?php 
                                $rating = number_format($subjectDetails['average_rating'] ?? 0, 1);
                                echo $rating; 
                                ?> 
                                <i class="bi bi-star-fill text-warning"></i>
                            </span>
                        </div>
                        <div class="stat-item d-flex justify-content-between align-items-center">
                            <span class="stat-label">Completion Rate</span>
                            <span class="stat-value"><?php echo number_format($subjectDetails['completion_rate'] ?? 0, 1); ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Your Active Classes -->
                <div class="dashboard-card mt-4">
                    <h3>Your Active Classes</h3>
                    <div class="active-classes mt-4">
                        <?php if (empty($activeClasses)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-calendar2-x" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">No active classes</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeClasses as $class): ?>
                                <div class="class-item p-3 mb-3 bg-light rounded">
                                    <h5 class="mb-2"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                    <p class="mb-2">
                                        <i class="bi bi-clock me-2"></i>
                                        <?php 
                                        $start = new DateTime($class['start_date']);
                                        echo $start->format('D, M j - g:i A'); 
                                        ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-person me-2"></i>
                                        <?php echo (int)$class['student_count']; ?> Students
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
