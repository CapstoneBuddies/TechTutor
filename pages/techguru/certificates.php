<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'certificate_management.php';
    
    // Ensure user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        header('Location: ' . BASE . 'login');
        exit();
    }
    
    $title = "Certificates";
    $certificates = getTechGuruCertificates($_SESSION['user']);
    $eligible_students = getEligibleStudentsForCertificates($_SESSION['user']);
    
    // Handle certificate creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'create_certificate') {
            $recipient_id = $_POST['recipient_id'];
            $award = $_POST['award'];
            $class_id = isset($_POST['class_id']) ? $_POST['class_id'] : null;
            
            if (createCertificate($_SESSION['user'], $recipient_id, $award, $class_id)) {
                $_SESSION['success'] = "Certificate created successfully";
            } else {
                $_SESSION['error'] = "Failed to create certificate";
            }
            
            header('Location: ' . BASE . 'dashboard/t/certificates');
            exit();
        } elseif ($_POST['action'] === 'delete_certificate') {
            $cert_uuid = $_POST['cert_uuid'];
            
            if (deleteCertificate($cert_uuid, $_SESSION['user'])) {
                $_SESSION['success'] = "Certificate deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete certificate";
            }
            
            header('Location: ' . BASE . 'dashboard/t/certificates');
            exit();
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
                                    <li class="breadcrumb-item active">Certificates</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Certificates</h2>
                            <p class="subtitle">Manage certificates issued to your students</p>
                        </div>
                        <?php if (!empty($eligible_students)): ?>
                        <button type="button" class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#createCertificateModal">
                            <i class="bi bi-plus-lg"></i>
                            Issue Certificate
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Certificates Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <?php if (empty($certificates)): ?>
                        <div class="text-center py-5">
                            <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Certificates" class="mb-4" style="width: 200px;">
                            <h3>No Certificates Issued Yet</h3>
                            <p class="text-muted">Start issuing certificates to your students who've completed your classes.</p>
                            <?php if (!empty($eligible_students)): ?>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createCertificateModal">
                                    <i class="bi bi-plus-lg"></i>
                                    Issue First Certificate
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Recipient</th>
                                        <th>Certificate</th>
                                        <th>Class</th>
                                        <th>Issue Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certificates as $cert): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo USER_IMG . htmlspecialchars($cert['recipient_profile']); ?>" 
                                                         alt="Profile" class="rounded-circle me-2" width="40" height="40">
                                                    <div>
                                                        <div><?php echo htmlspecialchars($cert['recipient_name']); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($cert['recipient_email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-award-fill text-warning me-2" style="font-size: 1.5rem;"></i>
                                                    <div>
                                                        <div><?php echo htmlspecialchars($cert['award']); ?></div>
                                                        <div class="text-muted small">Certificate #<?php echo substr($cert['cert_uuid'], 0, 8); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (isset($cert['class_name'])): ?>
                                                    <a href="<?php echo BASE; ?>dashboard/t/class/<?php echo $cert['class_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($cert['class_name']); ?>
                                                    </a>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($cert['subject_name']); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not associated with a class</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($cert['issue_date'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo BASE; ?>certificate/<?php echo $cert['cert_uuid']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCertificateModal<?php echo $cert['cert_id']; ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </div>
                                                
                                                <!-- Delete Certificate Modal -->
                                                <div class="modal fade" id="deleteCertificateModal<?php echo $cert['cert_id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirm Deletion</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete the certificate "<strong><?php echo htmlspecialchars($cert['award']); ?></strong>" issued to <strong><?php echo htmlspecialchars($cert['recipient_name']); ?></strong>?</p>
                                                                <p class="text-danger"><small>This action cannot be undone.</small></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="action" value="delete_certificate">
                                                                    <input type="hidden" name="cert_uuid" value="<?php echo $cert['cert_uuid']; ?>">
                                                                    <button type="submit" class="btn btn-danger">Delete Certificate</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Certificate Modal -->
    <?php if (!empty($eligible_students)): ?>
    <div class="modal fade" id="createCertificateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Issue New Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="certificateForm">
                        <input type="hidden" name="action" value="create_certificate">
                        
                        <div class="mb-3">
                            <label for="studentSelect" class="form-label">Select Student & Class</label>
                            <select class="form-select" id="studentSelect" name="student_class" required>
                                <option value="">-- Select Student & Class --</option>
                                <?php foreach ($eligible_students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>|<?php echo $student['class_id']; ?>">
                                    <?php echo htmlspecialchars($student['student_name']); ?> - 
                                    <?php echo htmlspecialchars($student['class_name']); ?> 
                                    (<?php echo htmlspecialchars($student['subject_name']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="recipient_id" id="recipientIdInput">
                            <input type="hidden" name="class_id" id="classIdInput">
                        </div>
                        
                        <div class="mb-3">
                            <label for="awardInput" class="form-label">Certificate Title/Award</label>
                            <input type="text" class="form-control" id="awardInput" name="award" placeholder="e.g. Certificate of Completion for Python Programming" required>
                            <div class="form-text">Enter the title that will appear on the certificate</div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Issue Certificate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> 
    <?php endif; ?>
    
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle student and class selection
            const studentSelect = document.getElementById('studentSelect');
            const recipientIdInput = document.getElementById('recipientIdInput');
            const classIdInput = document.getElementById('classIdInput');
            
            if (studentSelect) {
                studentSelect.addEventListener('change', function() {
                    const selectedValue = this.value;
                    if (selectedValue) {
                        const [studentId, classId] = selectedValue.split('|');
                        recipientIdInput.value = studentId;
                        classIdInput.value = classId;
                        
                        // Auto-populate award title based on selection
                        const selectedOption = this.options[this.selectedIndex];
                        const studentClassText = selectedOption.text;
                        document.getElementById('awardInput').value = 'Certificate of Completion for ' + 
                            studentClassText.split(' - ')[1].split(' (')[0].trim();
                    }
                });
            }
        });
    </script>
</body>
</html>