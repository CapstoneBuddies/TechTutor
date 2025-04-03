<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is an ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

$title = "Edit " . htmlspecialchars($classDetails['class_name']);
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
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-1">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="././">Classes</a></li>
                                            <li class="breadcrumb-item d-none d-md-inline"><a href="./?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                            <li class="breadcrumb-item active">Edit</li>
                                        </ol>
                                    </nav>
                                    <h2 class="page-header mb-0">Edit Class</h2>
                                    <p class="text-muted">Update class details for <?php echo htmlspecialchars($classDetails['subject_name']); ?></p>
                                </div>
                                <div class="mt-2 mt-md-0">
                                    <a href="./?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Class
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="alerts-container"></div>

            <!-- Class Edit Form -->
            <div class="row mt-4">
                <div class="col-lg-8">
                    <form id="editClassForm">
                        <input type="hidden" id="class_id" value="<?php echo $class_id; ?>">
                        
                        <!-- Basic Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title mb-0"><i class="bi bi-info-circle"></i> Basic Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="className" class="form-label">Class Name</label>
                                        <input type="text" class="form-control" id="className" name="class_name" 
                                               value="<?php echo htmlspecialchars($classDetails['class_name']); ?>" 
                                               required
                                               minlength="5"
                                               maxlength="100">
                                        <div class="form-text">Choose a clear and descriptive name (5-100 characters)</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="maxStudents" class="form-label">Maximum Students</label>
                                        <input type="number" class="form-control" id="maxStudents" name="class_size" 
                                               min="1" max="50" 
                                               value="<?php echo (int)$classDetails['class_size']; ?>" 
                                               required>
                                        <div class="form-text">Set a limit between 1-50 students</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Class Description</label>
                                    <textarea class="form-control" id="description" name="class_desc" 
                                              rows="4" required minlength="50" maxlength="500"><?php echo htmlspecialchars($classDetails['class_desc']); ?></textarea>
                                    <div class="form-text">
                                        <span id="descriptionCount">0</span>/500 characters
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title mb-0"><i class="bi bi-calendar"></i> Class Schedule</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="startDate" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="startDate" name="start_date"
                                                   value="<?php echo date('Y-m-d', strtotime($classDetails['start_date'])); ?>" 
                                                   required>
                                            <div class="form-text">Class start date</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="endDate" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="endDate" name="end_date"
                                                   value="<?php echo date('Y-m-d', strtotime($classDetails['end_date'])); ?>" 
                                                   required>
                                            <div class="form-text">Class end date</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Individual session scheduling should be managed in the 
                                    <a href="sessions?id=<?php echo $class_id; ?>" class="alert-link">
                                        Sessions Management
                                    </a> page.
                                </div>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title mb-0"><i class="bi bi-currency-dollar"></i> Pricing</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="is_free" 
                                                   id="pricingFree" value="1" 
                                                   <?php echo $classDetails['is_free'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pricingFree">
                                                <i class="bi bi-gift text-success"></i> Free Class
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_free" 
                                                   id="pricingPaid" value="0" 
                                                   <?php echo !$classDetails['is_free'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pricingPaid">
                                                <i class="bi bi-cash text-primary"></i> Paid Class
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="priceField" <?php echo $classDetails['is_free'] ? 'style="display:none;"' : ''; ?>>
                                        <label for="price" class="form-label">Price (USD)</label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   min="0.01" step="0.01" 
                                                   value="<?php echo number_format($classDetails['price'], 2); ?>"
                                                   <?php echo $classDetails['is_free'] ? 'disabled' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title mb-0"><i class="bi bi-toggle-on"></i> Class Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Current Status</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="statusPending" value="pending" 
                                                   <?php echo $classDetails['status'] === 'pending' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="statusPending">
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="statusActive" value="active" 
                                                   <?php echo $classDetails['status'] === 'active' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="statusActive">
                                                <span class="badge bg-success">Active</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="statusCompleted" value="completed" 
                                                   <?php echo $classDetails['status'] === 'completed' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="statusCompleted">
                                                <span class="badge bg-secondary">Completed</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="statusCancelled" value="cancelled" 
                                                   <?php echo $classDetails['status'] === 'cancelled' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="statusCancelled">
                                                <span class="badge bg-danger">Cancelled</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button type="button" id="saveChangesBtn" class="btn btn-primary me-2">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <a href="./?id=<?php echo $class_id; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="col-lg-4">
                    <!-- Class Info Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0"><i class="bi bi-info-circle"></i> Class Information</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Created</span>
                                    <span><?php echo date('M d, Y', strtotime($classDetails['created_at'])); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Subject</span>
                                    <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($classDetails['subject_name']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Teacher</span>
                                    <span><?php echo htmlspecialchars($classDetails['techguru_name']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Sessions</span>
                                    <span><?php echo (int)$classDetails['total_sessions']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Duration</span>
                                    <span>
                                        <?php 
                                            $start = new DateTime($classDetails['start_date']);
                                            $end = new DateTime($classDetails['end_date']);
                                            $duration = $start->diff($end);
                                            echo $duration->days + 1 . ' days';
                                        ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0"><i class="bi bi-link-45deg"></i> Quick Links</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="./?id=<?php echo $class_id; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="bi bi-info-circle me-3 text-primary"></i> View Class Details
                                </a>
                                <a href="sessions?id=<?php echo $class_id; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="bi bi-calendar-event me-3 text-primary"></i> Manage Sessions
                                </a>
                                <a href="enroll?id=<?php echo $class_id; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="bi bi-people me-3 text-primary"></i> Manage Enrollments
                                </a>
                                <a href="recordings?id=<?php echo $class_id; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="bi bi-camera-video me-3 text-primary"></i> View Recordings
                                </a>
                                <a href="feedback?id=<?php echo $class_id; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <i class="bi bi-chat-quote me-3 text-primary"></i> View Feedback
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include ROOT_PATH . '/components/footer.php'; ?>
        
        <script src="<?php echo BASE; ?>assets/js/admin-class-management.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const classId = <?php echo $class_id; ?>;
                
                // Character count for description
                const description = document.getElementById('description');
                const descriptionCount = document.getElementById('descriptionCount');
                
                // Update description character count
                function updateDescriptionCount() {
                    const count = description.value.length;
                    descriptionCount.textContent = count;
                    
                    if (count > 500) {
                        descriptionCount.classList.add('text-danger');
                    } else {
                        descriptionCount.classList.remove('text-danger');
                    }
                }
                
                // Initial count
                updateDescriptionCount();
                
                // Update count on input
                description.addEventListener('input', updateDescriptionCount);
                
                // Toggle price field based on pricing type
                const freeRadio = document.getElementById('pricingFree');
                const paidRadio = document.getElementById('pricingPaid');
                const priceField = document.getElementById('priceField');
                const priceInput = document.getElementById('price');
                
                freeRadio.addEventListener('change', function() {
                    if (this.checked) {
                        priceField.style.display = 'none';
                        priceInput.disabled = true;
                    }
                });
                
                paidRadio.addEventListener('change', function() {
                    if (this.checked) {
                        priceField.style.display = 'block';
                        priceInput.disabled = false;
                    }
                });
                
                // Handle form submission
                const form = document.getElementById('editClassForm');
                const saveBtn = document.getElementById('saveChangesBtn');
                
                saveBtn.addEventListener('click', function() {
                    // Validate the form
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    // Start of date validations
                    const startDate = new Date(document.getElementById('startDate').value);
                    const endDate = new Date(document.getElementById('endDate').value);
                    
                    if (endDate < startDate) {
                        ClassManager.showAlert('End date cannot be earlier than start date', 'danger');
                        return;
                    }
                    
                    // Prepare form data
                    const formData = {
                        class_id: classId,
                        class_name: document.getElementById('className').value,
                        class_desc: document.getElementById('description').value,
                        class_size: document.getElementById('maxStudents').value,
                        start_date: document.getElementById('startDate').value,
                        end_date: document.getElementById('endDate').value,
                        is_free: document.querySelector('input[name="is_free"]:checked').value,
                        price: document.getElementById('price').value,
                        status: document.querySelector('input[name="status"]:checked').value,
                        tutor_id: '<?php echo $classDetails['tutor_id']; ?>',
                        subject_id: '<?php echo $classDetails['subject_id']; ?>'
                    };
                    
                    // Disable the save button while saving
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
                    
                    // Call the API through our utility
                    ClassManager.updateClass(formData, function(response) {
                        // Redirect to class details page
                        window.location.href = './?id=' + classId;
                    });
                });
            });
        </script>
        
        <style>
            /* Common Admin Class Pages Styling */
            .page-header {
                font-size: 1.75rem;
                font-weight: 600;
                color: var(--primary-color, #0052cc);
            }
            
            .breadcrumb {
                font-size: 0.875rem;
            }
            
            .breadcrumb-item.active {
                color: var(--primary-color, #0052cc);
                font-weight: 500;
            }
            
            .card {
                border-radius: 0.5rem;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                margin-bottom: 1.5rem;
                overflow: hidden;
            }
            
            .card-header {
                background-color: rgba(0, 0, 0, 0.02);
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 1rem;
            }
            
            .card-header .card-title {
                margin-bottom: 0;
                display: flex;
                align-items: center;
            }
            
            .card-header .card-title i {
                margin-right: 0.5rem;
                color: var(--primary-color, #0052cc);
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .btn {
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .btn i {
                font-size: 1.1em;
            }
            
            .list-group-item {
                border-color: rgba(0, 0, 0, 0.05);
            }
            
            /* Mobile Responsiveness */
            @media (max-width: 991.98px) {
                .container {
                    max-width: 100%;
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
                
                .card-body {
                    padding: 1rem;
                }
                
                .row {
                    margin-left: -0.5rem;
                    margin-right: -0.5rem;
                }
                
                .col, .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12, 
                .col-sm, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, 
                .col-md, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, 
                .col-lg, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12 {
                    padding-left: 0.5rem;
                    padding-right: 0.5rem;
                }
            }
            
            @media (max-width: 767.98px) {
                .page-header {
                    font-size: 1.5rem;
                }
                
                .btn {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
                }
                
                .d-flex {
                    flex-wrap: wrap;
                }
            }
            
            @media (max-width: 575.98px) {
                .card-header .card-title {
                    font-size: 1.1rem;
                }
                
                .py-4 {
                    padding-top: 1rem !important;
                    padding-bottom: 1rem !important;
                }
                
                .mt-4 {
                    margin-top: 1rem !important;
                }
                
                .mb-4 {
                    margin-bottom: 1rem !important;
                }
            }
        </style>
    </body>
</html> 