<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';
    require_once BACKEND.'rating_management.php';

    if(!isset($_SESSION['user'])) {
        
        $_SESSION['msg'] = "Invalid Action";
        log_error("User accessed an invalid page",'security');
        header("location: ".BASE."login");
        exit();
    }

    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    // Get class ID from URL parameter
    $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$class_id) {
        header('Location: ../');
        exit();
    }
    // Check if student is enrolled in the class
    $check = checkStudentEnrollment($_SESSION['user'], $class_id);
    if(!$check) {
        header("location: ".BASE."dashboard/s/enrollments/class?id=".$class_id);
        exit();
    }

    // Get class details
    $classDetails = getClassDetails($class_id);
    if (!$classDetails) {
        header('Location: ../');
        exit();
    }

    // Verify the student is enrolled in this class
    $stmt = $conn->prepare("
        SELECT enrollment_id 
        FROM enrollments 
        WHERE class_id = ? AND student_id = ? AND status NOT IN ('dropped', 'pending')
    ");
    $stmt->bind_param("ii", $class_id, $_SESSION['user']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $_SESSION['msg'] = "You are not enrolled in this class.";
        header('Location: ../');
        exit();
    }

    // Initialize the rating management class
    $ratingManager = new RatingManagement();

    // Get all feedback for this student in this class
    $feedbacks = $ratingManager->getStudentFeedbacks($class_id, $_SESSION['user']);

    $title = "Feedback for " . htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .feedback-card {
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .feedback-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .star-rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .archived {
            opacity: 0.7;
            border-left: 4px solid #6c757d;
        }
        
        .feedback-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .session-date {
            font-weight: 500;
            color: #495057;
        }
        
        .no-feedback-container {
            padding: 3rem;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }
        
        .editable {
            border-left: 4px solid #007bff;
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <!-- Page Loader -->
        <div id="page-loader">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="loading-text">Loading content...</div>
        </div>
        
        <script>
            // Show loading screen at the start of page load
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize page components
                initializePage();
            });
            
            function initializePage() {
                console.log('Feedback page initialized');
                
                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            
            function archiveFeedback(ratingId) {
                if (!confirm('Are you sure you want to archive this feedback? It will be hidden from your TechGuru.')) {
                    return;
                }
                
                showLoading(true);
                
                fetch(`${BASE}api/feedback?action=archive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rating_id: ratingId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast('success', 'Feedback archived successfully');
                        
                        // Update UI to show archived status
                        const feedbackCard = document.getElementById(`feedback-${ratingId}`);
                        if (feedbackCard) {
                            feedbackCard.classList.add('archived');
                            const archiveBtn = feedbackCard.querySelector('.archive-btn');
                            if (archiveBtn) {
                                archiveBtn.innerHTML = '<i class="bi bi-eye"></i> Unarchive';
                                archiveBtn.onclick = function() { unarchiveFeedback(ratingId); };
                            }
                            
                            // Add archived badge
                            const badgeContainer = feedbackCard.querySelector('.badge-container');
                            if (badgeContainer) {
                                const archivedBadge = document.createElement('span');
                                archivedBadge.className = 'badge bg-secondary ms-2';
                                archivedBadge.textContent = 'Archived';
                                badgeContainer.appendChild(archivedBadge);
                            }
                        }
                    } else {
                        showToast('error', data.message || 'Failed to archive feedback');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error archiving feedback:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }
            
            function unarchiveFeedback(ratingId) {
                showLoading(true);
                
                fetch(`${BASE}api/feedback?action=unarchive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rating_id: ratingId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showToast('success', 'Feedback unarchived successfully');
                        
                        // Update UI to show unarchived status
                        const feedbackCard = document.getElementById(`feedback-${ratingId}`);
                        if (feedbackCard) {
                            feedbackCard.classList.remove('archived');
                            const archiveBtn = feedbackCard.querySelector('.archive-btn');
                            if (archiveBtn) {
                                archiveBtn.innerHTML = '<i class="bi bi-archive"></i> Archive';
                                archiveBtn.onclick = function() { archiveFeedback(ratingId); };
                            }
                            
                            // Remove archived badge
                            const archivedBadge = feedbackCard.querySelector('.badge.bg-secondary');
                            if (archivedBadge && archivedBadge.textContent === 'Archived') {
                                archivedBadge.remove();
                            }
                        }
                    } else {
                        showToast('error', data.message || 'Failed to unarchive feedback');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error unarchiving feedback:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }
            
            function editFeedback(ratingId) {
                const feedbackContent = document.getElementById(`feedback-content-${ratingId}`).textContent;
                const currentRating = parseInt(document.getElementById(`current-rating-${ratingId}`).value);
                
                document.getElementById('edit-rating-id').value = ratingId;
                document.getElementById('edit-feedback-content').value = feedbackContent;
                
                // Set the rating stars
                const starInputs = document.querySelectorAll('input[name="edit-rating"]');
                for (let i = 0; i < starInputs.length; i++) {
                    if (parseInt(starInputs[i].value) === currentRating) {
                        starInputs[i].checked = true;
                        break;
                    }
                }
                
                const editModal = new bootstrap.Modal(document.getElementById('editFeedbackModal'));
                editModal.show();
            }
            
            function saveEditedFeedback() {
                const ratingId = document.getElementById('edit-rating-id').value;
                const feedbackContent = document.getElementById('edit-feedback-content').value;
                const ratingInput = document.querySelector('input[name="edit-rating"]:checked');
                
                if (!ratingInput) {
                    showToast('error', 'Please select a rating');
                    return;
                }
                
                const rating = ratingInput.value;
                
                showLoading(true);
                
                fetch(`${BASE}api/feedback?action=update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rating_id: ratingId,
                        rating: rating,
                        feedback: feedbackContent
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    showLoading(false);
                    if (data.success) {
                        showToast('success', 'Feedback updated successfully');
                        bootstrap.Modal.getInstance(document.getElementById('editFeedbackModal')).hide();
                        
                        // Update UI with new feedback content and rating
                        document.getElementById(`feedback-content-${ratingId}`).textContent = feedbackContent;
                        
                        // Update stars
                        const starsContainer = document.getElementById(`rating-stars-${ratingId}`);
                        starsContainer.innerHTML = '';
                        for (let i = 1; i <= 5; i++) {
                            const star = document.createElement('i');
                            star.className = i <= rating ? 'bi bi-star-fill' : 'bi bi-star';
                            starsContainer.appendChild(star);
                        }
                    } else {
                        showToast('error', data.message || 'Failed to update feedback');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error updating feedback:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }
        </script>
        
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <div class="dashboard-content bg">
                <!-- Header Section -->
                <div class="content-section mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                        <ol class="breadcrumb mb-2">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="../">My Classes</a></li>
                                            <li class="breadcrumb-item"><a href="../details?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                            <li class="breadcrumb-item active">Feedback</li>
                                        </ol>
                                    </nav>
                                    <h1 class="page-title mb-1">My Feedback for <?php echo htmlspecialchars($classDetails['class_name']); ?></h1>
                                    <p class="text-muted">
                                        View and manage your session feedback for this class.
                                        <span class="d-block mt-1 small">
                                            <i class="bi bi-info-circle"></i> You can edit feedback within 24 hours of submission and archive feedback at any time.
                                        </span>
                                    </p>
                                </div>
                                <a href="../details?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Class
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback List -->
                <div class="content-section">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <h2 class="section-title mb-4">Session Feedback</h2>
                            
                            <?php if (empty($feedbacks)): ?>
                            <div class="no-feedback-container">
                                <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #6c757d;"></i>
                                <h3 class="mt-3">No Feedback Submitted Yet</h3>
                                <p class="text-muted">You haven't provided any feedback for sessions in this class yet.</p>
                                <a href="../details?id=<?php echo $class_id; ?>" class="btn btn-primary mt-2">
                                    Go to Class Details
                                </a>
                            </div>
                            <?php else: ?>
                                <?php foreach ($feedbacks as $feedback): ?>
                                    <?php 
                                        $is_editable = (time() - strtotime($feedback['created_at']) < 86400); // 24 hours
                                        $session_date = date('F j, Y', strtotime($feedback['session_date']));
                                        $feedback_date = date('M j, Y, g:i A', strtotime($feedback['created_at']));
                                        $is_archived = $feedback['is_archived'] ? true : false;
                                    ?>
                                    <div id="feedback-<?php echo $feedback['rating_id']; ?>" class="feedback-card card <?php echo $is_archived ? 'archived' : ($is_editable ? 'editable' : ''); ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1">Session on <span class="session-date"><?php echo $session_date; ?></span></h5>
                                                    <div class="d-flex align-items-center">
                                                        <div id="rating-stars-<?php echo $feedback['rating_id']; ?>" class="star-rating me-2">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="bi <?php echo $i <= $feedback['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <input type="hidden" id="current-rating-<?php echo $feedback['rating_id']; ?>" value="<?php echo $feedback['rating']; ?>">
                                                        <div class="badge-container">
                                                            <?php if ($is_editable): ?>
                                                                <span class="badge bg-primary" data-bs-toggle="tooltip" title="You can edit this feedback within 24 hours of submission">Editable</span>
                                                            <?php endif; ?>
                                                            <?php if ($is_archived): ?>
                                                                <span class="badge bg-secondary ms-2">Archived</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="feedback-date mt-1">Submitted on <?php echo $feedback_date; ?></div>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" id="dropdownMenuButton<?php echo $feedback['rating_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton<?php echo $feedback['rating_id']; ?>">
                                                        <?php if ($is_editable): ?>
                                                            <li>
                                                                <button class="dropdown-item" onclick="editFeedback(<?php echo $feedback['rating_id']; ?>)">
                                                                    <i class="bi bi-pencil"></i> Edit Feedback
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <button class="dropdown-item archive-btn" onclick="<?php echo $is_archived ? 'unarchiveFeedback' : 'archiveFeedback'; ?>(<?php echo $feedback['rating_id']; ?>)">
                                                                <i class="bi <?php echo $is_archived ? 'bi-eye' : 'bi-archive'; ?>"></i> 
                                                                <?php echo $is_archived ? 'Unarchive' : 'Archive'; ?>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="card-text" id="feedback-content-<?php echo $feedback['rating_id']; ?>">
                                                <?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
    </main>
        
        <!-- Edit Feedback Modal -->
        <div class="modal fade" id="editFeedbackModal" tabindex="-1" aria-labelledby="editFeedbackModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editFeedbackModalLabel">Edit Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editFeedbackForm">
                            <input type="hidden" id="edit-rating-id">
                            
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="star-rating-select">
                                    <div class="d-flex gap-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="edit-rating" id="rating-<?php echo $i; ?>" value="<?php echo $i; ?>">
                                                <label class="form-check-label" for="rating-<?php echo $i; ?>">
                                                    <?php echo $i; ?> <i class="bi bi-star-fill star-rating"></i>
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit-feedback-content" class="form-label">Feedback</label>
                                <textarea class="form-control" id="edit-feedback-content" rows="5"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveEditedFeedback()">Save Changes</button>
                    </div>
                </div>
            </div>
    </div> 
        
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>