<?php 
    require_once '../../backends/config.php';
    require_once '../../backends/main.php';

    // Get current page from URL parameter, default to 1
    $techgurus_page = isset($_GET['tgpage']) ? (int)$_GET['tgpage'] : 1;
    $items_per_page = 50;

    // Get paginated data
    $techguru = getUserByRole('TECHGURU', $techgurus_page, $items_per_page);
    $techguruCount = getItemCountByTable('users','TECHGURU');

    // Calculate total pages
    $techgurus_total_pages = ceil($techguruCount / $items_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | TechGurus</title>
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
                            <h2>TechGurus</h2>
                            <div class="search-bar">
                                <i class="bi bi-search"></i>
                                <input type="text" id="searchInput" placeholder="Search TechGurus...">
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th><!-- Need to update table data  -->
                                        <th>Students</th><!-- Need to update table data(count)  -->
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techguru as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo USER_IMG.$user['profile_picture']; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>Computer Programming</td>
                                        <td>15 Students</td>
                                        <td>
                                            <span class="status-badge <?php echo getStatusBadgeClass($user['status']); ?>">
                                                <?php echo normalizeStatus($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary">View</button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="restrictAccount(<?php echo $user['uid']; ?>)">Restrict</button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAccountConfirm(<?php echo $user['uid']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($techgurus_total_pages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $techgurus_total_pages; $i++): ?>
                                <a href="?tgpage=<?php echo $i; ?>" class="<?php echo $techgurus_page == $i ? 'active' : ''; ?>">
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

    <!-- Vendor JS Files -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/php-email-form/validate.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Main JS File -->
    <script src="<?php echo BASE; ?>assets/js/main.js"></script>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
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

        // Sidebar toggle
        document.querySelector('.mobile-nav-toggle').addEventListener('click', function() {
            document.querySelector('body').classList.toggle('sidebar-open');
        });

        // Delete account confirmation
        function deleteAccountConfirm(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
            deleteModal.show();
        }

        // Delete account
        function deleteAccount() {
            const userId = document.getElementById('deleteUserId').value;
            const deleteButton = document.querySelector('#deleteAccountModal .btn-danger');
            
            // Show loading state
            deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            deleteButton.disabled = true;
            
            // Send AJAX request
            $.ajax({
                url: '<?php echo BASE; ?>admin-delete-user',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert('success', 'Account deleted successfully');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', response.message || 'Failed to delete account');
                        deleteButton.innerHTML = 'Delete Account';
                        deleteButton.disabled = false;
                    }
                    
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal'));
                    deleteModal.hide();
                },
                error: function(xhr) {
                    showAlert('error', 'Connection error. Please try again.');
                    deleteButton.innerHTML = 'Delete Account';
                    deleteButton.disabled = false;
                }
            });
        }

        // Restrict account
        function restrictAccount(userId) {
            document.getElementById('restrictUserId').value = userId;
            
            const restrictModal = new bootstrap.Modal(document.getElementById('restrictAccountModal'));
            restrictModal.show();
        }

        // Confirm restrict account
        function restrictAccountConfirmed() {
            const userId = document.getElementById('restrictUserId').value;
            const restrictButton = document.querySelector('#restrictAccountModal .btn-warning');
            
            // Show loading state
            restrictButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            restrictButton.disabled = true;
            
            // Send AJAX request
            $.ajax({
                url: '<?php echo BASE; ?>admin-restrict-user',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert('success', 'Account restricted successfully');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', response.message || 'Failed to restrict account');
                        restrictButton.innerHTML = 'Restrict Account';
                        restrictButton.disabled = false;
                    }
                    
                    const restrictModal = bootstrap.Modal.getInstance(document.getElementById('restrictAccountModal'));
                    restrictModal.hide();
                },
                error: function(xhr) {
                    showAlert('error', 'Connection error. Please try again.');
                    restrictButton.innerHTML = 'Restrict Account';
                    restrictButton.disabled = false;
                }
            });
        }

        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="bi ${icon} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Create a container for alerts if it doesn't exist
            let alertContainer = document.querySelector('.alert-container');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
                alertContainer.style.zIndex = '1050';
                document.body.appendChild(alertContainer);
            }
            
            // Add the alert to the container
            const alertElement = document.createElement('div');
            alertElement.innerHTML = alertHtml;
            alertContainer.appendChild(alertElement.firstChild);
            
            // Auto-remove the alert after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                if (alerts.length > 0) {
                    alerts[0].remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>