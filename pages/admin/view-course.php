<?php 
require_once '../../backends/main.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: " . BASE);
    exit();
}

// Get data using centralized functions
$subjects = getSubjectsWithCounts();
$courses = getAllCourses();
$stats = getSubjectStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Course Management</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo CSS; ?>header.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include_once ROOT_PATH . '/components/header.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1>Course Management</h1>
                        <p class="text-muted">Manage and monitor all courses and subjects</p>
                    </div>
                    <div>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <i class="bi bi-plus-lg"></i> Add Course
                        </button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                            <i class="bi bi-plus-lg"></i> Add Subject
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-primary text-white">
                                    <i class="bi bi-book"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Total Courses</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_courses']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-success text-white">
                                    <i class="bi bi-journal-text"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Total Subjects</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_subjects']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-info text-white">
                                    <i class="bi bi-person-check"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Active Tutors</h6>
                                    <h3 class="mb-0"><?php echo $stats['active_tutors']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-warning text-white">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Total Students</h6>
                                    <h3 class="mb-0"><?php echo $stats['enrolled_students']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course List -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title">Course List</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Description</th>
                                    <th>No. of Subjects</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_desc'] ?? ''); ?></td>
                                    <td><?php echo $course['subject_count']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary action-btn" onclick="editCourse(<?php echo $course['course_id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger action-btn" onclick="deleteCourse(<?php echo $course['course_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Subject List -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title">Subject List</h5>
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <label class="input-group-text" for="courseFilter">Course:</label>
                                <select class="form-select" id="courseFilter">
                                    <option value="">All</option>
                                    <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course['course_name']); ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="input-group">
                                <label class="input-group-text" for="statusFilter">Status:</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Course</th>
                                    <th>No. of Tutors</th>
                                    <th>No. of Students</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['course_name']); ?></td>
                                    <td><?php echo $subject['tutor_count']; ?></td>
                                    <td><?php echo $subject['student_count']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $subject['is_active'] ? 'status-active' : 'status-restricted'; ?>">
                                            <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary action-btn" onclick="editSubject(<?php echo $subject['subject_id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm <?php echo $subject['is_active'] ? 'btn-danger' : 'btn-success'; ?> action-btn" 
                                                onclick="toggleStatus(<?php echo $subject['subject_id']; ?>, <?php echo $subject['is_active'] ? 0 : 1; ?>)">
                                            <i class="bi <?php echo $subject['is_active'] ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCourseForm" onsubmit="saveCourse(); return false;">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Course Name</label>
                            <input type="text" class="form-control" name="course_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="course_desc" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addSubjectForm" onsubmit="saveSubject(); return false;">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-control" name="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="subject_desc" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.getElementById('courseFilter').addEventListener('change', filterSubjects);
        document.getElementById('statusFilter').addEventListener('change', filterSubjects);

        function filterSubjects() {
            const courseName = document.getElementById('courseFilter').value;
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let show = true;
                const courseCell = row.cells[1].textContent;
                const statusCell = row.cells[4].textContent.trim().toLowerCase();

                if (courseName && !courseCell.includes(courseName)) show = false;
                if (status !== '') {
                    const isActive = statusCell === 'active';
                    if (status === '1' && !isActive) show = false;
                    if (status === '0' && isActive) show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        // Subject status toggle
        function toggleStatus(subjectId, newStatus) {
            if (!confirm('Are you sure you want to change this subject\'s status?')) return;

            const formData = new URLSearchParams();
            formData.append('action', 'toggle-subject');
            formData.append('subject_id', subjectId);
            formData.append('status', newStatus);

            fetch(`toggle-subject-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the status');
            });
        }

        // Add new course
        function saveCourse() {
            const form = document.getElementById('addCourseForm');
            const formData = new URLSearchParams(new FormData(form));
            formData.append('action', 'add-course');

            fetch(`add-course`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to add course: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the course');
            });

            return false;
        }

        // Add new subject
        function saveSubject() {
            const form = document.getElementById('addSubjectForm');
            const formData = new URLSearchParams(new FormData(form));
            formData.append('action', 'add-subject');

            fetch(`add-subject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to add subject: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the subject');
            });

            return false;
        }

        // Edit course
        function editCourse(courseId) {
            const courseName = prompt('Enter new course name:');
            if (!courseName) return;

            const courseDesc = prompt('Enter new course description:');
            if (courseDesc === null) return;

            const formData = new URLSearchParams();
            formData.append('action', 'edit-course');
            formData.append('course_id', courseId);
            formData.append('course_name', courseName);
            formData.append('course_desc', courseDesc);

            fetch(`edit-course`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update course: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the course');
            });
        }

        // Delete course
        function deleteCourse(courseId) {
            if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) return;

            const formData = new URLSearchParams();
            formData.append('action', 'delete-course');
            formData.append('course_id', courseId);

            fetch(`delete-course`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete course: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the course');
            });
        }
    </script>
</body>
</html>
