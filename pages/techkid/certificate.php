<?php 
    require_once '../../backends/main.php';
    require_once ROOT_PATH.'/backends/student_management.php';
    
    $certificates = [];
    try {
        // Get student's certificates using centralized function
        $certificates = getStudentCertificates($_SESSION['user']);
    } catch (Exception $e) {
        log_error("Certificates error: " . $e->getMessage(), "database");
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="page-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>My Certificates</h1>
            <p>View and download your achievement certificates</p>
        </div>

        <!-- Certificates Grid -->
        <div class="row">
            <?php if (empty($certificates)): ?>
            <div class="col-12">
                <div class="content-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-award text-muted" style="font-size: 48px;"></i>
                        <h3 class="mt-3">No Certificates Yet</h3>
                        <p class="text-muted">Complete your courses to earn certificates!</p>
                        <a href="<?php echo BASE; ?>techkid/class" class="btn btn-primary mt-3">View My Classes</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($certificates as $cert): ?>
                <div class="col-md-4 mb-4">
                    <div class="content-card certificate-card">
                        <img src="<?php echo $cert['preview_image']; ?>" alt="<?php echo $cert['course_name']; ?> Certificate">
                        <div class="card-body">
                            <div class="completion-date">
                                Completed on <?php echo date('M d, Y', strtotime($cert['completion_date'])); ?>
                            </div>
                            <h3 class="course-name"><?php echo $cert['course_name']; ?></h3>
                            <div class="actions">
                                <a href="<?php echo $cert['pdf_url']; ?>" class="btn btn-primary" download>
                                    <i class="bi bi-download me-2"></i>Download
                                </a>
                                <button class="btn btn-outline" onclick="shareCertificate('<?php echo $cert['id']; ?>')">
                                    <i class="bi bi-share me-2"></i>Share
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        function shareCertificate(certId) {
            // Implementation for sharing certificate
            console.log('Sharing certificate:', certId);
            // You can implement social sharing or copy link functionality here
        }
    </script>
</body>
</html>