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
    <link href="<?php echo CSS; ?>main.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>profile.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <?php include ROOT_PATH . '/components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <?php include ROOT_PATH . '/components/header.php'; ?>
        
        <!-- Profile Content -->
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
                            <input type="email" class="form-control" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : ''; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" value="<?php echo isset($_SESSION['address']) ? $_SESSION['address'] : ''; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <div class="phone-input-container">
                                <select class="form-select country-code" name="countryCode" disabled style="max-width: 120px;">
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
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
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
        function openUpdateModal() {
            const modal = new bootstrap.Modal(document.getElementById('updateProfileModal'));
            modal.show();
        }

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.style.display = 'block';
        }

        function removeProfilePicture() {
            if(confirm('Are you sure you want to remove your profile picture? This cannot be undone.')) {
                // Add your remove profile picture logic here
                showToast('success', 'Profile picture removed successfully');
            }
        }

        function showToast(type, message) {
            const toastContainer = document.createElement('div');
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            toastContainer.appendChild(toast);
            document.body.appendChild(toastContainer);

            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', function () {
                document.body.removeChild(toastContainer);
            });
        }
    </script>
</body>
</html>