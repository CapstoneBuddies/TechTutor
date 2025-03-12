<?php 
require_once '../../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get subject from URL parameter
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Map subject IDs to readable names and categories
$subjectMap = [
    'java' => ['name' => 'Java Programming', 'category' => 'Computer Programming'],
    'python' => ['name' => 'Python Programming', 'category' => 'Computer Programming'],
    'cpp' => ['name' => 'C++ Programming', 'category' => 'Computer Programming'],
    'frontend' => ['name' => 'Frontend Development', 'category' => 'Web Development']
];

// Get subject details or redirect if invalid
if (!isset($subjectMap[$subject])) {
    header('Location: ./');
    exit();
}

$subjectDetails = $subjectMap[$subject];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | <?php echo htmlspecialchars($subjectDetails['name']); ?></title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>techguru-common.css" rel="stylesheet">
</head>

<body>
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
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="techguru_subjects.php">Teaching Subjects</a></li>
                                    <li class="breadcrumb-item"><?php echo htmlspecialchars($subjectDetails['category']); ?></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($subjectDetails['name']); ?></li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($subjectDetails['name']); ?></h2>
                            <p class="subtitle">View subject details and create a class</p>
                        </div>
                        <a href="class/create?subject=<?php echo urlencode($subject); ?>" class="btn btn-primary btn-action">
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
                        <?php if ($subject === 'java'): ?>
                        <h4>Course Description</h4>
                        <p>Java Programming is a comprehensive course that covers object-oriented programming principles using Java. Students will learn everything from basic syntax to advanced concepts like multithreading and GUI development.</p>
                        
                        <h4 class="mt-4">Learning Outcomes</h4>
                        <ul>
                            <li>Understand Java syntax and basic programming concepts</li>
                            <li>Master object-oriented programming principles</li>
                            <li>Work with Java collections and data structures</li>
                            <li>Develop GUI applications using JavaFX</li>
                            <li>Implement multithreading and concurrent programming</li>
                            <li>Handle exceptions and debug Java applications</li>
                        </ul>

                        <h4 class="mt-4">Prerequisites</h4>
                        <ul>
                            <li>Basic understanding of programming concepts</li>
                            <li>Familiarity with any programming language (preferred but not required)</li>
                            <li>Basic computer skills</li>
                        </ul>
                        <?php endif; ?>

                        <!-- Add similar blocks for other subjects -->
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
                            <span class="stat-value">5</span>
                        </div>
                        <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-label">Total Students</span>
                            <span class="stat-value">120</span>
                        </div>
                        <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                            <span class="stat-label">Average Rating</span>
                            <span class="stat-value">4.8 <i class="bi bi-star-fill text-warning"></i></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between align-items-center">
                            <span class="stat-label">Completion Rate</span>
                            <span class="stat-value">92%</span>
                        </div>
                    </div>
                </div>

                <!-- Your Active Classes -->
                <div class="dashboard-card mt-4">
                    <h3>Your Active Classes</h3>
                    <div class="active-classes mt-4">
                        <div class="class-item p-3 mb-3 bg-light rounded">
                            <h5 class="mb-2">Morning Batch</h5>
                            <p class="mb-2"><i class="bi bi-clock me-2"></i>Mon, Wed, Fri - 9:00 AM</p>
                            <p class="mb-0"><i class="bi bi-person me-2"></i>15 Students</p>
                        </div>
                        <div class="class-item p-3 bg-light rounded">
                            <h5 class="mb-2">Evening Batch</h5>
                            <p class="mb-2"><i class="bi bi-clock me-2"></i>Tue, Thu - 7:00 PM</p>
                            <p class="mb-0"><i class="bi bi-person me-2"></i>12 Students</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
