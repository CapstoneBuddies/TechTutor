<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php';
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }

    // Get class ID from URL
    $class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if($class_id == 0) {
        header("Location: ./");
        exit();
    }

    if ($class_id) {
        $class = getClassDetails($class_id);
        $students = getEnrolledStudents($class_id);
        log_error("TEsT: ".(!$class['status'] === 'completed') );
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <script> 
        showLoading(true);
        document.addEventListener('DOMContentLoaded', function() {
            showLoading(false);
        });
    </script>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <div class="row">
                <div class="col-12">
                    <!-- Header Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-1">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>admin">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="./">Classes</a></li>
                                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($class['class_name']); ?></li>
                                        </ol>
                                    </nav>
                                    <h2 class="page-header mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h2>
                                    <div class="d-flex align-items-center mt-2">
                                        <span class="badge bg-<?php echo $class['status'] == 'active' ? 'success' : 'warning'; ?> me-2">
                                            <?php echo ucfirst($class['status']); ?>
                                        </span>
                                        <span class="text-muted">Subject: <?php echo htmlspecialchars($class['subject_name']); ?></span>
                                    </div>
                                </div>
                                <div class="mt-2 mt-md-0">
                                    <a href="./" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Class Selection
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TechGuru Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-person-badge"></i> TechGuru</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo USER_IMG.(!empty($class['techguru_profile']) ? $class['techguru_profile'] : 'default.jpg'); ?>" 
                                     alt="TechGuru" 
                                     class="rounded-circle me-3" 
                                     width="64" 
                                     height="64"
                                     onerror="this.src='<?php echo USER_IMG; ?>default.jpg'; this.classList.add('img-error');">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($class['techguru_name']); ?></h5>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($class['techguru_email']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if(!($class['status'] === 'completed') ): ?>
                        <a href="details/edit?id=<?php echo $class_id; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Class
                        </a>
                        <?php endif; ?>
                        <a href="details/sessions?id=<?php echo $class_id; ?>" class="btn btn-success">
                            <i class="bi bi-calendar-event"></i> Sessions
                        </a>
                        <a href="details/recordings?id=<?php echo $class_id; ?>" class="btn btn-info text-white">
                            <i class="bi bi-camera-video"></i> Recordings
                        </a>
                        <a href="details/feedback?id=<?php echo $class_id; ?>" class="btn btn-warning text-dark">
                            <i class="bi bi-chat-dots"></i> Feedback
                        </a>
                        <?php if(!($class['status'] === 'completed') ): ?>
                        <a href="details/enroll?id=<?php echo $class_id; ?>" class="btn btn-secondary">
                            <i class="bi bi-people"></i> Enrollments
                        </a>
                        <?php endif; ?>
                        <a href="details/files?id=<?php echo $class_id; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-folder"></i> Files
                        </a>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column align-items-center text-center">
                                    <div class="stat-icon bg-primary text-white mb-2">
                                        <i class="bi bi-calendar-week"></i>
                                    </div>
                                    <h6 class="card-subtitle mb-1 text-muted">Total Sessions</h6>
                                    <h2 class="card-title mb-0"><?php echo count(getClassSchedules($class_id)) ; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column align-items-center text-center">
                                    <div class="stat-icon bg-info text-white mb-2">
                                        <i class="bi bi-camera-video"></i>
                                    </div>
                                    <h6 class="card-subtitle mb-1 text-muted">Recordings</h6>
                                    <h2 class="card-title mb-0"><?php echo getClassRecordingsCount($class_id); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 mb-sm-0">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column align-items-center text-center">
                                    <div class="stat-icon bg-warning text-white mb-2">
                                        <i class="bi bi-star"></i>
                                    </div>
                                    <h6 class="card-subtitle mb-1 text-muted">Average Rating</h6>
                                    <h2 class="card-title mb-0"><?php echo number_format(getClassRating($class_id), 1); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column align-items-center text-center">
                                    <div class="stat-icon bg-success text-white mb-2">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <h6 class="card-subtitle mb-1 text-muted">Enrolled Students</h6>
                                    <h2 class="card-title mb-0"><?php echo count($students); ?>/<?php echo $class['class_size']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Information -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0"><i class="bi bi-calendar"></i> Class Schedule</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted">Start Date</h6>
                                    <p class="mb-0"><?php echo date('F j, Y', strtotime($class['start_date'])); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted">End Date</h6>
                                    <p class="mb-0"><?php echo date('F j, Y', strtotime($class['end_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrolled Students -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0"><i class="bi bi-people-fill"></i> Enrolled Students</h3>
                            <?php if(!$class['status'] === 'completed'): ?>
                            <a href="details/enroll?id=<?php echo $class_id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Manage
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover student-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th class="d-none d-md-table-cell">Email</th>
                                            <th class="d-none d-lg-table-cell">Enrollment Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No students enrolled yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo isset($student['profile_picture']) ? USER_IMG.$student['profile_picture'] : USER_IMG.'default.jpg'; ?>" 
                                                                 alt="Student" 
                                                                 class="rounded-circle me-2" 
                                                                 width="32" 
                                                                 height="32"
                                                                 onerror="this.src='<?php echo USER_IMG; ?>default.jpg'; this.classList.add('img-error');">
                                                            <div>
                                                                <div class="fw-medium"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                                <div class="d-md-none small text-muted"><?php echo htmlspecialchars($student['email']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td class="d-none d-lg-table-cell"><?php echo date('M j, Y', strtotime($student['enrollment_date'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $student['enrollment_status'] == 'active' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($student['enrollment_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        
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
            
            .stat-icon {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
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
            
            .img-error {
                opacity: 0.7;
                background-color: #f8f9fa !important;
                border: 1px dashed #ccc !important;
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
                
                .d-flex.flex-wrap.gap-2 {
                    gap: 0.5rem !important;
                }
                
                .table-responsive {
                    margin: 0 -1rem;
                    padding: 0 1rem;
                    width: calc(100% + 2rem);
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
            }
        </style>
    </body>
</html>