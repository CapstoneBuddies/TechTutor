<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'rating_management.php';

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

// Initialize rating management
$ratingManager = new RatingManagement();

// Get all feedback for this class
$activeFeedback = $ratingManager->getClassFeedbacks($class_id); 
$archivedFeedback = $ratingManager->getArchivedClassFeedbacks($class_id);

$title = "Class Feedback - " . htmlspecialchars($classDetails['class_name']);
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
                                            <li class="breadcrumb-item active">Feedback</li>
                                        </ol>
                                    </nav>
                                    <h2 class="page-header mb-0">Class Feedback</h2>
                                    <p class="text-muted">Manage student feedback for <?php echo htmlspecialchars($classDetails['class_name']); ?></p>
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

            <!-- Active Feedback -->
            <div class="row mt-4" id="active-feedback-container">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-chat-dots"></i> Active Feedback
                                <span class="badge bg-primary ms-2" id="active-count"><?php echo count($activeFeedback); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activeFeedback)): ?>
                                <div class="text-center text-muted py-4" id="no-active-feedback">
                                    <i class="bi bi-chat-dots display-4"></i>
                                    <p class="mt-2">No active feedback available</p>
                                </div>
                            <?php else: ?>
                                <div class="feedback-list" id="active-feedback-list">
                                    <?php foreach ($activeFeedback as $feedback): ?>
                                        <div class="feedback-item border-bottom p-3" data-feedback-id="<?php echo $feedback['rating_id']; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo USER_IMG . $feedback['student_picture']; ?>" 
                                                         class="rounded-circle me-2" 
                                                         width="40" 
                                                         height="40"
                                                         onerror="this.onerror=null; this.classList.add('img-error'); this.src='<?php echo USER_IMG; ?>default.jpg';"
                                                         alt="Student">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($feedback['student_name']); ?></h6>
                                                        <div class="text-muted small">
                                                            Session on <?php echo date('F d, Y', strtotime($feedback['session_date'])); ?> at <?php echo $feedback['session_time']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="rating-display">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?php echo $i <= $feedback['rating'] ? '-fill' : ''; ?> text-warning"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="feedback-content mt-3">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></p>
                                            </div>
                                            <div class="feedback-actions mt-3 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary archive-feedback-btn"
                                                      data-feedback-id="<?php echo $feedback['rating_id']; ?>">
                                                    <i class="bi bi-archive"></i> Archive
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-feedback-btn"
                                                      data-feedback-id="<?php echo $feedback['rating_id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archived Feedback -->
            <div class="row mt-4" id="archived-feedback-container">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-archive"></i> Archived Feedback
                                <span class="badge bg-secondary ms-2" id="archived-count"><?php echo count($archivedFeedback); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($archivedFeedback)): ?>
                                <div class="text-center text-muted py-4" id="no-archived-feedback">
                                    <i class="bi bi-archive display-4"></i>
                                    <p class="mt-2">No archived feedback</p>
                                </div>
                            <?php else: ?>
                                <div class="feedback-list" id="archived-feedback-list">
                                    <?php foreach ($archivedFeedback as $feedback): ?>
                                        <div class="feedback-item border-bottom p-3" data-feedback-id="<?php echo $feedback['rating_id']; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo USER_IMG . $feedback['student_picture']; ?>" 
                                                         class="rounded-circle me-2" 
                                                         width="40" 
                                                         height="40"
                                                         onerror="this.onerror=null; this.classList.add('img-error'); this.src='<?php echo USER_IMG; ?>default.jpg';"
                                                         alt="Student">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($feedback['student_name']); ?></h6>
                                                        <div class="text-muted small">
                                                            Session on <?php echo date('F d, Y', strtotime($feedback['session_date'])); ?> at <?php echo $feedback['session_time']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="rating-display">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?php echo $i <= $feedback['rating'] ? '-fill' : ''; ?> text-warning"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="feedback-content mt-3">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></p>
                                            </div>
                                            <div class="feedback-actions mt-3 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary unarchive-feedback-btn"
                                                      data-feedback-id="<?php echo $feedback['rating_id']; ?>">
                                                    <i class="bi bi-archive"></i> Unarchive
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-feedback-btn"
                                                      data-feedback-id="<?php echo $feedback['rating_id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
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
                // Initialize class ID
                const classId = <?php echo $class_id; ?>;
                
                // Handle Archive Feedback
                document.querySelectorAll('.archive-feedback-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const feedbackId = this.getAttribute('data-feedback-id');
                        const feedbackItem = this.closest('.feedback-item');
                        
                        ClassManager.archiveFeedback(feedbackId, classId, function(response) {
                            // Move the feedback to archived section
                            const archivedList = document.getElementById('archived-feedback-list');
                            const noArchivedFeedback = document.getElementById('no-archived-feedback');
                            
                            if (noArchivedFeedback) {
                                noArchivedFeedback.remove();
                                
                                // Create list if it doesn't exist
                                if (!archivedList) {
                                    document.querySelector('#archived-feedback-container .card-body').innerHTML = '<div class="feedback-list" id="archived-feedback-list"></div>';
                                }
                            }
                            
                            // Clone the feedback item for the archived section
                            const archivedItem = feedbackItem.cloneNode(true);
                            
                            // Update action buttons for archived state
                            const archiveBtn = archivedItem.querySelector('.archive-feedback-btn');
                            archiveBtn.classList.remove('archive-feedback-btn');
                            archiveBtn.classList.add('unarchive-feedback-btn');
                            archiveBtn.innerHTML = '<i class="bi bi-archive"></i> Unarchive';
                            
                            // Add event listeners to the new buttons
                            archivedItem.querySelector('.unarchive-feedback-btn').addEventListener('click', handleUnarchive);
                            archivedItem.querySelector('.delete-feedback-btn').addEventListener('click', handleDelete);
                            
                            // Add to archived list
                            document.getElementById('archived-feedback-list').appendChild(archivedItem);
                            
                            // Remove from active list
                            feedbackItem.remove();
                            
                            // Update counts
                            updateFeedbackCounts();
                            
                            // Check if active list is now empty
                            const activeList = document.getElementById('active-feedback-list');
                            if (activeList && activeList.children.length === 0) {
                                const noActiveHtml = `
                                    <div class="text-center text-muted py-4" id="no-active-feedback">
                                        <i class="bi bi-chat-dots display-4"></i>
                                        <p class="mt-2">No active feedback available</p>
                                    </div>
                                `;
                                
                                document.querySelector('#active-feedback-container .card-body').innerHTML = noActiveHtml;
                            }
                        });
                    });
                });
                
                // Handle Unarchive Feedback
                document.querySelectorAll('.unarchive-feedback-btn').forEach(button => {
                    button.addEventListener('click', handleUnarchive);
                });
                
                function handleUnarchive() {
                    const feedbackId = this.getAttribute('data-feedback-id');
                    const feedbackItem = this.closest('.feedback-item');
                    
                    ClassManager.unarchiveFeedback(feedbackId, classId, function(response) {
                        // Move the feedback to active section
                        const activeList = document.getElementById('active-feedback-list');
                        const noActiveFeedback = document.getElementById('no-active-feedback');
                        
                        if (noActiveFeedback) {
                            noActiveFeedback.remove();
                            
                            // Create list if it doesn't exist
                            if (!activeList) {
                                document.querySelector('#active-feedback-container .card-body').innerHTML = '<div class="feedback-list" id="active-feedback-list"></div>';
                            }
                        }
                        
                        // Clone the feedback item for the active section
                        const activeItem = feedbackItem.cloneNode(true);
                        
                        // Update action buttons for active state
                        const unarchiveBtn = activeItem.querySelector('.unarchive-feedback-btn');
                        unarchiveBtn.classList.remove('unarchive-feedback-btn');
                        unarchiveBtn.classList.add('archive-feedback-btn');
                        unarchiveBtn.innerHTML = '<i class="bi bi-archive"></i> Archive';
                        
                        // Add event listeners to the new buttons
                        activeItem.querySelector('.archive-feedback-btn').addEventListener('click', function() {
                            const feedbackId = this.getAttribute('data-feedback-id');
                            const feedbackItem = this.closest('.feedback-item');
                            
                            ClassManager.archiveFeedback(feedbackId, classId, function(response) {
                                // Implementation similar to archive function above
                            });
                        });
                        
                        activeItem.querySelector('.delete-feedback-btn').addEventListener('click', handleDelete);
                        
                        // Add to active list
                        document.getElementById('active-feedback-list').appendChild(activeItem);
                        
                        // Remove from archived list
                        feedbackItem.remove();
                        
                        // Update counts
                        updateFeedbackCounts();
                        
                        // Check if archived list is now empty
                        const archivedList = document.getElementById('archived-feedback-list');
                        if (archivedList && archivedList.children.length === 0) {
                            const noArchivedHtml = `
                                <div class="text-center text-muted py-4" id="no-archived-feedback">
                                    <i class="bi bi-archive display-4"></i>
                                    <p class="mt-2">No archived feedback</p>
                                </div>
                            `;
                            
                            document.querySelector('#archived-feedback-container .card-body').innerHTML = noArchivedHtml;
                        }
                    });
                }
                
                // Handle Delete Feedback
                document.querySelectorAll('.delete-feedback-btn').forEach(button => {
                    button.addEventListener('click', handleDelete);
                });
                
                function handleDelete() {
                    const feedbackId = this.getAttribute('data-feedback-id');
                    const feedbackItem = this.closest('.feedback-item');
                    const isArchived = feedbackItem.closest('#archived-feedback-list') !== null;
                    
                    ClassManager.deleteFeedback(feedbackId, classId, function(response) {
                        // Remove feedback item
                        feedbackItem.remove();
                        
                        // Update counts
                        updateFeedbackCounts();
                        
                        // Check if lists are now empty
                        if (isArchived) {
                            const archivedList = document.getElementById('archived-feedback-list');
                            if (archivedList && archivedList.children.length === 0) {
                                const noArchivedHtml = `
                                    <div class="text-center text-muted py-4" id="no-archived-feedback">
                                        <i class="bi bi-archive display-4"></i>
                                        <p class="mt-2">No archived feedback</p>
                                    </div>
                                `;
                                
                                document.querySelector('#archived-feedback-container .card-body').innerHTML = noArchivedHtml;
                            }
                        } else {
                            const activeList = document.getElementById('active-feedback-list');
                            if (activeList && activeList.children.length === 0) {
                                const noActiveHtml = `
                                    <div class="text-center text-muted py-4" id="no-active-feedback">
                                        <i class="bi bi-chat-dots display-4"></i>
                                        <p class="mt-2">No active feedback available</p>
                                    </div>
                                `;
                                
                                document.querySelector('#active-feedback-container .card-body').innerHTML = noActiveHtml;
                            }
                        }
                    });
                }
                
                // Helper function to update feedback counts
                function updateFeedbackCounts() {
                    const activeCount = document.getElementById('active-feedback-list') 
                        ? document.querySelectorAll('#active-feedback-list .feedback-item').length
                        : 0;
                        
                    const archivedCount = document.getElementById('archived-feedback-list')
                        ? document.querySelectorAll('#archived-feedback-list .feedback-item').length
                        : 0;
                        
                    document.getElementById('active-count').textContent = activeCount;
                    document.getElementById('archived-count').textContent = archivedCount;
                }
                
                // Close alert messages after 5 seconds
                setTimeout(() => {
                    document.querySelectorAll('.alert').forEach(alert => {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    });
                }, 5000);
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
            
            /* Feedback specific styles */
            .feedback-item {
                transition: background-color 0.2s;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
            }
            
            .feedback-item:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }
            
            .rating-display {
                font-size: 1.1rem;
            }
            
            .feedback-content {
                padding-left: 52px;
            }
            
            .feedback-actions {
                padding-left: 52px;
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
                
                .feedback-content,
                .feedback-actions {
                    padding-left: 0;
                }
                
                .btn {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
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
                
                .feedback-item {
                    padding: 0.75rem !important;
                }
                
                .feedback-item h6 {
                    font-size: 1rem;
                }
            }
        </style>
    </body>
</html> 