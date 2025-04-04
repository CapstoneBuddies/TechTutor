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
                                All Transaction Disputes
                            <?php else: ?>
                                My Transaction Disputes
                            <?php endif; ?>
                        </h5>
                        <div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="filterDisputes('all')">All</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterDisputes('pending')">Pending</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterDisputes('under_review')">Under Review</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterDisputes('resolved')">Resolved</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterDisputes('rejected')">Rejected</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="filterDisputes('cancelled')">Cancelled</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dispute ID</th>
                                        <th>Transaction ID</th>
                                        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                                            <th>User</th>
                                            <th>Role</th>
                                        <?php endif; ?>
                                        <th>Date Filed</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="disputesTableBody">
                                    <!-- Dispute rows will be loaded here via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <div id="loadingSpinner" class="text-center d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="noDisputes" class="text-center d-none">
                            <p class="text-muted">No disputes found</p>
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

    <!-- Dispute Details Modal -->
    <div class="modal fade" id="disputeDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispute Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="dispute-details">
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
            loadDisputes(currentPage, currentFilter);
        });

        function loadDisputes(page, status = 'all') {
            const loadingSpinner = $('#loadingSpinner');
            const noDisputes = $('#noDisputes');
            const tableBody = $('#disputesTableBody');

            loadingSpinner.removeClass('d-none');
            tableBody.html('');
            noDisputes.addClass('d-none');

            $.ajax({
                url: '<?php echo BASE; ?>get-disputes',
                type: 'GET',
                data: { 
                    page: page,
                    status: status
                },
                success: function(response) {
                    loadingSpinner.addClass('d-none');
                    
                    if (response.disputes && response.disputes.length > 0) {
                        response.disputes.forEach(dispute => {
                            const row = createDisputeRow(dispute);
                            tableBody.append(row);
                        });
                        updatePagination(response.totalPages, page);
                    } else {
                        noDisputes.removeClass('d-none');
                    }
                },
                error: function() {
                    loadingSpinner.addClass('d-none');
                    showAlert('error', 'Failed to load disputes');
                }
            });
        }

        function createDisputeRow(dispute) {
            let row = `<tr>
                <td>${dispute.id}</td>
                <td>${dispute.transactionId}</td>`;
            
            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                row += `<td>${dispute.userName}</td>
                       <td><span class="badge bg-${getRoleBadgeColor(dispute.userRole)}">${dispute.userRole}</span></td>`;
            <?php endif; ?>

            row += `<td>${formatDate(dispute.createdAt)}</td>
                   <td>${formatAmount(dispute.transactionAmount)}</td>
                   <td><span class="badge bg-${getDisputeStatusBadgeColor(dispute.status)}">${getDisputeStatusText(dispute.status)}</span></td>
                   <td>
                       <button class="btn btn-sm btn-outline-primary" onclick="viewDisputeDetails(${dispute.id})">
                           <i class="bi bi-eye"></i>
                       </button>
                       ${(dispute.status === 'pending' || dispute.status === 'under_review') ? 
                        `<button class="btn btn-sm btn-outline-danger ms-1" onclick="cancelDispute(${dispute.id})">
                            <i class="bi bi-x-circle"></i>
                         </button>` : ''}
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

        function getDisputeStatusBadgeColor(status) {
            switch(status) {
                case 'pending': return 'warning';
                case 'under_review': return 'info';
                case 'resolved': return 'success';
                case 'rejected': return 'danger';
                case 'cancelled': return 'secondary';
                default: return 'secondary';
            }
        }

        function getDisputeStatusText(status) {
            switch(status) {
                case 'pending': return 'Pending';
                case 'under_review': return 'Under Review';
                case 'resolved': return 'Resolved';
                case 'rejected': return 'Rejected';
                case 'cancelled': return 'Cancelled';
                default: return status;
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
                    <a class="page-link" href="#" onclick="loadDisputes(${currentPage - 1}, '${currentFilter}')">&laquo;</a>
                </li>`;

            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadDisputes(${i}, '${currentFilter}')">${i}</a>
                    </li>`;
            }

            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadDisputes(${currentPage + 1}, '${currentFilter}')">&raquo;</a>
                </li>`;

            pagination.html(paginationHtml);
        }

        function filterDisputes(status) {
            currentFilter = status;
            currentPage = 1;
            loadDisputes(currentPage, status);
        }

        function viewDisputeDetails(disputeId) {
            const modal = $('#disputeDetailsModal');
            const detailsContainer = modal.find('.dispute-details');
            
            // Get the dispute details by finding it in the table data
            const disputeRow = $(`#disputesTableBody tr:has(td:contains(${disputeId}))`);
            if (disputeRow.length === 0) {
                showAlert('error', 'Dispute details not found');
                return;
            }
            
            // For a complete implementation, you would make an AJAX call to get full dispute details
            // For now, we'll just use the information from the row
            const dispute = getDisputeDetailsFromRow(disputeRow);
            
            detailsContainer.html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');
            modal.modal('show');
            
            // Display the dispute details in the modal
            const detailsHtml = `
                <div class="mb-3">
                    <strong>Dispute ID:</strong> ${dispute.id}
                </div>
                <div class="mb-3">
                    <strong>Transaction ID:</strong> ${dispute.transactionId}
                </div>
                <div class="mb-3">
                    <strong>Date Filed:</strong> ${dispute.date}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> 
                    <span class="badge bg-${getDisputeStatusBadgeColor(dispute.status)}">${getDisputeStatusText(dispute.status)}</span>
                </div>
                ${<?php echo $_SESSION['role'] === 'ADMIN' ? 'true' : 'false'; ?> ? `
                <div class="mb-3">
                    <strong>User:</strong> ${dispute.userName || 'N/A'}
                </div>` : ''}
                <div class="mb-3">
                    <strong>Amount:</strong> ${dispute.amount}
                </div>
                <div class="mb-3">
                    <strong>Reason:</strong><br>
                    <p class="text-muted">${dispute.reason || 'No reason provided'}</p>
                </div>
                ${dispute.adminNotes ? `
                <div class="mb-3">
                    <strong>Admin Notes:</strong><br>
                    <p class="text-muted">${dispute.adminNotes}</p>
                </div>` : ''}
            `;
            
            detailsContainer.html(detailsHtml);
            
            // Add action buttons if applicable
            if (dispute.status === 'pending' || dispute.status === 'under_review') {
                let actionsHtml = `
                    <hr>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-danger" onclick="cancelDispute(${dispute.id})">
                            Cancel Dispute
                        </button>
                    </div>
                `;
                
                // Add admin controls
                if (<?php echo $_SESSION['role'] === 'ADMIN' ? 'true' : 'false'; ?>) {
                    actionsHtml = `
                        <hr>
                        <div class="admin-dispute-controls">
                            <h6>Admin Actions</h6>
                            <div class="mb-3">
                                <label for="adminNotes" class="form-label">Admin Notes</label>
                                <textarea class="form-control" id="adminNotes" rows="3">${dispute.adminNotes || ''}</textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-primary" onclick="updateDisputeStatus(${dispute.id}, 'under_review')">
                                        Mark Under Review
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="updateDisputeStatus(${dispute.id}, 'resolved')">
                                        Resolve
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="updateDisputeStatus(${dispute.id}, 'rejected')">
                                        Reject
                                    </button>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="processRefund(${dispute.transactionId}, ${dispute.id})">
                                    Process Refund
                                </button>
                            </div>
                        </div>
                    `;
                }
                
                detailsContainer.append(actionsHtml);
            }
        }

        function getDisputeDetailsFromRow(row) {
            const cells = row.find('td');
            const id = $(cells[0]).text();
            const transactionId = $(cells[1]).text();
            const date = $(cells[<?php echo $_SESSION['role'] === 'ADMIN' ? '4' : '2'; ?>]).text();
            const amount = $(cells[<?php echo $_SESSION['role'] === 'ADMIN' ? '5' : '3'; ?>]).text();
            const statusCell = $(cells[<?php echo $_SESSION['role'] === 'ADMIN' ? '6' : '4'; ?>]).find('span');
            const status = statusCell.text();
            
            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
            const userName = $(cells[2]).text();
            const userRole = $(cells[3]).find('span').text();
            <?php endif; ?>
            
            return {
                id,
                transactionId,
                date,
                amount,
                status,
                <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                userName,
                userRole,
                <?php endif; ?>
                // These fields would normally come from an API call
                reason: "This dispute is pending additional information from the API.",
                adminNotes: ""
            };
        }

        function cancelDispute(disputeId) {
            if (!confirm('Are you sure you want to cancel this dispute?')) {
                return;
            }
            
            $.ajax({
                url: '<?php echo BASE; ?>cancel-dispute',
                type: 'POST',
                data: {
                    dispute_id: disputeId
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        $('#disputeDetailsModal').modal('hide');
                        // Reload the disputes list
                        loadDisputes(currentPage, currentFilter);
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to cancel dispute');
                }
            });
        }

        function updateDisputeStatus(disputeId, status) {
            const adminNotes = $('#adminNotes').val().trim();
            
            $.ajax({
                url: '<?php echo BASE; ?>update-dispute',
                type: 'POST',
                data: {
                    dispute_id: disputeId,
                    status: status,
                    admin_notes: adminNotes
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        $('#disputeDetailsModal').modal('hide');
                        // Reload the disputes list
                        loadDisputes(currentPage, currentFilter);
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to update dispute status');
                }
            });
        }

        function processRefund(transactionId, disputeId) {
            // Redirect to a more detailed refund processing page or open a modal
            // This would normally involve fetching transaction details and showing a form
            if (confirm('Process refund for this transaction? This will take you to the refund processing screen.')) {
                window.location.href = `<?php echo BASE; ?>refund-transaction?transaction_id=${transactionId}&dispute_id=${disputeId}`;
            }
        }

        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Add alert to the page
            $('.container-fluid').prepend(alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        }
    </script>
</body>
</html> 