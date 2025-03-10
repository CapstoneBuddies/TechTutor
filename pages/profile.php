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
    <link href="<?php echo BASE; ?>assets/css/main.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/css/dashboard.css" rel="stylesheet">
</head>

<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="dashboard-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Main Content -->
                <main class="col-12">
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
                                        <input type="email" class="form-control" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" value="<?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : $_SESSION['name']; ?>" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" value="<?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : 'Not set'; ?>" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" rows="3" readonly><?php echo isset($_SESSION['address']) ? $_SESSION['address'] : 'Not set'; ?></textarea>
                                    </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
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
                                    <i class="bi bi-upload"></i> Upload Photo
                                </label>
                                <input type="file" id="profilePicture" name="profile_picture" class="hidden-file-input" accept="image/*">
                                <button type="button" id="removePhotoBtn" class="btn btn-outline-danger">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" name="name" value="<?php echo $_SESSION['name']; ?>" required>
                            <div class="invalid-feedback">Please enter your full name (3-50 characters)</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
                            <div class="form-text">Email address cannot be changed</div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : ''; ?>" placeholder="e.g., 09123456789">
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter your address"><?php echo isset($_SESSION['address']) ? $_SESSION['address'] : ''; ?></textarea>
                            <div class="invalid-feedback">Address cannot exceed 100 characters</div>
                        </div>

                        <input type="hidden" name="update" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="updateProfileBtn" onclick="submitUpdateForm()">
                        <i class="bi bi-check-lg"></i>
                        Update Profile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>

    <script>
        const updateModal = new bootstrap.Modal(document.getElementById('updateProfileModal'));
        let hasProfileChanges = false;
        
        function openUpdateModal() {
            updateModal.show();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Profile picture preview
            const profileInput = document.getElementById('profilePicture');
            const profilePreview = document.querySelector('.profile-picture-modal');
            const removePhotoBtn = document.getElementById('removePhotoBtn');
            
            profileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size exceeds 5MB. Please choose a smaller image.');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        alert('Please select an image file.');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                        hasProfileChanges = true;
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Remove photo functionality
            removePhotoBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove your profile picture?')) {
                    const formData = new FormData();
                    formData.append('remove_photo', '1');
                    
                    // Show loading state
                    removePhotoBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Removing...';
                    removePhotoBtn.disabled = true;
                    
                    fetch('user-profile-update', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update all profile pictures on the page
                            const defaultImg = '<?php echo BASE; ?>assets/img/users/default.png';
                            document.querySelectorAll('.profile-picture, .profile-picture-modal, .avatar-icon').forEach(img => {
                                img.src = defaultImg;
                            });
                            
                            alert('Profile picture removed successfully.');
                        } else {
                            alert('Failed to remove profile picture: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while removing the profile picture.');
                    })
                    .finally(() => {
                        // Reset button state
                        removePhotoBtn.innerHTML = '<i class="bi bi-trash"></i> Remove';
                        removePhotoBtn.disabled = false;
                    });
                }
            });
            
            // Form validation
            const fullNameInput = document.getElementById('fullName');
            const phoneInput = document.getElementById('phone');
            const addressInput = document.getElementById('address');
            
            fullNameInput.addEventListener('input', function() {
                validateFullName(this);
            });
            
            phoneInput.addEventListener('input', function() {
                validatePhone(this);
            });
            
            addressInput.addEventListener('input', function() {
                validateAddress(this);
            });
        });
        
        function validateFullName(input) {
            const value = input.value.trim();
            const isValid = value.length >= 3 && value.length <= 50;
            
            if (isValid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
            
            return isValid;
        }
        
        function validatePhone(input) {
            const value = input.value.trim();
            
            // Allow empty phone or validate format
            if (value === '') {
                input.classList.remove('is-invalid');
                input.classList.remove('is-valid');
                return true;
            }
            
            // Check for 11 digits
            const isValid = /^\d{11}$/.test(value);
            
            if (isValid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
            
            return isValid;
        }
        
        function validateAddress(input) {
            const value = input.value.trim();
            
            // Allow empty address or validate length
            if (value === '') {
                input.classList.remove('is-invalid');
                input.classList.remove('is-valid');
                return true;
            }
            
            const isValid = value.length <= 100;
            
            if (isValid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
            
            return isValid;
        }
        
        function submitUpdateForm() {
            const form = document.getElementById('updateProfileForm');
            
            // Remove formatting from phone number before submission
            const phoneInput = form.querySelector('input[name="phone"]');
            if (phoneInput.value) {
                phoneInput.value = phoneInput.value.replace(/\D/g, '');
            }
            
            // Validate all fields
            const isFullNameValid = validateFullName(form.querySelector('input[name="name"]'));
            const isPhoneValid = validatePhone(phoneInput);
            const isAddressValid = validateAddress(form.querySelector('textarea[name="address"]'));
            
            if (isFullNameValid && isPhoneValid && isAddressValid) {
                // Show loading state
                const updateBtn = document.getElementById('updateProfileBtn');
                updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                updateBtn.disabled = true;
                
                // Submit form
                form.submit();
            }
        }
    </script>
</body>
</html>