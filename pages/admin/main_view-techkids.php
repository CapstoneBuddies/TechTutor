<?php 
    require_once '../../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';

    // Get current page from URL parameter, default to 1
    $techkids_page = isset($_GET['tkpage']) ? (int)$_GET['tkpage'] : 1;
    $items_per_page = 50;

    // Get paginated data
    $techkids = getUserByRole('TECHKID', $techkids_page, $items_per_page);
    $techkidsCount = getItemCountByTable('users','TECHKID');

    // Calculate total pages
    $techkids_total_pages = ceil($techkidsCount / $items_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | TechKids</title>
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
    <style>
        .dashboard-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: #f5f5f5;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            width: 300px;
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            padding-left: 0.5rem;
            width: 100%;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-deleted {
            background-color: #263238;
            color: #ffffff;
        }

        .table-container {
            overflow-x: auto;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
        }

        .pagination a.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 1rem;
            }

            .search-bar {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="dashboard-content">
        <div class="container-fluid">
            <div class="row">
                <main class="col-12">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>TechKids</h2>
                            <div class="search-bar">
                                <i class="bi bi-search"></i>
                                <input type="text" id="searchInput" placeholder="Search TechKids...">
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Enrolled Courses</th><!-- Need to update table data  -->
                                        <th>Progress</th><!-- Need to update table data  -->
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techkids as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo USER_IMG.$user['profile_picture']; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>3 Courses</td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php 
                                                if ($user['status'] == 1) {
                                                    echo 'status-active';
                                                } elseif ($user['status'] == 0) {
                                                    echo 'status-inactive';
                                                } else {
                                                    echo 'status-deleted';
                                                }
                                            ?>">
                                                <?php 
                                                    if ($user['status'] == 1) {
                                                        echo 'Active';
                                                    } elseif ($user['status'] == 0) {
                                                        echo 'Inactive';
                                                    } else {
                                                        echo 'Deleted';
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary">View</button>
                                                <?php if ($user['status'] == 1): ?>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="showRestrictModal(<?php echo $user['uid']; ?>)">Restrict</button>
                                                <?php elseif ($user['status'] == 0): ?>
                                                    <button class="btn btn-sm btn-outline-success" onclick="showRestrictModal(<?php echo $user['uid']; ?>)">Activate</button>
                                                <?php endif; ?>
                                                <?php if ($user['status'] != 2): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="showDeleteModal(<?php echo $user['uid']; ?>)">Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($techkids_total_pages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $techkids_total_pages; $i++): ?>
                                <a href="?tkpage=<?php echo $i; ?>" class="<?php echo $techkids_page == $i ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span id="deleteUserName"></span>'s account? This action cannot be undone.</p>
                    <input type="hidden" id="deleteUserId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="deleteAccount()">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restrict Account Confirmation Modal -->
    <div class="modal fade" id="restrictAccountModal" tabindex="-1" aria-labelledby="restrictAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restrictAccountModalLabel">Restrict Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restrict this account? The user will not be able to access the platform until reactivated.</p>
                    <input type="hidden" id="restrictUserId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="restrictAccountConfirmed()">Restrict Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modals -->
    <div class="modal fade" id="restrictModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restrictModalTitle">Restrict TechKid Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="restrictModalBody">
                    Are you sure you want to restrict this TechKid's account? They will no longer be able to access the platform.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmRestrict">Restrict Account</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete TechKid Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this TechKid's account? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    
    <!-- Commenting out main.js temporarily to isolate issues -->
    <!-- <script src="<?php echo BASE; ?>assets/js/main.js"></script> -->
    
    <script>
        // Make functions available globally
        let selectedUserId = null;
        let selectedAction = null;
        
        function showRestrictModal(userId) {
            selectedUserId = userId;
            
            // Find the user's current status to determine if we're restricting or activating
            const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
            const statusBadge = userRow ? userRow.querySelector('.status-badge') : null;
            const isActive = statusBadge && statusBadge.classList.contains('status-active');
            
            // Set the appropriate modal content based on the action
            if (isActive) {
                selectedAction = 'restrict';
                document.getElementById('restrictModalTitle').textContent = 'Restrict TechKid Account';
                document.getElementById('restrictModalBody').textContent = 'Are you sure you want to restrict this TechKid\'s account? They will no longer be able to access the platform.';
                document.getElementById('confirmRestrict').textContent = 'Restrict Account';
                document.getElementById('confirmRestrict').className = 'btn btn-warning';
            } else {
                selectedAction = 'activate';
                document.getElementById('restrictModalTitle').textContent = 'Activate TechKid Account';
                document.getElementById('restrictModalBody').textContent = 'Are you sure you want to activate this TechKid\'s account? They will regain access to the platform.';
                document.getElementById('confirmRestrict').textContent = 'Activate Account';
                document.getElementById('confirmRestrict').className = 'btn btn-success';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('restrictModal'));
            modal.show();
        }

        function showDeleteModal(userId) {
            selectedUserId = userId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Add data-user-id attribute to each user row for easier reference
            document.querySelectorAll('.table tbody tr').forEach(row => {
                try {
                    // Get the user ID from the row's first action button with an onclick attribute
                    const actionButtons = row.querySelectorAll('.action-buttons button[onclick]');
                    if (actionButtons.length > 0) {
                        const onclickAttr = actionButtons[0].getAttribute('onclick');
                        const match = onclickAttr.match(/\d+/);
                        if (match) {
                            row.setAttribute('data-user-id', match[0]);
                        }
                    }
                } catch (error) {
                    console.error('Error setting data-user-id attribute:', error);
                }
            });
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchValue = this.value.toLowerCase();
                    const tableRows = document.querySelectorAll('.table tbody tr');
                    tableRows.forEach(row => {
                        const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                        const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                        const subject = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        if (name.includes(searchValue) || email.includes(searchValue) || subject.includes(searchValue)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // Sidebar toggle functionality
            const toggleSidebar = document.querySelector('.toggle-sidebar');
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    document.querySelector('body').classList.toggle('sidebar-open');
                });
            }

            // Add event listeners for modal buttons
            const confirmRestrict = document.getElementById('confirmRestrict');
            if (confirmRestrict) {
                confirmRestrict.addEventListener('click', function() {
                    if (!selectedUserId) return;
                    
                    // Determine the endpoint based on the action
                    const endpoint = selectedAction === 'activate' 
                        ? '<?php echo BASE; ?>admin-activate-user' 
                        : '<?php echo BASE; ?>admin-restrict-user';
                    
                    fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'userId=' + selectedUserId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload(); // Refresh to show updated status
                        } else {
                            alert(data.message || 'Failed to update account status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the account status');
                    });
                });
            }

            const confirmDelete = document.getElementById('confirmDelete');
            if (confirmDelete) {
                confirmDelete.addEventListener('click', function() {
                    if (!selectedUserId) return;
                    fetch('<?php echo BASE; ?>admin-delete-user', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'userId=' + selectedUserId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload(); // Refresh to show updated list
                        } else {
                            alert(data.message || 'Failed to delete account');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the account');
                    });
                });
            }
        });
    </script>
</body>
</html>