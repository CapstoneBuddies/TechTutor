<?php 
    require_once '../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Profile</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS Files -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>profile.css" rel="stylesheet">
</head>

<body>
    
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Main Content -->
    <div class="profile-container">
        <div class="profile-section">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="mb-4">Account Settings</h2>
                    <h5 class="text-muted mb-4">Personal Details</h5>
                    <div class="text-center mb-4">
                        <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Full Name</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext"><?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : $_SESSION['name']; ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Birthday</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext"><?php echo isset($_SESSION['birthday']) ? $_SESSION['birthday'] : 'August 8, 1988'; ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Address</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext"><?php echo isset($_SESSION['address']) ? $_SESSION['address'] : 'Cebu City, Philippines, 6000'; ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Phone</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext"><?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : '+6391 2346 789'; ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Email Address</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext"><?php echo $_SESSION['email']; ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Password</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">••••••••••</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Position</label>
                        </div>
                        <div class="col-md-9">
                            <p class="form-control-plaintext"><?php echo $_SESSION['role']; ?></p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">Delete Account</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
<!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">Update Profile | <?php echo $_SESSION['role']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProfileForm" action="user-profile-update" method="POST" enctype="multipart/form-data">
                        <!-- Profile Picture Section -->
                        <div class="text-center mb-4">
                            <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile Picture" class="profile-picture-modal">
                            <div class="profile-buttons mt-3">
                                <label for="profilePicture" class="btn btn-primary">
                                    <i class="bi bi-upload"></i>
                                    Upload Profile Picture
                                </label>
                                <button type="button" class="btn btn-danger" onclick="removeProfilePicture()">
                                    <i class="bi bi-trash"></i>
                                    Remove
                                </button>
                            </div>
                            <input type="file" id="profilePicture" name="profilePicture" accept="image/*" class="hidden-file-input" onchange="previewImage(this)">
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i>
                                    New profile picture selected
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="firstName" value="<?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>" required minlength="2" maxlength="50">
                                <div class="invalid-feedback">Please enter a valid first name (2-50 characters)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastName" value="<?php echo isset($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>" required minlength="2" maxlength="50">
                                <div class="invalid-feedback">Please enter a valid last name (2-50 characters)</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" value="<?php echo isset($_SESSION['address']) ? $_SESSION['address'] : ''; ?>" maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <div class="phone-input-container">
                                <select class="form-select country-code" name="countryCode" style="max-width: 120px;">
                                    <option value="+63" <?php echo substr($_SESSION['phone'] ?? '', 0, 3) === '+63' ? 'selected' : ''; ?>>+63 (PH)</option>
                                    <option value="+1" <?php echo substr($_SESSION['phone'] ?? '', 0, 2) === '+1' ? 'selected' : ''; ?>>+1 (US/CA)</option>
                                    <option value="+44" <?php echo substr($_SESSION['phone'] ?? '', 0, 3) === '+44' ? 'selected' : ''; ?>>+44 (UK)</option>
                                    <option value="+81" <?php echo substr($_SESSION['phone'] ?? '', 0, 3) === '+81' ? 'selected' : ''; ?>>+81 (JP)</option>
                                    <option value="+82" <?php echo substr($_SESSION['phone'] ?? '', 0, 3) === '+82' ? 'selected' : ''; ?>>+82 (KR)</option>
                                    <option value="+86" <?php echo substr($_SESSION['phone'] ?? '', 0, 3) === '+86' ? 'selected' : ''; ?>>+86 (CN)</option>
                                </select>
                                <input type="tel" class="form-control" name="phone" 
                                    value="<?php 
                                        $phone = $_SESSION['phone'] ?? '';
                                        echo preg_match('/^\+\d{1,3}(.*)/', $phone, $matches) ? $matches[1] : $phone;
                                    ?>" 
                                    pattern="[0-9-]{12}" maxlength="12" 
                                    placeholder="XXX-XXX-XXXX">
                            </div>
                            <div class="invalid-feedback">Please enter a valid phone number in XXX-XXX-XXXX format</div>
                            <small class="form-text text-muted">Format: Country Code + XXX-XXX-XXXX (e.g., +63 912-345-6789)</small>
                        </div>

                        <input type="hidden" name="update" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitUpdateForm()" id="updateProfileBtn" name="updateProfileBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check-lg"></i>
                        Update Profile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="changePassword()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                    <form id="deleteAccountForm">
                        <div class="mb-3">
                            <label for="deleteConfirmPassword" class="form-label">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="deleteConfirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="if (confirm('Are you sure you want to deactivate your account? This action cannot be undone.')) { deleteAccount(); }">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Section -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize any components
            initializeComponents();
        });

        function initializeComponents() {
            // Add event listeners for the delete account button
            document.querySelector('.btn-danger[data-bs-toggle="modal"]').addEventListener('click', function() {
                // Reset the form when the modal is opened
                document.getElementById('deleteAccountForm').reset();
            });
        }

        function deleteAccount() {
            const password = document.getElementById('deleteConfirmPassword').value;
            const deleteModal = document.getElementById('deleteAccountModal');
            const deleteButton = document.querySelector('#deleteAccountModal .btn-danger');
            
            if (!password) {
                showAlert('error', 'Please enter your password to confirm');
                return;
            }
            
            // Show loading state
            deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            deleteButton.disabled = true;
            
            // Send AJAX request
            $.ajax({
                url: '<?php echo BASE; ?>user-deactivate',
                type: 'POST',
                data: { password: password },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        bootstrap.Modal.getInstance(deleteModal).hide();
                        showAlert('success', 'Account deleted successfully');
                        setTimeout(function() {
                            window.location.href = '<?php echo BASE; ?>login';
                        }, 1000);
                    } else {
                        showAlert('error', response.message || 'Failed to delete account');
                        deleteButton.innerHTML = 'Delete Account';
                        deleteButton.disabled = false;
                    }
                },
                error: function(xhr) {
                    showAlert('error', 'Connection error. Please try again.');
                    deleteButton.innerHTML = 'Delete Account';
                    deleteButton.disabled = false;
                }
            });
        }

        function changePassword() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const changeModal = document.getElementById('changePasswordModal');
            const saveButton = document.querySelector('#changePasswordModal .btn-primary');
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields');
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match');
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                return;
            }
            
            if (newPassword.length < 8) {
                alert('Password must be at least 8 characters');
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                return;
            }
            
            // Show loading state
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            saveButton.disabled = true;
            
            // Send AJAX request
            $.ajax({
                url: '<?php echo BASE; ?>user-change-password',
                type: 'POST',
                data: {
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Password was changed successfully! Please log back in.');
                        window.location.href = '<?php echo BASE; ?>user-logout';
                    } else {
                        alert(response.message || 'Failed to change password');
                        document.getElementById('currentPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                    }
                    saveButton.innerHTML = 'Save Changes';
                    saveButton.disabled = false;
                },
                error: function(xhr) {
                    alert('Connection error. Please try again.');
                    saveButton.innerHTML = 'Save Changes';
                    saveButton.disabled = false;
                }
            });
        }
        
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="bi ${icon} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Create a container for alerts if it doesn't exist
            let alertContainer = document.querySelector('.alert-container');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
                alertContainer.style.zIndex = '1050';
                document.body.appendChild(alertContainer);
            }
            
            // Add the alert to the container
            const alertElement = document.createElement('div');
            alertElement.innerHTML = alertHtml;
            alertContainer.appendChild(alertElement.firstChild);
            
            // Auto-remove the alert after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                if (alerts.length > 0) {
                    alerts[0].remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>