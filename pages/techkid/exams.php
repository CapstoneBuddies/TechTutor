<?php
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit();
}

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$class_id) {
    header('Location: ./');
    exit();
}
$exam_type = isset($_GET['exam']) ? $_GET['exam'] : null;

$exam_data = null;
$exam_json = null;

if ($exam_type) {
    // Fetch exam from exams table
    $stmt = $conn->prepare("SELECT exam_item, duration FROM exams WHERE class_id = ? AND exam_type = ? AND exam_status = 'active' LIMIT 1");
    $stmt->bind_param("is",  $class_id, $exam_type);
    $stmt->execute();
    $stmt->bind_result($exam_json, $duration);
    $stmt->fetch();
    $stmt->close();
    
    if ($exam_json) {
        $exam_data = json_decode($exam_json, true);
        $title = ucfirst($exam_type) . " Exam";
    } else {
        // Exam not found or not active
        header('location: ./details?id=' . $class_id);
        exit();
    }
} else {
    // Fetch diagnostic exam from exams table
    $stmt = $conn->prepare("SELECT exam_id, exam_item, duration FROM exams WHERE class_id = ? AND exam_type = 'diagnostic' AND exam_status = 'active' LIMIT 1");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->bind_result($diag_exam_id, $exam_json, $duration);
    $stmt->fetch();
    $stmt->close();

    if (!$exam_json) {
        // Generate diagnostics if not present
        $exam_json = generateExamJSON($class_id,$exam_type);

        if ($exam_json) {
            // Insert new exam record into exams table
            $duration = 60; // default duration in minutes
            $total_marks = 30; // default total marks, can be adjusted
            $created_by = ($_SESSION['role'] === 'TECHGURU') ? $_SESSION['users'] : 1;

            // Use current logged-in user id as created_by if available
            $created_by = $_SESSION['user'];

            // Validate JSON string is not empty and valid
            if (empty($exam_json) || json_decode($exam_json) === null) {
                log_error("Failed to generate valid diagnostics JSON for class ID: $class_id");
                header('Location: ./?id=' . $class_id);
                exit();
            }

            $insert_stmt = $conn->prepare("INSERT INTO exams (class_id, exam_item, exam_status, duration, total_marks, exam_type, created_by) VALUES (?, ?, 'active', ?, ?, ?, ?)");
            $insert_stmt->bind_param("isiisi", $class_id, $exam_json, $duration, $total_marks, $examType, $created_by);
            $insert_stmt->execute();
            $insert_stmt->close();
        } else {
            // Failed to generate exam JSON
            log_error("Failed to generate diagnostics JSON for class ID: $class_id");
            header('Location: ./?id=' . $class_id);
            exit();
        }
    }

    $exam_data = json_decode($exam_json, true);
    $exam_type = "diagnostic";
    $title = ucfirst($exam_type)." Exam";
    $exam_id = $diag_exam_id ?? 0;
}

// If no exam data available, redirect back
if (!$exam_data) {
    log_error("No exam data found for class ID: $class_id and exam ID: $exam_id");
    header('Location: ./?id=' . $class_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-base="<?php echo BASE; ?>">
<?php include ROOT_PATH . '/components/header.php'; ?>
<main class="container py-4">
    <div class="dashboard-content bg">
        <div class="content-section mb-4">
            <div class="content-card bg-snow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title m-0"><?php echo htmlspecialchars($title); ?></h1>
                        <div class="exam-timer text-primary fw-bold d-none">
                            Time Remaining: <span id="timer">--:--</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        This diagnostic exam helps us understand your current knowledge level. Take your time and answer honestly.
                    </div>

                    <form id="examForm" class="needs-validation" novalidate>
                        <?php if (!empty($exam_data['questions'])): ?>
                            <div class="questions-container">
                                <?php foreach ($exam_data['questions'] as $qnum => $qdata): ?>
                                    <?php foreach ($qdata as $question => $choices): ?>
                                        <div class="question-card mb-4 p-4 border rounded bg-white shadow-sm">
                                            <label class="h5 mb-3 d-block">
                                                <?php echo $qnum . ". " . htmlspecialchars($question); ?>
                                            </label>
                                            <div class="choices-container">
                                                <?php foreach ($choices as $letter => $choice): ?>
                                                    <div class="form-check custom-radio mb-2">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="radio" 
                                                            name="q<?php echo $qnum; ?>" 
                                                            value="<?php echo $letter; ?>" 
                                                            id="q<?php echo $qnum . $letter; ?>" 
                                                            required
                                                        >
                                                        <label class="form-check-label w-100 py-2 px-3 rounded hover-bg" 
                                                               for="q<?php echo $qnum . $letter; ?>">
                                                            <span class="choice-letter"><?php echo strtoupper($letter); ?></span>
                                                            <?php echo htmlspecialchars($choice); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                        <i class="bi bi-arrow-left me-2"></i>Back
                                    </button>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-check2-circle me-2"></i>Submit Exam
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No exam questions available for this class.
                            </div>
                        <?php endif; ?>
                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $exam_type; ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/components/footer.php'; ?>

<style>
.custom-radio .form-check-input {
    display: none;
}

.custom-radio .form-check-label {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    position: relative;
    padding-left: 3rem !important;
}

.custom-radio .form-check-label:hover {
    background-color: #f8f9fa;
}

.custom-radio .form-check-input:checked + .form-check-label {
    background-color: #e7f1ff;
    border-color: #0d6efd;
}

.choice-letter {
    position: absolute;
    left: 1rem;
    font-weight: bold;
    color: #6c757d;
}

.custom-radio .form-check-input:checked + .form-check-label .choice-letter {
    color: #0d6efd;
}

.question-card {
    transition: all 0.3s ease;
}

.question-card:hover {
    transform: translateY(-2px);
}

.exam-timer {
    font-size: 1.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('examForm');
    const classId = <?php echo json_encode($class_id); ?>;
    const examId = <?php echo json_encode($exam_id); ?>;
    const examType = <?php echo json_encode($exam_type); ?>;
    const storageKey = 'exam_answers_' + classId + '_' + examId;

    // Restore answers from localStorage
    const savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');
    Object.keys(savedAnswers).forEach(q => {
        const selector = 'input[name="' + q + '"][value="' + savedAnswers[q] + '"]';
        const input = form.querySelector(selector);
        if (input) input.checked = true;
    });

    // Save answer on change
    form.addEventListener('change', function(e) {
        if (e.target.type === 'radio') {
            const answers = JSON.parse(localStorage.getItem(storageKey) || '{}');
            answers[e.target.name] = e.target.value;
            localStorage.setItem(storageKey, JSON.stringify(answers));
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Check if all questions are answered
        const questions = form.querySelectorAll('input[type="radio"]');
        const questionGroups = {};
        let allAnswered = true;
        
        questions.forEach(q => {
            const name = q.getAttribute('name');
            questionGroups[name] = questionGroups[name] || false;
            if (q.checked) {
                questionGroups[name] = true;
            }
        });
        
        for (let group in questionGroups) {
            if (!questionGroups[group]) {
                allAnswered = false;
                break;
            }
        }
        
        if (!allAnswered) {
            Swal.fire({
                title: 'Incomplete Exam',
                text: 'Please answer all questions before submitting.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Confirmation dialog
        const confirmed = await Swal.fire({
            title: 'Submit Exam?',
            text: 'Are you sure you want to submit your answers?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, submit',
            cancelButtonText: 'Cancel'
        });

        if (!confirmed.isConfirmed) return;

        // Show loading indicator
        const loading = document.getElementById('loadingIndicator');
        if (loading) loading.classList.remove('d-none');

        // Gather answers
        const formData = new FormData(form);
        const answers = {};
        for (let [key, value] of formData.entries()) {
            answers[key] = value;
        }

        try {
            const response = await fetch('<?php echo BASE; ?>submit-exam', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    class_id: classId,
                    exam_id: examId,
                    exam_type: examType,
                    answers: answers
                })
            });
            const result = await response.json();

            // Hide loading indicator
            if (loading) loading.classList.add('d-none');

            if (result.success) {
                // Clear saved answers
                localStorage.removeItem(storageKey);
                Swal.fire({
                    title: 'Exam Submitted!',
                    text: `Score: ${result.score}/${result.total}\nProficiency Level: ${result.proficiency_level}`,
                    icon: 'success'
                }).then(() => {
                    window.location.href = '../../class/details?id=<?php echo $class_id; ?>';
                });
            } else {
                Swal.fire({
                    title: 'Submission Failed',
                    text: result.message || 'An error occurred.',
                    icon: 'error'
                });
            }
        } catch (err) {
            if (loading) loading.classList.add('d-none');
            Swal.fire({
                title: 'Error',
                text: 'Could not submit exam. Please try again.',
                icon: 'error'
            });
        }
    });
});
</script>
</body>
</html>
