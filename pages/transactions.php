<?php 
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body>
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                                All User Transactions
                            <?php else: ?>
                                My Transactions
                            <?php endif; ?>
                        </h5>
                        <div>
                            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="exportTransactions()">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <?php endif; ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="filterTransactions('all')">All</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterTransactions('completed')">Completed</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterTransactions('pending')">Pending</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterTransactions('failed')">Failed</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                                            <th>User</th>
                                            <th>Role</th>
                                        <?php endif; ?>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionsTableBody">
                                    <!-- Transaction rows will be loaded here via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <div id="loadingSpinner" class="text-center d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="noTransactions" class="text-center d-none">
                            <p class="text-muted">No transactions found</p>
                        </div>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center" id="pagination">
                                <!-- Pagination will be generated via JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="transaction-details">
                        <!-- Details will be loaded here via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    </main>
    </div>

    <!-- JavaScript Section -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script>
        let currentPage = 1;
        let currentFilter = 'all';

        $(document).ready(function() {
            loadTransactions(currentPage, currentFilter);
        });

        function loadTransactions(page, filter = 'all') {
            const loadingSpinner = $('#loadingSpinner');
            const noTransactions = $('#noTransactions');
            const tableBody = $('#transactionsTableBody');

            loadingSpinner.removeClass('d-none');
            tableBody.html('');
            noTransactions.addClass('d-none');

            $.ajax({
                url: '<?php echo BASE; ?>get-transactions',
                type: 'GET',
                data: { 
                    page: page,
                    filter: filter,
                    role: '<?php echo $_SESSION['role']; ?>',
                    userId: <?php echo $_SESSION['user']; ?>
                },
                success: function(response) {
                    loadingSpinner.addClass('d-none');
                    
                    if (response.transactions && response.transactions.length > 0) {
                        response.transactions.forEach(transaction => {
                            const row = createTransactionRow(transaction);
                            tableBody.append(row);
                        });
                        updatePagination(response.totalPages, page);
                    } else {
                        noTransactions.removeClass('d-none');
                    }
                },
                error: function() {
                    loadingSpinner.addClass('d-none');
                    showAlert('error', 'Failed to load transactions');
                }
            });
        }

        function createTransactionRow(transaction) {
            let row = `<tr>
                <td>${transaction.id}</td>`;
            
            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                row += `<td>${transaction.userName}</td>
                       <td><span class="badge bg-${getRoleBadgeColor(transaction.userRole)}">${transaction.userRole}</span></td>`;
            <?php endif; ?>

            row += `<td>${formatDate(transaction.date)}</td>
                   <td>${transaction.type}</td>
                   <td>${formatAmount(transaction.amount)}</td>
                   <td><span class="badge bg-${getStatusBadgeColor(transaction.status)}">${transaction.status}</span></td>
                   <td>
                       <button class="btn btn-sm btn-outline-primary" onclick="viewTransactionDetails('${transaction.id}')">
                           <i class="bi bi-eye"></i>
                       </button>
                   </td>
                </tr>`;
            return row;
        }

        function getRoleBadgeColor(role) {
            switch(role) {
                case 'ADMIN': return 'danger';
                case 'TECHGURU': return 'primary';
                case 'TECHKID': return 'success';
                default: return 'secondary';
            }
        }

        function getStatusBadgeColor(status) {
            switch(status.toLowerCase()) {
                case 'completed': return 'success';
                case 'pending': return 'warning';
                case 'failed': return 'danger';
                default: return 'secondary';
            }
        }

        function formatDate(dateString) {
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function formatAmount(amount) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(amount);
        }

        function updatePagination(totalPages, currentPage) {
            const pagination = $('#pagination');
            pagination.empty();

            if (totalPages <= 1) return;

            let paginationHtml = `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadTransactions(${currentPage - 1}, '${currentFilter}')">&laquo;</a>
                </li>`;

            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadTransactions(${i}, '${currentFilter}')">${i}</a>
                    </li>`;
            }

            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadTransactions(${currentPage + 1}, '${currentFilter}')">&raquo;</a>
                </li>`;

            pagination.html(paginationHtml);
        }

        function filterTransactions(filter) {
            currentFilter = filter;
            currentPage = 1;
            loadTransactions(currentPage, filter);
        }

        function viewTransactionDetails(transactionId) {
            const modal = $('#transactionDetailsModal');
            const detailsContainer = modal.find('.transaction-details');
            
            detailsContainer.html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');
            modal.modal('show');

            $.ajax({
                url: '<?php echo BASE; ?>get-transaction-details',
                type: 'GET',
                data: { id: transactionId },
                success: function(response) {
                    if (response.success) {
                        const details = response.transaction;
                        let detailsHtml = `
                            <div class="mb-3">
                                <strong>Transaction ID:</strong> ${details.id}
                            </div>
                            <div class="mb-3">
                                <strong>Date:</strong> ${formatDate(details.date)}
                            </div>
                            <div class="mb-3">
                                <strong>Type:</strong> ${details.type}
                            </div>
                            <div class="mb-3">
                                <strong>Amount:</strong> ${formatAmount(details.amount)}
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong> 
                                <span class="badge bg-${getStatusBadgeColor(details.status)}">${details.status}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Description:</strong><br>
                                ${details.description || 'No description available'}
                            </div>`;

                        if (details.reference) {
                            detailsHtml += `
                                <div class="mb-3">
                                    <strong>Reference Number:</strong> ${details.reference}
                                </div>`;
                        }

                        detailsContainer.html(detailsHtml);
                    } else {
                        detailsContainer.html('<div class="alert alert-danger">Failed to load transaction details</div>');
                    }
                },
                error: function() {
                    detailsContainer.html('<div class="alert alert-danger">An error occurred while loading transaction details</div>');
                }
            });
        }

        function exportTransactions() {
            window.location.href = `<?php echo BASE; ?>export-transactions?filter=${currentFilter}`;
        }

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(function() {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>