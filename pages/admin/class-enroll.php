<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'student_management.php';

// Ensure user is logged in and is an ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id);
if (!$classDetails || $classDetails['status'] === 'completed') {
    header('Location: ./?id='.$class_id);
    exit();
}

// Get enrolled students and available students
$enrolledStudents = getClassStudents($class_id);
$availableStudents = getAvailableStudentsForClass($class_id);

$title = "Manage Enrollments - " . htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
        
        <main class="container py-4">
            <!-- Header Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-1">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="../">Classes</a></li>
                                            <li class="breadcrumb-item d-none d-md-inline"><a href="./?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                            <li class="breadcrumb-item active">Enrollments</li>
                                        </ol>
                                    </nav>
                                    <h2 class="page-header mb-0">Manage Class Enrollments</h2>
                                    <p class="text-muted">Manage student enrollments for <?php echo htmlspecialchars($classDetails['class_name']); ?></p>
                                </div>
                                <div class="mt-2 mt-md-0">
                                    <a href="class-details?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Class
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row mt-4">
                <!-- Enrolled Students -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h3 class="card-title mb-0 mb-md-0 mb-2">
                                <i class="bi bi-people-fill"></i> 
                                Enrolled Students 
                                <span class="badge bg-primary"><?php echo count($enrolledStudents); ?>/<?php echo $classDetails['class_size']; ?></span>
                            </h3>
                        </div>
                        <div class="card-body p-0 p-md-3">
                            <?php if (empty($enrolledStudents)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-people display-4"></i>
                                    <p class="mt-2">No students enrolled yet</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($enrolledStudents as $student): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <img src="<?php echo USER_IMG . (!empty($student['profile']) ? $student['profile'] : 'default.jpg'); ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="40" 
                                                     height="40"
                                                     onerror="this.onerror=null; this.classList.add('img-error'); this.src='<?php echo USER_IMG; ?>default.jpg';"
                                                     alt="Student">
                                                <div class="overflow-hidden">
                                                    <h6 class="mb-0 text-truncate"><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></h6>
                                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 100%;"><?php echo htmlspecialchars($student['email']); ?></small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-student-btn ms-2" 
                                                   data-student-id="<?php echo $student['uid']; ?>">
                                                <i class="bi bi-person-x"></i>
                                                <span class="d-none d-md-inline"> Remove</span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Available Students -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h3 class="card-title mb-0 mb-md-0 mb-2">
                                <i class="bi bi-person-plus"></i> 
                                Available Students
                            </h3>
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <input type="text" class="form-control" id="studentSearch" placeholder="Search students...">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0 p-md-3">
                            <?php if (empty($availableStudents)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-person-plus display-4"></i>
                                    <p class="mt-2">No available students to enroll</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush" id="availableStudentsList">
                                    <?php foreach ($availableStudents as $student): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center student-item">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <img src="<?php echo USER_IMG . (!empty($student['profile']) ? $student['profile'] : 'default.jpg'); ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="40" 
                                                     height="40"
                                                     onerror="this.onerror=null; this.classList.add('img-error'); this.src='<?php echo USER_IMG; ?>default.jpg';"
                                                     alt="Student">
                                                <div class="overflow-hidden">
                                                    <h6 class="mb-0 text-truncate student-name"><?php echo htmlspecialchars($student['name']); ?></h6>
                                                    <small class="text-muted text-truncate d-inline-block student-email" style="max-width: 100%;"><?php echo htmlspecialchars($student['email']); ?></small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-success enroll-student-btn ms-2"
                                                   data-student-id="<?php echo $student['student_id']; ?>"
                                                   data-bs-toggle="tooltip" 
                                                   title="Enroll this student">
                                                <i class="bi bi-person-plus"></i>
                                                <span class="d-none d-md-inline"> Enroll</span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include ROOT_PATH . '/components/footer.php'; ?>
        
        <script src="<?php echo BASE; ?>assets/js/admin-class-management.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const classId = <?php echo $class_id; ?>;
                
                // Initialize UI elements
                ClassManager.initUI();
                
                // Student search functionality
                const searchInput = document.getElementById('studentSearch');
                const studentsList = document.getElementById('availableStudentsList');
                
                if (searchInput && studentsList) {
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const students = studentsList.getElementsByClassName('student-item');
                        
                        Array.from(students).forEach(student => {
                            const name = student.querySelector('.student-name').textContent.toLowerCase();
                            const email = student.querySelector('.student-email').textContent.toLowerCase();
                            
                            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                                student.style.display = '';
                            } else {
                                student.style.display = 'none';
                            }
                        });
                    });
                }
                
                // Enroll student functionality
                document.querySelectorAll('.enroll-student-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const studentId = this.getAttribute('data-student-id');
                        const studentItem = this.closest('.student-item');
                        const studentName = studentItem.querySelector('.student-name').textContent;
                        const studentEmail = studentItem.querySelector('.student-email').textContent;
                        const studentImg = studentItem.querySelector('img').getAttribute('src');
                        
                        // Add visual feedback while processing
                        this.disabled = true;
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Enrolling...';
                        studentItem.classList.add('enrolling');
                        
                        // Call the API through our utility
                        ClassManager.enrollStudent(studentId, classId, function(response) {
                            // Remove student from available list
                            studentItem.remove();
                            
                            // Add student to enrolled list
                            const enrolledList = document.querySelector('.col-md-6:first-child .list-group');
                            const noStudentsMessage = document.querySelector('.col-md-6:first-child .text-center');
                            
                            if (noStudentsMessage) {
                                noStudentsMessage.remove();
                                const listGroup = document.createElement('div');
                                listGroup.className = 'list-group list-group-flush';
                                document.querySelector('.col-md-6:first-child .card-body').appendChild(listGroup);
                            }
                            
                            // Create new item for enrolled students list
                            const newItem = document.createElement('div');
                            newItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                            newItem.innerHTML = `
                                <div class="d-flex align-items-center flex-grow-1">
                                    <img src="${studentImg}" 
                                         class="rounded-circle me-2" 
                                         width="40" 
                                         height="40"
                                         onerror="this.onerror=null; this.classList.add('img-error'); this.src='${BASE}assets/img/users/default.jpg';"
                                         alt="Student">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-0 text-truncate">${studentName}</h6>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 100%;">${studentEmail}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-student-btn ms-2" 
                                       data-student-id="${studentId}">
                                    <i class="bi bi-person-x"></i>
                                    <span class="d-none d-md-inline"> Remove</span>
                                </button>
                            `;
                            
                            // Add the item to the enrolled list
                            document.querySelector('.col-md-6:first-child .list-group').appendChild(newItem);
                            
                            // Add highlight effect to new item
                            setTimeout(() => {
                                newItem.style.backgroundColor = 'rgba(25, 135, 84, 0.1)';
                                setTimeout(() => {
                                    newItem.style.transition = 'background-color 1s';
                                    newItem.style.backgroundColor = '';
                                }, 500);
                            }, 0);
                            
                            // Attach remove handler to the new button
                            newItem.querySelector('.remove-student-btn').addEventListener('click', handleRemoveStudent);
                            
                            // Update the enrollment badge count
                            updateEnrollmentCount();
                            
                            // Check if available students list is now empty
                            if (studentsList && studentsList.children.length === 0) {
                                const noAvailableHtml = `
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-person-plus display-4"></i>
                                        <p class="mt-2">No available students to enroll</p>
                                    </div>
                                `;
                                
                                studentsList.parentElement.innerHTML = noAvailableHtml;
                            }
                        });
                    });
                });
                
                // Remove student functionality
                document.querySelectorAll('.remove-student-btn').forEach(button => {
                    button.addEventListener('click', handleRemoveStudent);
                });
                
                function handleRemoveStudent() {
                    const studentId = this.getAttribute('data-student-id');
                    const studentItem = this.closest('.list-group-item');
                    const studentName = studentItem.querySelector('h6').textContent;
                    const studentEmail = studentItem.querySelector('small').textContent;
                    const studentImg = studentItem.querySelector('img').getAttribute('src');
                    
                    if (confirm(`Are you sure you want to remove ${studentName} from the class?`)) {
                        // Add visual feedback while processing
                        this.disabled = true;
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Removing...';
                        studentItem.classList.add('removing');
                        
                        // Call the API through our utility
                        ClassManager.removeStudent(studentId, classId, function(response) {
                            // Remove student from enrolled list
                            studentItem.remove();
                            
                            // Update the enrollment badge count
                            updateEnrollmentCount();
                            
                            // Check if enrolled students list is now empty
                            const enrolledList = document.querySelector('.col-md-6:first-child .list-group');
                            if (enrolledList && enrolledList.children.length === 0) {
                                const noEnrolledHtml = `
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-people display-4"></i>
                                        <p class="mt-2">No students enrolled yet</p>
                                    </div>
                                `;
                                
                                document.querySelector('.col-md-6:first-child .card-body').innerHTML = noEnrolledHtml;
                            }
                            
                            // Add student back to available list if it exists
                            const availableList = document.getElementById('availableStudentsList');
                            if (availableList) {
                                const noAvailableMessage = document.querySelector('.col-md-6:last-child .text-center');
                                
                                if (noAvailableMessage) {
                                    noAvailableMessage.remove();
                                    const listGroup = document.createElement('div');
                                    listGroup.className = 'list-group list-group-flush';
                                    listGroup.id = 'availableStudentsList';
                                    document.querySelector('.col-md-6:last-child .card-body').appendChild(listGroup);
                                }
                                
                                // Create new item for available students list
                                const newItem = document.createElement('div');
                                newItem.className = 'list-group-item d-flex justify-content-between align-items-center student-item';
                                newItem.innerHTML = `
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <img src="${studentImg}" 
                                             class="rounded-circle me-2" 
                                             width="40" 
                                             height="40"
                                             onerror="this.onerror=null; this.classList.add('img-error'); this.src='${BASE}assets/img/users/default.jpg';"
                                             alt="Student">
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 text-truncate student-name">${studentName}</h6>
                                            <small class="text-muted text-truncate d-inline-block student-email" style="max-width: 100%;">${studentEmail}</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success enroll-student-btn ms-2"
                                           data-student-id="${studentId}"
                                           data-bs-toggle="tooltip" 
                                           title="Enroll this student">
                                        <i class="bi bi-person-plus"></i>
                                        <span class="d-none d-md-inline"> Enroll</span>
                                    </button>
                                `;
                                
                                // Add the item to the available list
                                availableList.appendChild(newItem);
                                
                                // Add highlight effect to new item
                                setTimeout(() => {
                                    newItem.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
                                    setTimeout(() => {
                                        newItem.style.transition = 'background-color 1s';
                                        newItem.style.backgroundColor = '';
                                    }, 500);
                                }, 0);
                                
                                // Attach enroll handler to the new button
                                newItem.querySelector('.enroll-student-btn').addEventListener('click', function() {
                                    const studentId = this.getAttribute('data-student-id');
                                    const studentItem = this.closest('.student-item');
                                    const studentName = studentItem.querySelector('.student-name').textContent;
                                    const studentEmail = studentItem.querySelector('.student-email').textContent;
                                    const studentImg = studentItem.querySelector('img').getAttribute('src');
                                    
                                    // Add visual feedback while processing
                                    this.disabled = true;
                                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> Enrolling...';
                                    studentItem.classList.add('enrolling');
                                    
                                    ClassManager.enrollStudent(studentId, classId, function(response) {
                                        // Handle successful enrollment (reuse logic from above)
                                        studentItem.remove();
                                        
                                        // Continue with enrollment logic...
                                        // This would be the same as the enrollment handler above
                                    });
                                });
                            }
                        });
                    }
                }
                
                // Helper function to update the enrollment badge count
                function updateEnrollmentCount() {
                    const enrolledCount = document.querySelector('.col-md-6:first-child .list-group') 
                        ? document.querySelectorAll('.col-md-6:first-child .list-group-item').length 
                        : 0;
                    
                    const badge = document.querySelector('.col-md-6:first-child .badge');
                    const classSize = <?php echo $classDetails['class_size'] ?: '"-"'; ?>;
                    
                    if (badge) {
                        badge.textContent = enrolledCount + '/' + classSize;
                    }
                }
            });
        </script>
        
        <style>
            /* Common Admin Class Pages Styling */
            .page-header {
                font-size: 1.75rem;
                font-weight: 600;
                color: var(--primary-color, #0052cc);
            }
            
            .breadcrumb {
                font-size: 0.875rem;
            }
            
            .breadcrumb-item.active {
                color: var(--primary-color, #0052cc);
                font-weight: 500;
            }
            
            .card {
                border-radius: 0.5rem;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                margin-bottom: 1.5rem;
                overflow: hidden;
            }
            
            .card-header {
                background-color: rgba(0, 0, 0, 0.02);
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 1rem;
            }
            
            .card-header .card-title {
                margin-bottom: 0;
                display: flex;
                align-items: center;
            }
            
            .card-header .card-title i {
                margin-right: 0.5rem;
                color: var(--primary-color, #0052cc);
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .btn {
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .btn i {
                font-size: 1.1em;
            }
            
            .list-group-item {
                border-color: rgba(0, 0, 0, 0.05);
                transition: background-color 0.2s;
                padding: 0.75rem 1rem;
            }
            
            .list-group-item:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }
            
            .img-error {
                opacity: 0.7;
                background-color: #f8f9fa !important;
                border: 1px dashed #ccc !important;
            }
            
            .list-group-flush .list-group-item:first-child {
                border-top: 0;
            }
            
            .list-group-flush .list-group-item:last-child {
                border-bottom: 0;
            }
            
            /* Student enrollment specific styles */
            .student-item {
                transition: all 0.3s ease;
            }
            
            .student-item.removing {
                opacity: 0.5;
                background-color: rgba(220, 53, 69, 0.1);
            }
            
            .student-item.enrolling {
                opacity: 0.5;
                background-color: rgba(25, 135, 84, 0.1);
            }
            
            /* Mobile Responsiveness */
            @media (max-width: 991.98px) {
                .container {
                    max-width: 100%;
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
                
                .card-body {
                    padding: 1rem;
                }
                
                .row {
                    margin-left: -0.5rem;
                    margin-right: -0.5rem;
                }
                
                .col, .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12, 
                .col-sm, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, 
                .col-md, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, 
                .col-lg, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12 {
                    padding-left: 0.5rem;
                    padding-right: 0.5rem;
                }
            }
            
            @media (max-width: 767.98px) {
                .page-header {
                    font-size: 1.5rem;
                }
                
                .btn {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
                }
                
                .d-flex {
                    flex-wrap: wrap;
                }
                
                .list-group-item {
                    padding: 0.75rem 0.5rem;
                }
                
                .card-header .input-group {
                    margin-top: 0.5rem;
                    width: 100% !important;
                    max-width: none !important;
                }
                
                .card-body.p-0 {
                    padding: 0 !important;
                }
            }
            
            @media (max-width: 575.98px) {
                .card-header .card-title {
                    font-size: 1.1rem;
                }
                
                .py-4 {
                    padding-top: 1rem !important;
                    padding-bottom: 1rem !important;
                }
                
                .mt-4 {
                    margin-top: 1rem !important;
                }
                
                .mb-4 {
                    margin-bottom: 1rem !important;
                }
                
                .d-flex.justify-content-between {
                    flex-direction: column;
                    gap: 0.5rem;
                }
                
                .list-group-item h6 {
                    font-size: 0.95rem;
                }
                
                .list-group-item img {
                    width: 32px;
                    height: 32px;
                }
                
                .enroll-student-btn, .remove-student-btn {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.75rem;
                }
            }
        </style>
    </body>
</html> 