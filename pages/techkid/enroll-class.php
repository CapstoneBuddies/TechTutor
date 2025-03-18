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
    <body data-base="<?php echo BASE; ?>">
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
                                <a href="enrollments/class?id=<?php echo htmlspecialchars($class['class_id']); ?>" class="card h-100 class-card shadow-sm">
                                    <img src="<?php echo !empty($class['thumbnail']) ? CLASS_IMG . $class['thumbnail'] : CLASS_IMG . 'default.png'; ?>" 
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
                                            <img src="<?php echo !empty($class['tutor_avatar']) ? USER_IMG . $class['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
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
                                </a>//
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
                                    <img src="<?php echo !empty($class['thumbnail']) ? CLASS_IMG . $class['thumbnail'] : CLASS_IMG . 'default.png'; ?>" 
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
                                            <img src="<?php echo !empty($class['tutor_avatar']) ? USER_IMG . $class['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
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
    </main> 
    </div> 
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>