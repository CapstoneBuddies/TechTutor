<?php 
    require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'rating_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tutor_id = $_SESSION['user'];
$view_archived = isset($_GET['archived']) && $_GET['archived'] == 'true';

// Initialize variables
$classDetails = null;
$classFeedbacks = [];
$allFeedbacks = [];
$archivedFeedbacks = [];
$averageRating = 0;
$totalFeedbacks = 0;
$ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

// Initialize Rating Management
$ratingManager = new RatingManagement();

// If class ID is provided, get specific class details and feedbacks
if ($class_id > 0) {
    // Get class details
    $classDetails = getClassDetails($class_id, $tutor_id);
    
    if (!$classDetails) {
        $_SESSION['error'] = 'Class not found or you do not have permission to access it.';
        header('Location: ./');
        exit();
    }
    
    // Get all feedbacks for this specific class
    $classResult = $ratingManager->getClassFeedbacks($class_id, $tutor_id, $view_archived);
    $classFeedbacks = $classResult['feedbacks'];
    $averageRating = $classResult['averageRating'];
    $totalFeedbacks = $classResult['totalFeedbacks'];
    $ratingDistribution = $classResult['ratingDistribution'];
}

// Get feedbacks based on the view mode
if ($view_archived) {
    $allFeedbacks = $ratingManager->getArchivedFeedbacks($tutor_id);
} else {
    $allFeedbacks = $ratingManager->getAllTutorFeedbacks($tutor_id);
}

// Count archived feedbacks for the badge
$archivedCount = count($ratingManager->getArchivedFeedbacks($tutor_id, 1000));

// Set page title
$title = $classDetails ? 'Feedbacks - ' . htmlspecialchars($classDetails['class_name']) : 'All Feedbacks';
$title = $view_archived ? 'Archived ' . $title : $title;
?>

<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .rating-overview {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
        }
        .rating-stars {
            font-size: 2.5rem;
            color: #ffc107;
        }
        .rating-number {
            font-size: 3rem;
            font-weight: bold;
        }
        .rating-distribution {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .rating-label {
            width: 30px;
            text-align: right;
            margin-right: 10px;
        }
        .rating-progress {
            flex-grow: 1;
            height: 8px;
        }
        .rating-count {
            width: 40px;
            text-align: left;
            margin-left: 10px;
            font-size: 0.8rem;
        }
        .feedback-card {
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }
        .feedback-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .feedback-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            background-color: #f8f9fa;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .feedback-body {
            padding: 1rem;
        }
        .feedback-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .feedback-student-info {
            display: flex;
            align-items: center;
        }
        .feedback-student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .feedback-modal-body {
            white-space: pre-line;
        }
        .feedback-actions {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .nav-pills .nav-link {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            background-color: var(--bs-primary);
        }
        @media (max-width: 768px) {
            .rating-stars {
                font-size: 2rem;
            }
            .rating-number {
                font-size: 2.5rem;
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <!-- Header Section -->
            <div class="dashboard-card mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb" class="breadcrumb-nav">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                <?php if ($classDetails): ?>
                                <li class="breadcrumb-item">
                                    <a href="class-details?id=<?php echo $class_id; ?>">
                                        <?php echo htmlspecialchars($classDetails['class_name']); ?>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active"><?php echo $view_archived ? 'Archived Feedbacks' : 'Feedbacks'; ?></li>
                                <?php else: ?>
                                <li class="breadcrumb-item active"><?php echo $view_archived ? 'Archived Feedbacks' : 'All Feedbacks'; ?></li>
                                <?php endif; ?>
                            </ol>
                        </nav>
                        <h2 class="page-header mb-0"><?php echo $title; ?></h2>
                        <p class="text-muted">Review student feedback and ratings</p>
                    </div>

                    <!-- Feedback View Tabs -->
                    <div>
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo !$view_archived ? 'active' : ''; ?>" 
                                   href="<?php echo $classDetails ? "details/feedbacks?id={$class_id}" : "feedbacks"; ?>">
                                    <i class="bi bi-chat-text me-1"></i> Active
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $view_archived ? 'active' : ''; ?>" 
                                   href="<?php echo $classDetails ? "details/feedbacks?id={$class_id}&archived=true" : "feedbacks?archived=true"; ?>">
                                    <i class="bi bi-archive me-1"></i> Archived
                                    <?php if (!$view_archived && $archivedCount > 0): ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $archivedCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Feedback Content -->
            <div class="row">
                <?php if ($classDetails && !$view_archived): ?>
                <!-- Class-specific Feedback Section -->
                <div class="col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <h3 class="mb-4">Rating Overview</h3>
                        <div class="rating-overview text-center">
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo ($i <= round($averageRating)) ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-number my-2">
                                <?php echo number_format($averageRating, 1); ?>
                            </div>
                            <div class="text-muted mb-4">
                                Based on <?php echo $totalFeedbacks; ?> feedbacks
                            </div>

                            <div class="rating-distributions">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <div class="rating-distribution">
                                        <div class="rating-label"><?php echo $i; ?> <i class="bi bi-star-fill" style="font-size: 0.7rem;"></i></div>
                                        <div class="progress rating-progress">
                                            <div class="progress-bar bg-warning" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $totalFeedbacks > 0 ? ($ratingDistribution[$i] / $totalFeedbacks * 100) : 0; ?>%">
                                            </div>
                                        </div>
                                        <div class="rating-count"><?php echo $ratingDistribution[$i]; ?></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?php echo ($classDetails && !$view_archived) ? 'col-lg-8' : 'col-12'; ?>">
                    <div class="dashboard-card">
                        <h3 class="mb-4">
                            <?php echo $classDetails ? 'Class Feedbacks' : 'All Feedbacks'; ?>
                            <?php if ($view_archived): ?>
                            <span class="badge bg-secondary">Archived</span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if (empty($classFeedbacks) && $classDetails): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-square-text display-1 text-muted"></i>
                                <h4 class="mt-3">No Feedbacks Yet</h4>
                                <p class="text-muted">
                                    <?php echo $view_archived ? 'No archived feedbacks for this class' : 'Feedback will appear here after students submit ratings for your sessions'; ?>
                                </p>
                            </div>
                        <?php elseif ($classDetails): ?>
                            <?php foreach ($classFeedbacks as $feedback): ?>
                                <div class="feedback-card position-relative">
                                    <div class="feedback-actions">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary archive-toggle" 
                                                data-feedback-id="<?php echo $feedback['rating_id']; ?>"
                                                data-archive="<?php echo $view_archived ? 'false' : 'true'; ?>"
                                                title="<?php echo $view_archived ? 'Unarchive' : 'Archive'; ?> feedback">
                                            <i class="bi bi-<?php echo $view_archived ? 'arrow-counterclockwise' : 'archive'; ?>"></i>
                                        </button>
                                    </div>
                                    <div class="feedback-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="feedback-student-info">
                                                <img src="<?php echo IMG . 'users/' . $feedback['profile_picture']; ?>" 
                                                     class="feedback-student-avatar" 
                                                     alt="<?php echo htmlspecialchars($feedback['student_name']); ?>">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($feedback['student_name']); ?></h6>
                                                    <div class="feedback-meta">
                                                        <span>
                                                            <?php echo date('F j, Y', strtotime($feedback['session_date'])); ?> 
                                                            (<?php echo date('g:i A', strtotime($feedback['start_time'])); ?> - 
                                                             <?php echo date('g:i A', strtotime($feedback['end_time'])); ?>)
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo ($i <= $feedback['rating']) ? '-fill' : ''; ?> text-warning"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="feedback-body">
                                        <?php if (!empty($feedback['feedback'])): ?>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0"><em>No written feedback provided</em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- All Feedbacks Section -->
                <?php if (!$classDetails || $view_archived): ?>
                <div class="col-12 <?php echo $classDetails ? 'mt-4' : ''; ?>">
                    <div class="dashboard-card">
                        <h3 class="mb-4">
                            <?php echo $view_archived ? 'Archived Feedbacks' : 'All Feedbacks'; ?>
                        </h3>
                        
                        <?php if (empty($allFeedbacks)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-square-text display-1 text-muted"></i>
                                <h4 class="mt-3">No Feedbacks</h4>
                                <p class="text-muted">
                                    <?php echo $view_archived ? 'You have not archived any feedback yet' : 'You haven\'t received any feedback yet'; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Session Date</th>
                                            <th>Rating</th>
                                            <th>Feedback</th>
                                            <th>Date Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allFeedbacks as $index => $feedback): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo IMG . 'users/' . $feedback['profile_picture']; ?>" 
                                                             class="rounded-circle me-2" 
                                                             width="30" 
                                                             height="30"
                                                             alt="<?php echo htmlspecialchars($feedback['student_name']); ?>">
                                                        <div><?php echo htmlspecialchars($feedback['student_name']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="class-details?id=<?php echo $feedback['class_id']; ?>">
                                                        <?php echo htmlspecialchars($feedback['class_name']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($feedback['session_date'])); ?>
                                                    <small class="d-block text-muted">
                                                        <?php echo date('g:i A', strtotime($feedback['start_time'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?php echo ($i <= $feedback['rating']) ? '-fill' : ''; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($feedback['feedback'])): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-secondary view-feedback" 
                                                                data-feedback-id="<?php echo $index; ?>"
                                                                data-student-name="<?php echo htmlspecialchars($feedback['student_name']); ?>"
                                                                data-session-date="<?php echo date('F j, Y', strtotime($feedback['session_date'])); ?>"
                                                                data-session-time="<?php echo date('g:i A', strtotime($feedback['start_time'])); ?> - <?php echo date('g:i A', strtotime($feedback['end_time'])); ?>"
                                                                data-rating="<?php echo $feedback['rating']; ?>"
                                                                data-feedback="<?php echo htmlspecialchars($feedback['feedback']); ?>"
                                                                data-class-name="<?php echo htmlspecialchars($feedback['class_name']); ?>">
                                                            View
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                                                    <small class="d-block text-muted">
                                                        <?php echo date('g:i A', strtotime($feedback['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-secondary archive-toggle" 
                                                            data-feedback-id="<?php echo $feedback['rating_id']; ?>"
                                                            data-archive="<?php echo $view_archived ? 'false' : 'true'; ?>"
                                                            title="<?php echo $view_archived ? 'Unarchive' : 'Archive'; ?> feedback">
                                                        <i class="bi bi-<?php echo $view_archived ? 'arrow-counterclockwise' : 'archive'; ?>"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
    </main>

        <!-- Feedback Detail Modal -->
        <div class="modal fade" id="feedbackDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Student Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 class="text-primary" id="modal-student-name"></h6>
                            <div class="small text-muted" id="modal-class-name"></div>
                            <div class="small text-muted" id="modal-session-datetime"></div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="text-warning" id="modal-rating"></div>
                        </div>
                        
                        <h6>Feedback:</h6>
                        <div class="feedback-modal-body" id="modal-feedback-content"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div class="modal fade" id="archiveConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="archive-modal-title">Archive Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="archive-modal-content">
                        Are you sure you want to archive this feedback?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirm-archive-btn">Confirm</button>
                    </div>
                </div>
            </div>
    </div> 

    <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize modals
                const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackDetailModal'));
                const archiveModal = new bootstrap.Modal(document.getElementById('archiveConfirmModal'));
                
                // Add click event listeners to View buttons
                const viewButtons = document.querySelectorAll('.view-feedback');
                viewButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Get feedback details from data attributes
                        const studentName = this.getAttribute('data-student-name');
                        const sessionDate = this.getAttribute('data-session-date');
                        const sessionTime = this.getAttribute('data-session-time');
                        const rating = this.getAttribute('data-rating');
                        const feedback = this.getAttribute('data-feedback');
                        const className = this.getAttribute('data-class-name');
                        
                        // Set modal content
                        document.getElementById('modal-student-name').textContent = studentName;
                        document.getElementById('modal-class-name').textContent = className;
                        document.getElementById('modal-session-datetime').textContent = `${sessionDate} (${sessionTime})`;
                        
                        // Set star rating
                        let starsHtml = '';
                        for (let i = 1; i <= 5; i++) {
                            starsHtml += `<i class="bi bi-star${i <= rating ? '-fill' : ''}"></i>`;
                        }
                        document.getElementById('modal-rating').innerHTML = starsHtml;
                        
                        // Set feedback content
                        document.getElementById('modal-feedback-content').textContent = feedback;
                        
                        // Show modal
                        feedbackModal.show();
                    });
                });

                // Add click event listeners to Archive buttons
                let currentFeedbackId = null;
                let currentArchiveAction = null;
                
                const archiveButtons = document.querySelectorAll('.archive-toggle');
                archiveButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Get feedback ID and archive action
                        currentFeedbackId = this.getAttribute('data-feedback-id');
                        currentArchiveAction = this.getAttribute('data-archive') === 'true';
                        
                        // Set modal content
                        const modalTitle = document.getElementById('archive-modal-title');
                        const modalContent = document.getElementById('archive-modal-content');
                        const confirmBtn = document.getElementById('confirm-archive-btn');
                        
                        if (currentArchiveAction) {
                            modalTitle.textContent = 'Archive Feedback';
                            modalContent.textContent = 'Are you sure you want to archive this feedback? Archived feedbacks will be moved to the archive section.';
                            confirmBtn.textContent = 'Archive';
                            confirmBtn.classList.remove('btn-success');
                            confirmBtn.classList.add('btn-primary');
                        } else {
                            modalTitle.textContent = 'Unarchive Feedback';
                            modalContent.textContent = 'Are you sure you want to unarchive this feedback? It will be moved back to the active feedbacks.';
                            confirmBtn.textContent = 'Unarchive';
                            confirmBtn.classList.remove('btn-primary');
                            confirmBtn.classList.add('btn-success');
                        }
                        
                        // Show confirmation modal
                        archiveModal.show();
                    });
                });
                
                // Handle archive confirmation
                document.getElementById('confirm-archive-btn').addEventListener('click', function() {
                    // Hide confirmation modal
                    archiveModal.hide();
                    
                    // Show loading indicator
                    showLoading(true);
                    
                    // Send archive/unarchive request
                    fetch(`${BASE}backends/handler/rating_handlers.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'toggle_archive',
                            feedback_id: currentFeedbackId,
                            archive: currentArchiveAction
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        if (data.success) {
                            // Reload page to reflect changes
                            location.reload();
                        } else {
                            showToast('error', 'Failed to update feedback: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error('Error:', error);
                        showToast('error', 'An error occurred while updating the feedback');
                    });
                });
            });
        </script>
</body>
</html>