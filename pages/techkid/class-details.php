<?php 
require_once '../../backends/main.php';
require_once ROOT_PATH.'/backends/class_management.php';

// Ensure user is logged in and is a TechKid
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="page-header"><?php echo htmlspecialchars($classDetails['class_name']); ?></h2>
                            <p>
                                <strong>Subject:</strong> <?php echo htmlspecialchars($classDetails['subject_name']); ?><br>
                                <strong>Course:</strong> <?php echo htmlspecialchars($classDetails['course_name']); ?><br>
                                <strong>Duration:</strong> <?php echo date('M d, Y', strtotime($classDetails['start_date'])); ?> - <?php echo date('M d, Y', strtotime($classDetails['end_date'])); ?>
                            </p>
                        </div>
                        <div>
                            <a href="classes" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to Classes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row mt-4">
            <div class="col-md-8">
                <!-- Class Information -->
                <div class="dashboard-card mb-4">
                    <h3>Class Information</h3>
                    <p><?php echo nl2br(htmlspecialchars($classDetails['class_desc'])); ?></p>
                </div>

                <!-- Select Class Schedules -->
                <div class="dashboard-card mb-4">
                    <h3>Select Class Schedule</h3>
                    <p>Choose the sessions you want to enroll in:</p>
                    <form id="enrollForm">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <div class="table-responsive mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="schedule_ids[]" value="<?php echo $schedule['schedule_id']; ?>">
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($schedule['session_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($schedule['status']) {
                                                    case 'confirmed': echo 'bg-primary'; break;
                                                    case 'completed': echo 'bg-success'; break;
                                                    case 'canceled': echo 'bg-danger'; break;
                                                    default: echo 'bg-warning'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($schedule['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">
                            <i class="bi bi-check-circle"></i> Enroll in Selected Sessions
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Tutor Details -->
                <div class="dashboard-card mb-4 text-center">
                    <h3>Instructor</h3>
                    <img src="<?php echo !empty($classDetails['tutor_avatar']) ? USER_IMG . $classDetails['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                        class="rounded-circle mb-2" width="80">
                    <p class="mb-0"><strong><?php echo htmlspecialchars($classDetails['techguru_name']); ?></strong></p>
                    <p class="text-muted">Average Rating: <?php echo number_format($classDetails['average_rating'] ?? 0, 1); ?>/5</p>
                </div>

                <!-- Enrollment Statistics -->
                <div class="dashboard-card mb-4">
                    <h3>Class Stats</h3>
                    <p><strong>Total Enrolled:</strong> <?php echo $classDetails['enrolled_students']; ?> students</p>
                    <p><strong>Completion Rate:</strong> <?php echo number_format($classDetails['completion_rate'] ?? 0, 1); ?>%</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Enrollment Script -->
    <script>
        document.getElementById("enrollForm").addEventListener("submit", function(e) {
            e.preventDefault();

            let selectedSchedules = [];
            document.querySelectorAll('input[name="schedule_ids[]"]:checked').forEach((checkbox) => {
                selectedSchedules.push(checkbox.value);
            });

            if (selectedSchedules.length === 0) {
                showToast('error', "Please select at least one schedule.");
                return;
            }

            fetch("<?php echo BASE; ?>api/enroll-class.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    class_id: "<?php echo $class_id; ?>",
                    user_id: "<?php echo $_SESSION['user']; ?>",
                    selected_sessions: selectedSchedules
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', "Successfully enrolled in selected sessions!");
                    setTimeout(() => location.href='<?php echo BASE;?>dashboard/s/class', 1500);
                } else {
                    showToast('error', data.message || "Enrollment failed.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showToast("error", "An unexpected error occurred.");
            });
        });
    </script>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>
