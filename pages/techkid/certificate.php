<?php 
    require_once '../../backends/main.php';
    require_once BACKEND . 'management/student_management.php';
    
    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }
    
    $title = "My Certificates";
    $certificates = getStudentCertificates($_SESSION['user']);
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
                                    <li class="breadcrumb-item active">My Certificates</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">My Certificates</h2>
                            <p class="subtitle">View and download your awarded certificates</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificates Section -->
        <div class="row mt-4">
            <?php if (empty($certificates)): ?>
                <div class="col-12">
                    <div class="dashboard-card text-center py-5">
                        <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Certificates" class="mb-4" style="width: 200px;">
                        <h3>No Certificates Yet</h3>
                        <p class="text-muted">Complete your courses to earn certificates from your tutors.</p>
                        <a href="<?php echo BASE; ?>dashboard/s/class" class="btn btn-primary mt-3">
                            <i class="bi bi-book"></i>
                            View My Classes
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($certificates as $cert): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="dashboard-card h-100">
                        <div class="certificate-card">
                            <div class="certificate-preview">
                                <div class="certificate-badge">
                                    <i class="bi bi-award-fill"></i>
                                </div>
                                <h4 class="certificate-award"><?php echo htmlspecialchars($cert['award']); ?></h4>
                                <div class="certificate-meta">
                                    <div class="certificate-tutor">
                                        <small>Issued by</small>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo USER_IMG . htmlspecialchars($cert['donor_profile']); ?>" 
                                                 alt="Tutor" class="rounded-circle me-2" width="24" height="24">
                                            <span><?php echo htmlspecialchars($cert['donor_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="certificate-date">
                                        <small>Date Issued</small>
                                        <div><?php echo date('M d, Y', strtotime($cert['issue_date'])); ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($cert['class_name'])): ?>
                                <div class="certificate-class">
                                    <small>For class</small>
                                    <div><?php echo htmlspecialchars($cert['class_name']); ?></div>
                                    <?php if (!empty($cert['subject_name'])): ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars($cert['subject_name']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="certificate-actions">
                                <a href="<?php echo BASE; ?>certificate/<?php echo $cert['cert_uuid']; ?>" 
                                   class="btn btn-primary w-100" target="_blank">
                                    <i class="bi bi-eye me-2"></i>
                                    View Certificate
                                </a>
                                <a href="<?php echo BASE; ?>certificate/<?php echo $cert['cert_uuid']; ?>?download=1" 
                                   class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="bi bi-download me-2"></i>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    
    <style>
        .certificate-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .certificate-preview {
            flex: 1;
            padding: 1.5rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            position: relative;
            overflow: hidden;
        }
        
        .certificate-badge {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 80px;
            height: 80px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-15deg);
        }
        
        .certificate-badge i {
            font-size: 2rem;
            color: #f39c12;
        }
        
        .certificate-award {
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .certificate-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-top: 1px solid #e9ecef;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
        }
        
        .certificate-tutor, 
        .certificate-date {
            display: flex;
            flex-direction: column;
        }
        
        .certificate-tutor small,
        .certificate-date small,
        .certificate-class small {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .certificate-class {
            margin-top: 1rem;
        }
        
        .certificate-actions {
            padding: 1rem 0 0;
        }
    </style>
</body>
</html>