<?php 
    require_once '../../backends/main.php';
    
    // Check if user is logged in and has admin role
    if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE);
        exit();
    }
    
    // Get course data
    $query = "SELECT c.course_id, c.course_name, c.course_desc, s.subject_name, 
             COUNT(DISTINCT cl.class_id) as total_classes,
             COUNT(DISTINCT cs.user_id) as student_count,
             u.first_name, u.last_name
             FROM course c
             LEFT JOIN subject s ON c.course_id = s.course_id
             LEFT JOIN class cl ON s.subject_id = cl.subject_id
             LEFT JOIN class_schedule cs ON cl.class_id = cs.class_id AND cs.role = 'STUDENT'
             LEFT JOIN users u ON cl.tutor_id = u.uid
             GROUP BY c.course_id";
             
    $result = $conn->query($query);
    $courses = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = [
                'id' => $row['course_id'],
                'name' => $row['course_name'],
                'description' => $row['course_desc'],
                'category' => $row['subject_name'],
                'tutor_name' => $row['first_name'] . ' ' . $row['last_name'],
                'student_count' => $row['student_count'],
                'total_classes' => $row['total_classes'],
                'status' => true // You may want to add a status field to your course table
            ];
        }
    }

    // Get tutors for the add course form
    $tutor_query = "SELECT uid, first_name, last_name FROM users WHERE role = 'TECHGURU' AND status = 1";
    $tutor_result = $conn->query($tutor_query);
    $tutors = [];
    if ($tutor_result) {
        while ($row = $tutor_result->fetch_assoc()) {
            $tutors[] = [
                'id' => $row['uid'],
                'name' => $row['first_name'] . ' ' . $row['last_name']
            ];
        }
    }

    // Get statistics
    $stats = [];
    
    // Total active tutors
    $tutor_count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'TECHGURU' AND status = 1";
    $result = $conn->query($tutor_count_query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['active_tutors'] = $row['count'];
    }
    
    // Total enrolled students
    $student_count_query = "SELECT COUNT(DISTINCT user_id) as count FROM class_schedule WHERE role = 'STUDENT'";
    $result = $conn->query($student_count_query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_students'] = $row['count'];
    }
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>course.css" rel="stylesheet">
    <style>
        
    </style>
</head>
<body>
    <?php include_once ROOT_PATH . '/components/header.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content-wrapper">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Course Management</h1>
                    <p class="text-muted">Manage and monitor all courses</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="bi bi-plus-lg"></i> Add New Course
                </button>
            </div>
        </div>

        <!-- Course Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white">
                    <div class="stat-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Courses</h3>
                        <p class="stat-number"><?php echo count($courses); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Tutors</h3>
                        <p class="stat-number"><?php echo $stats['active_tutors'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Enrolled Students</h3>
                        <p class="stat-number"><?php echo $stats['total_students'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject List -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title">Subject List</h5>
                    <div class="course-filters">
                        <select class="form-select form-select-sm me-2 course-filter">
                            <option value="">All Categories</option>
                            <!-- Categories will be dynamically generated -->
                            <option value="programming">Programming</option>
                            <option value="networking">Networking</option>
                            <option value="design">Design</option>
                        </select>
                        <select class="form-select form-select-sm">
                            <option value="">Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Tutor</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-2">
                                            <i class="bi bi-book"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo $course['name']; ?></h6>
                                            <small class="text-muted"><?php echo substr($course['description'], 0, 50); ?>...</small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $course['category']; ?></td>
                                <td><?php echo $course['tutor_name']; ?></td> <!-- Tutor Count -->
                                <td><?php echo $course['student_count']; ?></td>
                                <td>
                                    <span class="badge <?php echo $course['status'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $course['status'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary action-btn    " onclick="viewCourse(<?php echo $course['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning action-btn" onclick="editCourse(<?php echo $course['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger action-btn" onclick="deleteCourse(<?php echo $course['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCourseForm">
                        <div class="mb-3">
                            <label class="form-label">Course Name</label>
                            <input type="text" class="form-control" name="course_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <option value="programming">Programming</option>
                                <option value="networking">Networking</option>
                                <option value="design">Design</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Tutor</label>
                            <select class="form-select" name="tutor_id" required>
                                <option value="">Select Tutor</option>
                                <?php foreach ($tutors as $tutor): ?>
                                <option value="<?php echo $tutor['id']; ?>"><?php echo $tutor['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCourse()">Save Course</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Course Management Scripts -->
    <script>
        function viewCourse(id) {
            window.location.href = `${BASE}dashboard/courses/view/${id}`;
        }
        
        function editCourse(id) {
            window.location.href = `${BASE}dashboard/courses/edit/${id}`;
        }
        
        function deleteCourse(id) {
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                fetch(`${BASE}api/courses/delete/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Course deleted successfully');
                        location.reload();
                    } else {
                        alert('Failed to delete course: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the course');
                });
            }
        }
        
        function saveCourse() {
            const form = document.getElementById('addCourseForm');
            const formData = new FormData(form);
            
            fetch(`${BASE}api/courses/add`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Course added successfully');
                    location.reload();
                } else {
                    alert('Failed to add course: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the course');
            });
        }

        // Filter courses based on category and status
        document.querySelectorAll('.course-filters select').forEach(select => {
            select.addEventListener('change', function() {
                const category = document.querySelector('select[name="category"]').value;
                const status = document.querySelector('select[name="status"]').value;
                
                // You can implement filtering logic here
                // For now, just reload with query parameters
                const params = new URLSearchParams();
                if (category) params.append('category', category);
                if (status) params.append('status', status);
                
                window.location.href = `${window.location.pathname}?${params.toString()}`;
            });
        });
    </script>
</body>
</html>
