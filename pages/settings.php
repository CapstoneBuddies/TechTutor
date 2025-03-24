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
    </div>
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
                    <button type="button" class="btn btn-primary" onclick="changePassword(this)">Save Changes</button>
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
        window.addEventListener('load', function() {
            document.getElementById('changePasswordForm')?.addEventListener('submit', function(event) {
                event.preventDefault();
                changePassword(event);
            });

            document.getElementById('deleteAccountForm')?.addEventListener('submit', function(event) {
                event.preventDefault();
                deleteAccount(event);
            });
        });

        function changePassword(event) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const modalElement = document.getElementById('changePasswordModal');
            const saveButton = document.querySelector('#changePasswordModal .btn-primary');

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
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            saveButton.disabled = true;

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
                if (data.status === 'success') {
                    // Proper modal closing
                    bootstrap.Modal.getInstance(modalElement).hide();
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                    showToast('success', 'Password changed successfully! Please log back in.');

                    // Reliable redirect
                    setTimeout(() => {
                        window.location.href = BASE + 'user-logout';
                    }, 2000);
                } else {
                    showToast('error', data.message || 'Failed to change password');
                    document.getElementById('changePasswordForm').reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Connection error. Please try again.');
                document.getElementById('changePasswordForm').reset();
            })
            .finally(() => {
                saveButton.innerHTML = 'Save Changes';
                saveButton.disabled = false;
            });
        }
        
        function deleteAccount(event) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
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
                if (data.status === 'success') {
                    // Proper modal closing
                    bootstrap.Modal.getInstance(modalElement).hide();
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                    showToast('success', 'Account deleted successfully');

                    // Reliable redirect
                    setTimeout(() => {
                        window.location.href = BASE + 'user-logout';
                    }, 2000);
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

        const optimizedInterval = setInterval(() => {
            // Keep operations minimal
        }, 1000);

        window.addEventListener('beforeunload', () => {
            clearInterval(optimizedInterval);
        });
    </script>
</body>
</html>