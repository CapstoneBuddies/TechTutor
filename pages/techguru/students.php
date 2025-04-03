<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';

    // Check if user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    $tutor_id = $_SESSION['user'];
    $students_data = getStudentByTutor($tutor_id);
    $title = 'My Students';
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
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard/t/class">My Class</a></li>
                                    <li class="breadcrumb-item active">My Students</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">My Students</h2>
                            <p class="subtitle">View and manage all students enrolled in your classes</p>
                        </div>
                        <div>
                            <?php if (!empty($students_data['students'])): ?>
                            <div class="input-group search-bar">
                                <span class="input-group-text bg-light border-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light" id="studentSearch" 
                                    placeholder="Search students..." oninput="filterStudents()">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <?php if (empty($students_data['students'])): ?>
                        <div class="text-center py-5">
                            <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Students" class="mb-4" style="width: 200px;">
                            <h3>No Students Enrolled Yet</h3>
                            <p class="text-muted">You don't have any students enrolled in your classes yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5><i class="bi bi-people-fill me-2"></i>Total Students: <?php echo $students_data['count']; ?></h5>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="toggleView('grid')">
                                        <i class="bi bi-grid"></i> Grid
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" onclick="toggleView('list')">
                                        <i class="bi bi-list"></i> List
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Grid View (Default) -->
                        <div id="gridView" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($students_data['students'] as $student): ?>
                            <div class="col student-item-container">
                                <div class="student-card h-100">
                                    <div class="text-center pt-4">
                                        <img src="<?php echo empty($student['profile_picture']) ? IMG.'user-placeholder.jpg' : USER_IMG.$student['profile_picture']; ?>" 
                                             class="student-avatar" alt="<?php echo htmlspecialchars($student['student_first_name']); ?>">
                                        <h5 class="mt-3"><?php echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']); ?></h5>
                                        
                                        <?php if (!empty($student['student_rating'])): ?>
                                        <div class="student-rating">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= round($student['student_rating']) ? '-fill' : ''; ?> text-warning"></i>
                                            <?php endfor; ?>
                                            <span class="rating-value">(<?php echo number_format($student['student_rating'], 1); ?>)</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="student-info mt-3">
                                        <div class="info-item">
                                            <i class="bi bi-envelope text-muted"></i>
                                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-book text-muted"></i>
                                            <span><?php echo htmlspecialchars($student['class_name']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-calendar text-muted"></i>
                                            <span>Enrolled: <?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></span>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span>Progress</span>
                                                <span><?php echo $student['progress']; ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $student['progress']; ?>%" 
                                                     aria-valuenow="<?php echo $student['progress']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <span class="badge bg-primary">Next Session</span>
                                            <div class="next-session-info">
                                                <div><i class="bi bi-calendar-event me-1"></i> <?php echo $student['next_session_date']; ?></div>
                                                <div><i class="bi bi-clock me-1"></i> <?php echo $student['next_session_time']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="student-actions mt-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="sendMessage(<?php echo $student['student_id']; ?>, <?php echo $student['class_id']; ?>, '<?php echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']); ?>')">
                                            <i class="bi bi-chat-dots"></i> Message
                                        </button>
                                        <a href="<?php echo BASE; ?>dashboard/t/class/details?id=<?php echo $student['class_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i> View Class
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- List View (Hidden by default) -->
                        <div id="listView" class="table-responsive table-scroll" style="display: none;">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Class</th>
                                        <th>Enrolled On</th>
                                        <th>Progress</th>
                                        <th>Next Session</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_data['students'] as $student): ?>
                                    <tr class="student-item-container">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo empty($student['profile_picture']) ? IMG.'user-placeholder.jpg' : USER_IMG.$student['profile_picture']; ?>" 
                                                     class="rounded-circle me-2" width="40" height="40" alt="">
                                                <div>
                                                    <div><?php echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']); ?></div>
                                                    <?php if (!empty($student['student_rating'])): ?>
                                                    <div class="text-warning small">
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?php echo $i <= round($student['student_rating']) ? '-fill' : ''; ?> small"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 100px;" data-bs-toggle="tooltip" 
                                                 title="<?php echo $student['progress']; ?>% Complete">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $student['progress']; ?>%">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div><i class="bi bi-calendar-event me-1"></i> <?php echo $student['next_session_date']; ?></div>
                                                <div><i class="bi bi-clock me-1"></i> <?php echo $student['next_session_time']; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="sendMessage(<?php echo $student['student_id']; ?>, <?php echo $student['class_id']; ?>, '<?php echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']); ?>')">
                                                    <i class="bi bi-chat-dots"></i>
                                                </button>
                                                <a href="<?php echo BASE; ?>dashboard/t/class/details?id=<?php echo $student['class_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Send Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="messageForm">
                        <input type="hidden" id="studentId" name="student_id">
                        <input type="hidden" id="classId" name="class_id">
                        
                        <div class="mb-3">
                            <label for="messageSubject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="messageSubject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="messageContent" class="form-label">Message</label>
                            <textarea class="form-control" id="messageContent" name="message" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sendEmail" name="send_email">
                            <label class="form-check-label" for="sendEmail">
                                Also send as email
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> 

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <style>
        .student-card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
            padding: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            height: 100%;
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .student-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f8f9fa;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .student-info {
            padding: 0 1rem;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .info-item i {
            margin-right: 0.5rem;
            width: 20px;
        }
        
        .student-actions {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .next-session-info {
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            margin-top: 0.5rem;
        }
        
        .table-scroll {
            max-height: 600px;
            overflow-y: auto;
            border-radius: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }
        
        .table-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .table-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .table-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .table-scroll thead th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
            border-top: none;
        }
        
        .search-bar {
            width: 250px;
            border-radius: 20px;
            overflow: hidden;
        }
    </style>
    <script>
        // Toggle between grid and list view
        function toggleView(viewType) {
            if (viewType === 'grid') {
                document.getElementById('gridView').style.display = 'flex';
                document.getElementById('listView').style.display = 'none';
            } else {
                document.getElementById('gridView').style.display = 'none';
                document.getElementById('listView').style.display = 'block';
            }
        }
        
        // Filter students based on search input
        function filterStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const studentContainers = document.querySelectorAll('.student-item-container');
            
            studentContainers.forEach(container => {
                const studentInfo = container.textContent.toLowerCase();
                if (studentInfo.includes(searchTerm)) {
                    container.style.display = '';
                } else {
                    container.style.display = 'none';
                }
            });
        }
        
        // Open message modal
        function sendMessage(studentId, classId, studentName) {
            document.getElementById('studentId').value = studentId;
            document.getElementById('classId').value = classId;
            document.getElementById('messageModalLabel').innerText = `Send Message to ${studentName}`;
            
            const modal = new bootstrap.Modal(document.getElementById('messageModal'));
            modal.show();
        }
        
        // Handle message form submission
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                class_id: document.getElementById('classId').value,
                subject: document.getElementById('messageSubject').value,
                message: document.getElementById('messageContent').value,
                send_email: document.getElementById('sendEmail').checked,
                selected_students: [document.getElementById('studentId').value]
            };
            
            fetch('<?php echo BASE; ?>backends/api/send-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
                    alert('Message sent successfully!');
                    document.getElementById('messageForm').reset();
                } else {
                    alert('Error: ' + (data.error || 'Failed to send message'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>