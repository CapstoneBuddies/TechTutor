<?php 
    require_once '../../backends/main.php';
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }
    if(isset($error)) {
        log_error($error,1);
    }
    $title = 'Users Management';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
        <main class="dashboard-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">User Management</h5>
                            </div>
                            <div class="card-body">
                                <!-- Filter Buttons -->
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <div class="user-filter-buttons">
                                            <button class="btn btn-outline-primary role-filter <?php echo !isset($_GET['role']) || $_GET['role'] === 'all' ? 'active' : ''; ?>" data-role="all">All Users</button>
                                            <button class="btn btn-outline-primary role-filter <?php echo isset($_GET['role']) && $_GET['role'] === 'ADMIN' ? 'active' : ''; ?>" data-role="ADMIN">Admins</button>
                                            <button class="btn btn-outline-primary role-filter <?php echo isset($_GET['role']) && $_GET['role'] === 'TECHGURU' ? 'active' : ''; ?>" data-role="TECHGURU">Tech Gurus</button>
                                            <button class="btn btn-outline-primary role-filter <?php echo isset($_GET['role']) && $_GET['role'] === 'TECHKID' ? 'active' : ''; ?>" data-role="TECHKID">Tech Kids</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-warning reset-password-btn" data-bs-toggle="modal" data-bs-target="#resetPasswordModal"><i class="bi bi-key"></i> Reset Password</button>
                                    </div>
                                </div>
                                <!-- Search Box -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="search-container">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Users Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th class="techguru-column d-none">Classes Created</th>
                                                <th class="techkid-column d-none">Enrolled Classes</th>
                                                <th>Status</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <!-- User data will be loaded here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Loading Indicator -->
                                <div id="loadingIndicator" class="text-center my-4 d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <!-- No Results Message -->
                                <div id="noResults" class="alert alert-info text-center my-4 d-none">
                                    No users found matching your criteria.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        </main> <!-- Ending All Main Content -->
        </div>
        <!-- Reset Password Modal -->
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resetPasswordModalLabel">Reset User Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="resetPasswordForm">
                            <div class="mb-3 autocomplete-container">
                                <label for="userEmail" class="form-label">User Email</label>
                                <input type="email" class="form-control" id="userEmail" placeholder="Start typing user email..." required>
                                <div id="emailAutocomplete" class="autocomplete-items d-none"></div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Restrict User Modal -->
        <div class="modal fade" id="restrictUserModal" tabindex="-1" aria-labelledby="restrictUserLabel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="restrictUserLabel">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="restrictUserMessage">Are you sure you want to restrict this user?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" id="confirmRestrictBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete User Modal -->
        <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mainContent = document.querySelector('.dashboard-content');

            // Variables
            let currentRole = '<?php echo isset($_GET['role']) ? $_GET['role'] : 'all'; ?>';
            let currentSearch = '';
            let selectedUserId = null;
            
            // DOM Elements
            const roleFilters = document.querySelectorAll('.role-filter');
            const searchInput = document.getElementById('searchInput');
            const usersTableBody = document.getElementById('usersTableBody');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const noResults = document.getElementById('noResults');
            const techguruColumn = document.querySelectorAll('.techguru-column');
            const techkidColumn = document.querySelectorAll('.techkid-column');
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            const userEmailInput = document.getElementById('userEmail');
            const emailAutocomplete = document.getElementById('emailAutocomplete');
            const resetPasswordModalEl = document.getElementById('resetPasswordModal');
            
            // Modals
            const restrictUserModal = new bootstrap.Modal(document.getElementById('restrictUserModal'));
            const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            const confirmRestrictBtn = document.getElementById('confirmRestrictBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const resetPasswordModal = bootstrap.Modal.getInstance(resetPasswordModalEl) || new bootstrap.Modal(resetPasswordModalEl);


            // Actions
            const getUsers =  "<?php echo BASE.'get-users'; ?>";
            const userStatus = "<?php echo BASE.'toggle-user-status'; ?>"; 
            const deleteUserLink = "<?php echo BASE.'delete-user'; ?>"; 
            const passwordReset = "<?php echo BASE.'send-password-reset'; ?>"; 
            const getUserEmail = "<?php echo BASE.'get-user-emails'; ?>"; 
            
            // Load users on page load
            loadUsers();
            
            // Role filter click event
            roleFilters.forEach(button => {
                button.addEventListener('click', function() {
                    const role = this.dataset.role;
                    document.querySelectorAll('.role-filter').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    currentRole = role;
                    updateURL(role);
                    loadUsers();
                });
            });
            
            // Search input event
            searchInput.addEventListener('input', function() {
                currentSearch = this.value.trim();
                loadUsers();
            });
            
            // Email autocomplete for reset password
            userEmailInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length > 1) {
                    fetchUserEmails(query);
                } else {
                    emailAutocomplete.classList.add('d-none');
                    emailAutocomplete.innerHTML = '';
                }
            });
            
            // Reset password form submit
            resetPasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = userEmailInput.value.trim();
                if (email) {
                    sendPasswordReset(email);
                }
            });
            
            // Function to load users
            function loadUsers() {
                showLoading(true);
                
                // AJAX request to get users
                fetch(getUsers, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `role=${currentRole}&search=${currentSearch}`
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    
                    if (data.success && data.users.length > 0) {
                        renderUsers(data.users);
                        noResults.classList.add('d-none');
                    } else {
                        usersTableBody.innerHTML = '';
                        noResults.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error fetching users:', error);
                    showLoading(false);
                    noResults.classList.remove('d-none');
                    noResults.textContent = 'Error loading users. Please try again.';
                });
            }
            
            // Function to render users
            function renderUsers(users) {
                usersTableBody.innerHTML = '';
                
                users.forEach(user => {
                    const row = document.createElement('tr');

                    // Name cell with profile picture
                    const nameCell = document.createElement('td');
                    nameCell.className = 'user-name-cell';
                    nameCell.innerHTML = `
                        <img src="${'<?php echo USER_IMG; ?>' + (user.profile_picture || 'default.jpg')}" alt="${user.first_name}" class="user-avatar">
                        <span>${user.first_name} ${user.last_name}</span>
                    `;
                    row.appendChild(nameCell);

                    // Email cell
                    const emailCell = document.createElement('td');
                    emailCell.textContent = user.email;
                    row.appendChild(emailCell);

                    // Classes Created (for TECHGURU)
                    const classesCreatedCell = document.createElement('td');
                    classesCreatedCell.className = 'techguru-column text-center';
                    if (currentRole !== 'TECHGURU') {
                        classesCreatedCell.classList.add('d-none');
                    }
                    classesCreatedCell.textContent = user.num_classes || 0;
                    row.appendChild(classesCreatedCell);

                    // Enrolled Classes (for TECHKID)
                    const enrolledClassesCell = document.createElement('td');
                    enrolledClassesCell.className = 'techkid-column text-center';
                    if (currentRole !== 'TECHKID') {
                        enrolledClassesCell.classList.add('d-none');
                    }
                    enrolledClassesCell.textContent = user.num_classes || 0;
                    row.appendChild(enrolledClassesCell);

                    // Status cell
                    const statusCell = document.createElement('td');
                    const statusText = user.status == 1 ? 'Active' : 'Inactive';
                    const statusClass = user.status == 1 ? 'bg-success' : 'bg-danger';
                    statusCell.innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;
                    row.appendChild(statusCell);

                    // Last login cell
                    const lastLoginCell = document.createElement('td');
                    lastLoginCell.textContent = user.last_login ? formatDate(user.last_login) : 'Never';
                    row.appendChild(lastLoginCell);

                    // Actions cell
                    const actionsCell = document.createElement('td');

                    // Don't allow actions on the current user
                    if (user.uid != <?php echo $_SESSION['user']; ?>) {
                        const restrictBtnText = user.status == 1 ? 'Restrict' : 'Activate';
                        const restrictBtnClass = user.status == 1 ? 'btn-warning' : 'btn-success';
                        const restrictBtnIcon = user.status == 1 ? 'fas fa-ban' : 'fas fa-check-circle';

                        actionsCell.innerHTML = `
                            <button class="btn ${restrictBtnClass} restrict-user" data-user-id="${user.uid}" data-status="${user.status}" data-bs-toggle="tooltip" title='${restrictBtnText}'>
                                <i class="${restrictBtnIcon}"></i> 
                            </button>
                            <a href="users/details?id=${user.uid}" class="btn btn-info view-user" data-bs-toggle="tooltip" title='View'>
                                <i class="bi bi-eye"></i> 
                            </a>
                        `;
                    } else {
                        actionsCell.innerHTML = `<span class="text-muted">Current User</span>`;
                    }

                    row.appendChild(actionsCell);
                    usersTableBody.appendChild(row);
                });

                // Show/hide role-specific columns dynamically
                updateRoleColumns();

                // Add event listeners for action buttons
                addActionButtonListeners();
            }

            // Function to ensure role-specific columns are properly displayed
            function updateRoleColumns() {
                const techguruColumns = document.querySelectorAll('.techguru-column');
                const techkidColumns = document.querySelectorAll('.techkid-column');

                if (currentRole === 'TECHGURU') {
                    techguruColumns.forEach(col => col.classList.remove('d-none'));
                } else {
                    techguruColumns.forEach(col => col.classList.add('d-none'));
                }

                if (currentRole === 'TECHKID') {
                    techkidColumns.forEach(col => col.classList.remove('d-none'));
                } else {
                    techkidColumns.forEach(col => col.classList.add('d-none'));
                }
            }

            
            // Function to add event listeners to action buttons
            function addActionButtonListeners() {
                // Restrict user buttons
                document.querySelectorAll('.restrict-user').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.dataset.userId;
                        const status = this.dataset.status;
                        const action = status == 1 ? 'restrict' : 'activate';
                        const message = status == 1 
                            ? 'Are you sure you want to restrict this user? They will no longer be able to log in.'
                            : 'Are you sure you want to activate this user? They will be able to log in again.';
                        
                        document.getElementById('restrictUserMessage').textContent = message;
                        selectedUserId = userId;
                        restrictUserModal.show();
                    });
                });
                
                // Delete user buttons
                document.querySelectorAll('.delete-user').forEach(button => {
                    button.addEventListener('click', function() {
                        selectedUserId = this.dataset.userId;
                        deleteUserModal.show();
                    });
                });
            }
            
            // Confirm restrict user
            confirmRestrictBtn.addEventListener('click', function() {
                if (selectedUserId) {
                    toggleUserStatus(selectedUserId);
                    restrictUserModal.hide();
                }
            });
            
            // Confirm delete user
            confirmDeleteBtn.addEventListener('click', function() {
                if (selectedUserId) {
                    deleteUser(selectedUserId);
                    deleteUserModal.hide();
                }
            });
            
            // Function to toggle user status (restrict/activate)
            function toggleUserStatus(userId) {
                showLoading(true);
                fetch(userStatus, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadUsers();
                        showToast('success', "User status was successfully updated.");
                    } else {
                        showToast('error', data.message || 'Failed to update user status.');
                    }
                })
                .catch(error => {
                    console.error('Error updating user status:', error);
                    showToast('error', data.message || 'An error occurred. Please try again.');
                });
            }
            
            // Function to delete user
            function deleteUser(userId) {
                fetch(deleteUserLink, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadUsers();
                        showToast('success' ,data.message || 'User was successfully deleted.');
                    } else {
                        showToast('error' ,data.message || 'Failed to delete user.');
                    }
                })
                .catch(error => {
                    console.error('Error deleting user:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }
            
            // Function to send password reset
            function sendPasswordReset(email) {
                fetch(passwordReset, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${email}`
                })
                .then(response => response.json())
                .then(data => {
                        resetPasswordModal.hide();
                        setTimeout(() => {
                            document.body.classList.remove('modal-open');
                            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        }, 300);
                    if (data.success) {
                        showToast('success', 'Password reset link has been sent to the user.');
                        userEmailInput.value = '';
                    } else {
                        showToast('error', data.message || 'Failed to send password reset.');
                    }
                })
                .catch(error => {
                    console.error('Error sending password reset:', error);
                    showToast('error', 'An error occurred. Please try again.');
                });
            }
            
            // Function to fetch user emails for autocomplete
            function fetchUserEmails(query) {
                fetch(getUserEmail, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `query=${query}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users.length > 0) {
                        showEmailAutocomplete(data.users);
                    } else {
                        emailAutocomplete.classList.add('d-none');
                        emailAutocomplete.innerHTML = '';
                    }
                })
                .catch(error => {
                    console.error('Error fetching user emails:', error);
                    showToast('error', 'An error occured while getting the user emails');
                    emailAutocomplete.classList.add('d-none');
                });
            }
            
            // Function to show email autocomplete
            function showEmailAutocomplete(users) {
                emailAutocomplete.innerHTML = '';
                
                users.forEach(user => {
                    // Skip current user
                    if (user.uid == <?php echo $_SESSION['user']; ?>) {
                        return;
                    }
                    
                    const div = document.createElement('div');
                    div.textContent = `${user.email} <${user.first_name} ${user.last_name}>`;
                    
                    div.addEventListener('click', function() {
                        userEmailInput.value = user.email;
                        emailAutocomplete.classList.add('d-none');
                    });
                    
                    emailAutocomplete.appendChild(div);
                });
                
                if (emailAutocomplete.children.length > 0) {
                    emailAutocomplete.classList.remove('d-none');
                } else {
                    emailAutocomplete.classList.add('d-none');
                }
            }
            
            // Close autocomplete when clicking outside
            document.addEventListener('click', function(e) {
                if (!userEmailInput.contains(e.target) && !emailAutocomplete.contains(e.target)) {
                    emailAutocomplete.classList.add('d-none');
                }
            });
            
            // Function to update URL without reloading
            function updateURL(role) {
                const url = new URL(window.location);
                url.searchParams.set('role', role);
                window.history.pushState({}, '', url);
            }
            
            // Helper function to show/hide loading indicator
            function showLoading(show) {
                if (show) {
                    loadingIndicator.classList.remove('d-none');
                    usersTableBody.innerHTML = '';
                    noResults.classList.add('d-none');
                } else {
                    loadingIndicator.classList.add('d-none');
                }
            }
            
            // Helper function to format date
            function formatDate(dateString) {
                if (!dateString) return 'Never';
                
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = Math.abs(now - date);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays < 1) {
                    return 'Today at ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                } else if (diffDays === 1) {
                    return 'Yesterday at ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                } else {
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }
        });
    </script>
    </body>
</html>