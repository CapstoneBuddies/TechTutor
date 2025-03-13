<?php 
    require_once '../../backends/main.php';
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | User Management</title>
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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS Files -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    
    <style>
        .user-filter-buttons {
            margin-bottom: 20px;
        }
        
        .user-filter-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .user-filter-buttons .btn.active {
            background-color: #FF6B00;
            border-color: #FF6B00;
            color: white;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .user-name-cell {
            display: flex;
            align-items: center;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        
        .reset-password-btn {
            margin-left: 10px;
        }
        
        /* Autocomplete styles */
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background-color: #fff;
        }
        
        .autocomplete-items div {
            padding: 10px;
            cursor: pointer;
            background-color: #fff;
            border-bottom: 1px solid #d4d4d4;
        }
        
        .autocomplete-items div:hover {
            background-color: #e9e9e9;
        }
        
        .autocomplete-active {
            background-color: #FF6B00 !important;
            color: #ffffff;
        }
        
        .autocomplete-container {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>
        
        <main class="dashboard-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">User Management (PA HELP KO TT)</h5>
                            </div>
                            <div class="card-body">
                                <!-- Filter Buttons -->
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <div class="user-filter-buttons">
                                            <button class="btn btn-outline-primary role-filter active" data-role="all">All Users</button>
                                            <button class="btn btn-outline-primary role-filter" data-role="ADMIN">Admins</button>
                                            <button class="btn btn-outline-primary role-filter" data-role="TECHGURU">Tech Gurus</button>
                                            <button class="btn btn-outline-primary role-filter" data-role="TECHKID">Tech Kids</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-warning reset-password-btn" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                            <i class="bi bi-key"></i> Reset Password
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Search Box -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="search-container">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name...">
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

    <!-- JavaScript Section -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mainContent = document.querySelector('.dashboard-content');

            // Variables
            let currentRole = 'all';
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
            
            // Modals
            const restrictUser = new bootstrap.Modal(document.getElementById('restrictUserModal'));
            const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            const confirmRestrictBtn = document.getElementById('confirmRestrictBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            // Load users on page load
            loadUsers();
            
            // Role filter click event
            roleFilters.forEach(button => {
                button.addEventListener('click', function() {
                    roleFilters.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    currentRole = this.dataset.role;
                    
                    // Show/hide role-specific columns
                    if (currentRole === 'TECHGURU') {
                        techguruColumn.forEach(col => col.classList.remove('d-none'));
                        techkidColumn.forEach(col => col.classList.add('d-none'));
                    } else if (currentRole === 'TECHKID') {
                        techkidColumn.forEach(col => col.classList.remove('d-none'));
                        techguruColumn.forEach(col => col.classList.add('d-none'));
                    } else {
                        techguruColumn.forEach(col => col.classList.add('d-none'));
                        techkidColumn.forEach(col => col.classList.add('d-none'));
                    }
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
                fetch('get-users', {
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
                    
                    // Classes created (for TECHGURU)
                    const classesCreatedCell = document.createElement('td');
                    if (currentRole === 'TECHGURU') {
                        classesCreatedCell.className = 'techguru-column text-center';
                    }
                    else {
                        classesCreatedCell.className = 'techguru-column d-none';
                    }
                    classesCreatedCell.textContent = user.num_classes || 0;
                    row.appendChild(classesCreatedCell);
                    
                    // Enrolled classes (for TECHKID)
                    const enrolledClassesCell = document.createElement('td');
                    if (currentRole === 'TECHKID') {
                        enrolledClassesCell.className = 'techkid-column text-center';
                    }
                    else {
                        enrolledClassesCell.className = 'techkid-column d-none';
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
                    
                    // Don't allow actions on current user
                    if (user.uid != <?php echo $_SESSION['user']; ?>) {
                        const restrictBtnText = user.status == 1 ? 'Restrict' : 'Activate';
                        const restrictBtnClass = user.status == 1 ? 'btn-warning' : 'btn-success';
                        const restrictBtnIcon = user.status == 1 ? 'bi-slash-circle' : 'bi-check-circle';
                        
                        actionsCell.innerHTML = `
                            <button class="btn ${restrictBtnClass} restrict-user" data-user-id="${user.uid}" data-status="${user.status}">
                                <i class="bi ${restrictBtnIcon}"></i> ${restrictBtnText}
                            </button>
                            <button class="btn btn-danger delete-user" data-user-id="${user.uid}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        `;
                    } else {
                        actionsCell.innerHTML = `<span class="text-muted">Current User</span>`;
                    }
                    
                    row.appendChild(actionsCell);
                    usersTableBody.appendChild(row);
                });
                
                // Add event listeners for action buttons
                addActionButtonListeners();
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
                        restrictUser.show();
                        console.log(restrictUser);
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
                    restrictUser.hide();
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
                fetch('toggle-user-status', {
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
                    } else {
                        alert(data.message || 'Failed to update user status.');
                    }
                })
                .catch(error => {
                    console.error('Error updating user status:', error);
                    alert('An error occurred. Please try again.');
                });
            }
            
            // Function to delete user
            function deleteUser(userId) {
                fetch('delete-user', {
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
                    } else {
                        alert(data.message || 'Failed to delete user.');
                    }
                })
                .catch(error => {
                    console.error('Error deleting user:', error);
                    alert('An error occurred. Please try again.');
                });
            }
            
            // Function to send password reset
            function sendPasswordReset(email) {
                fetch('send-password-reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${email}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Password reset link has been sent to the user.');
                        userEmailInput.value = '';
                        const resetPasswordModal = bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal'));
                        resetPasswordModal.hide();
                    } else {
                        alert(data.message || 'Failed to send password reset.');
                    }
                })
                .catch(error => {
                    console.error('Error sending password reset:', error);
                    alert('An error occurred. Please try again.');
                });
            }
            
            // Function to fetch user emails for autocomplete
            function fetchUserEmails(query) {
                fetch('get-user-emails', {
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