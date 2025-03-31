<?php 
require_once '../../backends/main.php';
require_once BACKEND.'student_management.php';

if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit;
}

$available_classes = getCurrentActiveClass();
$courses = getCoursesWithSubjects();
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content">
            <!-- Header Section with Title and Search -->
            <div class="content-section mb-4">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="page-title mb-0">Available Classes</h1>
                            <div class="search-section">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search classes...">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <a href="class" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Class
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="content-section mb-4">
                <div class="content-card">
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-3">Filter by Type</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-outline-primary active" data-filter="all">
                                    All Classes
                                </button>
                                <button class="btn btn-outline-success" data-filter="free">
                                    Free Classes
                                </button>
                                <button class="btn btn-outline-info" data-filter="paid">
                                    Paid Classes
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <h6 class="mb-3">Filter by Course & Subject</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($courses as $course_id => $course): ?>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?php echo htmlspecialchars($course['name']); ?>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" type="button" data-filter="course-<?php echo $course_id; ?>">
                                                All <?php echo htmlspecialchars($course['name']); ?>
                                            </button>
                                        </li>
                                        <?php if (!empty($course['subjects'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php foreach ($course['subjects'] as $subject): ?>
                                            <li>
                                                <button class="dropdown-item" type="button" data-filter="subject-<?php echo $subject['id']; ?>">
                                                    <?php echo htmlspecialchars($subject['name']); ?>
                                                </button>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classes Grid -->
            <div class="row g-4" id="classesGrid">
                <?php if (empty($available_classes)): ?>
                <div class="col-12">
                    <div class="content-card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-calendar2-x text-muted" style="font-size: 48px;"></i>
                            <h3 class="mt-3">No Classes Available</h3>
                            <p class="text-muted">Check back later for new classes</p>
                        </div>
                    </div>
                </div>
                <?php else: 
                    foreach ($available_classes as $class): 
                ?>
                <div class="col-md-6 col-lg-4" 
                     data-name="<?php echo strtolower(htmlspecialchars($class['class_name'])); ?>"
                     data-price="<?php echo $class['is_free'] ? 'free' : 'paid'; ?>"
                     data-course="course-<?php echo htmlspecialchars($class['course_id']); ?>"
                     data-subject="subject-<?php echo htmlspecialchars($class['subject_id']); ?>">
                    <div class="content-card h-100">
                        <div class="card-body">
                            <div class="position-relative">
                                <img src="<?php echo !empty($class['thumbnail']) ? CLASS_IMG . $class['thumbnail'] : CLASS_IMG . 'default.jpg'; ?>" 
                                     class="card-img-top rounded mb-3" 
                                     alt="<?php echo htmlspecialchars($class['class_name']); ?>">
                                <?php if ($class['enrolled_students'] >= 5): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning">
                                        <i class="bi bi-fire"></i> Popular
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($class['subject_name']); ?>
                                </span>
                                <span class="badge <?php echo $class['is_free'] ? 'bg-success' : 'bg-info'; ?>">
                                    <?php echo $class['is_free'] ? 'Free' : '₱' . number_format($class['price'], 2); ?>
                                </span>
                            </div>
                            
                            <h5 class="card-title mb-2">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </h5>
                            
                            <p class="text-muted small mb-3">
                                <?php echo htmlspecialchars($class['course_name']); ?>
                            </p>
                            
                            <div class="tutor-info d-flex align-items-center mb-3">
                                <img src="<?php echo !empty($class['tutor_avatar']) ? USER_IMG . $class['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                                     class="tutor-avatar rounded-circle me-2" 
                                     alt="<?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?>"
                                     width="32" height="32">
                                <div>
                                    <p class="mb-0 fw-medium">
                                        <?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">
                                    <i class="bi bi-people me-1"></i>
                                    <?php echo $class['enrolled_students']; ?> enrolled
                                </span>
                                <a href="enrollments/class?id=<?php echo $class['class_id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>
    </main>
</div>
        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            // Search and filter functionality
            const searchInput = document.getElementById('searchInput');
            const filterButtons = document.querySelectorAll('[data-filter]');
            const classCards = document.querySelectorAll('#classesGrid > div[data-name]');
            
            let currentFilter = 'all';
            
            // Search functionality
            searchInput.addEventListener('input', filterClasses);
            
            // Filter buttons
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Remove active class from all buttons in the same group
                    if (filter === 'all' || filter === 'free' || filter === 'paid') {
                        document.querySelectorAll('[data-filter="all"], [data-filter="free"], [data-filter="paid"]')
                            .forEach(btn => btn.classList.remove('active'));
                    } else {
                        document.querySelectorAll('[data-filter^="course-"], [data-filter^="subject-"]')
                            .forEach(btn => btn.classList.remove('active'));
                    }
                    
                    this.classList.add('active');
                    currentFilter = filter;
                    filterClasses();
                });
            });
            
            function filterClasses() {
                const searchTerm = searchInput.value.toLowerCase();
                
                classCards.forEach(card => {
                    const name = card.dataset.name;
                    const price = card.dataset.price;
                    const course = card.dataset.course;
                    const subject = card.dataset.subject;
                    
                    const matchesSearch = name.includes(searchTerm);
                    const matchesFilter = currentFilter === 'all' || 
                                        currentFilter === price || 
                                        currentFilter === course ||
                                        currentFilter === subject;
                    
                    card.style.display = matchesSearch && matchesFilter ? '' : 'none';
                });
            }

            // Initialize Bootstrap dropdowns
            document.addEventListener('DOMContentLoaded', function() {
                var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
                var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                    return new bootstrap.Dropdown(dropdownToggleEl);
                });
            });
        </script>

        <style>
            .dashboard-content {
                padding: 2rem;
                background-color: #F5F5F5;
            }
            .search-section .input-group {
                width: 300px;
            }
            .content-card {
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                overflow: hidden;
                background-color: #FFFFFF;
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            }
            .content-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .card-img-top {
                height: 160px;
                width: 100%;
                object-fit: cover;
                object-position: center;
            }
            .tutor-avatar {
                width: 32px;
                height: 32px;
                object-fit: cover;
                object-position: center;
            }
            .btn-outline-primary.active {
                background-color: var(--bs-primary);
                color: white;
            }
            .btn-outline-success.active {
                background-color: var(--bs-success);
                color: white;
            }
            .btn-outline-info.active {
                background-color: var(--bs-info);
                color: white;
            }
            .btn-outline-secondary.active {
                background-color: var(--bs-secondary);
                color: white;
            }
            .dropdown {
                position: relative;
                display: inline-block;
            }
            .dropdown-menu {
                display: none;
                position: absolute;
                background-color: #fff;
                min-width: 200px;
                max-height: 300px;
                overflow-y: auto;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                z-index: 9999;
                padding: 0.5rem 0;
                margin: 0.125rem 0 0;
                border: 1px solid rgba(0, 0, 0, 0.15);
                border-radius: 0.375rem;
            }
            .dropdown-menu.show {
                display: block;
            }
            .dropdown-item {
                display: block;
                width: 100%;
                padding: 0.5rem 1rem;
                clear: both;
                font-weight: 400;
                color: #212529;
                text-align: inherit;
                text-decoration: none;
                white-space: nowrap;
                background-color: transparent;
                border: 0;
                cursor: pointer;
            }
            .dropdown-item:hover,
            .dropdown-item:focus {
                color: #1e2125;
                background-color: #f8f9fa;
            }
            .dropdown-divider {
                height: 0;
                margin: 0.5rem 0;
                overflow: hidden;
                border-top: 1px solid #e9ecef;
            }
            @media (max-width: 768px) {
                .search-section {
                    width: 100%;
                    margin-top: 1rem;
                }
                .search-section .input-group {
                    width: 100%;
                }
                .card-img-top {
                    height: 140px;
                }
            }
        </style>
    </body>
</html>