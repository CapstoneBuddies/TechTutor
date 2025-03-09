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

    <!-- Main CSS File -->
    <link href="<?php echo CSS; ?>main.css" rel="stylesheet">
</head>

<body class="index-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">
            <a href="<?php echo BASE; ?>" class="logo d-flex align-items-center me-auto">
                <img src="<?php echo IMG; ?>stand_alone_logo.png" alt="">
                <img src="<?php echo IMG; ?>TechTutor_text.png" alt="">
            </a>

            <nav id="navmenu" class="navmenu">
                <ul class="d-flex align-items-center">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-bell"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle main-avatar" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="avatar-icon">
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><span class="dropdown-item user-item"><?php echo $_SESSION['name']; ?></span></li>
                            <li><a class="dropdown-item" href="<?php echo BASE; ?>dashboard/profile" disabled>Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE; ?>dashboard/settings">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="user-logout">Log Out</a></li>
                        </ul>
                    </li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>
    <br><br><br><br>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>dashboard">
                                <i class="bi bi-house-door"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link active" disabled>
                                <i class="bi bi-person"></i>
                                Profile
                            </a>
                        </li>

                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>pages/admin/main_view-users">
                                <i class="bi bi-people"></i>
                                User Management
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>pages/admin/main_view-courses">
                                <i class="bi bi-book"></i>
                                Course Management
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if ($_SESSION['role'] === 'TechGuru'): ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>pages/guru/courses">
                                <i class="bi bi-book"></i>
                                My Courses
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>pages/guru/students">
                                <i class="bi bi-mortarboard"></i>
                                My Students
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if ($_SESSION['role'] === 'TechKids'): ?>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>pages/student/courses">
                                <i class="bi bi-book"></i>
                                My Courses
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>pages/student/progress">
                                <i class="bi bi-graph-up"></i>
                                My Progress
                            </a>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?php echo BASE; ?>dashboard/settings">
                                <i class="bi bi-gear"></i>
                                Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
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
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" value="<?php echo isset($_SESSION['address']) ? $_SESSION['address'] : ''; ?>" readonly>
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
                        </div>
                    </div>
                </div>
            </main>
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

    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/js/main.js"></script>

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

        function showToast(type, message) {
            const toastContainer = document.createElement('div');
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}-fill me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            toastContainer.appendChild(toast);
            document.body.appendChild(toastContainer);

            const bsToast = new bootstrap.Toast(toast, {
                animation: true,
                autohide: true,
                delay: 3000
            });

            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toastContainer);
            });
        }
    </script>
</body>
</html>