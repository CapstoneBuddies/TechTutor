<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'student_management.php';

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
    $classCover = $_FILES['classCover'] ?? null;
    
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
        'thumbnail' => $classCover ? $classCover['name'] : 'default.jpg',
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
        header("Location: ".BASE."dashboard/t/class/details?id={$result['class_id']}&created=1");
        exit();
    } else {
        $error = $result['error'];
    }
}

$title = 'Create Class - '.htmlspecialchars($subjectDetails['subject_name']);

?>

<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .form-section {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .form-section:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        .form-section h3 {
            color: var(--bs-primary);
            font-size: 1.25rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--bs-gray-200);
        }
        .preview-card {
            background: var(--bs-gray-100);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--bs-gray-200);
        }
        .preview-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .preview-card img:hover {
            transform: scale(1.02);
        }
        .preview-card h4 {
            color: var(--bs-gray-900);
            margin-bottom: 0.75rem;
        }
        .time-slot {
            background: var(--bs-gray-100);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative;
            border: 1px solid var(--bs-gray-200);
            transition: all 0.2s ease;
        }
        .time-slot:hover {
            background: var(--bs-gray-50);
            border-color: var(--bs-primary);
        }
        .time-slot .remove-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .time-slot:hover .remove-btn {
            opacity: 1;
        }
        .btn-check:checked + .btn-outline-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }
        .btn-check:checked + .btn-outline-primary i {
            transform: scale(1.1);
        }
        .btn-group .btn {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-group .btn i {
            transition: transform 0.2s ease;
        }
        .tip-card {
            background: var(--bs-gray-100);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--bs-primary);
            transition: all 0.2s ease;
        }
        .tip-card:hover {
            transform: translateX(4px);
        }
        .tip-card.warning { border-color: var(--bs-warning); }
        .tip-card.info { border-color: var(--bs-info); }
        .tip-card.success { border-color: var(--bs-success); }
        .tip-card h5 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--bs-gray-900);
            margin-bottom: 0.75rem;
        }
        .tip-card p {
            color: var(--bs-gray-700);
            font-size: 0.9rem;
        }
        .form-floating > .form-control:focus ~ label {
            color: var(--bs-primary);
        }
        .character-count {
            position: absolute;
            right: 1rem;
            bottom: 0.5rem;
            font-size: 0.8rem;
            color: var(--bs-gray-600);
        }
        .character-count.warning {
            color: var(--bs-warning);
        }
        .character-count.danger {
            color: var(--bs-danger);
        }
        @media (max-width: 768px) {
            .form-section {
                padding: 1.25rem;
            }
            .time-slot {
                padding: 1rem;
            }
            .time-slot .remove-btn {
                opacity: 1;
                top: 0.5rem;
                right: 0.5rem;
            }
            .btn-group {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .btn-group .btn {
                flex: 1;
                min-width: calc(33.333% - 0.5rem);
                margin: 0.25rem;
            }
            .preview-card img {
                height: 180px;
            }
        }
        /* Form validation styles */
        .form-control.is-invalid,
        .was-validated .form-control:invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .was-validated .form-control:invalid ~ .invalid-feedback,
        .form-control.is-invalid ~ .invalid-feedback {
            display: block;
        }

        .error-shake {
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .field-error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
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
                                    <li class="breadcrumb-item"><a href="home">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?php echo BASE.'dashboard/t/subjects'; ?>">Teaching Subjects</a></li>
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
                <div class="col-lg-8">
                    <form id="createClassForm" method="POST" action="" enctype="multipart/form-data">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3><i class="bi bi-info-circle"></i> Basic Information</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="className" class="form-label">Class Name</label>
                                    <input type="text" class="form-control" id="className" name="className" 
                                           required minlength="5" maxlength="100">
                                    <div class="form-text">Choose a clear and descriptive name (5-100 characters)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="maxStudents" class="form-label">Maximum Students</label>
                                    <input type="number" class="form-control" id="maxStudents" name="maxStudents" 
                                           min="1" max="50" value="10">
                                    <div class="form-text">Set a limit between 1-50 students</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Class Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4" required minlength="50" maxlength="500"></textarea>
                                <div class="form-text descriptionCountContainer">
                                    <span id="descriptionCount">0</span>/500 characters
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="classCover" class="form-label">Class Cover Image</label>
                                <input type="file" class="form-control" id="classCover" name="classCover" 
                                       accept="image/*" required>
                                <div class="form-text">Upload an eye-catching image (max 2MB, JPG/PNG)</div>
                            </div>
                            <div id="imagePreview" class="d-none">
                                <img src="" alt="Preview" class="img-fluid rounded">
                            </div>
                        </div>

                        <!-- Schedule -->
                        <div class="form-section">
                            <h3><i class="bi bi-calendar"></i> Class Schedule</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" required>
                                    <div class="form-text">When will the class begin?</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" required>
                                    <div class="form-text">When will the class end?</div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label d-block">Class Days</label>
                                <div class="btn-group" role="group">
                                    <input type="checkbox" class="btn-check" id="monday" name="days[]" value="monday">
                                    <label class="btn btn-outline-primary" for="monday">
                                        <i class="bi bi-calendar-day"></i> Mon
                                    </label>
                                    
                                    <input type="checkbox" class="btn-check" id="tuesday" name="days[]" value="tuesday">
                                    <label class="btn btn-outline-primary" for="tuesday">
                                        <i class="bi bi-calendar-day"></i> Tue
                                    </label>
                                    
                                    <input type="checkbox" class="btn-check" id="wednesday" name="days[]" value="wednesday">
                                    <label class="btn btn-outline-primary" for="wednesday">
                                        <i class="bi bi-calendar-day"></i> Wed
                                    </label>
                                    
                                    <input type="checkbox" class="btn-check" id="thursday" name="days[]" value="thursday">
                                    <label class="btn btn-outline-primary" for="thursday">
                                        <i class="bi bi-calendar-day"></i> Thu
                                    </label>
                                    
                                    <input type="checkbox" class="btn-check" id="friday" name="days[]" value="friday">
                                    <label class="btn btn-outline-primary" for="friday">
                                        <i class="bi bi-calendar-day"></i> Fri
                                    </label>
                                    
                                    <input type="checkbox" class="btn-check" id="saturday" name="days[]" value="saturday">
                                    <label class="btn btn-outline-primary" for="saturday">
                                        <i class="bi bi-calendar-day"></i> Sat
                                    </label>
                                    
                                    <input type="checkbox" class="btn-check" id="sunday" name="days[]" value="sunday">
                                    <label class="btn btn-outline-primary" for="sunday">
                                        <i class="bi bi-calendar-day"></i> Sun
                                    </label>
                                </div>
                                <div class="form-text mt-2">Select the days when the class will be held</div>
                            </div>

                            <div id="timeSlots">
                                <label class="form-label">Time Slots</label>
                                <div class="time-slot">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-btn d-none">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group clockpicker">
                                                <input type="text" name="startTime[]" class="form-control" placeholder="--:--" required>
                                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group clockpicker">
                                                <input type="text" name="endTime[]" class="form-control" placeholder="--:--" required>
                                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary mt-3" onclick="addTimeSlot()">
                                <i class="bi bi-plus-lg"></i> Add Another Time Slot
                            </button>
                        </div>

                        <!-- Pricing -->
                        <div class="form-section">
                            <h3><i class="bi bi-currency-dollar"></i> Pricing</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="pricingType" 
                                               id="pricingFree" value="free">
                                        <label class="form-check-label" for="pricingFree">
                                            <i class="bi bi-gift text-success"></i> Free Class
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pricingType" 
                                               id="pricingPaid" value="paid" checked>
                                        <label class="form-check-label" for="pricingPaid">
                                            <i class="bi bi-cash text-primary"></i> Paid Class
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6" id="priceField">
                                    <label for="price" class="form-label">Price (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               min="0" step="0.01" value="0.00">
                                    </div>
                                    <div class="form-text">Set a reasonable price for your class</div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="form-section">
                            <h3><i class="bi bi-eye"></i> Preview</h3>
                            <div class="preview-card">
                                <img id="coverPreview" src="<?php echo CLASS_IMG; ?>default.jpg" alt="Class Cover">
                                <h4 id="previewTitle">Class Name</h4>
                                <p id="previewDescription" class="text-muted">Class description will appear here...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-people"></i>
                                        <span id="previewStudents">0 students max</span>
                                    </div>
                                    <div>
                                        <i class="bi bi-calendar-check"></i>
                                        <span id="previewDuration">0 sessions</span>
                                    </div>
                                    <div>
                                        <i class="bi bi-tag"></i>
                                        <span id="previewPrice">Free</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="./?subject=<?php echo urlencode($subject); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2"></i> Create Class
                            </button>
                        </div>
                    </form>
            </div>

            <!-- Tips Section -->
                <div class="col-lg-4">
                <div class="dashboard-card">
                        <h3><i class="bi bi-lightbulb"></i> Creation Tips</h3>
                        <div class="tip-card warning">
                            <h5><i class="bi bi-lightbulb"></i> Class Name</h5>
                            <p class="mb-0">Choose a name that clearly describes what students will learn.</p>
                        </div>
                        <div class="tip-card info">
                            <h5><i class="bi bi-image"></i> Cover Image</h5>
                            <p class="mb-0">Use high-quality images that represent your class content.</p>
                        </div>
                        <div class="tip-card success">
                            <h5><i class="bi bi-calendar-check"></i> Schedule</h5>
                            <p class="mb-0">Plan your schedule carefully to maintain consistency.</p>
                        </div>
                        <div class="tip-card primary">
                            <h5><i class="bi bi-currency-dollar"></i> Pricing</h5>
                            <p class="mb-0">Research similar classes to set competitive pricing.</p>
                        </div>
                    </div>
                </div>
            </div>
    </main> 

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                initializeClockpicker();
                setupFormValidation();
                setupImagePreview();
                setupPricing();
                updatePreview();
                initializeTooltips();
                setupCharacterCount();

                // Form 
            });

            function initializeClockpicker() {
            $('.clockpicker').clockpicker({
                autoclose: true,
                twelvehour: true,
                    donetext: 'Done'
                });
            }

            function setupFormValidation() {
                const form = document.getElementById('createClassForm');
                const fields = {
                    className: {
                        element: document.getElementById('className'),
                        rules: {
                            required: 'Class name is required',
                            minLength: { value: 5, message: 'Class name must be at least 5 characters' },
                            maxLength: { value: 100, message: 'Class name must not exceed 100 characters' }
                        }
                    },
                    maxStudents: {
                        element: document.getElementById('maxStudents'),
                        rules: {
                            required: 'Maximum students is required',
                            min: { value: 1, message: 'Must allow at least 1 student' },
                            max: { value: 50, message: 'Cannot exceed 50 students' }
                        }
                    },
                    description: {
                        element: document.getElementById('description'),
                        rules: {
                            required: 'Description is required',
                            minLength: { value: 50, message: 'Description must be at least 50 characters' },
                            maxLength: { value: 500, message: 'Description must not exceed 500 characters' }
                        }
                    },
                    classCover: {
                        element: document.getElementById('classCover'),
                        rules: {
                            // required: 'Class cover image is required',
                            fileType: { value: ['image/jpeg', 'image/png'], message: 'Only JPG and PNG files are allowed' },
                            fileSize: { value: 2 * 1024 * 1024, message: 'File size must not exceed 2MB' }
                        }
                    }
                };

                // Validate single field
                function validateField(fieldName, showError = true) {
                    const field = fields[fieldName];
                    const element = field.element;
                    const rules = field.rules;
                    let isValid = true;
                    let errorMessage = '';

                    // Remove existing error messages
                    const existingError = element.parentElement.querySelector('.field-error');
                    if (existingError) {
                        existingError.remove();
                    }

                    // Check each validation rule
                    for (const rule in rules) {
                        switch(rule) {
                            case 'required':
                                if (!element.value) {
                                    isValid = false;
                                    errorMessage = rules[rule];
                                }
                                break;
                            case 'minLength':
                                if (element.value.length < rules[rule].value) {
                                    isValid = false;
                                    errorMessage = rules[rule].message;
                                }
                                break;
                            case 'maxLength':
                                if (element.value.length > rules[rule].value) {
                                    isValid = false;
                                    errorMessage = rules[rule].message;
                                }
                                break;
                            case 'min':
                                if (Number(element.value) < rules[rule].value) {
                                    isValid = false;
                                    errorMessage = rules[rule].message;
                                }
                                break;
                            case 'max':
                                if (Number(element.value) > rules[rule].value) {
                                    isValid = false;
                                    errorMessage = rules[rule].message;
                                }
                                break;
                            case 'fileType':
                                if (element.files.length > 0) {
                                    if (!rules[rule].value.includes(element.files[0].type)) {
                                        isValid = false;
                                        errorMessage = rules[rule].message;
                                    }
                                }
                                break;
                            case 'fileSize':
                                if (element.files.length > 0) {
                                    if (element.files[0].size > rules[rule].value) {
                                        isValid = false;
                                        errorMessage = rules[rule].message;
                                    }
                                }
                                break;
                        }
                        if (!isValid) break;
                    }

                    // Show/hide error
                    if (!isValid && showError) {
                        element.classList.add('is-invalid');
                        element.classList.add('error-shake');
                        
                        // Add error message
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'field-error';
                        errorDiv.textContent = errorMessage;
                        element.parentElement.appendChild(errorDiv);
                        
                        // Focus the invalid field
                        element.focus();
                        
                        // Remove shake animation after it completes
                        setTimeout(() => {
                            element.classList.remove('error-shake');
                        }, 500);
                    } else {
                        element.classList.remove('is-invalid');
                    }

                    return isValid;
                }

                // Validate all fields
                function validateForm() {
                    let isValid = true;
                    
                    // Validate basic information
                    for (const fieldName in fields) {
                        if (!validateField(fieldName, true)) {
                            isValid = false;
                        }
                    }
                    
                    // Validate class days
                    const days = document.querySelectorAll('input[name="days[]"]:checked').length;
                    if (days === 0) {
                        const daysSection = document.querySelector('.btn-group[role="group"]');
                        daysSection.classList.add('is-invalid', 'error-shake');
                        
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'field-error';
                        errorDiv.textContent = 'Please select at least one class day';
                        daysSection.parentElement.appendChild(errorDiv);
                        
                        isValid = false;
                        
                        setTimeout(() => {
                            daysSection.classList.remove('error-shake');
                        }, 500);
                    }
                    
                    // Validate time slots
                    const slots = document.getElementsByClassName('time-slot');
                    Array.from(slots).forEach(slot => {
                        const start = slot.querySelector('input[name="startTime[]"]');
                        const end = slot.querySelector('input[name="endTime[]"]');
                        
                        if (start.value && end.value) {
                            const startTime = new Date(`1970-01-01T${start.value}`);
                            const endTime = new Date(`1970-01-01T${end.value}`);
                            
                            if (startTime >= endTime) {
                                end.classList.add('is-invalid', 'error-shake');
                                
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'field-error';
                                errorDiv.textContent = 'End time must be after start time';
                                end.parentElement.appendChild(errorDiv);
                                
                                isValid = false;
                                
                                setTimeout(() => {
                                    end.classList.remove('error-shake');
                                }, 500);
                            }
                        }
                    });
                    
                    // Validate price for paid classes
                    if (document.getElementById('pricingPaid').checked) {
                        const price = document.getElementById('price');
                        const priceValue = parseFloat(price.value);
                        if (isNaN(priceValue) || priceValue <= 0) {
                            price.classList.add('is-invalid', 'error-shake');
                            
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'field-error';
                            errorDiv.textContent = 'Please enter a valid price';
                            price.parentElement.appendChild(errorDiv);
                            
                            isValid = false;
                            
                            setTimeout(() => {
                                price.classList.remove('error-shake');
                            }, 500);
                        }
                    }
                    
                    return isValid;
                }

                // Add input event listeners for real-time validation
                for (const fieldName in fields) {
                    const element = fields[fieldName].element;
                    element.addEventListener('input', () => validateField(fieldName, true));
                    element.addEventListener('blur', () => validateField(fieldName, true));
                }

                // Form submission
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            }

            function setupImagePreview() {
                const input = document.getElementById('classCover');
                const preview = document.getElementById('coverPreview');
                const previewContainer = document.getElementById('imagePreview');
                
                input.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        
                        // Validate file size
                        if (file.size > 2 * 1024 * 1024) {
                            alert('File size must be less than 2MB');
                            this.value = '';
                            return;
                        }
                        
                        // Validate file type
                        if (!file.type.match('image.*')) {
                            alert('Please upload an image file');
                            this.value = '';
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            previewContainer.classList.remove('d-none');
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            function setupPricing() {
                const pricingFree = document.getElementById('pricingFree');
                const pricingPaid = document.getElementById('pricingPaid');
                const price = document.getElementById('price');
                
                function togglePrice() {
                    price.disabled = pricingFree.checked;
                    if (pricingFree.checked) {
                        price.value = '0.00';
                    }
                    updatePreview();
                }
                
                pricingFree.addEventListener('change', togglePrice);
                pricingPaid.addEventListener('change', togglePrice);
                price.addEventListener('input', updatePreview);
            }

            function addTimeSlot() {
                const container = document.getElementById('timeSlots');
                const slots = container.getElementsByClassName('time-slot');
                
                const template = `
                    <div class="time-slot">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-btn">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="startTime[]" class="form-control" placeholder="--:--" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="endTime[]" class="form-control" placeholder="--:--" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', template);
                initializeClockpicker();
                
                // Show remove buttons if more than one slot
                if (slots.length > 0) {
                    document.querySelectorAll('.remove-btn').forEach(btn => btn.classList.remove('d-none'));
                }
                
                // Add remove event listener
                container.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-btn')) {
                        const slot = e.target.closest('.time-slot');
                        slot.classList.add('fade-out');
                        setTimeout(() => {
                            slot.remove();
                            updateRemoveButtons();
                        }, 200);
                    }
                });
            }

            function updateRemoveButtons() {
                const slots = document.getElementsByClassName('time-slot');
                const removeButtons = document.querySelectorAll('.remove-btn');
                
                removeButtons.forEach(btn => {
                    btn.classList.toggle('d-none', slots.length === 1);
                });
            }

            function updatePreview() {
                const title = document.getElementById('className').value || 'Class Name';
                const description = document.getElementById('description').value || 'Class description will appear here...';
                const maxStudents = document.getElementById('maxStudents').value || '0';
                const price = document.getElementById('pricingFree').checked ? 'Free' : `₱${document.getElementById('price').value || '0.00'}`;
                
                // Calculate number of sessions
                const days = document.querySelectorAll('input[name="days[]"]:checked').length;
                const slots = document.getElementsByClassName('time-slot').length;
                const startDate = new Date(document.getElementById('startDate').value);
                const endDate = new Date(document.getElementById('endDate').value);
                let sessions = 0;
                
                if (!isNaN(startDate) && !isNaN(endDate) && days > 0) {
                    const weeks = Math.ceil((endDate - startDate) / (7 * 24 * 60 * 60 * 1000));
                    sessions = weeks * days * slots;
                }
                
                document.getElementById('previewTitle').textContent = title;
                document.getElementById('previewDescription').textContent = description;
                document.getElementById('previewStudents').textContent = `${maxStudents} students max`;
                document.getElementById('previewDuration').textContent = `${sessions} sessions`;
                document.getElementById('previewPrice').textContent = price;
            }

            function setupCharacterCount() {
                const description = document.getElementById('description');
                const container = document.getElementById('descriptionCountContainer');
                const counter = document.getElementById('descriptionCount');
                // counter.className = 'character-count';
                // container.style.position = 'relative';
                // container.appendChild(counter);

                function updateCount() {
                    const count = description.value.length;
                    const remaining = 500 - count;
                    counter.textContent = `${count}`;
                    counter.className = 
                        (remaining < 50 ? 'danger' : 
                         remaining < 100 ? 'warning' : '');
                }

                description.addEventListener('input', updateCount);
                updateCount();
            }

            function validateTimeSlot(input) {
                const slot = input.closest('.time-slot');
                const start = slot.querySelector('input[name="startTime[]"]');
                const end = slot.querySelector('input[name="endTime[]"]');
                
                if (start.value && end.value) {
                    const startTime = new Date(`1970-01-01T${start.value}`);
                    const endTime = new Date(`1970-01-01T${end.value}`);
                    
                    if (startTime >= endTime) {
                        end.classList.add('is-invalid');
                        showTooltip(end, 'End time must be after start time');
                    } else {
                        end.classList.remove('is-invalid');
                        hideTooltip(end);
                    }
                }
            }

            function showTooltip(element, message) {
                const tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip) {
                    tooltip.dispose();
                }
                new bootstrap.Tooltip(element, {
                    title: message,
                    placement: 'top',
                    trigger: 'manual'
                }).show();
            }

            function hideTooltip(element) {
                const tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip) {
                    tooltip.dispose();
                }
            }

            function initializeTooltips() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
    </script>
</body>
</html>
