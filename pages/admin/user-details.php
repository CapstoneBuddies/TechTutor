<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'admin_management.php';

    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }
    $user = '';
    $title = '';
    if(isset($_GET['id'])) {
        $user = getUserDetails($_GET['id']);
        $title = $user['first_name'].' '.$user['last_name'];
    }
    if(empty($user)) {
        $_SESSION['msg'] = 'No such user exist';
        header("location ./");
        exit();
    }


 
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <link rel="stylesheet" href="<?php echo CSS.'profile.css'; ?>">
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
    <div class="profile-container">
        <div class="profile-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><?php echo isset($user['first_name']) && isset($user['last_name']) ? $user['first_name'] . ' ' . $user['last_name'] : 'Profile'; ?></h2>
                    <div class="role-badge">
                        <span class="role-text">
                            <?php 
                                $roleIcon = '';
                                switch($user['role']) {
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
                            <?php echo $user['role']; ?>
                        </span>
                        <span class="verified-badge">
                            <i class="bi bi-check-circle-fill"></i> Verified
                        </span>
                        <span class="update-role">
                            <button class="btn btn-primary btn-sm" id="updateRoleBtn">
                                <i class="bi bi-pencil-square"></i> Update Role
                            </button>
                        </span>

                    </div>
                </div>
                <div>
                    <a href="./" class="btn btn-warning">
                        <i class="bi bi-arrow-left"></i> Go Back
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 text-center mb-4">
                <img src="<?php echo USER_IMG.$user['profile_picture']; ?>" alt="Profile Picture" class="profile-picture">
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control item-disable" name="email" value="<?php echo $user['email']; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control item-disable" value="<?php echo isset($user['first_name']) && isset($user['last_name']) ? $user['first_name'] . ' ' . $user['last_name'] : ''; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control item-disable" value="<?php echo isset($user['address']) ? $user['address'] : ''; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <div class="phone-input-container">
                            <select class="form-select country-code item-disable" name="countryCode" disabled style="max-width: 120px;">
                                <option value="+63" <?php echo substr($user['phone'] ?? '', 0, 3) === '+63' ? 'selected' : ''; ?>>+63 (PH)</option>
                                <option value="+1" <?php echo substr($user['phone'] ?? '', 0, 2) === '+1' ? 'selected' : ''; ?>>+1 (US/CA)</option>
                                <option value="+44" <?php echo substr($user['phone'] ?? '', 0, 3) === '+44' ? 'selected' : ''; ?>>+44 (UK)</option>
                                <option value="+81" <?php echo substr($user['phone'] ?? '', 0, 3) === '+81' ? 'selected' : ''; ?>>+81 (JP)</option>
                                <option value="+82" <?php echo substr($user['phone'] ?? '', 0, 3) === '+82' ? 'selected' : ''; ?>>+82 (KR)</option>
                                <option value="+86" <?php echo substr($user['phone'] ?? '', 0, 3) === '+86' ? 'selected' : ''; ?>>+86 (CN)</option>
                            </select>
                            <input type="tel" class="form-control" name="phone" 
                            value="<?php $phone = $user['phone'] ?? ''; echo preg_match('/^\+\d{1,3}(.*)/', $phone, $matches) ? $matches[1] : $phone; ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main> <!-- Ending Main Container Content -->
    </div> <!-- End Header -->

    <!-- Update Role Modal -->
    <div class="modal fade" id="updateRoleModal" tabindex="-1" aria-labelledby="updateRoleLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateRoleLabel">Update User Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="newRole" class="form-label">Select New Role:</label>
                    <select class="form-select" id="newRole">
                        <option value="ADMIN" <?php echo ($user['role'] === 'ADMIN') ? 'disabled selected hidden' : ''; ?>>Admin</option>
                        <option value="TECHGURU" <?php echo ($user['role'] === 'TECHGURU') ? 'disabled selected hidden' : ''; ?>>Tech Guru</option>
                        <option value="TECHKID" <?php echo ($user['role'] === 'TECHKID') ? 'disabled selected hidden' : ''; ?>>Tech Kid</option>
                    </select>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveRoleBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <!-- Modified JavaScript Section -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let updateRoleBtn = document.getElementById("updateRoleBtn");
            let saveRoleBtn = document.getElementById("saveRoleBtn");
            let updateRoleModal = new bootstrap.Modal(document.getElementById("updateRoleModal"));

            updateRoleBtn.addEventListener("click", function() {
                updateRoleModal.show();
            });

            saveRoleBtn.addEventListener("click", function() {
                let userId = "<?php echo $_GET['id']; ?>";
                let newRole = document.getElementById("newRole").value;
                let updateRole = "<?php echo BASE; ?>update-role";

                fetch(updateRole, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${userId}&role=${newRole}`
                })
                .then(response => response.json())
                .then(data => {
                    updateRoleModal.hide();
                    if (data.success) {
                        showToast('success',"Role updated successfully!");
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error',"Failed to update role: " + data.message);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    showToast("error", "An error occurred while updating the user's role");
                    toggleStatusModal.hide();
                });
            });
        });
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