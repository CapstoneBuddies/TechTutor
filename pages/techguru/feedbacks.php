<?php 
    require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = "Feedbacks";

// If class ID provided, get specific class details and feedbacks
if ($class_id > 0) {
    $classDetails = getClassDetails($class_id, $_SESSION['user']);
    if (!$classDetails) {
        header('Location: ./');
        exit();
    }
    $classFeedbacks = getClassFeedbacks($class_id);
    $title = "Feedbacks for " . htmlspecialchars($classDetails['class_name']);
}

// Get all feedbacks across all classes
$allFeedbacks = getAllTutorFeedbacks($_SESSION['user']);

// Helper function to display star rating
function displayStarRating($rating) {
    $html = '<div class="rating-stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            $html .= '<i class="bi bi-star text-warning"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .feedback-card {
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }
        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .feedback-header {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .feedback-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        .student-info {
            flex-grow: 1;
        }
        .rating-stars {
            margin-top: 0.25rem;
        }
        .rating-stars i {
            margin-right: 0.1rem;
        }
        .feedback-body {
            padding: 1.5rem;
        }
        .feedback-meta {
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
        }
        .empty-feedback {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-feedback i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .nav-tabs .nav-link {
            font-weight: 500;
            padding: 0.75rem 1rem;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid var(--bs-primary);
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .feedback-header img {
                margin-bottom: 0.5rem;
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                    <?php if ($class_id > 0): ?>
                                        <li class="breadcrumb-item"><a href="././">My Classes</a></li>
                                        <li class="breadcrumb-item"><a href="./?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                        <li class="breadcrumb-item active">Feedbacks</li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                        <li class="breadcrumb-item active">All Feedbacks</li>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo $title; ?></h2>
                        </div>
                        <?php if ($class_id > 0): ?>
                            <a href="./?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Class
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for navigation between all feedbacks and class-specific feedbacks -->
        <?php if ($class_id > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="feedbackTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="class-tab" data-bs-toggle="tab" data-bs-target="#class-feedbacks" type="button" role="tab" aria-controls="class-feedbacks" aria-selected="true">
                            <i class="bi bi-mortarboard me-2"></i> Class Feedbacks
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-feedbacks" type="button" role="tab" aria-controls="all-feedbacks" aria-selected="false">
                            <i class="bi bi-collection me-2"></i> All Feedbacks
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Feedback Content -->
        <div class="row mt-4">
            <div class="col-12">
                <?php if ($class_id > 0): ?>
                    <div class="tab-content" id="feedbackTabsContent">
                        <!-- Class-specific Feedbacks -->
                        <div class="tab-pane fade show active" id="class-feedbacks" role="tabpanel" aria-labelledby="class-tab">
                            <?php if (empty($classFeedbacks)): ?>
                                <div class="empty-feedback">
                                    <i class="bi bi-chat-square-text"></i>
                                    <h4>No Feedbacks Yet</h4>
                                    <p class="text-muted">When students submit feedback for this class, they will appear here.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($classFeedbacks as $feedback): ?>
                                        <div class="col-md-6">
                                            <div class="feedback-card bg-white">
                                                <div class="feedback-header">
                                                    <img src="<?php echo IMG . 'users/' . $feedback['profile_picture']; ?>" alt="Profile picture">
                                                    <div class="student-info">
                                                        <h5 class="mb-0"><?php echo htmlspecialchars($feedback['student_name']); ?></h5>
                                                        <?php echo displayStarRating($feedback['rating']); ?>
                                                    </div>
                                                </div>
                                                <div class="feedback-body">
                                                    <p><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></p>
                                                    <div class="feedback-meta">
                                                        <span><i class="bi bi-calendar-event me-1"></i> <?php echo date('M d, Y', strtotime($feedback['session_date'])); ?></span>
                                                        <span><i class="bi bi-clock me-1"></i> <?php echo $feedback['session_time']; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- All Feedbacks Tab -->
                        <div class="tab-pane fade" id="all-feedbacks" role="tabpanel" aria-labelledby="all-tab">
                            <?php if (empty($allFeedbacks)): ?>
                                <div class="empty-feedback">
                                    <i class="bi bi-chat-square-text"></i>
                                    <h4>No Feedbacks Yet</h4>
                                    <p class="text-muted">When students submit feedback for any of your classes, they will appear here.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($allFeedbacks as $feedback): ?>
                                        <div class="col-md-6">
                                            <div class="feedback-card bg-white">
                                                <div class="feedback-header">
                                                    <img src="<?php echo IMG . 'users/' . $feedback['profile_picture']; ?>" alt="Profile picture">
                                                    <div class="student-info">
                                                        <h5 class="mb-0"><?php echo htmlspecialchars($feedback['student_name']); ?></h5>
                                                        <?php echo displayStarRating($feedback['rating']); ?>
                                                    </div>
                                                </div>
                                                <div class="feedback-body">
                                                    <p><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></p>
                                                    <div class="feedback-meta">
                                                        <span><i class="bi bi-mortarboard me-1"></i> <?php echo htmlspecialchars($feedback['class_name']); ?></span>
                                                        <span><i class="bi bi-calendar-event me-1"></i> <?php echo date('M d, Y', strtotime($feedback['session_date'])); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Direct display of all feedbacks when no class ID provided -->
                    <?php if (empty($allFeedbacks)): ?>
                        <div class="empty-feedback">
                            <i class="bi bi-chat-square-text"></i>
                            <h4>No Feedbacks Yet</h4>
                            <p class="text-muted">When students submit feedback for any of your classes, they will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($allFeedbacks as $feedback): ?>
                                <div class="col-md-6">
                                    <div class="feedback-card bg-white">
                                        <div class="feedback-header">
                                            <img src="<?php echo IMG . 'users/' . $feedback['profile_picture']; ?>" alt="Profile picture">
                                            <div class="student-info">
                                                <h5 class="mb-0"><?php echo htmlspecialchars($feedback['student_name']); ?></h5>
                                                <?php echo displayStarRating($feedback['rating']); ?>
                                            </div>
                                        </div>
                                        <div class="feedback-body">
                                            <p><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></p>
                                            <div class="feedback-meta">
                                                <span>
                                                    <a href="details?id=<?php echo $feedback['class_id']; ?>">
                                                        <i class="bi bi-mortarboard me-1"></i> <?php echo htmlspecialchars($feedback['class_name']); ?>
                                                    </a>
                                                </span>
                                                <span><i class="bi bi-calendar-event me-1"></i> <?php echo date('M d, Y', strtotime($feedback['session_date'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tabs if they exist
            if (document.getElementById('feedbackTabs')) {
                const tabEl = document.querySelector('button[data-bs-toggle="tab"]');
                tabEl.addEventListener('shown.bs.tab', function (event) {
                    // Update URL when tab changes without page reload
                    const tabId = event.target.id;
                    if (tabId === 'all-tab') {
                        history.replaceState(null, null, '<?= BASE ?>dashboard/t/class/feedbacks');
                    } else {
                        history.replaceState(null, null, '<?= BASE ?>dashboard/t/class/details/feedbacks?id=<?= $class_id ?>');
                    }
                });
            }
        });
    </script>
</body>
</html>