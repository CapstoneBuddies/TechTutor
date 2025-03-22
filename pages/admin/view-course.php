<?php 
require_once '../../backends/main.php';
require_once BACKEND.'admin_management.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: " . BASE);
    exit();
}

// Get data using centralized functions
$subjects = getSubjectsWithCounts();
$courses = getAllCourses();
$stats = getSubjectStatistics();
$title = 'Course Management';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
 .subject-image{
    margin-bottom: 30px;
    width: 300px;
    height: 200px;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.subject-image-icon {
    height: 50px;
    width: 50px;
    aspect-ratio: 16 / 9;
}
.add-subject-image, .edit-subject-image {
    margin-left: 15%;
}
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
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
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addCourseModal" title="Click to add course">
                                <i class="bi bi-plus-lg"></i> Add Course
                            </button>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal" title="Click to add subject">
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
                                        <th>No. of Active Subjects</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="text-start"><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($course['course_desc'] ?? ''); ?></td>
                                        <td ><?php echo $course['subject_count']; ?></td>
                                        <td >
                                            <button class="btn btn-sm btn-primary action-btn" onclick="openEditModal('course', <?php echo $course['course_id']; ?>, '<?php echo addslashes($course['course_name']); ?>', '<?php echo addslashes($course['course_desc']); ?>')" data-bs-toggle="modal" title="Click to update course information">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger action-btn" onclick="removeCourse(<?php echo $course['course_id']; ?>)" data-bs-toggle="modal" title="Click to delete course">
                                                <i class="bi bi-trash"></i> Delete
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
                            <div>
                                <h5 class="card-title">Subject List</h5>
                                <p>Hover on the subject's name to see its description</p>
                            </div>
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
                                <div class="input-group">
                                    <button class="btn btn-sm btn-primary action-btn updateSubjectCoverBtn">
                                        <i class="fas fa-edit"></i> Update Subject's Cover
                                    </button>
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
                                        <td class="text-start tooltip-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($subject['subject_desc']); ?>">
                                            <img class="shadow subject-image-icon" src="<?php echo SUBJECT_IMG.htmlspecialchars($subject['image']); ?>" alt="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </td>

                                        <td class="text-start"><?php echo htmlspecialchars($subject['course_name']); ?></td>
                                        <td><?php echo $subject['tutor_count']; ?></td>
                                        <td><?php echo $subject['student_count']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $subject['is_active'] ? 'status-active' : 'status-restricted'; ?>">
                                                <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn" onclick="openEditModal('subject', <?php echo $subject['subject_id']; ?>, '<?php echo addslashes($subject['subject_name']); ?>', '<?php echo addslashes($subject['subject_desc']); ?>')" data-bs-toggle="modal" title="Click to edit subject information">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <button class="btn btn-sm <?php echo $subject['is_active'] ? 'btn-danger' : 'btn-success'; ?> action-btn" onclick="toggleSubjectStatus(<?php echo $subject['subject_id']; ?>, <?php echo $subject['is_active'] ? 1 : 0; ?>)" data-bs-toggle="modal" title="Click to restrict subject">
                                                <i class="bi <?php echo $subject['is_active'] ? 'bi bi-lock-fill' : 'bi-unlock-fill'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger action-btn" onclick="removeSubject(<?php echo $subject['subject_id']; ?>)" data-bs-toggle="modal" title="Click to delete subject">
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
            </div>
        </main>
        </main>
        </div>
        <!-- Add Course Modal -->
        <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addCourseForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="courseName" class="form-label">Course Name</label>
                                <input type="text" class="form-control" id="courseName" name="course_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="courseDesc" class="form-label">Description</label>
                                <textarea class="form-control no-resize" id="courseDesc" name="course_desc" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Course</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Subject Modal -->
        <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addSubjectForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <img src="<?php echo SUBJECT_IMG.'default.jpg';?>" alt="Subject Image" class="subject-image add-subject-image img-fluid">
                                <label for="subjectImage" class="form-label">
                                    <input type="file" class="form-control" id="subjectImage" name="subjectImage" accept="image/*" onchange="previewImage(this, 'add')">
                                </label>
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i>
                                        New Image Added
                                    </small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subjectCourse" class="form-label">Course</label>
                                <select class="form-select" id="subjectCourse" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="subjectName" class="form-label">Subject Name</label>
                                <input type="text" class="form-control" id="subjectName" name="subject_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="subjectDesc" class="form-label">Description</label>
                                <textarea class="form-control no-resize" id="subjectDesc" name="subject_desc" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Subject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm">
                        <input type="hidden" id="editId" name="id">
                        <input type="hidden" id="editType" name="type">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="editName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="editName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editDesc" class="form-label">Description</label>
                                <textarea class="form-control no-resize" id="editDesc" name="desc" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Course Confirmation Modal -->
        <div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteCourseModalLabel">Delete Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this course? This action cannot be undone.</p>
                        <p class="text-danger"><strong>Note:</strong> This will delete all subjects and classes under this course.</p>
                        <p class="text-danger"><strong>Note:</strong> You cannot delete a course that has existing subjects.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteCourse">Delete Course</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Subject Confirmation Modal -->
        <div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteSubjectModalLabel">Delete Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this subject? This action cannot be undone.</p>
                        <p class="text-danger"><strong>Note:</strong> This will delete all classes under this subject.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteSubject">Delete Subject</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Subject Status Modal -->
        <div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="toggleStatusModalLabel">Change Subject Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="statusChangeMessage" class="fw-bold"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn" id="confirmToggleStatus">Change Status</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Subject Cover Modal -->
        <div class="modal fade" id="updateSubjectCoverModal" tabindex="-1" aria-labelledby="updateSubjectCoverModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateSubjectCoverModalLabel">Update Subject Image</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="updateSubjectCoverForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <!-- Image Preview -->
                            <div class="mb-3 text-center">
                                <img src="" alt="Subject Image" class="subject-image edit-subject-image img-fluid" id="curImage" style="max-height: 200px; object-fit: cover;">
                                <div id="edit-imagePreview" class="mt-2" style="display: none;">
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i>
                                        New Image Added
                                    </small>
                                </div>
                            </div>


                            <!-- Select Subject Dropdown -->
                            <div class="mb-3">
                                <label for="imageSubject" class="form-label">Subject Name:</label>
                                <select class="form-select" id="imageSubject" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>" data-image="<?php echo SUBJECT_IMG . $subject['image']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- File Input for Image Upload -->
                            <div class="mb-3">
                                <label for="subjectImage" class="form-label">Upload New Image:</label>
                                <input type="file" class="form-control" id="subjectImage" name="subjectImage" accept="image/*" onchange="previewImage(this,'edit')">
                            </div>

                            <!-- Remove Image Button -->
                            <div class="mb-3 text-center">
                                <button type="button" class="btn btn-danger" id="removeImageBtn">Remove Image</button>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Subject Cover</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>



        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <!-- Bootstrap Bundle with Popper -->
        <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script>
            // Links
            const addCourse = BASE+"add-course";
            const editCourse = BASE+"edit-course";
            const deleteCourse = BASE+"delete-course";
            const addSubject = BASE+"add-subject";
            const editSubject = BASE+"edit-subject";
            const deleteSubject = BASE+"delete-subject"; 
            const subjectStatus = BASE+"toggle-subject";
            const updateCover = BASE+"update-subject-cover";
            document.addEventListener("DOMContentLoaded", function () {
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipTriggerList.forEach((tooltipTriggerEl) => {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Elements
                let addSubjectModalElement = document.getElementById("addSubjectModal");
                let editModalElement = document.getElementById('editModal');
                let deleteCourseModalElement = document.getElementById("deleteCourseModal");
                let deleteSubjectModalElement = document.getElementById("deleteSubjectModal")
                // Modals
                let addSubjectModal = new bootstrap.Modal(addSubjectModalElement);
                let editModal = new bootstrap.Modal(editModalElement);

                // Add Course Form Submit
                document.getElementById('addCourseForm').addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    const formData = new FormData(this);
                    formData.append('action', 'add-course');
                    showLoading(true);
                    fetch(addCourse, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        if (data.success) {
                            addSubjectModal.hide(); // Hide the modal properly
                            showToast('success', 'A new course was successfully added.');
                            setTimeout(() => location.reload(), 2000); // Delay reload
                        } else {
                            showToast('error', data.message || 'Error adding course');
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error('Error:', error);
                        showToast('error', 'An error occurred while adding the course');
                    });
                });

                // Add Subject Form Submit
                document.getElementById('addSubjectForm').addEventListener('submit', function (e) {
                    e.preventDefault(); // Prevent default form submission

                    const formData = new FormData(this); // Get form data directly
                    formData.append('action', 'add-subject');
                    showLoading(true);
                    fetch(addSubject, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        if (data.success) {
                            addSubjectModal.hide(); // Close modal properly
                            showToast('success', 'A new subject was successfully added.');
                            setTimeout(() => location.reload(), 1000); // Reload after 1s
                        } else {
                            showToast('error', data.message || 'Error adding subject');
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error('Error:', error);
                        showToast('error', 'An error occurred while adding the subject');
                    });
                });

                // Open edit modal
                function openEditModal(type, id, name, desc) {
                    document.getElementById('editModalLabel').textContent = `Edit ${type.charAt(0).toUpperCase() + type.slice(1)} Information`;
                    document.getElementById('editType').value = type;
                    document.getElementById('editId').value = id;
                    document.getElementById('editName').value = name;
                    document.getElementById('editDesc').value = desc.replace(/"/g, '&quot;') || ''; // Prevent quote breaking

                    editModal.show();
                }
                window.openEditModal = openEditModal;
                // Edit Form Submit
                document.getElementById('editForm').addEventListener('submit', function (e) {
                    e.preventDefault(); // Prevent page reload

                    const formData = new FormData();
                    const type = document.getElementById('editType').value;
                    const id = document.getElementById('editId').value;
                    const name = document.getElementById('editName').value;
                    const desc = document.getElementById('editDesc').value;

                    formData.append('action', `edit-${type}`);
                    formData.append(`${type}_id`, id);
                    formData.append(`${type}_name`, name);
                    formData.append(`${type}_desc`, desc);

                    // Determine fetch URL dynamically
                    const fetchUrl = type === 'course' ? editCourse : editSubject;
                    showLoading(true);
                    fetch(fetchUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        if (data.success) {
                            editModal.hide(); // Hide modal properly
                            showToast('success', `You have successfully updated the ${type} information.`);
                            setTimeout(() => location.reload(), 1000); // Reload after 1s
                        } else {
                            showToast('error', data.message || `Error updating ${type}`);
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error('Error:', error);
                        showToast('error', `An error occurred while updating the ${type}`);
                    });
                });

                // Remove Course
                if (deleteCourseModalElement) {
                    window.deleteCourseModal = new bootstrap.Modal(deleteCourseModalElement);
                }
                document.getElementById("confirmDeleteCourse").addEventListener("click", function () {
                    if (!courseToDelete) {
                        showToast('error', "Error: No course selected for deletion.");
                        return;
                    }

                    const formData = new URLSearchParams();
                    formData.append("action", "delete-course");
                    formData.append("course_id", courseToDelete);
                    showLoading(true);
                    fetch(deleteCourse, {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: formData.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        deleteCourseModal.hide();
                        if (data.success) {
                            showToast('success', "Course deleted successfully!");
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast('error', data.message || "Error deleting course.");
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error("Error:", error);
                        showToast('error', "An error occurred while deleting the course.");
                    });
                });

                // Remove Subject
                if (deleteSubjectModalElement) {
                    window.deleteSubjectModal = new bootstrap.Modal(deleteSubjectModalElement);
                }
                document.getElementById("confirmDeleteSubject").addEventListener("click", function () {
                    if (!subjectToDelete) {
                        showToast('error', "Error: No subject selected for deletion.");
                        return;
                    }

                    const formData = new URLSearchParams();
                    formData.append("action", "delete-subject");
                    formData.append("subject_id", subjectToDelete);
                    showLoading(true);
                    fetch(deleteSubject, {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: formData.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        deleteSubjectModal.hide();
                        if (data.success) {
                            showToast('success', "Subject was deleted successfully!");
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast('error', data.message || "Error deleting subject.");
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error("Error:", error);
                        showToast('error', "An error occurred while deleting the subject.");
                    });
                });

                // Toggle Status Modal Handler
                let subjectToToggle = null;
                let currentStatus = null;
                const toggleStatusModal = new bootstrap.Modal(document.getElementById("toggleStatusModal"));
                function toggleSubjectStatus(subjectId, isActive) {
                    subjectToToggle = subjectId;
                    currentStatus = isActive;

                    const statusMessage = document.getElementById("statusChangeMessage");
                    statusMessage.textContent = `Are you sure you want to ${isActive ? "deactivate" : "activate"} this subject?`;
                    statusMessage.className = isActive ? "text-danger" : "text-success";

                    const confirmBtn = document.getElementById("confirmToggleStatus");
                    confirmBtn.className = `btn ${isActive ? "btn-danger" : "btn-success"}`;

                    toggleStatusModal.show();
                }

                document.getElementById("confirmToggleStatus").addEventListener("click", function () {
                    if (!subjectToToggle || currentStatus === null) return;

                    const formData = new FormData();
                    formData.append("action", "toggle-subject");
                    formData.append("subject_id", subjectToToggle);
                    formData.append("status", currentStatus === 1 ? 0 : 1); // Flip the status
                    showLoading(true);
                    fetch(subjectStatus, {
                        method: "POST",
                        body: formData,
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            showLoading(false);
                            toggleStatusModal.hide();
                            if (data.success) {
                                showToast("success", data.message);
                                setTimeout(() => location.reload(), 1000); // Refresh after success
                            } else {
                                showToast("error", data.message || "Error updating subject status");
                            }
                        })
                        .catch((error) => {
                            showLoading(false);
                            console.error("Error:", error);
                            showToast("error", "An error occurred while updating the subject status");
                            toggleStatusModal.hide();
                        });
                });
                window.toggleSubjectStatus = toggleSubjectStatus;

                // Filter subjects
                function filterSubjects() {
                    const courseName = document.getElementById("courseFilter").value.toLowerCase();
                    const status = document.getElementById("statusFilter").value;
                    const rows = document.querySelectorAll("tbody tr");

                    rows.forEach(row => {
                        const courseCell = row.querySelector("td:nth-child(2)").textContent.toLowerCase();
                        const statusCell = row.querySelector("td:nth-child(5)").textContent.trim() === "Active" ? "1" : "0";
                        const courseMatch = !courseName || courseCell.includes(courseName);
                        const statusMatch = !status || statusCell === status;
                        row.style.display = courseMatch && statusMatch ? "" : "none";
                    });
                }

                // Add event listeners for filters
                document.getElementById("courseFilter").addEventListener("change", filterSubjects);
                document.getElementById("statusFilter").addEventListener("change", filterSubjects);
                
                // TEST
                document.querySelectorAll(".updateSubjectCoverBtn").forEach(function (button) {
                    button.addEventListener("click", function () {
                        document.querySelector("#curImage").setAttribute("src", "<?php echo SUBJECT_IMG;?>default.jpg");
                        $('#updateSubjectCoverModal').modal("show");
                    });
                });

                // Update image preview when selecting a subject
                document.querySelector("#imageSubject").addEventListener("change", function () {
                    const selectedOption = this.selectedOptions[0];
                    const imageUrl = selectedOption.getAttribute("data-image");

                    if (imageUrl) {
                        document.querySelector("#curImage").setAttribute("src", imageUrl);
                        document.querySelector("#curImage").style.display = "block";
                    } else {
                        document.querySelector("#curImage").setAttribute("src", "<?php echo SUBJECT_IMG;?>default.jpg"); // Set to default if no image found
                    }
                });

                // Preview selected image file
                document.querySelector("#subjectImage").addEventListener("change", function () {
                    const file = this.files[0];

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            document.querySelector("#curImage").setAttribute("src", e.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Remove Image - Resets the file input and image preview
                document.querySelector("#removeImageBtn").addEventListener("click", function () {
                    document.querySelector("#subjectImage").value = ""; // Reset file input
                    document.querySelector("#curImage").setAttribute("src", "<?php echo SUBJECT_IMG.'default.jpg'; ?>"); // Reset preview image
                });

                // Form Submission
                document.querySelector("#updateSubjectCoverForm").addEventListener("submit", function (e) {
                    e.preventDefault();

                    const formData = new FormData(this); // This automatically includes the form data
                    formData.append("action", "update-subject-cover"); // Append the action

                    // If you want to override or ensure the subject ID is correctly appended (optional):
                    formData.append("subject_id", document.querySelector("#imageSubject").value); // Append subject ID     
                    showLoading(true);
                    fetch(updateCover, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        if(data.success) {
                            showToast("success", "Subject cover updated successfully!");
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        }
                        else {
                            console.log("I RUN?");
                            showToast("error", data.message || "Failed to update subject cover.");
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        showToast("error", "An error occurred while updating the subject cover.");
                    })
                    .finally(() => {
                    setTimeout(() => {
                        showLoading(false);
                    }, 900);
                });
                });





            });
            // Updated remove course function to use modal
            function removeCourse(courseId) {
                courseToDelete = courseId;
                if (window.deleteCourseModal) {
                    window.deleteCourseModal.show();
                } else {
                    console.error("Delete course modal not found.");
                }
            }
            // Updated remove course function to use modal
            function removeSubject(subjectId) {
                subjectToDelete = subjectId;
                if (window.deleteSubjectModal) {
                    window.deleteSubjectModal.show();
                } else {
                    console.error("Delete subject modal not found.");
                }
            }
            function previewImage(input, action) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    // Check file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB');
                        input.value = '';
                        return;
                    }
                    
                    // Check file type
                    if (!file.type.match('image.*')) {
                        alert('Please select an image file');
                        input.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if(action === 'add') {
                            document.querySelector('.add-subject-image').src = e.target.result;
                            document.getElementById('imagePreview').style.display = 'block';
                        }
                        else {
                            document.querySelector('.edit-subject-image').src = e.target.result;
                            document.getElementById('edit-imagePreview').style.display = 'block'; 
                        }
                    };
                    reader.readAsDataURL(file);
                }
            }
        </script>

    </body>
</html>