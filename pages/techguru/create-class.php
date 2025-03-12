<?php 
require_once '../../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get subject from URL parameter
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Map subject IDs to readable names and categories
$subjectMap = [
    'java' => ['name' => 'Java Programming', 'category' => 'Computer Programming'],
    'python' => ['name' => 'Python Programming', 'category' => 'Computer Programming'],
    'cpp' => ['name' => 'C++ Programming', 'category' => 'Computer Programming'],
    'frontend' => ['name' => 'Frontend Development', 'category' => 'Web Development']
];

// Get subject details or redirect if invalid
if (!isset($subjectMap[$subject])) {
    header('Location: subjects/class');
    exit();
}

$subjectDetails = $subjectMap[$subject];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Add class creation logic here
    // This will handle:
    // 1. Class duration (start/end dates)
    // 2. Schedule availability
    // 3. Pricing
    // 4. Maximum students
    // 5. Class description
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Create Class - <?php echo htmlspecialchars($subjectDetails['name']); ?></title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>techguru-common.css" rel="stylesheet">
</head>

<body>
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
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="techguru_subjects.php">Teaching Subjects</a></li>
                                    <li class="breadcrumb-item"><a href="techguru_subject_details.php?subject=<?php echo urlencode($subject); ?>"><?php echo htmlspecialchars($subjectDetails['name']); ?></a></li>
                                    <li class="breadcrumb-item active">Create Class</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Create New Class</h2>
                            <p class="subtitle">Set up your tutoring session for <?php echo htmlspecialchars($subjectDetails['name']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Creation Form -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="dashboard-card">
                    <form id="createClassForm" method="POST" action="">
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h3>Basic Information</h3>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="className" class="form-label">Class Name</label>
                                    <input type="text" class="form-control" id="className" name="className" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="maxStudents" class="form-label">Maximum Students</label>
                                    <input type="number" class="form-control" id="maxStudents" name="maxStudents" min="1" max="50" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Class Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                        </div>

                        <!-- Schedule -->
                        <div class="mb-4">
                            <h3>Class Schedule</h3>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label d-block">Available Days</label>
                                <div class="btn-group" role="group">
                                    <input type="checkbox" class="btn-check" id="monday" name="days[]" value="monday">
                                    <label class="btn btn-outline-primary" for="monday">Mon</label>
                                    
                                    <input type="checkbox" class="btn-check" id="tuesday" name="days[]" value="tuesday">
                                    <label class="btn btn-outline-primary" for="tuesday">Tue</label>
                                    
                                    <input type="checkbox" class="btn-check" id="wednesday" name="days[]" value="wednesday">
                                    <label class="btn btn-outline-primary" for="wednesday">Wed</label>
                                    
                                    <input type="checkbox" class="btn-check" id="thursday" name="days[]" value="thursday">
                                    <label class="btn btn-outline-primary" for="thursday">Thu</label>
                                    
                                    <input type="checkbox" class="btn-check" id="friday" name="days[]" value="friday">
                                    <label class="btn btn-outline-primary" for="friday">Fri</label>
                                    
                                    <input type="checkbox" class="btn-check" id="saturday" name="days[]" value="saturday">
                                    <label class="btn btn-outline-primary" for="saturday">Sat</label>
                                    
                                    <input type="checkbox" class="btn-check" id="sunday" name="days[]" value="sunday">
                                    <label class="btn btn-outline-primary" for="sunday">Sun</label>
                                </div>
                            </div>

                            <div id="timeSlots" class="mb-3">
                                <label class="form-label">Time Slots</label>
                                <div class="time-slot mb-2">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input type="time" class="form-control" name="startTime[]" required>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="time" class="form-control" name="endTime[]" required>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-primary w-100" onclick="addTimeSlot()">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="mb-4">
                            <h3>Pricing</h3>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pricingType" id="freeClass" value="free" checked>
                                        <label class="form-check-label" for="freeClass">
                                            Free Class
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pricingType" id="paidClass" value="paid">
                                        <label class="form-check-label" for="paidClass">
                                            Paid Class
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3" id="priceField" style="display: none;">
                                    <label for="price" class="form-label">Price per Session (PHP)</label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-end">
                            <a href="techguru_subject_details.php?subject=<?php echo urlencode($subject); ?>" class="btn btn-outline me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Class</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h3>Tips for Success</h3>
                    <div class="tips-content mt-4">
                        <div class="tip-item mb-4">
                            <h5><i class="bi bi-lightbulb me-2"></i>Class Size</h5>
                            <p>Keep your class size manageable. 15-20 students is ideal for effective interaction.</p>
                        </div>
                        <div class="tip-item mb-4">
                            <h5><i class="bi bi-clock me-2"></i>Session Duration</h5>
                            <p>2-hour sessions work best for most subjects. Include short breaks for better engagement.</p>
                        </div>
                        <div class="tip-item mb-4">
                            <h5><i class="bi bi-calendar me-2"></i>Schedule</h5>
                            <p>Offer multiple time slots to accommodate different student schedules.</p>
                        </div>
                        <div class="tip-item">
                            <h5><i class="bi bi-currency-dollar me-2"></i>Pricing</h5>
                            <p>Consider offering an introductory discount or a free trial session to attract students.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide price field based on pricing type
        document.querySelectorAll('input[name="pricingType"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const priceField = document.getElementById('priceField');
                if (this.value === 'paid') {
                    priceField.style.display = 'block';
                    document.getElementById('price').required = true;
                } else {
                    priceField.style.display = 'none';
                    document.getElementById('price').required = false;
                }
            });
        });

        // Add new time slot
        function addTimeSlot() {
            const timeSlots = document.getElementById('timeSlots');
            const newSlot = document.createElement('div');
            newSlot.className = 'time-slot mb-2';
            newSlot.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <input type="time" class="form-control" name="startTime[]" required>
                    </div>
                    <div class="col-md-5">
                        <input type="time" class="form-control" name="endTime[]" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="removeTimeSlot(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            timeSlots.appendChild(newSlot);
        }

        // Remove time slot
        function removeTimeSlot(button) {
            button.closest('.time-slot').remove();
        }

        // Form validation
        document.getElementById('createClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate dates
            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            
            if (endDate <= startDate) {
                alert('End date must be after start date');
                return;
            }

            // Validate days selection
            const selectedDays = document.querySelectorAll('input[name="days[]"]:checked');
            if (selectedDays.length === 0) {
                alert('Please select at least one day');
                return;
            }

            // If all validations pass, submit the form
            this.submit();
        });

        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('startDate').min = today;
        document.getElementById('endDate').min = today;
    </script>
</body>
</html>
