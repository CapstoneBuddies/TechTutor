<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}
    $courses = getCourseDetails();
    $title = "Teaching Subjects";
?>

<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .course-table {
            background-color: #f5f5f5; /* Dirty white */
            overflow-y: auto; 
            position: relative;
        }
        #noResults {
            display: none;
        }
        #noResults i {
            font-size: 3rem; 
            color: #6c757d;
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
            <?php foreach ($courses as $course):?>
            <div class="col-12 mt-4">
                <div class="dashboard-card">
                    <div class="course-info">
                        <h3 class="category-title mb-3"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <div class="subtitle mb-4">
                            <?php echo htmlspecialchars($course['course_desc']); ?>
                        </div>
                    </div>
                    <hr style="border: 1px solid #ccc; margin: 1rem 0;"> <!-- Divider -->
                    <div class="row mx-0">
                        <?php foreach (getSubjectDetails($course['course_id']) as $subject):?>
                        <div class="col-md-4 mb-4">
                            <div class="subject-card">
                                <img src="<?php echo SUBJECT_IMG.$subject['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($subject['subject_desc']); ?></p>
                                    <div class="subject-stats">
                                        <span class="subject-stat">
                                            <i class="bi bi-person"></i>
                                            <?php echo htmlspecialchars($subject['student_count']); ?> Students
                                        </span>
                                        <span class="subject-stat">
                                            <i class="bi bi-journal-bookmark-fill"></i>
                                            <?php echo htmlspecialchars($subject['class_count']); ?> Classes
                                        </span>
                                    </div>
                                    <a href="subjects/class?subject=<?php echo urlencode($subject['subject_name']); ?>" class="btn btn-primary btn-action mt-3">
                                        <i class="bi bi-book"></i>
                                        View Subject
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
    </main> 
    </div> 

    <!-- No Results Message -->
    <div id="noResults" class="text-center py-5" style="">
        <i class="bi bi-search"></i>
        <h4 class="mt-3">No subjects found</h4>
        <p class="text-muted">Try adjusting your search terms</p>
    </div>

    <!-- Scripts -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listener for real-time search
            document.getElementById('subjectSearch').addEventListener('input', searchSubjects);

            function searchSubjects() {
                const searchTerm = document.getElementById('subjectSearch').value.toLowerCase();
                const subjectCards = document.querySelectorAll('.subject-card');
                const courseCategories = document.querySelectorAll('.dashboard-card');
                let hasResults = false;

                if (searchTerm.trim() === '') {
                    document.getElementById('noResults').style.display = 'none';
                    courseCategories.forEach(category => {
                        category.style.display = 'block';
                    });
                    subjectCards.forEach(card => {
                        card.closest('.col-md-4').style.display = 'block';
                    });
                    return;
                }

                courseCategories.forEach(category => {
                    const courseName = category.querySelector('.category-title').textContent.toLowerCase();
                    const courseDesc = category.querySelector('.subtitle').textContent.toLowerCase();
                    let categoryHasMatch = false;
                    
                    const categorySubjects = category.querySelectorAll('.subject-card');
                    categorySubjects.forEach(card => {
                        const title = card.querySelector('.card-title').textContent.toLowerCase();
                        const description = card.querySelector('.card-text').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || description.includes(searchTerm) || courseName.includes(searchTerm) || courseDesc.includes(searchTerm)) {
                            card.closest('.col-md-4').style.display = 'block';
                            categoryHasMatch = true;
                            hasResults = true;
                        } else {
                            card.closest('.col-md-4').style.display = 'none';
                        }
                    });

                    category.style.display = categoryHasMatch ? 'block' : 'none';
                });

                document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
            }
        });
    </script>
</body>
</html>
