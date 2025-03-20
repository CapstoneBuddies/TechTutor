<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id, $_SESSION['user']);
$classSchedule = getClassSchedules($class_id);
if (!$classDetails && !$classSchedule) {
    header('Location: ./');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = $_POST['className'];
    $maxStudents = $_POST['maxStudents'];
    $description = $_POST['description'];
    $pricingType = $_POST['pricingType'];
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    
    // Prepare class data
    $classData = [
        'class_id' => $class_id,
        'class_name' => $className,
        'class_desc' => $description,
        'class_size' => $maxStudents,
        'is_free' => $pricingType === 'free' ? 1 : 0,
        'price' => $pricingType === 'free' ? 0 : $price
    ];
    // Update the class
    $result = updateClass($classData);
    if (isset($_POST['schedules']) && is_array($_POST['schedules'])) {
        $data = $_POST['schedules'];
        $schedules = [];

        for ($i = 0; $i < count($data); $i += 3) {
            $date = $data[$i]['date'];
            $start = $data[$i + 1]['start'];
            $end = $data[$i + 2]['end'];

            // Group them into the desired format
            $schedules[] = ['session_date'=>$date, 'start_time'=>$start, 'end_time'=>$end];
        }
        if (!empty($schedules)) {
            $result = updateClassSchedules($class_id, $schedules, $_SESSION['user']);
            if (!$result['success']) {
                $error = $result['error'];
            }
        }
    }
    if ($result['success']) {
        // Redirect to class details page
        header("Location: details?id={$class_id}&updated=1");
        exit();
    } else {
        $error = $result['error'];
    }
}
$title = $classDetails['class_name'];
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
    <main class="container py-4">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard/t/classes">My Classes</a></li>
                                    <li class="breadcrumb-item"><a href="details?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                    <li class="breadcrumb-item active">Edit Class</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Edit Class</h2>
                            <p class="subtitle">Update your class details for <?php echo htmlspecialchars($classDetails['subject_name']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Class Edit Form -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="dashboard-card">
                    <form id="editClassForm" method="POST" action="">
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h3>Basic Information</h3>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="className" class="form-label">Class Name</label>
                                    <input type="text" class="form-control" id="className" name="className" value="<?php echo htmlspecialchars($classDetails['class_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="maxStudents" class="form-label">Maximum Students</label>
                                    <input type="number" class="form-control" id="maxStudents" name="maxStudents" min="1" max="50" value="<?php echo (int)$classDetails['class_size']; ?>" placedholder="No limit" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Class Description</label>
                                <textarea class="form-control no-resize" id="description" name="description" rows="4" required><?php echo htmlspecialchars($classDetails['class_desc']); ?></textarea>
                            </div>
                        </div>

                        <!-- Schedule Editing Section -->
                        <div class="mb-4">
                            <h3>Class Schedule</h3>
                            <div id="scheduleContainer">
                                <?php foreach ($classSchedule as $schedule): ?>
                                <div class="schedule-item mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Date</label>
                                            <input type="date" id="schedule_date" class="form-control" name="schedules[][date]" value="<?php echo $schedule['session_date']; ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                                <input type="text" class="form-control" name="schedules[][start]" value="<?php echo $schedule['start_time']; ?>" required>
                                                <span class="input-group-addon">
                                                    <span class="bi bi-clock"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                                <input type="text" class="form-control" name="schedules[][end]" value="<?php echo $schedule['end_time']; ?>" required>
                                                <span class="input-group-addon">
                                                    <span class="bi bi-clock"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger btn-sm remove-schedule" data-bs-toggle="tooltip" title="Remove class schedule">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="addSchedule">
                                <i class="bi bi-plus-circle"></i> Add Schedule
                            </button>
                        </div>

                        <!-- Pricing -->
                        <div class="mb-4">
                            <h3>Pricing</h3>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="pricingType" id="pricingFree" value="free" <?php echo $classDetails['is_free'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pricingFree">Free Class</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pricingType" id="pricingPaid" value="paid" <?php echo !$classDetails['is_free'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pricingPaid">Paid Class</label>
                                    </div>
                                </div>
                                <div class="col-md-6" id="priceField" style="display: <?php echo !$classDetails['is_free'] ? 'block' : 'none'; ?>;">
                                    <label for="price" class="form-label">Price (₱)</label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" placeholder="<?php echo number_format($classDetails['price'], 2, '.', ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="details?id=<?php echo $class_id; ?>" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h3>Editing Tips</h3>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-3">
                            <i class="bi bi-lightbulb text-warning me-2"></i>
                            Keep your class name clear and descriptive
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-people text-info me-2"></i>
                            Consider your current enrollment when adjusting class size
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-currency-dollar text-success me-2"></i>
                            Price changes will only affect new enrollments
                        </li>
                        <li>
                            <i class="bi bi-calendar-check text-primary me-2"></i>
                            Schedule changes require creating a new class
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    </main> 
    </div> 
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
        const scheduleContainer = document.getElementById("scheduleContainer");
        const addScheduleBtn = document.getElementById("addSchedule");
        const editClassForm = document.getElementById("editClassForm");

        // Function to initialize clockpicker
        function initializeClockpicker() {
            $('.clockpicker').clockpicker({
                autoclose: true,
                twelvehour: true,
                donetext: 'Set',
                afterDone: function () {
                    validateTimeSlots();
                }
            });
        }

        // Validate time slots before submission
        function validateTimeSlots() {
            let isValid = true;
            document.querySelectorAll('.schedule-item').forEach(slot => {
                const startInput = slot.querySelector('input[name^="schedules"][name$="[start]"]');
                const endInput = slot.querySelector('input[name^="schedules"][name$="[end]"]');

                if (startInput.value && endInput.value) {
                    const startTime = new Date(`1970-01-01T${startInput.value}`);
                    const endTime = new Date(`1970-01-01T${endInput.value}`);

                    if (startTime >= endTime) {
                        endInput.setCustomValidity("End time must be after start time");
                        isValid = false;
                    } else {
                        endInput.setCustomValidity("");
                    }
                }
            });

            return isValid;
        }

        // Add new schedule dynamically
        addScheduleBtn.addEventListener("click", function () {
            const newSchedule = `
                <div class="schedule-item mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="schedules[][date]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Time</label>
                            <div class="input-group clockpicker">
                                <input type="text" class="form-control" name="schedules[][start]" required>
                                <span class="input-group-addon">
                                    <span class="bi bi-clock"></span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Time</label>
                            <div class="input-group clockpicker">
                                <input type="text" class="form-control" name="schedules[][end]" required>
                                <span class="input-group-addon">
                                    <span class="bi bi-clock"></span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-schedule">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            scheduleContainer.insertAdjacentHTML("beforeend", newSchedule);
            initializeClockpicker();
        });

        // Remove schedule dynamically
        scheduleContainer.addEventListener("click", function (event) {
            if (event.target.closest(".remove-schedule")) {
                event.target.closest(".schedule-item").remove();
            }
        });

        // Validate and submit the form
        editClassForm.addEventListener("submit", function (event) {
            if (!validateTimeSlots()) {
                event.preventDefault();
                alert("Please fix the invalid schedule times.");
            }
        });

        // Initialize clockpicker for existing schedules
        initializeClockpicker();
    });

    </script>
</body>
</html>