<?php 
require_once '../../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Teaching Subjects</title>
    
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
                                    <li class="breadcrumb-item active">Teaching Subjects</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Teaching Subjects</h2>
                            <p class="subtitle">Select a subject category to view or create classes</p>
                        </div>
                        <div class="search-container">
                            <div class="search-bar">
                                <input type="text" id="subjectSearch" placeholder="Search subjects..." class="form-control">
                                <button class="btn btn-search" onclick="searchSubjects()">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Categories -->
        <div class="row">
            <!-- Computer Programming Category -->
            <div class="col-12 mt-4">
                <h3 class="category-title">Computer Programming</h3>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="subject-card">
                    <img src="<?php echo IMG; ?>subjects/java.jpg" class="card-img-top" alt="Java Programming">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Java Programming</h5>
                        <p class="card-text">Learn object-oriented programming with Java. Perfect for beginners and intermediate programmers.</p>
                        <div class="subject-stats">
                            <span class="subject-stat">
                                <i class="bi bi-person"></i>
                                120 Students
                            </span>
                            <span class="subject-stat">
                                <i class="bi bi-star-fill"></i>
                                4.8
                            </span>
                        </div>
                        <a href="subjects/class?subject=java" class="btn btn-primary btn-action mt-3">
                            <i class="bi bi-book"></i>
                            View Subject
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="subject-card">
                    <img src="<?php echo IMG; ?>subjects/python.jpg" class="card-img-top" alt="Python Programming">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Python Programming</h5>
                        <p class="card-text">Master Python programming language. From basics to advanced concepts including AI and ML.</p>
                        <div class="subject-stats">
                            <span class="subject-stat">
                                <i class="bi bi-person"></i>
                                150 Students
                            </span>
                            <span class="subject-stat">
                                <i class="bi bi-star-fill"></i>
                                4.9
                            </span>
                        </div>
                        <a href="subjects/class?subject=python" class="btn btn-primary btn-action mt-3">
                            <i class="bi bi-book"></i>
                            View Subject
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="subject-card">
                    <img src="<?php echo IMG; ?>subjects/cpp.jpg" class="card-img-top" alt="C++ Programming">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">C++ Programming</h5>
                        <p class="card-text">Learn system and game development with C++. Covers both basic and advanced topics.</p>
                        <div class="subject-stats">
                            <span class="subject-stat">
                                <i class="bi bi-person"></i>
                                90 Students
                            </span>
                            <span class="subject-stat">
                                <i class="bi bi-star-fill"></i>
                                4.7
                            </span>
                        </div>
                        <a href="subjects/class?subject=cpp" class="btn btn-primary btn-action mt-3">
                            <i class="bi bi-book"></i>
                            View Subject
                        </a>
                    </div>
                </div>
            </div>

            <!-- Web Development Category -->
            <div class="col-12 mt-4">
                <h3 class="category-title">Web Development</h3>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="subject-card">
                    <img src="<?php echo IMG; ?>subjects/frontend.jpg" class="card-img-top" alt="Frontend Development">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Frontend Development</h5>
                        <p class="card-text">Master HTML, CSS, and JavaScript to create beautiful and responsive websites.</p>
                        <div class="subject-stats">
                            <span class="subject-stat">
                                <i class="bi bi-person"></i>
                                200 Students
                            </span>
                            <span class="subject-stat">
                                <i class="bi bi-star-fill"></i>
                                4.8
                            </span>
                        </div>
                        <a href="subjects/class?subject=frontend" class="btn btn-primary btn-action mt-3">
                            <i class="bi bi-book"></i>
                            View Subject
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchSubjects() {
            const searchTerm = document.getElementById('subjectSearch').value.toLowerCase();
            const subjectCards = document.querySelectorAll('.subject-card');
            
            subjectCards.forEach(card => {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const description = card.querySelector('.card-text').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.closest('.col-md-4').style.display = 'block';
                } else {
                    card.closest('.col-md-4').style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
