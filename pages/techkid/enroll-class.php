<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'student_management.php';

// Ensure user is logged in and is a TechKid
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if already enrolled
$enrollment = checkStudentEnrollment($_SESSION['user'], $class_id);
if ($enrollment) {
    header('Location: ' . BASE . 'dashboard/s/class/details?id=' . $enrollment['class_id']);
    exit();
}

// Fetch class details
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Fetch available schedules for enrollment
$schedules = getClassSchedules($class_id);
$title = htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <div class="dashboard-content bg">
                <!-- Header Section -->
                <div class="content-section mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <a href="./" class="btn btn-outline-primary btn-sm mb-2">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <h1 class="page-title mb-1"><?php echo htmlspecialchars($classDetails['class_name']); ?></h1>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="text-muted small">
                                            <strong>Subject:</strong> <?php echo htmlspecialchars($classDetails['subject_name']); ?>
                                        </span>
                                        <span class="text-muted">•</span>
                                        <span class="text-muted small">
                                            <strong>Course:</strong> <?php echo htmlspecialchars($classDetails['course_name']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-start align-items-md-end gap-2">
                                    <?php if ($classDetails['is_free']): ?>
                                        <span class="badge bg-success">Free Class</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">₱<?php echo number_format($classDetails['price'], 2); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Check if this class has an invitation (pending enrollment) for this student
                                    $invitation = checkPendingInvitation($_SESSION['user'], $class_id);
                                    if ($invitation): 
                                    ?>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success" onclick="updateEnrollment(<?php echo $class_id; ?>, 'accept')">
                                                <i class="bi bi-check-circle"></i> Accept Invitation
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="updateEnrollment(<?php echo $class_id; ?>, 'decline')">
                                                <i class="bi bi-x-circle"></i> Decline
                                            </button>
                                        </div>
                                        <p class="text-muted small mt-1">Invited by <?php echo htmlspecialchars($invitation['tutor_name']); ?> on <?php echo date('M d, Y', strtotime($invitation['enrollment_date'])); ?></p>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary" onclick="enrollInClass()">
                                            <i class="bi bi-check-circle"></i> Enroll in Class
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row g-4">
                    <!-- Left Column - Main Content -->
                    <div class="col-12 col-lg-8">
                        <!-- Class Information -->
                        <div class="enrollment-info mb-4">
                            <h2 class="section-title mb-4">Class Information</h2>
                            <div class="class-info">
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>Start Date</strong></p>
                                        <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($classDetails['start_date'])); ?></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>End Date</strong></p>
                                        <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($classDetails['end_date'])); ?></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>Class Size</strong></p>
                                        <p class="text-muted mb-0"><?php echo $classDetails['total_students']; ?>/<?php echo $classDetails['class_size'] ? $classDetails['class_size'] : 'Unlimited'; ?></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="small mb-1"><strong>Status</strong></p>
                                        <span class="badge bg-<?php echo $classDetails['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($classDetails['status']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Schedule -->
                        <div class="enrollment-info">
                            <h2 class="section-title mb-4">Class Schedule</h2>
                            <div class="table-responsive">
                                <table class="schedule-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo date('F d, Y', strtotime($schedule['session_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php if ($invitation): ?>
                        <!-- Invitation Information -->
                        <div class="enrollment-info mt-4">
                            <h2 class="section-title mb-4">Class Invitation</h2>
                            <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle me-2"></i>You've Been Invited!</h5>
                                <p class="mb-0">You have received an invitation from <?php echo htmlspecialchars($invitation['tutor_name']); ?> to join this class. 
                                You can accept this invitation to enroll in the class or decline it if you're not interested.</p>
                            </div>
                            
                            <?php 
                            // Fetch invitation message if it exists
                            $stmt = $conn->prepare("SELECT message FROM enrollments WHERE enrollment_id = ?");
                            $stmt->bind_param("i", $invitation['enrollment_id']);
                            $stmt->execute();
                            $msg_result = $stmt->get_result();
                            $invitation_message = '';
                            if ($msg_result && $msg_result->num_rows > 0) {
                                $msg_row = $msg_result->fetch_assoc();
                                $invitation_message = $msg_row['message'];
                            }
                            
                            if (!empty($invitation_message)): 
                            ?>
                            <div class="card border-0 bg-light p-3 mt-3">
                                <p class="fw-bold mb-1">Message from instructor:</p>
                                <p class="fst-italic mb-0">"<?php echo htmlspecialchars($invitation_message); ?>"</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column - Tutor Info -->
                    <div class="col-12 col-lg-4">
                        <!-- Tutor Details -->
                        <div class="enrollment-info mb-4">
                            <h2 class="section-title mb-4">Instructor</h2>
                            <div class="tutor-profile">
                                <img src="<?php echo !empty($classDetails['tutor_avatar']) ? USER_IMG . $classDetails['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($classDetails['techguru_name']); ?>">
                                <h4><?php echo htmlspecialchars($classDetails['techguru_name']); ?></h4>
                                
                                <!-- Tutor Stats -->
                                <div class="tutor-stats">
                                    <div class="stat-card">
                                        <div class="value"><?php echo number_format($classDetails['average_rating'] ?? 0, 1); ?></div>
                                        <div class="label">Rating</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="value"><?php echo number_format($classDetails['completion_rate'] ?? 0); ?>%</div>
                                        <div class="label">Completion</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Stats -->
                        <div class="enrollment-info">
                            <h2 class="section-title mb-4">Class Stats</h2>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="small text-muted mb-1">Total Students</p>
                                    <h4 class="mb-0"><?php echo $classDetails['total_students']; ?></h4>
                                </div>
                                <div class="h3 text-muted">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </main>
</div>
        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            async function enrollInClass() {
                if (!confirm("Are you sure you want to enroll in this class? You will be enrolled in all sessions.")) {
                    return;
                }
                
                showLoading(true);
                
                try {
                    const response = await fetch(`${BASE}api/enroll-class`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            class_id: <?php echo $class_id; ?>
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('success', "Successfully enrolled in the class!");
                        setTimeout(() => location.href = `${BASE}dashboard/s/class/details?id=${<?php echo $class_id; ?>}`, 1500);
                    } else {
                        if (data.already_enrolled) {
                            location.href = `${BASE}dashboard/s/class/details?id=${<?php echo $class_id; ?>}`;
                        } else {
                            showToast('error', data.message || 'Failed to enroll in the class.');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', 'An unexpected error occurred.');
                } finally {
                    showLoading(false);
                }
            }
            
            async function updateEnrollment(classId, action) {
                if (!confirm(`Are you sure you want to ${action} this class invitation?`)) {
                    return;
                }
                
                showLoading(true);
                
                try {
                    const response = await fetch(`${BASE}api/update-enrollment-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            class_id: classId,
                            action: action
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('success', data.message);
                        if (action === 'accept') {
                            setTimeout(() => location.href = `${BASE}dashboard/s/class/details?id=${classId}`, 1500);
                        } else {
                            setTimeout(() => location.href = `${BASE}dashboard/s/class`, 1500);
                        }
                    } else {
                        showToast('error', data.message || 'An error occurred');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', 'Failed to update enrollment status');
                } finally {
                    showLoading(false);
                }
            }
            
            function showLoading(show) {
                // Create or find loading overlay
                let loadingOverlay = document.getElementById('loading-overlay');
                if (!loadingOverlay && show) {
                    loadingOverlay = document.createElement('div');
                    loadingOverlay.id = 'loading-overlay';
                    loadingOverlay.innerHTML = `
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `;
                    loadingOverlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; display: flex; justify-content: center; align-items: center;';
                    document.body.appendChild(loadingOverlay);
                } else if (loadingOverlay && !show) {
                    loadingOverlay.remove();
                }
            }
            
            function showToast(type, message) {
                // Toast notification implementation
                const toastContainer = document.getElementById('toast-container') || document.createElement('div');
                if (!document.getElementById('toast-container')) {
                    toastContainer.id = 'toast-container';
                    toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                    document.body.appendChild(toastContainer);
                }
                
                const toastId = 'toast-' + Date.now();
                const toastHTML = `
                    <div id="${toastId}" class="toast align-items-center border-0 border-start border-4 border-${type === 'success' ? 'success' : 'danger'}" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-${type === 'success' ? 'check-circle' : 'x-circle'} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                toastContainer.insertAdjacentHTML('beforeend', toastHTML);
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
                toast.show();
                
                toastElement.addEventListener('hidden.bs.toast', () => {
                    toastElement.remove();
                });
            }
        </script>
        
        <style>
            /* Existing styles */
            .table-responsive {
                max-height: 100vh;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .enrollment-info {
                background: #fff;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                margin-bottom: 1.5rem;
            }
            
            .section-title {
                font-size: 1.3rem;
                font-weight: 600;
                color: #333;
                margin-bottom: 1rem;
            }
            
            .tutor-profile {
                text-align: center;
                padding: 1rem;
            }
            
            .tutor-profile img {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                object-fit: cover;
                margin: 0 auto 1rem;
                border: 4px solid #f8f9fa;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .tutor-stats {
                display: flex;
                justify-content: center;
                margin-top: 1rem;
                gap: 1rem;
            }
            
            .stat-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 0.75rem 1.25rem;
                text-align: center;
                flex: 1;
            }
            
            .stat-card .value {
                font-size: 1.5rem;
                font-weight: bold;
                color: var(--bs-primary);
            }
            
            .stat-card .label {
                font-size: 0.8rem;
                color: #6c757d;
            }
            
            .schedule-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            
            .schedule-table th, .schedule-table td {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #e9ecef;
            }
            
            .schedule-table th {
                font-weight: 600;
                background-color: #f8f9fa;
                text-align: left;
            }
        </style>
    </body>
</html>
