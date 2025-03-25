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
            background-color: #f8f9fa;
            overflow-y: auto; 
            position: relative;
        }
        .subject-card {
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .subject-card .card-img-top {
            height: 160px;
            object-fit: cover;
        }
        .subject-card .card-body {
            padding: 1.25rem;
            background: white;
        }
        .subject-stats {
            display: flex;
            gap: 1rem;
            margin-top: auto;
            padding-top: 1rem;
        }
        .subject-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .search-container {
            position: relative;
            max-width: 300px;
            width: 100%;
        }
        .search-bar {
            display: flex;
            gap: 0.5rem;
        }
        .btn-search {
            background: var(--bs-primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
        }
        .btn-search:hover {
            background: var(--bs-primary-dark);
        }
        #noResults {
            display: none;
            padding: 3rem 1rem;
        }
        #noResults i {
            font-size: 3rem; 
            color: #6c757d;
        }
        .category-title {
            color: var(--bs-primary);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .course-info {
            position: relative;
            padding-left: 1rem;
        }
        .course-info::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--bs-primary);
            border-radius: 2px;
        }
        @media (max-width: 768px) {
            .search-container {
                max-width: 100%;
                margin-top: 1rem;
            }
            .subject-card .card-img-top {
                height: 140px;
            }
            .subject-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        .fade-enter {
            opacity: 0;
            transform: translateY(20px);
        }
        .fade-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s, transform 0.3s;
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
            <div class="col-12 mt-4 fade-enter">
                <div class="dashboard-card">
                    <div class="course-info">
                        <h3 class="category-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <div class="subtitle text-muted">
                            <?php echo htmlspecialchars($course['course_desc']); ?>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="row g-4">
                        <?php foreach (getSubjectDetails($course['course_id']) as $subject):?>
                        <div class="col-md-4 mb-0">
                            <div class="subject-card h-100">
                                <img src="<?php echo SUBJECT_IMG.$subject['image']; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                     loading="lazy">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                    <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($subject['subject_desc']); ?></p>
                                    <div class="subject-stats">
                                        <span class="subject-stat" data-bs-toggle="tooltip" title="Total enrolled students">
                                            <i class="bi bi-person text-primary"></i>
                                            <?php echo htmlspecialchars($subject['student_count']); ?> Students
                                        </span>
                                        <span class="subject-stat" data-bs-toggle="tooltip" title="Active classes">
                                            <i class="bi bi-journal-bookmark-fill text-success"></i>
                                            <?php echo htmlspecialchars($subject['class_count']); ?> Classes
                                        </span>
                                    </div>
                                    <a href="subjects/class?subject=<?php echo urlencode($subject['subject_name']); ?>" 
                                       class="btn btn-primary btn-action mt-3 w-100">
                                        <i class="bi bi-book me-2"></i>
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
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Animate elements on load
            const fadeElements = document.querySelectorAll('.fade-enter');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('fade-enter-active');
                }, index * 100);
            });

            // Add event listener for real-time search with debounce
            const searchInput = document.getElementById('subjectSearch');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(searchSubjects, 300);
            });

            function searchSubjects() {
                const searchTerm = searchInput.value.toLowerCase();
                const subjectCards = document.querySelectorAll('.subject-card');
                const courseCategories = document.querySelectorAll('.col-12.mt-4');
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
                        
                        if (title.includes(searchTerm) || description.includes(searchTerm) || 
                            courseName.includes(searchTerm) || courseDesc.includes(searchTerm)) {
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
