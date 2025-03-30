<?php
require_once '../../backends/main.php';
require_once BACKEND.'rating_management.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: '.BASE.'login');
    exit;
}

$userId = $_SESSION['user'];
$scheduleId = $_GET['schedule_id'] ?? null;
$classId = $_GET['class_id'] ?? null;

if (!$scheduleId || !$classId) {
    header('Location: '.BASE.'dashboard');
    exit;
}

$ratingManager = new RatingManagement();
$classRating = $ratingManager->getClassRating($classId);
$sessionFeedback = $ratingManager->getSessionFeedback($scheduleId);

$db = $conn;

// Get class details
$sql = "SELECT c.*, CONCAT(u.first_name,' ',u.last_name) as tutor_name 
        FROM class c 
        JOIN users u ON c.tutor_id = u.uid 
        WHERE c.class_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $classId);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

// Get schedule details
$sql = "SELECT * FROM class_schedule WHERE schedule_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $scheduleId);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

// Check if feedback already submitted
$sql = "SELECT * FROM session_feedback WHERE session_id = ? AND student_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("ii", $scheduleId, $userId);
$stmt->execute();
$existingFeedback = $stmt->get_result()->fetch_assoc();


// Check if class rating already submitted
$sql = "SELECT * FROM class_ratings WHERE class_id = ? AND student_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("ii", $classId, $userId);
$stmt->execute();
$existingRating = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../components/head.php'; ?>
    <title>Class Feedback - TechKids</title>
    <style>
        .feedback-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .class-info {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .rating-section {
            margin-bottom: 2rem;
        }

        .star-rating {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star {
            font-size: 2rem;
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }

        .star:hover,
        .star.active {
            color: #ffd700;
        }

        .feedback-form {
            display: grid;
            gap: 1.5rem;
        }

        .rating-item {
            display: grid;
            gap: 0.5rem;
        }

        .rating-item label {
            font-weight: 500;
            color: #333;
        }

        .rating-item .rating-value {
            display: flex;
            gap: 0.5rem;
        }

        .rating-item .rating-value span {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .rating-item .rating-value span:hover {
            background-color: #f0f0f0;
        }

        .rating-item .rating-value span.active {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }

        .submit-btn {
            background-color: #1976d2;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #1565c0;
        }

        .thank-you-message {
            text-align: center;
            padding: 2rem;
            display: none;
        }

        .thank-you-message.show {
            display: block;
        }

        @media (max-width: 768px) {
            .feedback-container {
                margin: 1rem;
                padding: 1rem;
            }

            .star {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../../components/header.php'; ?>

    <div class="feedback-container">
        <div class="class-info">
            <h1>Class Feedback</h1>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($class['class_name']); ?></p>
            <p><strong>Tutor:</strong> <?php echo htmlspecialchars($class['tutor_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($schedule['schedule_date'])); ?></p>
        </div>

        <?php if (!$existingFeedback && !$existingRating): ?>
            <form id="feedbackForm" class="feedback-form">
                <input type="hidden" name="schedule_id" value="<?php echo $scheduleId; ?>">
                <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
                <input type="hidden" name="tutor_id" value="<?php echo $class['tutor_id']; ?>">

                <div class="rating-section">
                    <h2>Overall Class Rating</h2>
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-rating="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <textarea name="review" placeholder="Share your thoughts about the class (optional)"></textarea>
                </div>

                <div class="rating-section">
                    <h2>Session Feedback</h2>
                    
                    <div class="rating-item">
                        <label>How well did you understand the material?</label>
                        <div class="rating-value">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span data-rating="<?php echo $i; ?>"><?php echo $i; ?></span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="understanding" value="">
                    </div>

                    <div class="rating-item">
                        <label>How was the pace of the session?</label>
                        <div class="rating-value">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span data-rating="<?php echo $i; ?>"><?php echo $i; ?></span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="pace" value="">
                    </div>

                    <div class="rating-item">
                        <label>How would you rate the quality of materials?</label>
                        <div class="rating-value">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span data-rating="<?php echo $i; ?>"><?php echo $i; ?></span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="material" value="">
                    </div>

                    <div class="rating-item">
                        <label>How engaging was the session?</label>
                        <div class="rating-value">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span data-rating="<?php echo $i; ?>"><?php echo $i; ?></span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="engagement" value="">
                    </div>

                    <div class="rating-item">
                        <label>Additional Comments</label>
                        <textarea name="comments" placeholder="Share any additional feedback about the session"></textarea>
                    </div>

                    <div class="rating-item">
                        <label>Suggestions for Improvement</label>
                        <textarea name="improvements" placeholder="What could be improved in future sessions?"></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Submit Feedback</button>
            </form>

            <div class="thank-you-message">
                <h2>Thank You!</h2>
                <p>Your feedback has been submitted successfully.</p>
                <a href="dashboard.php" class="submit-btn">Return to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="thank-you-message show">
                <h2>Thank You!</h2>
                <p>You have already submitted feedback for this session.</p>
                <a href="dashboard.php" class="submit-btn">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</main>
</div>
<?php include '../../components/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('feedbackForm');
            const thankYouMessage = document.querySelector('.thank-you-message');

            // Handle star rating
            document.querySelectorAll('.star-rating .star').forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.dataset.rating;
                    const stars = this.parentElement.querySelectorAll('.star');
                    stars.forEach(s => s.classList.remove('active'));
                    for (let i = 0; i < rating; i++) {
                        stars[i].classList.add('active');
                    }
                    form.querySelector('input[name="rating"]').value = rating;
                });
            });

            // Handle numeric ratings
            document.querySelectorAll('.rating-value').forEach(container => {
                container.querySelectorAll('span').forEach(span => {
                    span.addEventListener('click', function() {
                        const rating = this.dataset.rating;
                        const spans = this.parentElement.querySelectorAll('span');
                        spans.forEach(s => s.classList.remove('active'));
                        this.classList.add('active');
                        this.parentElement.nextElementSibling.value = rating;
                    });
                });
            });

            // Handle form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'submit_feedback');

                try {
                    const response = await fetch('/api/ratings', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        form.style.display = 'none';
                        thankYouMessage.classList.add('show');
                    } else {
                        alert('Failed to submit feedback. Please try again.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                }
            });
        });
    </script>
</body>
</html> 