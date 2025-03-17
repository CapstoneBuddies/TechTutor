<?php 
require_once '../../backends/main.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id, $_SESSION['user']);
if (!$classDetails) {
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
    
    if ($result['success']) {
        // Redirect to class details page
        header("Location: details?id={$class_id}&updated=1");
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
    <title>TechTutor | Edit Class - <?php echo htmlspecialchars($classDetails['class_name']); ?></title>
    
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
                                    <input type="number" class="form-control" id="maxStudents" name="maxStudents" min="1" max="50" value="<?php echo (int)$classDetails['class_size']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Class Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($classDetails['class_desc']); ?></textarea>
                            </div>
                        </div>

                        <!-- Schedule Information (Read-only) -->
                        <div class="mb-4">
                            <h3>Class Schedule</h3>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Class schedule cannot be modified after creation. If you need to change the schedule, please create a new class.
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($classDetails['start_date'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>End Date:</strong> <?php echo date('F j, Y', strtotime($classDetails['end_date'])); ?></p>
                                </div>
                            </div>
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
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="<?php echo number_format($classDetails['price'], 2, '.', ''); ?>">
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

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide price field based on pricing type
        document.querySelectorAll('input[name="pricingType"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const priceField = document.getElementById('priceField');
                const priceInput = document.getElementById('price');
                
                if (this.value === 'paid') {
                    priceField.style.display = 'block';
                    priceInput.required = true;
                } else {
                    priceField.style.display = 'none';
                    priceInput.required = false;
                    priceInput.value = '0';
                }
            });
        });

        // Form validation
        document.getElementById('editClassForm').addEventListener('submit', function(e) {
            const pricingType = document.querySelector('input[name="pricingType"]:checked').value;
            const price = document.getElementById('price').value;
            
            if (pricingType === 'paid' && (!price || parseFloat(price) <= 0)) {
                e.preventDefault();
                alert('Please enter a valid price for paid classes');
            }
        });
    </script>
</body>
</html>