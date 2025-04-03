<?php  
    require_once '../../backends/main.php';
    require_once BACKEND.'certificate_management.php';

    // Ensure user is logged in and is an Admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header('Location: ' . BASE . 'login');
        exit();
    }
    
    $title = "Certificate Management";
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
                            <h2 class="page-header">Certificate Management</h2>
                            <p class="subtitle">Create, view, assign, and manage certificates</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-action me-2" data-bs-toggle="modal" data-bs-target="#createCertificateModal">
                                <i class="bi bi-plus-lg"></i>
                                Create Certificate
                            </button>
                            <button type="button" class="btn btn-success btn-action" data-bs-toggle="modal" data-bs-target="#assignCertificateModal">
                                <i class="bi bi-person-check"></i>
                                Assign Certificate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer" class="mt-3"></div>

        <!-- Certificates Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="certificatesTable">
                            <thead>
                                <tr>
                                    <th>Recipient</th>
                                    <th>Certificate</th>
                                    <th>Issued By</th>
                                    <th>Class</th>
                                    <th>Issue Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Loading certificates...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Certificate Modal -->
    <div class="modal fade" id="createCertificateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createCertificateForm">
                        <input type="hidden" name="action" value="create_certificate">
                        
                        <div class="mb-3">
                            <label for="recipientSelect" class="form-label">Student (Recipient)</label>
                            <select class="form-select" id="recipientSelect" name="recipient_id" required>
                                <option value="">-- Select Student --</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="donorSelect" class="form-label">Teacher (Issuer)</label>
                            <select class="form-select" id="donorSelect" name="donor_id" required>
                                <option value="">-- Select Teacher --</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="classSelect" class="form-label">Related Class (Optional)</label>
                            <select class="form-select" id="classSelect" name="class_id">
                                <option value="">-- Not Associated with a Class --</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="awardInput" class="form-label">Certificate Title/Award</label>
                            <input type="text" class="form-control" id="awardInput" name="award" placeholder="e.g. Certificate of Completion for Python Programming" required>
                            <div class="form-text">Enter the title that will appear on the certificate</div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Certificate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assign Certificate Modal -->
    <div class="modal fade" id="assignCertificateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Certificate to Eligible Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignCertificateForm">
                        <input type="hidden" name="action" value="assign_certificate">
                        
                        <div class="mb-3">
                            <label for="eligibleStudentSelect" class="form-label">Eligible Student & Class</label>
                            <select class="form-select" id="eligibleStudentSelect" required>
                                <option value="">-- Select Student & Class --</option>
                            </select>
                            <input type="hidden" name="recipient_id" id="eligibleRecipientIdInput">
                            <input type="hidden" name="class_id" id="eligibleClassIdInput">
                            <input type="hidden" name="donor_id" id="eligibleDonorIdInput">
                        </div>
                        
                        <div class="mb-3">
                            <label for="eligibleAwardInput" class="form-label">Certificate Title/Award</label>
                            <input type="text" class="form-control" id="eligibleAwardInput" name="award" placeholder="e.g. Certificate of Completion for Python Programming" required>
                            <div class="form-text">Enter the title that will appear on the certificate</div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Assign Certificate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Certificate Modal -->
    <div class="modal fade" id="viewCertificateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="certificatePreview" class="d-none">
                        <!-- Certificate preview will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="downloadCertLink" class="btn btn-primary" target="_blank">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Certificate Modal -->
    <div class="modal fade" id="deleteCertificateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this certificate?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteCertificateForm">
                        <input type="hidden" name="action" value="delete_certificate">
                        <input type="hidden" name="cert_uuid" id="deleteCertUuid">
                        <button type="submit" class="btn btn-danger">Delete Certificate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load all certificates
            loadCertificates();
            
            // Load students, tutors, classes and eligible students for dropdowns
            loadStudents();
            loadTutors();
            loadClasses();
            loadEligibleStudents();
            
            // Handle create certificate form submission
            document.getElementById('createCertificateForm').addEventListener('submit', function(e) {
                e.preventDefault();
                createCertificate(this);
            });
            
            // Handle assign certificate form submission
            document.getElementById('assignCertificateForm').addEventListener('submit', function(e) {
                e.preventDefault();
                assignCertificate(this);
            });
            
            // Handle delete certificate form submission
            document.getElementById('deleteCertificateForm').addEventListener('submit', function(e) {
                e.preventDefault();
                deleteCertificate(this);
            });
            
            // Handle eligible student selection
            document.getElementById('eligibleStudentSelect').addEventListener('change', function() {
                if (this.value) {
                    const [studentId, classId, tutorId, className] = this.value.split('|');
                    document.getElementById('eligibleRecipientIdInput').value = studentId;
                    document.getElementById('eligibleClassIdInput').value = classId;
                    document.getElementById('eligibleDonorIdInput').value = tutorId;
                    document.getElementById('eligibleAwardInput').value = 'Certificate of Completion for ' + className;
                } else {
                    document.getElementById('eligibleRecipientIdInput').value = '';
                    document.getElementById('eligibleClassIdInput').value = '';
                    document.getElementById('eligibleDonorIdInput').value = '';
                    document.getElementById('eligibleAwardInput').value = '';
                }
            });
        });
        
        // Function to load all certificates
        function loadCertificates() {
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_all_certificates'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCertificates(data.certificates);
                } else {
                    showAlert('error', 'Failed to load certificates: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while loading certificates');
            });
        }
        
        // Function to display certificates in the table
        function displayCertificates(certificates) {
            const tableBody = document.querySelector('#certificatesTable tbody');
            
            if (certificates.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Certificates" class="mb-4" style="width: 200px;">
                            <h3>No Certificates Found</h3>
                            <p class="text-muted">Start creating certificates for students.</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tableBody.innerHTML = '';
            
            certificates.forEach(cert => {
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?php echo USER_IMG; ?>${cert.recipient_profile}" 
                                 alt="Profile" class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <div>${cert.recipient_name}</div>
                                <div class="text-muted small">${cert.recipient_email}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-award-fill text-warning me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <div>${cert.award}</div>
                                <div class="text-muted small">Certificate #${cert.cert_uuid.substring(0, 8)}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>${cert.donor_name}</div>
                        <div class="text-muted small">${cert.donor_email}</div>
                    </td>
                    <td>
                        ${cert.class_name ? 
                            `<a href="<?php echo BASE; ?>dashboard/a/view-class.php?id=${cert.class_id}" class="text-decoration-none">
                                ${cert.class_name}
                            </a>
                            <div class="text-muted small">${cert.subject_name}</div>` : 
                            `<span class="text-muted">Not associated with a class</span>`
                        }
                    </td>
                    <td>
                        ${new Date(cert.issue_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCertificate('${cert.cert_uuid}')">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <a href="<?php echo BASE; ?>certificate/${cert.cert_uuid}?download=1" class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="bi bi-download"></i> Download
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteCertificate('${cert.cert_uuid}', '${cert.award}', '${cert.recipient_name}')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Function to load students for the dropdown
        function loadStudents() {
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_students'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('recipientSelect');
                    select.innerHTML = '<option value="">-- Select Student --</option>';
                    
                    data.students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.uid;
                        option.textContent = `${student.student_name} (${student.email})`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Function to load tutors for the dropdown
        function loadTutors() {
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_tutors'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('donorSelect');
                    select.innerHTML = '<option value="">-- Select Teacher --</option>';
                    
                    data.tutors.forEach(tutor => {
                        const option = document.createElement('option');
                        option.value = tutor.uid;
                        option.textContent = `${tutor.tutor_name} (${tutor.email})`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Function to load classes for the dropdown
        function loadClasses() {
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_classes'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('classSelect');
                    select.innerHTML = '<option value="">-- Not Associated with a Class --</option>';
                    
                    data.classes.forEach(classItem => {
                        const option = document.createElement('option');
                        option.value = classItem.class_id;
                        option.textContent = `${classItem.class_name} (${classItem.subject_name}) - by ${classItem.tutor_name}`;
                        option.dataset.tutorId = classItem.tutor_id;
                        select.appendChild(option);
                    });
                    
                    // Add change event to automatically select the tutor based on class
                    select.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption.dataset.tutorId) {
                            document.getElementById('donorSelect').value = selectedOption.dataset.tutorId;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Function to load eligible students
        function loadEligibleStudents() {
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_eligible_students'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('eligibleStudentSelect');
                    select.innerHTML = '<option value="">-- Select Student & Class --</option>';
                    
                    data.students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = `${student.student_id}|${student.class_id}|${student.tutor_id}|${student.class_name}`;
                        option.textContent = `${student.student_name} - ${student.class_name} (${student.subject_name}) - by ${student.tutor_name}`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Function to create a certificate
        function createCertificate(form) {
            const formData = new FormData(form);
            const data = new URLSearchParams();
            
            for (const pair of formData) {
                data.append(pair[0], pair[1]);
            }
            
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Certificate created successfully');
                    loadCertificates();
                    
                    // Reset form and close modal
                    form.reset();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createCertificateModal'));
                    modal.hide();
                } else {
                    showAlert('error', 'Failed to create certificate: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while creating the certificate');
            });
        }
        
        // Function to assign a certificate
        function assignCertificate(form) {
            const formData = new FormData(form);
            const data = new URLSearchParams();
            
            for (const pair of formData) {
                data.append(pair[0], pair[1]);
            }
            
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Certificate assigned successfully');
                    loadCertificates();
                    loadEligibleStudents();
                    
                    // Reset form and close modal
                    form.reset();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('assignCertificateModal'));
                    modal.hide();
                } else {
                    showAlert('error', 'Failed to assign certificate: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while assigning the certificate');
            });
        }
        
        // Function to delete a certificate
        function deleteCertificate(form) {
            const formData = new FormData(form);
            const data = new URLSearchParams();
            
            for (const pair of formData) {
                data.append(pair[0], pair[1]);
            }
            
            fetch('<?php echo BASE; ?>certificate-handling', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Certificate deleted successfully');
                    loadCertificates();
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCertificateModal'));
                    modal.hide();
                } else {
                    showAlert('error', 'Failed to delete certificate: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while deleting the certificate');
            });
        }
        
        // Function to view a certificate
        function viewCertificate(certUuid) {
            const modal = new bootstrap.Modal(document.getElementById('viewCertificateModal'));
            modal.show();
            
            document.getElementById('downloadCertLink').href = `<?php echo BASE; ?>certificate/${certUuid}?download=1`;
            
            // Load certificate preview iframe
            const previewDiv = document.getElementById('certificatePreview');
            previewDiv.classList.add('d-none');
            
            fetch(`<?php echo BASE; ?>certificate/${certUuid}`, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(html => {
                const spinner = document.querySelector('#viewCertificateModal .spinner-border');
                spinner.style.display = 'none';
                
                previewDiv.innerHTML = `<iframe src="<?php echo BASE; ?>certificate/${certUuid}?view=1" width="100%" height="600" frameborder="0"></iframe>`;
                previewDiv.classList.remove('d-none');
            })
            .catch(error => {
                console.error('Error:', error);
                const spinner = document.querySelector('#viewCertificateModal .spinner-border');
                spinner.style.display = 'none';
                
                previewDiv.innerHTML = `<div class="alert alert-danger">Failed to load certificate preview</div>`;
                previewDiv.classList.remove('d-none');
            });
        }
        
        // Function to confirm certificate deletion
        function confirmDeleteCertificate(certUuid, award, recipientName) {
            document.getElementById('deleteCertUuid').value = certUuid;
            
            const modalBody = document.querySelector('#deleteCertificateModal .modal-body');
            modalBody.innerHTML = `
                <p>Are you sure you want to delete the certificate "<strong>${award}</strong>" issued to <strong>${recipientName}</strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteCertificateModal'));
            modal.show();
        }
        
        // Function to show alerts
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="bi ${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            alertContainer.appendChild(alert);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => {
                    alertContainer.removeChild(alert);
                }, 150);
            }, 5000);
        }
    </script>
    
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html> 