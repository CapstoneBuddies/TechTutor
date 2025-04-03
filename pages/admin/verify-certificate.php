<?php  
    require_once '../../backends/main.php';
    require_once BACKEND.'management/certificate_management.php';

    // Ensure user is logged in and is an Admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header('Location: ' . BASE . 'login');
        exit();
    }
    
    $title = "Verify Certificate";
    $certificate = null;
    $error = null;
    
    // Check if certificate UUID is provided
    if(isset($_GET['uuid']) && !empty($_GET['uuid'])) {
        $cert_uuid = $_GET['uuid'];
        $certificate = getCertificateByUUID($cert_uuid);
        
        if(!$certificate) {
            $error = "Certificate not found. The certificate may have been deleted or the UUID is invalid.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
    
    <!-- Main Dashboard Content -->
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
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard/a/certificates">Certificates</a></li>
                                    <li class="breadcrumb-item active">Verify Certificate</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Certificate Verification</h2>
                            <p class="subtitle">Verify the authenticity of certificates issued by TechTutor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mx-auto">
                <div class="dashboard-card">
                    <h3 class="mb-4 text-center">Verify a Certificate</h3>
                    
                    <form id="verifyCertificateForm" class="mb-4">
                        <div class="mb-3">
                            <label for="certUuidInput" class="form-label">Certificate UUID</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="certUuidInput" name="cert_uuid" 
                                       placeholder="Enter certificate UUID" 
                                       value="<?php echo isset($_GET['uuid']) ? htmlspecialchars($_GET['uuid']) : ''; ?>" required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Verify
                                </button>
                            </div>
                            <div class="form-text">Enter the unique certificate ID to verify its authenticity</div>
                        </div>
                    </form>
                    
                    <div id="verificationResult">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php elseif($certificate): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                This certificate is valid and was issued by TechTutor.
                            </div>
                            
                            <div class="card border-success mb-4">
                                <div class="card-header bg-success text-white">
                                    <i class="bi bi-award-fill me-2"></i> Certificate Details
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Certificate ID</th>
                                            <td><?php echo htmlspecialchars($certificate['cert_uuid']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Award</th>
                                            <td><?php echo htmlspecialchars($certificate['award']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Recipient</th>
                                            <td><?php echo htmlspecialchars($certificate['recipient_name']); ?> (<?php echo htmlspecialchars($certificate['recipient_email']); ?>)</td>
                                        </tr>
                                        <tr>
                                            <th>Issued By</th>
                                            <td><?php echo htmlspecialchars($certificate['donor_name']); ?> (<?php echo htmlspecialchars($certificate['donor_email']); ?>)</td>
                                        </tr>
                                        <tr>
                                            <th>Issue Date</th>
                                            <td><?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?></td>
                                        </tr>
                                        <?php if(!empty($certificate['class_name'])): ?>
                                        <tr>
                                            <th>Related Class</th>
                                            <td>
                                                <?php echo htmlspecialchars($certificate['class_name']); ?>
                                                <?php if(!empty($certificate['subject_name'])): ?>
                                                    (<?php echo htmlspecialchars($certificate['subject_name']); ?>)
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <a href="<?php echo BASE; ?>certificate/<?php echo htmlspecialchars($certificate['cert_uuid']); ?>" class="btn btn-primary me-2" target="_blank">
                                    <i class="bi bi-eye"></i> View Certificate
                                </a>
                                <a href="<?php echo BASE; ?>certificate/<?php echo htmlspecialchars($certificate['cert_uuid']); ?>?download=1" class="btn btn-success" target="_blank">
                                    <i class="bi bi-download"></i> Download Certificate
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('verifyCertificateForm');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const certUuid = document.getElementById('certUuidInput').value.trim();
                if (!certUuid) return;
                
                // Redirect to the same page with the UUID as a query parameter
                window.location.href = '<?php echo BASE; ?>dashboard/a/verify-certificate?uuid=' + encodeURIComponent(certUuid);
            });
        });
    </script>
    
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html> 