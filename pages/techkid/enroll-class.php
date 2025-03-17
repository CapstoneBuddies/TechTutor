<?php 
require_once '../../backends/main.php';

if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    header('Location: ' . BASE . 'login');
    exit;
}

$available_classes = [];
$enrolled_classes = [];
try {
    // Get available classes for enrollment
    $stmt = $conn->prepare("
        SELECT 
            c.class_id,
            c.class_name,
            c.description,
            c.thumbnail,
            c.status,
            s.subject_name,
            co.course_name,
            u.first_name AS tutor_first_name,
            u.last_name AS tutor_last_name,
            u.profile_picture AS tutor_avatar,
            COUNT(DISTINCT cs.user_id) as enrolled_students
        FROM class c
        JOIN subject s ON c.subject_id = s.subject_id
        JOIN course co ON s.course_id = co.course_id
        JOIN users u ON c.tutor_id = u.uid
        LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
        WHERE c.status = 'active'
        GROUP BY c.class_id
        ORDER BY c.created_at DESC
    ");
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch available classes: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Check if user is already enrolled
        $stmt2 = $conn->prepare("
            SELECT 1 FROM class_schedule 
            WHERE class_id = ? AND user_id = ? AND role = 'STUDENT'
        ");
        $stmt2->bind_param("ii", $row['class_id'], $_SESSION['user']);
        $stmt2->execute();
        $isEnrolled = $stmt2->get_result()->num_rows > 0;
        
        if ($isEnrolled) {
            $enrolled_classes[] = $row;
        } else {
            $available_classes[] = $row;
        }
    }
} catch (Exception $e) {
    log_error("Error in enroll-class page: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<style>
.class-card {
    transition: transform 0.2s;
    cursor: pointer;
}
.class-card:hover {
    transform: translateY(-5px);
}
.class-card .card-img-top {
    height: 200px;
    object-fit: cover;
}
.tutor-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}
.enrolled-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1;
}
</style>
<body data-bs-theme="<?php echo isset($_COOKIE['theme']) ? htmlspecialchars($_COOKIE['theme']) : 'light'; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-2">Available Classes</h1>
                        <p class="text-muted mb-0">Browse and enroll in classes that match your interests</p>
                    </div>
                    <a href="class" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to My Classes
                    </a>
                </div>

                <!-- Available Classes -->
                <?php if (empty($available_classes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-mortarboard text-muted" style="font-size: 48px;"></i>
                        <h3 class="mt-3">No Available Classes</h3>
                        <p class="text-muted">Check back later for new class offerings!</p>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                        <?php foreach ($available_classes as $class): ?>
                            <div class="col">
                                <div class="card h-100 class-card shadow-sm" 
                                     onclick="showEnrollModal(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                                    <img src="<?php echo !empty($class['thumbnail']) ? BASE . $class['thumbnail'] : BASE . 'assets/images/default-class.jpg'; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($class['class_name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars($class['course_name']); ?> • 
                                            <?php echo htmlspecialchars($class['subject_name']); ?>
                                        </p>
                                        <p class="card-text small">
                                            <?php echo htmlspecialchars(substr($class['description'], 0, 100)) . '...'; ?>
                                        </p>
                                        <div class="d-flex align-items-center mt-3">
                                            <img src="<?php echo !empty($class['tutor_avatar']) ? BASE . $class['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                                 class="tutor-avatar me-2" 
                                                 alt="Tutor">
                                            <div class="small">
                                                <p class="mb-0 fw-medium">
                                                    <?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <?php echo $class['enrolled_students']; ?> students enrolled
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Recently Enrolled Classes -->
                <?php if (!empty($enrolled_classes)): ?>
                    <h2 class="h4 mt-5 mb-4">Recently Enrolled Classes</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                        <?php foreach ($enrolled_classes as $class): ?>
                            <div class="col">
                                <div class="card h-100 class-card shadow-sm">
                                    <div class="enrolled-badge">
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Enrolled
                                        </span>
                                    </div>
                                    <img src="<?php echo !empty($class['thumbnail']) ? BASE . $class['thumbnail'] : BASE . 'assets/images/default-class.jpg'; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($class['class_name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars($class['course_name']); ?> • 
                                            <?php echo htmlspecialchars($class['subject_name']); ?>
                                        </p>
                                        <p class="card-text small">
                                            <?php echo htmlspecialchars(substr($class['description'], 0, 100)) . '...'; ?>
                                        </p>
                                        <div class="d-flex align-items-center mt-3">
                                            <img src="<?php echo !empty($class['tutor_avatar']) ? BASE . $class['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                                 class="tutor-avatar me-2" 
                                                 alt="Tutor">
                                            <div class="small">
                                                <p class="mb-0 fw-medium">
                                                    <?php echo htmlspecialchars($class['tutor_first_name'] . ' ' . $class['tutor_last_name']); ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <?php echo $class['enrolled_students']; ?> students enrolled
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div class="modal fade" id="enrollModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enroll in Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="enrollModalContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEnrollBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Confirm Enrollment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        let selectedClass = null;
        const enrollModal = new bootstrap.Modal(document.getElementById('enrollModal'));
        
        function showEnrollModal(classData) {
            selectedClass = classData;
            const modalContent = document.getElementById('enrollModalContent');
            
            modalContent.innerHTML = `
                <div class="text-center mb-4">
                    <img src="${classData.thumbnail || '<?php echo BASE; ?>assets/images/default-class.jpg'}" 
                         class="img-fluid rounded mb-3" 
                         style="max-height: 200px; object-fit: cover;" 
                         alt="${classData.class_name}">
                    <h4>${classData.class_name}</h4>
                    <p class="text-muted mb-0">${classData.course_name} • ${classData.subject_name}</p>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    By enrolling in this class, you agree to:
                    <ul class="mb-0 mt-2">
                        <li>Attend scheduled sessions on time</li>
                        <li>Complete assigned coursework</li>
                        <li>Participate actively in class discussions</li>
                    </ul>
                </div>
            `;
            
            enrollModal.show();
        }
        
        document.getElementById('confirmEnrollBtn').addEventListener('click', function() {
            if (!selectedClass) return;
            
            const button = this;
            const spinner = button.querySelector('.spinner-border');
            button.disabled = true;
            spinner.classList.remove('d-none');
            
            fetch('<?php echo BASE; ?>backends/api/enroll-class.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    class_id: selectedClass.class_id,
                    user_id: <?php echo $_SESSION['user']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Successfully enrolled in class!');
                    setTimeout(() => window.location.href = 'class.php', 1500);
                } else {
                    throw new Error(data.message || 'Failed to enroll in class');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', error.message);
                button.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    </script>
</body>
</html>