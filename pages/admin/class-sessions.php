<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'meeting_management.php';

// Ensure user is logged in and is an ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Initialize meeting management
$meeting = new MeetingManagement();

// Get sessions for this class
$sessions = getClassSchedules($class_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_session') {
        $sessionDate = $_POST['session_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $sessionTitle = $_POST['session_title'] ?? '';
        
        // Validate inputs
        if (empty($sessionDate) || empty($startTime) || empty($endTime) || empty($sessionTitle)) {
            $_SESSION['error'] = "All fields are required";
        } else {
            // Create session
            $result = addClassSession([
                'class_id' => $class_id,
                'session_date' => $sessionDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'title' => $sessionTitle,
            ]);
            
            if ($result['success']) {
                $_SESSION['success'] = "Session added successfully";
                header("Location: class-sessions?id={$class_id}");
                exit();
            } else {
                $_SESSION['error'] = $result['error'];
            }
        }
    } elseif ($action === 'delete_session') {
        $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        
        if ($session_id) {
            $result = deleteClassSession($session_id);
            
            if ($result['success']) {
                $_SESSION['success'] = "Session deleted successfully";
            } else {
                $_SESSION['error'] = $result['error'];
            }
            
            header("Location: class-sessions?id={$class_id}");
            exit();
        }
    }
}

$title = "Class Sessions - " . htmlspecialchars($classDetails['class_name']);
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
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-1">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="../">Classes</a></li>
                                            <li class="breadcrumb-item"><a href="./?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                            <li class="breadcrumb-item active">Class Sessions</li>
                                        </ol>
                                    </nav>
                                    <h2 class="page-header mb-0">Class Sessions</h2>
                                    <p class="text-muted">Manage sessions for <?php echo htmlspecialchars($classDetails['class_name']); ?></p>
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

            <!-- Sessions List -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-calendar-event"></i> Scheduled Sessions
                                <span class="badge bg-primary ms-2"><?php echo count($sessions); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($sessions)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-calendar-x display-4"></i>
                                    <p class="mt-2">No sessions scheduled yet</p>
                                    <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                                        <i class="bi bi-plus-circle"></i> Schedule First Session
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Session</th>
                                                <th class="d-none d-lg-table-cell">Duration</th>
                                                <th>Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sessions as $session): ?>
                                                <?php 
                                                    $sessionDate = new DateTime($session['session_date']); 
                                                    $startTime = new DateTime($session['start_time']); 
                                                    $endTime = new DateTime($session['end_time']);
                                                    $now = new DateTime();
                                                    
                                                    $sessionDateTime = clone $sessionDate;
                                                    $sessionDateTime->setTime(
                                                        $startTime->format('H'), 
                                                        $startTime->format('i')
                                                    );
                                                    
                                                    $status = 'upcoming';
                                                    if ($sessionDateTime < $now) {
                                                        $status = 'completed';
                                                    } elseif ($sessionDateTime->format('Y-m-d') === $now->format('Y-m-d')) {
                                                        $status = 'today';
                                                    }
                                                    
                                                    // Calculate duration
                                                    $interval = $startTime->diff($endTime);
                                                    $duration = $interval->format('%h hrs %i mins');
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="session-date-indicator bg-<?php 
                                                                echo $status === 'completed' ? 'secondary' : 
                                                                    ($status === 'today' ? 'success' : 'primary'); 
                                                            ?> me-3"></div>
                                                            <div>
                                                                <div class="fw-medium"><?php echo $sessionDate->format('l, F j, Y'); ?></div>
                                                                <div class="text-muted small">
                                                                    <?php echo $startTime->format('g:i A'); ?> - <?php echo $endTime->format('g:i A'); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($session['class_name']); ?></div>
                                                    </td>
                                                    <td class="d-none d-lg-table-cell"><?php echo $duration; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $status === 'completed' ? 'secondary' : 
                                                                ($status === 'today' ? 'success' : 'primary'); 
                                                        ?>">
                                                            <?php echo ucfirst($status); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary view-session" 
                                                                    data-session-id="<?php echo $session['schedule_id']; ?>"
                                                                    data-session-title="<?php echo htmlspecialchars($session['class_name']); ?>"
                                                                    data-session-date="<?php echo $sessionDate->format('F j, Y'); ?>"
                                                                    data-start-time="<?php echo $startTime->format('g:i A'); ?>"
                                                                    data-end-time="<?php echo $endTime->format('g:i A'); ?>">
                                                                <i class="bi bi-eye"></i>
                                                                <span class="d-none d-md-inline"> View</span>
                                                            </button>
                                                            <form method="POST" class="d-inline" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this session?');">
                                                                <input type="hidden" name="action" value="delete_session">
                                                                <input type="hidden" name="session_id" value="<?php echo $session['schedule_id']; ?>">
                                                            </form>
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
            </div>
        </main>

        <!-- Add Session Modal -->
        <div class="modal fade" id="addSessionModal" tabindex="-1" aria-labelledby="addSessionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSessionModalLabel">Add New Session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_session">
                            
                            <div class="mb-3">
                                <label for="session_title" class="form-label">Session Title</label>
                                <input type="text" class="form-control" id="session_title" name="session_title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="session_date" class="form-label">Session Date</label>
                                <input type="date" class="form-control" id="session_date" name="session_date" required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                                <div class="col-6">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Session</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Session Modal -->
        <div class="modal fade" id="viewSessionModal" tabindex="-1" aria-labelledby="viewSessionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewSessionModalLabel">Session Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h4 id="viewSessionTitle"></h4>
                            <div class="text-muted" id="viewSessionDateTime"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // View Session Modal
                const viewButtons = document.querySelectorAll('.view-session');
                const viewModal = new bootstrap.Modal(document.getElementById('viewSessionModal'));
                
                viewButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        document.getElementById('viewSessionTitle').textContent = this.dataset.sessionTitle;
                        document.getElementById('viewSessionDateTime').textContent = 
                            `${this.dataset.sessionDate} • ${this.dataset.startTime} - ${this.dataset.endTime}`;
                        
                        viewModal.show();
                    });
                });
                
                // Edit Session Modal (placeholder for future implementation)
                const editButtons = document.querySelectorAll('.edit-session');
                
                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        alert('Edit functionality to be implemented soon!');
                    });
                });
                
                // Validate session times
                const sessionDateInput = document.getElementById('session_date');
                const startTimeInput = document.getElementById('start_time');
                const endTimeInput = document.getElementById('end_time');
                
                function validateTimes() {
                    if (startTimeInput.value && endTimeInput.value) {
                        if (endTimeInput.value <= startTimeInput.value) {
                            endTimeInput.setCustomValidity('End time must be after start time');
                        } else {
                            endTimeInput.setCustomValidity('');
                        }
                    }
                }
                
                startTimeInput.addEventListener('input', validateTimes);
                endTimeInput.addEventListener('input', validateTimes);
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
            
            .session-date-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                flex-shrink: 0;
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
                
                .btn-group {
                    flex-wrap: nowrap;
                }
                
                .btn-group .btn {
                    padding: 0.375rem 0.5rem;
                    font-size: 0.875rem;
                }
            }
            
            @media (max-width: 767.98px) {
                .page-header {
                    font-size: 1.5rem;
                }
                
                .d-flex {
                    flex-wrap: wrap;
                }
                
                .table-responsive {
                    margin: 0 -1rem;
                    padding: 0 1rem;
                    width: calc(100% + 2rem);
                }
                
                .btn-group {
                    gap: 0.25rem;
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
                    gap: 1rem;
                }
                
                .d-flex.justify-content-between .btn {
                    width: 100%;
                }
            }
        </style>
    </body>
</html> 