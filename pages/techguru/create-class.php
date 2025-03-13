<?php 
require_once '../../backends/main.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get subject from URL parameter
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Get subject details from database or redirect if invalid
$subjectDetails = getSubjectByName($subject);
if (!$subjectDetails) {
    header('Location: ./');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = $_POST['className'];
    $maxStudents = $_POST['maxStudents'];
    $description = $_POST['description'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $days = $_POST['days'] ?? [];
    $startTimes = $_POST['startTime'];
    $endTimes = $_POST['endTime'];
    $pricingType = $_POST['pricingType'];
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    
    // Convert time format from HH:MM to HH:MM:SS for database
    $timeSlots = [];
    for ($i = 0; $i < count($startTimes); $i++) {
        if (!empty($startTimes[$i]) && !empty($endTimes[$i])) {
            $timeSlots[] = [
                'start' => $startTimes[$i] . ':00',
                'end' => $endTimes[$i] . ':00'
            ];
        }
    }
    
    // Generate all class schedules
    $schedules = generateClassSchedules($startDate, $endDate, $days, $timeSlots);
    
    // Prepare class data
    $classData = [
        'subject_id' => $subjectDetails['subject_id'],
        'class_name' => $className,
        'class_desc' => $description,
        'tutor_id' => $_SESSION['user'],
        'start_date' => $startDate,
        'end_date' => $endDate,
        'class_size' => $maxStudents,
        'is_free' => $pricingType === 'free' ? 1 : 0,
        'price' => $pricingType === 'free' ? 0 : $price,
        'thumbnail' => 'default.jpg',
        'schedules' => $schedules
    ];
    
    // Create the class
    $result = createClass($classData);
    
    if ($result['success']) {
        // Send notification to admin about new class
        sendNotification(
            null, 
            'ADMIN',
            "New class '{$className}' created for {$subject}",
            "/admin/class-details?id={$result['class_id']}",
            $result['class_id'],
            'bi-mortarboard-fill',
            'text-success'
        );
        
        // Redirect to class details page
        header("Location: ".BASE."dashboard/class/details?id={$result['class_id']}&created=1");
        exit();
    } else {
        $error = $result['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Create Class - <?php echo htmlspecialchars($subjectDetails['subject_name']); ?></title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/clockpicker/dist/bootstrap-clockpicker.min.css" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>techguru-common.css" rel="stylesheet">
    
    <style>
        .clockpicker-popover {
            z-index: 9999;
        }
        .input-group-addon {
            cursor: pointer;
            border: 1px solid #ced4da;
            border-left: none;
            background: #fff;
            padding: 0.375rem 0.75rem;
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
        /* Fix clockpicker placement */
        .popover {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1060;
            display: none;
            max-width: none; /* Override Bootstrap's max-width */
            font-style: normal;
            font-weight: 400;
            line-height: 1.5;
            text-align: left;
            text-decoration: none;
            text-shadow: none;
            text-transform: none;
            letter-spacing: normal;
            word-break: normal;
            word-spacing: normal;
            white-space: normal;
            line-break: auto;
            font-size: 0.875rem;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.3rem;
        }
    </style>
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
                                    <li class="breadcrumb-item"><a href="home">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?php echo BASE.'dashboard/subjects'; ?>">Teaching Subjects</a></li>
                                    <li class="breadcrumb-item"><a href="./?subject=<?php echo urlencode($subject); ?>"><?php echo htmlspecialchars($subjectDetails['subject_name']); ?></a></li>
                                    <li class="breadcrumb-item active">Create Class</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Create New Class</h2>
                            <p class="subtitle">Set up your tutoring session for <?php echo htmlspecialchars($subjectDetails['subject_name']); ?></p>
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
                                    <label for="maxStudents" class="form-label">Maximum Students <small class="text-muted">(Leave empty for unlimited)</small></label>
                                    <input type="number" class="form-control" id="maxStudents" name="maxStudents" min="1" max="50" placeholder="No limit">
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

                            <div id="timeSlots">
                                <div class="time-slot mb-3">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                                <input type="text" class="form-control" name="startTime[]" required>
                                                <span class="input-group-addon">
                                                    <span class="bi bi-clock"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                                <input type="text" class="form-control" name="endTime[]" required>
                                                <span class="input-group-addon">
                                                    <span class="bi bi-clock"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger remove-time" style="display: none;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addTimeSlot">
                                <i class="bi bi-plus"></i> Add Time Slot
                            </button>
                        </div>

                        <!-- Pricing -->
                        <div class="mb-4">
                            <h3>Pricing</h3>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="pricingType" id="freeClass" value="free" checked>
                                        <label class="form-check-label" for="freeClass">Free Class</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pricingType" id="paidClass" value="paid">
                                        <label class="form-check-label" for="paidClass">Paid Class</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div id="priceField" class="d-none">
                                        <label for="price" class="form-label">Price (PHP)</label>
                                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="./?subject=<?php echo urlencode($subject); ?>" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Class</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h3>Tips for Creating a Class</h3>
                    <ul class="tips-list">
                        <li>
                            <i class="bi bi-lightbulb text-warning"></i>
                            <strong>Class Name:</strong> Make it descriptive and engaging
                        </li>
                        <li>
                            <i class="bi bi-people text-info"></i>
                            <strong>Class Size:</strong> Consider a manageable number of students
                        </li>
                        <li>
                            <i class="bi bi-calendar-check text-success"></i>
                            <strong>Schedule:</strong> Set realistic time slots and duration
                        </li>
                        <li>
                            <i class="bi bi-cash text-primary"></i>
                            <strong>Pricing:</strong> Consider your expertise and market rates
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/clockpicker/dist/bootstrap-clockpicker.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates to today and a month from today
            const today = new Date();
            const nextMonth = new Date();
            nextMonth.setMonth(today.getMonth() + 1);
            
            document.getElementById('startDate').value = today.toISOString().split('T')[0];
            document.getElementById('endDate').value = nextMonth.toISOString().split('T')[0];
            
            // Set default time to current time (rounded to nearest hour)
            const now = new Date();
            now.setMinutes(0); // Round to nearest hour
            const timeInputs = document.querySelectorAll('input[name^="startTime"], input[name^="endTime"]');
            timeInputs.forEach(input => {
                if (input.name.includes('startTime')) {
                    input.value = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                } else {
                    now.setHours(now.getHours() + 1);
                    input.value = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                }
            });

            // Initialize clockpicker for existing time inputs
            $('.clockpicker').clockpicker({
                donetext: 'Done',
                autoclose: true,
                default: 'now',
                twelvehour: true,
                afterDone: function() {
                    // Validate time after selection
                    validateTimeSlots();
                }
            });

            // Time slot management
            const timeSlots = document.getElementById('timeSlots');
            const addButton = document.getElementById('addTimeSlot');
            
            function updateRemoveButtons() {
                const slots = timeSlots.querySelectorAll('.time-slot');
                slots.forEach((slot, index) => {
                    const removeBtn = slot.querySelector('.remove-time');
                    removeBtn.style.display = slots.length > 1 ? 'block' : 'none';
                    
                    // Hide labels for additional time slots
                    const labels = slot.querySelectorAll('.form-label');
                    labels.forEach(label => {
                        label.style.display = index === 0 ? 'block' : 'none';
                    });
                });
            }
            
            function validateTimeSlots() {
                const slots = timeSlots.querySelectorAll('.time-slot');
                slots.forEach(slot => {
                    const startInput = slot.querySelector('input[name^="startTime"]');
                    const endInput = slot.querySelector('input[name^="endTime"]');
                    
                    if (startInput.value && endInput.value) {
                        // Convert 12-hour times to Date objects for comparison
                        const startDate = new Date(`1970/01/01 ${startInput.value}`);
                        const endDate = new Date(`1970/01/01 ${endInput.value}`);
                        
                        if (startDate >= endDate) {
                            endInput.setCustomValidity('End time must be after start time');
                        } else {
                            endInput.setCustomValidity('');
                        }
                    }
                });
            }
            
            addButton.addEventListener('click', function() {
                const newSlot = timeSlots.querySelector('.time-slot').cloneNode(true);
                // Hide labels in the new slot
                newSlot.querySelectorAll('.form-label').forEach(label => {
                    label.style.display = 'none';
                });
                // Reset input values
                newSlot.querySelectorAll('input').forEach(input => {
                    input.value = '';
                });
                // Initialize clockpicker for new inputs
                $(newSlot).find('.clockpicker').clockpicker({
                    donetext: 'Done',
                    autoclose: true,
                    default: 'now',
                    twelvehour: true,
                    afterDone: function() {
                        validateTimeSlots();
                    }
                });
                newSlot.querySelector('button').addEventListener('click', function() {
                    newSlot.remove();
                    updateRemoveButtons();
                });
                timeSlots.appendChild(newSlot);
                updateRemoveButtons();
            });
            
            // Initial setup for remove buttons
            document.querySelectorAll('.remove-time').forEach(btn => {
                btn.addEventListener('click', function() {
                    btn.closest('.time-slot').remove();
                    updateRemoveButtons();
                });
            });
            
            // Pricing toggle
            const priceField = document.getElementById('priceField');
            const priceInput = document.getElementById('price');
            const pricingInputs = document.querySelectorAll('input[name="pricingType"]');
            
            pricingInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'paid') {
                        priceField.classList.remove('d-none');
                        priceInput.required = true;
                    } else {
                        priceField.classList.add('d-none');
                        priceInput.required = false;
                        priceInput.value = '';
                    }
                });
            });
            
            // Form validation
            document.getElementById('createClassForm').addEventListener('submit', function(e) {
                const startDate = new Date(document.getElementById('startDate').value);
                const endDate = new Date(document.getElementById('endDate').value);
                const days = document.querySelectorAll('input[name="days[]"]:checked');
                const pricingType = document.querySelector('input[name="pricingType"]:checked').value;
                const price = document.getElementById('price').value;
                
                // Validate all time slots
                validateTimeSlots();
                const hasInvalidTimes = Array.from(timeSlots.querySelectorAll('input[type="text"]')).some(input => !input.checkValidity());
                
                if (hasInvalidTimes) {
                    e.preventDefault();
                    alert('Please ensure all end times are after their respective start times');
                    return;
                }
                
                if (startDate >= endDate) {
                    e.preventDefault();
                    alert('End date must be after start date');
                    return;
                }
                
                if (days.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one day for classes');
                    return;
                }
                
                if (pricingType === 'paid' && (!price || parseFloat(price) <= 0)) {
                    e.preventDefault();
                    alert('Please enter a valid price for paid classes');
                    return;
                }
            });
        });
    </script>
</body>
</html>
