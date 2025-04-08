<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'certificate_management.php';
    
    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }
    
    $certificates = [];
    try {
        // Get student's certificates using centralized function
        $certificates = getStudentCertificatesDetails($_SESSION['user']);
    } catch (Exception $e) {
        log_error("Certificates error: " . $e->getMessage(), "database");
    }
    
    $title = "My Certificates";
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <!-- Page Loader -->
        <div id="page-loader">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="loading-text">Loading content...</div>
        </div>
        
        <script>
            // Show loading screen at the start of page load
            document.addEventListener('DOMContentLoaded', function() {
                initializePage();
            });
            
            function initializePage() {
                console.log('Certificates page initialized');
                showLoading(false);
            }
            
            function shareCertificate(certUuid, award) {
                const shareUrl = `${BASE}certificate/${certUuid}`;
                
                // Check if Web Share API is available
                if (navigator.share) {
                    navigator.share({
                        title: award,
                        text: 'Check out my certificate from TechTutor!',
                        url: shareUrl
                    })
                    .catch(error => {
                        console.error('Error sharing:', error);
                        copyToClipboard(shareUrl);
                    });
                } else {
                    copyToClipboard(shareUrl);
                }
            }
            
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        showToast('success', 'Certificate link copied to clipboard!');
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                        showToast('error', 'Failed to copy link');
                    });
            }
            
            function downloadCertificate(certUuid, award) {
                showLoading(true);
                fetch(`${BASE}certificate/${certUuid}?download=1`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cert_uuid: certUuid
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.blob();
                })
                .then(blob => {
                    showLoading(false);
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `${award}_Certificate.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error downloading certificate:', error);
                    showToast('error', 'Failed to download certificate');
                });
            }
        </script>
        
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <div class="dashboard-content bg">
                <!-- Header Section -->
                <div class="content-section mb-4">
                    <div class="content-card bg-snow">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                        <ol class="breadcrumb mb-2">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">My Certificates</li>
                                        </ol>
                                    </nav>
                                    <h1 class="h3 mb-0">My Certificates</h1>
                                </div>
                                <div>
                                    <a href="<?php echo BASE; ?>dashboard" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificates Grid -->
                <div class="content-section">
                    <div class="content-card bg-snow">
                        <div class="card-body p-4">
                            <?php if (empty($certificates)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-award-fill text-muted" style="font-size: 3rem;"></i>
                                    <h3 class="mt-3">No Certificates Yet</h3>
                                    <p class="text-muted">Complete your courses to earn certificates of achievement!</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach($certificates as $cert): ?>
                                        <div class="col-lg-4 col-md-6 mb-4">
                                            <div class="certificate-card card h-100">
                                                <div class="cert-preview p-3">
                                                    <div class="certificate-preview">
                                                        <div class="preview-border">
                                                            <div class="preview-content text-center p-3">
                                                                <h2 class="fs-5 mb-2">Certificate of Achievement</h2>
                                                                <div class="fs-6 mb-1">
                                                                    <strong><?php echo htmlspecialchars($cert['award'] ?? ''); ?></strong>
                                                                </div>
                                                                <div class="small text-muted mb-2">
                                                                    <?php echo htmlspecialchars($cert['class_name'] ?? ''); ?>
                                                                </div>
                                                                <div class="small text-end text-muted mt-2">
                                                                    <?php echo date('M d, Y', strtotime($cert['issue_date'] ?? date('Y-m-d'))); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body pt-0">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($cert['award'] ?? ''); ?></h5>
                                                    <p class="card-text text-muted">
                                                        <small>Issued by <?php echo htmlspecialchars($cert['donor_name'] ?? ''); ?> on <?php echo date('M d, Y', strtotime($cert['issue_date'] ?? date('Y-m-d'))); ?></small>
                                                    </p>
                                                </div>
                                                <div class="card-footer bg-transparent d-flex justify-content-between">
                                                    <a href="<?php echo BASE; ?>certificate/<?php echo $cert['cert_uuid']; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <div class="btn-group">
                                                        <button class="btn btn-outline-secondary btn-sm" onclick="shareCertificate('<?php echo $cert['cert_uuid']; ?>', '<?php echo htmlspecialchars(addslashes($cert['award'] ?? '')); ?>')">
                                                            <i class="bi bi-share"></i> Share
                                                        </button>
                                                        <button class="btn btn-outline-success btn-sm" onclick="downloadCertificate('<?php echo $cert['cert_uuid']; ?>', '<?php echo htmlspecialchars(addslashes($cert['award'] ?? '')); ?>')">
                                                            <i class="bi bi-download"></i>
                                                        </button>
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
            </div>
        </main>

        <style>
            .certificate-card {
                transition: transform 0.3s, box-shadow 0.3s;
                border: 1px solid rgba(0,0,0,0.1);
            }
            
            .certificate-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            
            .certificate-preview {
                background-color: #f8f9fa;
                border-radius: 4px;
                padding: 10px;
            }
            
            .preview-border {
                border: 5px solid #f4d03f;
                padding: 10px;
                position: relative;
                background: #fff;
            }
            
            .preview-border::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                border: 1px solid #2c3e50;
                margin: 3px;
                pointer-events: none;
            }
            
            .preview-content {
                position: relative;
                z-index: 1;
            }
        </style>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
    </body>
</html>