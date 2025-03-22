<?php 
require_once '../../backends/main.php';
require_once BACKEND.'student_management.php';

if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit;
}
$title = "View All Available Classes";
$available_classes = getCurrentActiveClass();
$enrolled_classes = getStudentClasses($_SESSION['user']);

?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <link href="<?php echo CSS; ?>techkid-common.css" rel="stylesheet">
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Available Classes</h1>
                <p class="text-muted">Browse and enroll in upcoming classes</p>
            </div>

            <!-- Search and Filter Section -->
            <div class="content-card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" 
                                       class="form-control" 
                                       id="searchInput" 
                                       placeholder="Search classes...">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                                <button class="btn btn-outline-primary active" data-filter="all">
                                    All Classes
                                </button>
                                <?php foreach ($subjects as $subject): ?>
                                <button class="btn btn-outline-primary" 
                                        data-filter="<?php echo htmlspecialchars($subject['id']); ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classes Grid -->
            <div class="row g-4" id="classesGrid">
                <?php if (empty($classes)): ?>
                <div class="col-12">
                    <div class="content-card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-calendar2-x text-muted" style="font-size: 48px;"></i>
                            <h3 class="h5 mt-3">No Classes Available</h3>
                            <p class="text-muted">Check back later for new classes</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                    <div class="col-md-6 col-lg-4" 
                         data-subject="<?php echo htmlspecialchars($class['subject_id']); ?>">
                        <div class="content-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($class['subject_name']); ?>
                                    </span>
                                    <span class="badge bg-<?php echo $class['slots_left'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $class['slots_left'] > 0 ? $class['slots_left'] . ' slots left' : 'Full'; ?>
                                    </span>
                                </div>
                                <h5 class="card-title mb-3">
                                    <?php echo htmlspecialchars($class['title']); ?>
                                </h5>
                                <div class="tutor mb-3">
                                    <img src="<?php echo !empty($class['tutor_avatar']) ? BASE . $class['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                         alt="Tutor" 
                                         class="tutor-avatar me-2">
                                    <div>
                                        <p class="mb-1"><?php echo htmlspecialchars($class['tutor_name']); ?></p>
                                        <div class="rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star-fill <?php echo $i <= $class['tutor_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-1">(<?php echo number_format($class['tutor_rating'], 1); ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="class-info mb-3">
                                    <p class="mb-2">
                                        <i class="bi bi-calendar me-2"></i>
                                        <?php echo date('l, M d, Y', strtotime($class['start_time'])); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-clock me-2"></i>
                                        <?php echo date('h:i A', strtotime($class['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($class['end_time'])); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-currency-dollar me-2"></i>
                                        <?php echo number_format($class['price'], 2); ?> USD
                                    </p>
                                </div>
                                <div class="d-grid">
                                    <?php if ($class['slots_left'] > 0): ?>
                                    <button class="btn btn-primary" 
                                            onclick="showEnrollmentModal('<?php echo $class['id']; ?>')">
                                        Enroll Now
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        Class Full
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enrollment Modal -->
        <div class="modal fade" id="enrollmentModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enroll in Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="enrollmentDetails"></div>
                        <form id="enrollmentForm" class="mt-4">
                            <input type="hidden" id="classId" name="class_id">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="paypal">PayPal</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="termsCheck" 
                                       required>
                                <label class="form-check-label" for="termsCheck">
                                    I agree to the terms and conditions
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Confirm Enrollment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            const enrollmentModal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
            
            // Search and filter functionality
            const searchInput = document.getElementById('searchInput');
            const filterButtons = document.querySelectorAll('[data-filter]');
            const classesGrid = document.getElementById('classesGrid');
            
            let currentFilter = 'all';
            
            searchInput.addEventListener('input', filterClasses);
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    filterClasses();
                });
            });
            
            function filterClasses() {
                const searchTerm = searchInput.value.toLowerCase();
                const classes = classesGrid.querySelectorAll('.col-md-6');
                
                classes.forEach(classCard => {
                    const title = classCard.querySelector('.card-title').textContent.toLowerCase();
                    const subject = classCard.dataset.subject;
                    
                    const matchesSearch = title.includes(searchTerm);
                    const matchesFilter = currentFilter === 'all' || subject === currentFilter;
                    
                    classCard.style.display = matchesSearch && matchesFilter ? '' : 'none';
                });
            }
            
            function showEnrollmentModal(classId) {
                fetch(`<?php echo BASE; ?>backends/api/get-class-details.php?id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('classId').value = classId;
                            document.getElementById('enrollmentDetails').innerHTML = `
                                <div class="text-center mb-4">
                                    <img src="${data.class.thumbnail || '<?php echo BASE; ?>assets/images/default-class.jpg'}" 
                                         class="img-fluid rounded mb-3" 
                                         style="max-height: 200px; object-fit: cover;" 
                                         alt="${data.class.title}">
                                    <h4>${data.class.title}</h4>
                                    <p class="text-muted mb-0">${data.class.subject_name}</p>
                                </div>
                                <div class="d-flex justify-content-between mb-4">
                                    <div>
                                        <p class="mb-1">
                                            <i class="bi bi-calendar me-2"></i>${data.class.date}
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-clock me-2"></i>${data.class.time}
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <p class="mb-1">Price</p>
                                        <h5 class="mb-0">$${parseFloat(data.class.price).toFixed(2)}</h5>
                                    </div>
                                </div>
                            `;
                            enrollmentModal.show();
                        } else {
                            showToast('error', data.message || 'Failed to load class details');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'Failed to load class details');
                    });
            }
            
            // Handle enrollment form submission
            document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('<?php echo BASE; ?>backends/api/enroll-class.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        enrollmentModal.hide();
                        showToast('success', 'Successfully enrolled in class!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('error', data.message || 'Failed to enroll in class');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Failed to enroll in class');
                });
            });
        </script>
    </body>
</html>