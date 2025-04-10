<?php 
    require_once '../backends/main.php';
    $title = 'Profile';
    $redirect = '';

    if(!isset($_SESSION['user'])) {
        $_SESSION['msg'] = "Invalid Action";
        log_error("User accessed an invalid page",'security');
        header("location: ".BASE."login");
        exit();
    }
    if(!empty($redirect)) {
        header("location: user-logout");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
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
                            <p class="form-control-plaintext"><?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : '+63 912-346-789'; ?></p>
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
                        <input type="hidden" name="username" value="<?php echo $_SESSION['email']; ?>">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('currentPassword')">
                                    <i class="bi bi-eye" id="currentPassword-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPassword')">
                                    <i class="bi bi-eye" id="newPassword-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirmPassword')">
                                    <i class="bi bi-eye" id="confirmPassword-icon"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="changePasswordBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deleteAccountForm">
                        <input type="hidden" name="username" value="<?php echo $_SESSION['email']; ?>">
                        <div class="mb-3">
                            <label for="deleteConfirmPassword" class="form-label">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="deleteConfirmPassword" required autocomplete="current-password">
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

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>

        $(document).ready(function(){
            $('#changePasswordBtn').click(function(event) {
                event.preventDefault();
               const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (!currentPassword || !newPassword || !confirmPassword) {
                    showToast('error', 'Please fill in all password fields');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    showToast('error', 'New passwords do not match');
                    return;
                }
                
                if (newPassword.length < 8) {
                    showToast('error', 'Password must be at least 8 characters');
                    return;
                }
                
                // Show loading state
                // saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                // saveButton.disabled = true;

                // Create form data
                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);

                // Send fetch request
                fetch('user-change-password', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Password changed successfully! Please log back in.');
                        setTimeout (function () { window. location. href = "user-logout"; }, 1000);

                    } else {
                        showToast('error', data.message || 'Failed to change password');
                        document.getElementById('changePasswordForm').reset();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Connection error. Please try again.');
                    document.getElementById('changePasswordForm').reset();
                });
            });
        });

        function deleteAccount() {
            const password = document.getElementById('deleteConfirmPassword').value;
            const modalElement = document.getElementById('deleteAccountModal');
            const deleteButton = document.querySelector('#deleteAccountModal .btn-danger');
            
            if (!password) {
                showToast('error', 'Please enter your password to confirm');
                return;
            }
            
            // Show loading state
            deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            deleteButton.disabled = true;
            
            // Create form data
            const formData = new FormData();
            formData.append('userId', <?php echo $_SESSION['user']; ?>);

            // Send fetch request
            fetch(BASE+'user-deactivate', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Proper modal closing
                    bootstrap.Modal.getInstance(modalElement).hide();
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                    alert('success', 'Account deleted successfully');

                    // Reliable redirect
                    window.location.href = 'user-logout';
                    return false;
                } else {
                    showToast('error', data.message || 'Failed to delete account');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Connection error. Please try again.');
            })
            .finally(() => {
                deleteButton.innerHTML = 'Delete Account';
                deleteButton.disabled = false;
            });
        }

        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>