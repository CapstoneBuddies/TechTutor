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
$title = $classDetails['class_name'];
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .form-section {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .form-section:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-section h3 {
            color: var(--bs-primary);
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
        }
        .no-resize {
            resize: none;
        }
        .tip-card {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .tip-card:hover {
            transform: translateX(5px);
        }
        .tip-card.warning { border-left-color: var(--bs-warning); }
        .tip-card.info { border-left-color: var(--bs-info); }
        .tip-card.success { border-left-color: var(--bs-success); }
        .tip-card.primary { border-left-color: var(--bs-primary); }
        .preview-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .preview-section h4 {
            color: var(--bs-primary);
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .dashboard-card {
                padding: 1rem;
            }
            .form-section {
                padding: 1rem;
            }
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
                                        <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="./">My Classes</a></li>
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
                <div class="col-lg-8">
                    <form id="editClassForm" method="POST" action="">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3><i class="bi bi-info-circle"></i> Basic Information</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="className" class="form-label">Class Name</label>
                                    <input type="text" class="form-control" id="className" name="className" 
                                           value="<?php echo htmlspecialchars($classDetails['class_name']); ?>" 
                                           required
                                           minlength="5"
                                           maxlength="100">
                                    <div class="form-text">Choose a clear and descriptive name (5-100 characters)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="maxStudents" class="form-label">Maximum Students</label>
                                    <input type="number" class="form-control" id="maxStudents" name="maxStudents" 
                                           min="1" max="50" 
                                           value="<?php echo (int)$classDetails['class_size']; ?>" 
                                           required>
                                    <div class="form-text">Set a limit between 1-50 students</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Class Description</label>
                                <textarea class="form-control no-resize" id="description" name="description" 
                                          rows="4" required minlength="50" maxlength="500"><?php echo htmlspecialchars($classDetails['class_desc']); ?></textarea>
                                <div class="form-text">
                                    <span id="descriptionCount">0</span>/500 characters
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Information -->
                        <div class="form-section">
                            <h3><i class="bi bi-calendar"></i> Class Schedule</h3>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Schedule changes require managing individual sessions. Visit the 
                                <a href="./details/schedules?id=<?php echo htmlspecialchars($class_id)?>" class="alert-link">
                                    Manage Schedule
                                </a> section to make changes.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('F j, Y', strtotime($classDetails['start_date'])); ?>" 
                                               readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('F j, Y', strtotime($classDetails['end_date'])); ?>" 
                                               readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="form-section">
                            <h3><i class="bi bi-currency-dollar"></i> Pricing</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="pricingType" 
                                               id="pricingFree" value="free" 
                                               <?php echo $classDetails['is_free'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pricingFree">
                                            <i class="bi bi-gift text-success"></i> Free Class
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pricingType" 
                                               id="pricingPaid" value="paid" 
                                               <?php echo !$classDetails['is_free'] ? 'checked' : ''; ?>>
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
                                               min="0" step="0.01" 
                                               value="<?php echo number_format($classDetails['price'], 2, '.', ''); ?>"
                                               <?php echo $classDetails['is_free'] ? 'disabled' : ''; ?>>
                                    </div>
                                    <div class="form-text">Set a reasonable price for your class</div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="form-section">
                            <h3><i class="bi bi-eye"></i> Preview</h3>
                            <div class="preview-section">
                                <h4 id="previewTitle"></h4>
                                <p id="previewDescription" class="text-muted"></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span id="previewStudents" class="badge bg-primary"></span>
                                    <span id="previewPrice" class="badge bg-success"></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="details?id=<?php echo $class_id; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Details
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tips Section -->
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <h3><i class="bi bi-lightbulb"></i> Editing Tips</h3>
                        <div class="tip-card warning">
                            <h5><i class="bi bi-lightbulb"></i> Class Name</h5>
                            <p class="mb-0">Keep your class name clear and descriptive. Use keywords that students might search for.</p>
                        </div>
                        <div class="tip-card info">
                            <h5><i class="bi bi-people"></i> Class Size</h5>
                            <p class="mb-0">Consider your teaching style and current enrollment when adjusting the maximum class size.</p>
                        </div>
                        <div class="tip-card success">
                            <h5><i class="bi bi-currency-dollar"></i> Pricing</h5>
                            <p class="mb-0">Price changes will only affect new enrollments. Current students will maintain their original pricing.</p>
                        </div>
                        <div class="tip-card primary">
                            <h5><i class="bi bi-calendar-check"></i> Schedule</h5>
                            <p class="mb-0">Need to change the schedule? Use the Schedule Management section for detailed control.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('editClassForm');
                const description = document.getElementById('description');
                const descriptionCount = document.getElementById('descriptionCount');
                const pricingFree = document.getElementById('pricingFree');
                const pricingPaid = document.getElementById('pricingPaid');
                const priceField = document.getElementById('priceField');
                const price = document.getElementById('price');

                // Update character count
                function updateCharCount() {
                    const count = description.value.length;
                    descriptionCount.textContent = count;
                    descriptionCount.className = count > 500 ? 'text-danger' : 'text-muted';
                }

                // Toggle price field
                function togglePriceField() {
                    const isPaid = pricingPaid.checked;
                    price.disabled = !isPaid;
                    if (!isPaid) price.value = '0.00';
                    updatePreview();
                }

                // Update preview
                function updatePreview() {
                    document.getElementById('previewTitle').textContent = 
                        document.getElementById('className').value || 'Class Name';
                    document.getElementById('previewDescription').textContent = 
                        description.value || 'Class description will appear here...';
                    document.getElementById('previewStudents').textContent = 
                        `${document.getElementById('maxStudents').value} Students Max`;
                    document.getElementById('previewPrice').textContent = 
                        pricingFree.checked ? 'Free' : `₱${price.value || '0.00'}`;
                }

                // Event listeners
                description.addEventListener('input', updateCharCount);
                pricingFree.addEventListener('change', togglePriceField);
                pricingPaid.addEventListener('change', togglePriceField);
                form.addEventListener('input', updatePreview);

                // Initialize
                updateCharCount();
                togglePriceField();
                updatePreview();

                // Form validation
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        </script>
    </body>
</html>