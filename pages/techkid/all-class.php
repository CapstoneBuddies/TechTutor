<?php 
    require_once(__DIR__ . '/../../backends/main.php');

    // Fetch active classes
    function getActiveClasses() {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT c.class_id, c.class_name, c.class_desc, c.start_date, c.end_date, 
                       s.subject_name, u.first_name, u.last_name, c.thumbnail
                FROM class c
                JOIN subject s ON c.subject_id = s.subject_id
                JOIN users u ON c.tutor_id = u.uid
                WHERE c.status = 'active'
                ORDER BY c.start_date ASC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Error fetching active classes: " . $e->getMessage(), 'database');
            return [];
        }
    }

    $title = "View All Available Classes";
    $classes = getActiveClasses();
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <!-- Main Dashboard Content -->
        <main class="dashboard-content">
            <div class="container mt-4">
                <h2 class="mb-4">Available Classes</h2>

                <?php if (empty($classes)): ?>
                    <div class="alert alert-warning">No active classes available at the moment.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($classes as $class): ?>
                            <div class="col-md-4">
                                <div class="card shadow-sm">
                                    <img src="<?php echo $class['thumbnail'] ?: BASE . 'assets/img/default-class.jpg'; ?>" 
                                         class="card-img-top" alt="Class Thumbnail">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                        <p class="card-text">
                                            <strong>Subject:</strong> <?php echo htmlspecialchars($class['subject_name']); ?><br>
                                            <strong>Instructor:</strong> <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?><br>
                                            <strong>Start:</strong> <?php echo date('M d, Y', strtotime($class['start_date'])); ?><br>
                                            <strong>End:</strong> <?php echo date('M d, Y', strtotime($class['end_date'])); ?>
                                        </p>
                                        <a href="<?php echo BASE; ?>dashboard/class-details?id=<?php echo $class['class_id']; ?>" 
                                           class="btn btn-info">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <!-- END Main Dashboard Content -->

        <?php include ROOT_PATH . '/components/footer.php'; ?>
    </body>
</html>
