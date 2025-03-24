<?php 
    require_once '../backends/main.php';
    if(!isset($_SESSION['user'])) {
        $_SESSION['msg'] = "Invalid Action";
        log_error("User accessed an invalid page",'security');
        header("location: ".BASE."login");
        exit();
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'Profile'; ?></h2>
                    <div class="role-badge">
                        <span class="role-text">
                            <?php 
                            $roleIcon = '';
                            switch($_SESSION['role']) {
                                case 'ADMIN':
                                    $roleIcon = 'bi-shield-fill';
                                    break;
                                case 'TECHGURU':
                                    $roleIcon = 'bi-mortarboard-fill';
                                    break;
                                case 'TECHKID':
                                    $roleIcon = 'bi-person-fill';
                                    break;
                            }
                            ?>
                            <i class="bi <?php echo $roleIcon; ?>"></i>
                            <?php echo $_SESSION['role']; ?>
                        </span>
                        <span class="verified-badge">
                            <i class="bi bi-check-circle-fill"></i> Verified
                        </span>
                    </div>
                </div>
                <button type="button" class="update-btn" onclick="openUpdateModal()">
                    <i class="bi bi-pencil-fill"></i>
                    Update
                </button>
            </div>

            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile Picture" class="profile-picture">
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control item-disable" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control item-disable" value="<?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : ''; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control item-disable" value="<?php echo isset($_SESSION['address']) ? $_SESSION['address'] : ''; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <div class="phone-input-container">
                            <select class="form-select country-code item-disable" name="countryCode" disabled style="max-width: 120px;">
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
                                readonly>
                        </div>
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
                                <input type="text" class="form-control" name="firstName" value="<?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastName" value="<?php echo isset($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" value="<?php echo isset($_SESSION['address']) ? $_SESSION['address'] : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <div class="phone-input-container">
                                <select class="form-select country-code" name="countryCode" style="max-width: 120px;">
                                    <option value="+63">+63 (PH)</option>
                                    <option value="+1">+1 (US/CA)</option>
                                    <option value="+44">+44 (UK)</option>
                                    <option value="+81">+81 (JP)</option>
                                    <option value="+82">+82 (KR)</option>
                                    <option value="+86">+86 (CN)</option>
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

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" onclick="submitUpdateForm()" id="updateProfileBtn" name="updateProfileBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        const updateModal = new bootstrap.Modal(document.getElementById('updateProfileModal'));
        let hasProfileChanges = false;
        
        function openUpdateModal() {
            hasProfileChanges = false;
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('updateProfileForm').classList.remove('was-validated');
            updateModal.show();
        }

        function formatPhoneNumber(input) {
            // Remove all non-digit characters
            let value = input.value.replace(/\D/g, '');
            
            // Format the number as XXX-XXX-XXXX
            if (value.length >= 6) {
                value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6);
            } else if (value.length >= 3) {
                value = value.slice(0, 3) + '-' + value.slice(3);
            }
            
            input.value = value;
        }

        // Add event listeners to phone inputs
        document.querySelectorAll('input[name="phone"]').forEach(input => {
            input.addEventListener('input', (e) => formatPhoneNumber(e.target));
            input.addEventListener('keypress', (e) => {
                // Allow numbers, hyphens, and control keys
                if (!/[\d-]/.test(e.key) && !e.ctrlKey && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                    e.preventDefault();
                }
                
                // Prevent more than 2 consecutive hyphens
                if (e.key === '-' && e.target.value.match(/-{2}$/)) {
                    e.preventDefault();
                }
            });
        });

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }
                
                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-picture-modal').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                    hasProfileChanges = true;
                };
                reader.readAsDataURL(file);
            }
        }

        function removeProfilePicture() {
            if(confirm('Are you sure you want to remove your profile picture? This cannot be undone.')) {
                const removeBtn = document.querySelector('.btn-danger');
                const originalBtnHtml = removeBtn.innerHTML;
                removeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Removing...';
                removeBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('removeProfilePicture', 'true');
                
                fetch('<?php echo BASE; ?>user-profile-update', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const defaultImage = '<?php echo BASE; ?>assets/img/users/default.jpg';
                        document.querySelector('.profile-picture').src = defaultImage;
                        document.querySelector('.profile-picture-modal').src = defaultImage;
                        document.querySelector('.avatar-icon').src = defaultImage;
                        document.getElementById('imagePreview').style.display = 'none';
                        hasProfileChanges = false;
                        showToast('success', 'Profile picture removed successfully');
                        // Close the modal after successful removal
                        updateModal.hide();
                    } else {
                        showToast('error', data.message || 'Failed to remove profile picture');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while removing the profile picture');
                })
                .finally(() => {
                    removeBtn.innerHTML = originalBtnHtml;
                    removeBtn.disabled = false;
                });
            }
        }

        function submitUpdateForm() {
            const form = document.getElementById('updateProfileForm');
            
            // Remove formatting from phone number before submission
            const phoneInput = form.querySelector('input[name="phone"]');
            if (phoneInput) {
                phoneInput.value = phoneInput.value.replace(/-/g, '');
            }
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const formData = new FormData(form);
            formData.append('profile_changed', hasProfileChanges);
            
            const updateBtn = document.getElementById('updateProfileBtn');
            const spinner = updateBtn.querySelector('.spinner-border');
            const originalBtnHtml = updateBtn.innerHTML;
            
            updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            updateBtn.disabled = true;
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showToast('success', 'Profile updated successfully');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('error', data.message || 'Failed to update profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while updating the profile');
            })
            .finally(() => {
                updateBtn.innerHTML = originalBtnHtml;
                updateBtn.disabled = false;
            });
        }
    </script>
</body>
</html>