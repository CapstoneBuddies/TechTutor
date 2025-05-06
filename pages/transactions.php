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
        <!-- Tabs for switching between Transactions and Disputes -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="transactionsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions-content" type="button" role="tab" aria-controls="transactions-content" aria-selected="true">
                            <i class="bi bi-currency-exchange me-1"></i> Transactions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="disputes-tab" data-bs-toggle="tab" data-bs-target="#disputes-content" type="button" role="tab" aria-controls="disputes-content" aria-selected="false">
                            <i class="bi bi-exclamation-triangle me-1"></i> Disputes
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content" id="transactionsTabsContent">
            <!-- Transactions Tab -->
            <div class="tab-pane fade show active" id="transactions-content" role="tabpanel" aria-labelledby="transactions-tab">
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
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                            <li><a class="dropdown-item" href="javascript:void(0)" data-filter="all">All</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" data-filter="completed">Completed</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" data-filter="pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" data-filter="failed">Failed</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <!-- New Advanced Filter Section -->
                            <div class="card-body border-bottom pb-0">
                                <form id="advancedFilterForm" class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small">Date From</label>
                                        <input type="date" class="form-control form-control-sm" id="dateFrom">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Date To</label>
                                        <input type="date" class="form-control form-control-sm" id="dateTo">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Status</label>
                                        <select class="form-select form-select-sm" id="statusFilter">
                                            <option value="">All</option>
                                            <option value="succeeded">Completed</option>
                                            <option value="pending">Pending</option>
                                            <option value="failed">Failed</option>
                                            <option value="processing">Processing</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Amount</label>
                                        <select class="form-select form-select-sm" id="amountFilter">
                                            <option value="">All</option>
                                            <option value="25-50">₱25 - ₱50</option>
                                            <option value="51-100">₱51 - ₱100</option>
                                            <option value="101-250">₱101 - ₱250</option>
                                            <option value="251+">₱251+</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyAdvancedFilter()">
                                            Apply Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th class="sortable" data-sort="id">
                                                    Transaction ID <i class="bi bi-arrow-down-up sort-icon"></i>
                                                </th>
                                                <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                                                    <th class="sortable" data-sort="user">
                                                        User <i class="bi bi-arrow-down-up sort-icon"></i>
                                                    </th>
                                                    <th class="sortable" data-sort="role">
                                                        Role <i class="bi bi-arrow-down-up sort-icon"></i>
                                                    </th>
                                                <?php endif; ?>
                                                <th class="sortable" data-sort="date">
                                                    Date <i class="bi bi-arrow-down-up sort-icon"></i>
                                                </th>
                                                <th class="sortable" data-sort="type">
                                                    Type <i class="bi bi-arrow-down-up sort-icon"></i>
                                                </th>
                                                <th class="sortable" data-sort="amount">
                                                    Amount <i class="bi bi-arrow-down-up sort-icon"></i>
                                                </th>
                                                <th class="sortable" data-sort="status">
                                                    Status <i class="bi bi-arrow-down-up sort-icon"></i>
                                                </th>
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
            
            <!-- Disputes Tab -->
            <div class="tab-pane fade" id="disputes-content" role="tabpanel" aria-labelledby="disputes-tab">
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
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="disputeFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="disputeFilterDropdown">
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterDisputes('all')">All</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterDisputes('pending')">Pending</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterDisputes('under_review')">Under Review</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterDisputes('resolved')">Resolved</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterDisputes('rejected')">Rejected</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterDisputes('cancelled')">Cancelled</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <!-- New Advanced Filter Section for Disputes -->
                            <div class="card-body border-bottom pb-0">
                                <form id="advancedDisputeFilterForm" class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small">Date From</label>
                                        <input type="date" class="form-control form-control-sm" id="disputeDateFrom">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Date To</label>
                                        <input type="date" class="form-control form-control-sm" id="disputeDateTo">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Status</label>
                                        <select class="form-select form-select-sm" id="disputeStatusFilter">
                                            <option value="">All</option>
                                            <option value="pending">Pending</option>
                                            <option value="under_review">Under Review</option>
                                            <option value="resolved">Resolved</option>
                                            <option value="rejected">Rejected</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyAdvancedDisputeFilter()">
                                            Apply Filter
                                        </button>
                                    </div>
                                </form>
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
                                <div id="disputesLoadingSpinner" class="text-center d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div id="noDisputes" class="text-center d-none">
                                    <p class="text-muted">No disputes found</p>
                                </div>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center" id="disputesPagination">
                                        <!-- Disputes pagination will be generated via JavaScript -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
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
                        <!-- Dispute details will be loaded here via JavaScript -->
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script>
        let currentPage = 1;
        let currentFilter = 'all';
        let currentSortBy = '';
        let currentSortOrder = 'asc';
        let advancedFilters = {};

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded');
            
            // Initialize Bootstrap components
            const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
            const dropdownList = [...dropdownElementList].map(dropdownToggleEl => {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            console.log('Dropdowns initialized', dropdownList);
            
            // Specifically initialize both dropdowns to ensure they work
            const filterDropdown = new bootstrap.Dropdown(document.getElementById('filterDropdown'));
            
            // Add event listener to reset modal title when closed
            document.getElementById('transactionDetailsModal').addEventListener('hidden.bs.modal', function() {
                document.querySelector('#transactionDetailsModal .modal-title').textContent = 'Transaction Details';
            });
            
            // Check URL parameters to see which tab should be active
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            // Check if there are filter parameters
            const txFilter = urlParams.get('txfilter');
            const dFilter = urlParams.get('dfilter');
            
            // Check for page parameters
            const txPage = urlParams.get('txpage');
            const dPage = urlParams.get('dpage');
            
            // Apply transaction filter and page if present
            if (txFilter) {
                currentFilter = txFilter;
                // Update filter button text if on transactions tab
                if (activeTab !== 'disputes') {
                    const filterBtn = document.querySelector('#filterDropdown');
                    let filterText = 'All';
                    switch(txFilter) {
                        case 'completed': filterText = 'Completed'; break;
                        case 'pending': filterText = 'Pending'; break;
                        case 'failed': filterText = 'Failed'; break;
                    }
                    filterBtn.innerHTML = `<i class="bi bi-funnel"></i> ${filterText}`;
                }
            }
            
            if (txPage && !isNaN(parseInt(txPage))) {
                currentPage = parseInt(txPage);
            }
            
            // Apply dispute filter and page if present
            if (dFilter) {
                disputesCurrentFilter = dFilter;
            }
            
            if (dPage && !isNaN(parseInt(dPage))) {
                disputesCurrentPage = parseInt(dPage);
            }
            
            if (activeTab === 'disputes') {
                // Show disputes tab
                document.getElementById('disputes-tab').classList.add('active');
                document.getElementById('transactions-tab').classList.remove('active');
                document.getElementById('disputes-content').classList.add('show', 'active');
                document.getElementById('transactions-content').classList.remove('show', 'active');
                // Load disputes with current page and filter
                loadDisputes(disputesCurrentPage, disputesCurrentFilter);
            } else {
                // Show transactions tab (default)
                document.getElementById('disputes-tab').classList.remove('active');
                document.getElementById('transactions-tab').classList.add('active');
                document.getElementById('disputes-content').classList.remove('show', 'active');
                document.getElementById('transactions-content').classList.add('show', 'active');
                // Load transactions with current page and filter
                loadTransactions(currentPage, currentFilter);
            }
            
            // Add direct click event listeners to filter items
            document.querySelectorAll('#filterDropdown + .dropdown-menu .dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const filter = this.getAttribute('data-filter');
                    console.log('Filter clicked:', filter);
                    filterTransactions(filter);
                });
            });
            
            // Direct toggle for dropdown as a fallback
            document.getElementById('filterDropdown').addEventListener('click', function(e) {
                console.log('Filter dropdown clicked');
                const dropdown = bootstrap.Dropdown.getInstance(this) || new bootstrap.Dropdown(this);
                dropdown.toggle();
            });
            
            // Add tab change event listeners to update URL
            const transactionsTabs = document.getElementById('transactionsTabs');
            if (transactionsTabs) {
                transactionsTabs.querySelectorAll('.nav-link').forEach(tab => {
                    tab.addEventListener('shown.bs.tab', function(event) {
                        // Get the tab id
                        const tabId = event.target.id;
                        
                        // Update URL based on which tab is active
                        const url = new URL(window.location.href);
                        if (tabId === 'disputes-tab') {
                            url.searchParams.set('tab', 'disputes');
                            
                            // Load disputes if not already loaded
                            loadDisputes(disputesCurrentPage, disputesCurrentFilter);
                        } else {
                            url.searchParams.delete('tab');
                        }
                        
                        // Update URL without reloading the page
                        window.history.pushState({}, '', url);
                    });
                });
            }
            
            // Add sorting functionality to table headers
            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', function() {
                    const sortBy = this.getAttribute('data-sort');
                    
                    // Toggle sort order if clicking the same column
                    if (currentSortBy === sortBy) {
                        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSortBy = sortBy;
                        currentSortOrder = 'asc';
                    }
                    
                    // Update visual indicators
                    document.querySelectorAll('.sortable').forEach(h => {
                        h.querySelector('.sort-icon').className = 'bi bi-arrow-down-up sort-icon';
                    });
                    
                    // Update clicked header icon
                    const icon = this.querySelector('.sort-icon');
                    icon.className = `bi bi-arrow-${currentSortOrder === 'asc' ? 'down' : 'up'} sort-icon`;
                    
                    // Reload data with sorting
                    loadTransactions(currentPage, currentFilter);
                });
            });
        });

        function loadTransactions(page, filter = 'all') {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const noTransactions = document.getElementById('noTransactions');
            const tableBody = document.getElementById('transactionsTableBody');

            loadingSpinner.classList.remove('d-none');
            tableBody.innerHTML = '';
            noTransactions.classList.add('d-none');

            // Build fetch URL with all filters
            let url = `<?php echo BASE; ?>get-transactions?page=${page}&filter=${filter}&role=<?php echo $_SESSION['role']; ?>&userId=<?php echo $_SESSION['user']; ?>`;
            
            // Add sorting parameters if set
            if (currentSortBy) {
                url += `&sortBy=${currentSortBy}&sortOrder=${currentSortOrder}`;
            }
            
            // Add advanced filters if any
            if (Object.keys(advancedFilters).length > 0) {
                for (const [key, value] of Object.entries(advancedFilters)) {
                    if (value) {
                        url += `&${key}=${encodeURIComponent(value)}`;
                    }
                }
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.classList.add('d-none');
                    
                    if (data.transactions && data.transactions.length > 0) {
                        data.transactions.forEach(transaction => {
                            const row = createTransactionRow(transaction);
                            tableBody.innerHTML += row;
                        });
                        updatePagination(data.totalPages, page);
                    } else {
                        noTransactions.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    loadingSpinner.classList.add('d-none');
                    showToast('error', 'Failed to load transactions');
                    console.error('Error:', error);
            });
        }

        function createTransactionRow(transaction) {
            let row = `<tr>
                <td>#${transaction.id}</td>`;
            
            <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                row += `<td>${transaction.userName}</td>
                       <td><span class="badge bg-${getRoleBadgeColor(transaction.userRole)}">${transaction.userRole}</span></td>`;
            <?php endif; ?>

            row += `<td>${formatDate(transaction.date)}</td>
                   <td>${formatTransactionType(transaction.type, transaction.description)}</td>
                   <td>${formatAmount(transaction.amount)}</td>
                   <td><span class="badge bg-${getStatusBadgeColor(transaction.status)}">${formatStatus(transaction.status)}</span></td>
                   <td>
                       <button class="btn btn-sm btn-outline-primary" onclick="viewTransactionDetails('${transaction.id}')">
                           <i class="bi bi-eye"></i>
                       </button>
                       ${transaction.hasOpenDispute ? 
                           `<span class="badge bg-warning ms-2" title="This transaction has an open dispute">
                               <i class="bi bi-exclamation-triangle"></i>
                            </span>` : ''
                       }
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

        function formatTransactionType(type, description) {
            let typeString = type.charAt(0).toUpperCase() + type.slice(1);
            
            // Check if it's a token purchase
            if (description && description.toLowerCase().includes('token')) {
                return `<span class="text-primary">
                    <i class="bi bi-coin me-1"></i> Token Purchase
                </span>`;
            }
            
            // Check if it's a class enrollment payment
            if (description && description.toLowerCase().includes('class') && description.toLowerCase().includes('enrollment')) {
                return `<span class="text-success">
                    <i class="bi bi-mortarboard me-1"></i> Class Payment
                </span>`;
            }
            
            return typeString;
        }

        function formatStatus(status) {
            switch(status.toLowerCase()) {
                case 'succeeded':
                    return 'Completed';
                case 'processing':
                    return 'Processing';
                default:
                    return status.charAt(0).toUpperCase() + status.slice(1);
            }
        }

        function updatePagination(totalPages, currentPage) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            if (totalPages <= 1) return;

            let paginationHtml = `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="navigateToPage(${currentPage - 1}); return false;">&laquo;</a>
                </li>`;

            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="navigateToPage(${i}); return false;">${i}</a>
                    </li>`;
            }

            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="navigateToPage(${currentPage + 1}); return false;">&raquo;</a>
                </li>`;

            pagination.innerHTML = paginationHtml;
        }
        
        function navigateToPage(page) {
            currentPage = page;
            loadTransactions(page, currentFilter);
            
            // Update URL with page parameter
            const url = new URL(window.location.href);
            url.searchParams.set('txpage', page);
            window.history.pushState({}, '', url);
        }

        function filterTransactions(filter) {
            console.log('filterTransactions called with:', filter);
            currentFilter = filter;
            currentPage = 1;
            
            // Reset advanced filters when using basic filter dropdown
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('amountFilter').value = '';
            advancedFilters = {};
            
            loadTransactions(currentPage, filter);
            
            // Update filter button text
            const filterBtn = document.querySelector('#filterDropdown');
            let filterText = 'All';
            switch(filter) {
                case 'completed': filterText = 'Completed'; break;
                case 'pending': filterText = 'Pending'; break;
                case 'failed': filterText = 'Failed'; break;
            }
            console.log('Updating button text to:', filterText);
            filterBtn.innerHTML = `<i class="bi bi-funnel"></i> ${filterText}`;
            
            // Update URL to preserve filter state
            const url = new URL(window.location.href);
            url.searchParams.set('txfilter', filter);
            // Remove advanced filter params from URL
            url.searchParams.delete('dateFrom');
            url.searchParams.delete('dateTo');
            url.searchParams.delete('status');
            url.searchParams.delete('amountRange');
            // Keep the tab parameter if it exists
            window.history.pushState({}, '', url);
        }

        function viewTransactionDetails(transactionId) {
            // Reset modal title
            document.querySelector('#transactionDetailsModal .modal-title').textContent = 'Transaction Details';
            
            // Show loading spinner
            const modalBody = document.querySelector('.transaction-details');
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            
            // Open modal
            const modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
            modal.show();
            
            // Fix URL construction - use template literal
            fetch(`<?php echo BASE; ?>get-transaction-details?id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const transaction = data.transaction;
                        let html = `
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">Transaction ID</h6>
                                        <p class="mb-0">#${transaction.id}</p>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-1">Status</h6>
                                        <span class="badge bg-${getStatusBadgeColor(transaction.status)}">${formatStatus(transaction.status)}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Date</h6>
                                        <p class="mb-3">${formatDate(transaction.date)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Amount</h6>
                                        <p class="mb-3">${formatAmount(transaction.amount)}</p>
                            </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Payment Method</h6>
                                        <p class="mb-3">${formatTransactionType(transaction.type, transaction.description)}</p>
                            </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1 reference">Reference Number</h6>
                                        <p class="mb-3">${transaction.reference || 'N/A'}</p>
                            </div>
                            </div>
                            </div>
                            <div class="mb-3">
                                <h6 class="mb-1">Description</h6>
                                <p class="mb-0">${transaction.description}</p>
                            </div>`;

                        // Add dispute section if applicable
                        if (transaction.hasDispute && transaction.dispute) {
                            html += `
                                <div class="alert alert-warning mt-3">
                                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Dispute Information</h6>
                                    <p class="mb-1"><strong>Status:</strong> ${transaction.dispute.status.charAt(0).toUpperCase() + transaction.dispute.status.slice(1).replace('_', ' ')}</p>
                                    <p class="mb-1"><strong>Reason:</strong> ${transaction.dispute.reason}</p>
                                    <p class="mb-0"><strong>Date Filed:</strong> ${formatDate(transaction.dispute.createdAt)}</p>
                                    ${transaction.dispute.status === 'resolved' || transaction.dispute.status === 'rejected' ? 
                                        `<p class="mb-0"><strong>Admin Notes:</strong> ${transaction.dispute.adminNotes || 'None'}</p>` : ''}
                                </div>`;
                        } else if (!transaction.hasDispute && transaction.status.toLowerCase() === 'succeeded' && 
                                  <?php echo $_SESSION['role'] !== 'ADMIN' ? 'true' : 'false'; ?>) {
                            html += `
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-danger" onclick="openDisputeForm('${transaction.id}')">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Report an Issue
                                    </button>
                                </div>`;
                        }

                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load transaction details'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Failed to load transaction details</div>';
                });
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
        
        function createDispute(transactionId) {
            const reason = document.getElementById('disputeReason').value.trim();
            
            if (!reason) {
                showToast('error', 'Please provide a reason for your dispute');
                return;
            }
            
            fetch('<?php echo BASE; ?>create-dispute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'transaction_id': transactionId,
                    'reason': reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    // Properly close the modal
                    const modalElement = document.getElementById('transactionDetailsModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    // Reload the transactions list
                    loadTransactions(currentPage, currentFilter);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to create dispute');
            });
        }
        
        function cancelDispute(disputeId) {
            if (!confirm('Are you sure you want to cancel this dispute?')) {
                return;
            }
            
            fetch('<?php echo BASE; ?>cancel-dispute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'dispute_id': disputeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    const modalElement = document.getElementById('transactionDetailsModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    // Reload the transactions list
                    loadTransactions(currentPage, currentFilter);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to cancel dispute');
            });
        }
        
        function updateDisputeStatus(disputeId, status) {
            const adminNotes = document.getElementById('adminNotes').value.trim();
            showLoading(true);
            fetch('<?php echo BASE; ?>update-dispute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'dispute_id': disputeId,
                    'status': status,
                    'admin_notes': adminNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', data.message);
                    const modalElement = document.getElementById('transactionDetailsModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    // Reload the transactions list
                    setTimeout(()=>{ window.location.reload(); }, 3000);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to update dispute status');
            });
        }
        
        function processRefund(transactionId, disputeId) {
            // Close the dispute details modal first
            const disputeModal = bootstrap.Modal.getInstance(document.getElementById('disputeDetailsModal'));
            if (disputeModal) {
                disputeModal.hide();
            }
            
            // Show the transaction details modal with refund form
            const txModal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
            txModal.show();
            
            // Get the modal body and show loading indicator
            const modalBody = document.querySelector('.transaction-details');
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            
            // Change the modal title
            document.querySelector('#transactionDetailsModal .modal-title').textContent = 'Process Refund';
            
            // Get transaction details to show in the modal
            fetch(`<?php echo BASE; ?>get-transaction-details?id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const transaction = data.transaction;
                        const totalAmount = parseFloat(transaction.amount);
                        
                        let html = `
                            <div class="mb-3">
                                <h5 class="border-bottom pb-2">Process Refund</h5>
                                <div class="alert alert-info">
                                    <strong>Transaction #${transaction.id}</strong><br>
                                    Original Amount: ${formatAmount(transaction.amount)}<br>
                                    Date: ${formatDate(transaction.date)}<br>
                                    Status: <span class="badge bg-${getStatusBadgeColor(transaction.status)}">${formatStatus(transaction.status)}</span><br>
                                    Customer: ${transaction.userName}
                                </div>
                            </div>
                            <form id="refundForm">
                                <div class="mb-3">
                                    <label for="refundAmount" class="form-label">Refund Amount</label>
                                    <input type="number" class="form-control" id="refundAmount" min="0.01" max="${totalAmount}" step="0.01" value="${totalAmount}" required>
                                    <div class="form-text">Maximum refund amount: ${formatAmount(totalAmount)}</div>
                                </div>
                                <div class="mb-3">
                                    <label for="adminNotes" class="form-label">Admin Notes <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="adminNotes" rows="3" placeholder="Enter reason for the refund" required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" onclick="submitRefund('${transactionId}', '${disputeId}')">Process Refund</button>
                                </div>
                            </form>
                        `;
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load transaction details'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Failed to load transaction details</div>';
                });
        }

        function submitRefund(transactionId, disputeId) {
            const amount = document.getElementById('refundAmount').value;
            const adminNotes = document.getElementById('adminNotes').value.trim();
            
            if (!amount || amount <= 0) {
                showToast('error', 'Please enter a valid refund amount');
                return;
            }
            
            if (!adminNotes) {
                showToast('error', 'Admin notes are required');
                return;
            }
            
            if (!confirm(`Are you sure you want to process a refund of ${formatAmount(amount)}?`)) {
                return;
            }
            
            showLoading(true);
            fetch('<?php echo BASE; ?>process-refund', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'transaction_id': transactionId,
                    'dispute_id': disputeId,
                    'amount': amount,
                    'notes': adminNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showToast('success', data.message);
                    
                    // Close the transaction details modal
                    const modalElement = document.getElementById('transactionDetailsModal');
                    const txModal = bootstrap.Modal.getInstance(modalElement);
                    if (txModal) {
                        txModal.hide();
                    }
                    
                    // If we're in the disputes tab, reload disputes; otherwise reload transactions
                    if (document.getElementById('disputes-tab').classList.contains('active')) {
                        loadDisputes(disputesCurrentPage, disputesCurrentFilter);
                    } else {
                        loadTransactions(currentPage, currentFilter);
                    }
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showToast('error', 'Failed to process refund');
            });
        }

        function showLoading(show) {
            if (show) {
                const loadingHtml = `
                    <div id="loadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', loadingHtml);
            } else {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.remove();
            }
        }

        function showToast(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Add alert to the page
            const container = document.querySelector('.container-fluid');
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                const alert = document.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        function exportTransactions() {
            window.location.href = `<?php echo BASE; ?>export-transactions?filter=${currentFilter}`;
        }

        function openDisputeForm(transactionId) {
            // Reset modal title
            document.querySelector('#transactionDetailsModal .modal-title').textContent = 'Report an Issue';
            
            // Show dispute form in modal
            const modalBody = document.querySelector('.transaction-details');
            modalBody.innerHTML = `
                <div class="border-bottom pb-3 mb-3">
                    <h6 class="mb-3">Report an Issue with Transaction #${transactionId}</h6>
                    <div class="mb-3">
                        <label for="disputeReason" class="form-label">Please describe the issue</label>
                        <textarea class="form-control" id="disputeReason" rows="4" placeholder="Describe your issue in detail..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" onclick="createDispute('${transactionId}')">Submit Report</button>
                    </div>
                </div>
            `;
        }
        
        // Disputes Tab functionality
        let disputesCurrentPage = 1;
        let disputesCurrentFilter = 'all';
        let disputeAdvancedFilters = {};
        
        function loadDisputes(page, status = 'all') {
            const loadingSpinner = document.getElementById('disputesLoadingSpinner');
            const noDisputes = document.getElementById('noDisputes');
            const tableBody = document.getElementById('disputesTableBody');
            
            if (!loadingSpinner || !noDisputes || !tableBody) return;

            loadingSpinner.classList.remove('d-none');
            tableBody.innerHTML = '';
            noDisputes.classList.add('d-none');

            // Build URL with all filters
            let url = `<?php echo BASE; ?>get-disputes?page=${page}&status=${status}`;
            
            // Add advanced filters if any
            if (Object.keys(disputeAdvancedFilters).length > 0) {
                for (const [key, value] of Object.entries(disputeAdvancedFilters)) {
                    if (value) {
                        url += `&${key}=${encodeURIComponent(value)}`;
                    }
                }
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.classList.add('d-none');
                    
                    if (data.disputes && data.disputes.length > 0) {
                        data.disputes.forEach(dispute => {
                            const row = createDisputeRow(dispute);
                            tableBody.innerHTML += row;
                        });
                        updateDisputesPagination(data.totalPages, page);
                    } else {
                        noDisputes.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    loadingSpinner.classList.add('d-none');
                    showToast('error', 'Failed to load disputes');
                    console.error('Error:', error);
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

        function updateDisputesPagination(totalPages, currentPage) {
            const pagination = document.getElementById('disputesPagination');
            if (!pagination) return;
            
            pagination.innerHTML = '';

            if (totalPages <= 1) return;

            let paginationHtml = `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="navigateToDisputePage(${currentPage - 1}); return false;">&laquo;</a>
                </li>`;

            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="navigateToDisputePage(${i}); return false;">${i}</a>
                    </li>`;
            }

            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="navigateToDisputePage(${currentPage + 1}); return false;">&raquo;</a>
                </li>`;

            pagination.innerHTML = paginationHtml;
        }
        
        function navigateToDisputePage(page) {
            disputesCurrentPage = page;
            loadDisputes(page, disputesCurrentFilter);
            
            // Update URL with page parameter
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'disputes');
            url.searchParams.set('dpage', page);
            window.history.pushState({}, '', url);
        }

        function filterDisputes(status) {
            disputesCurrentFilter = status;
            disputesCurrentPage = 1;
            
            // Reset advanced filters when using basic filter dropdown
            document.getElementById('disputeDateFrom').value = '';
            document.getElementById('disputeDateTo').value = '';
            document.getElementById('disputeStatusFilter').value = '';
            disputeAdvancedFilters = {};
            
            loadDisputes(disputesCurrentPage, status);
            
            // Update filter button text to match selection
            const filterBtn = document.querySelector('#disputeFilterDropdown');
            let filterText = 'All';
            switch(status) {
                case 'pending': filterText = 'Pending'; break;
                case 'under_review': filterText = 'Under Review'; break;
                case 'resolved': filterText = 'Resolved'; break;
                case 'rejected': filterText = 'Rejected'; break;
                case 'cancelled': filterText = 'Cancelled'; break;
            }
            filterBtn.innerHTML = `<i class="bi bi-funnel"></i> ${filterText}`;
            
            // Update URL to preserve filter state
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'disputes');
            url.searchParams.set('dfilter', status);
            // Remove advanced filter params from URL
            url.searchParams.delete('disputeDateFrom');
            url.searchParams.delete('disputeDateTo');
            url.searchParams.delete('disputeStatus');
            window.history.pushState({}, '', url);
        }
        
        function applyAdvancedDisputeFilter() {
            // Get all filter values
            disputeAdvancedFilters = {
                dateFrom: document.getElementById('disputeDateFrom').value,
                dateTo: document.getElementById('disputeDateTo').value,
                status: document.getElementById('disputeStatusFilter').value
            };
            
            // Reset basic filter when using advanced filters
            disputesCurrentFilter = 'all';
            
            // Update filter dropdown text
            const filterBtn = document.querySelector('#disputeFilterDropdown');
            filterBtn.innerHTML = `<i class="bi bi-funnel"></i> Custom`;
            
            // Reset to first page and reload with filters
            disputesCurrentPage = 1;
            loadDisputes(disputesCurrentPage, disputesCurrentFilter);
            
            // Update URL to preserve filter state
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'disputes');
            for (const [key, value] of Object.entries(disputeAdvancedFilters)) {
                if (value) {
                    url.searchParams.set('dispute' + key.charAt(0).toUpperCase() + key.slice(1), value);
                } else {
                    url.searchParams.delete('dispute' + key.charAt(0).toUpperCase() + key.slice(1));
                }
            }
            // Reset basic filter in URL
            url.searchParams.set('dfilter', 'all');
            window.history.pushState({}, '', url);
        }

        function viewDisputeDetails(disputeId) {
            const modalBody = document.querySelector('.dispute-details');
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            
            // Open modal
            const modal = new bootstrap.Modal(document.getElementById('disputeDetailsModal'));
            modal.show();
            
            // In a real implementation, you would fetch the dispute details via AJAX
            fetch(`<?php echo BASE; ?>get-dispute-details?id=${disputeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const dispute = data.dispute;
                        let html = `
                            <div class="mb-3">
                                <strong>Dispute ID:</strong> ${dispute.id}
                            </div>
                            <div class="mb-3">
                                <strong>Transaction ID:</strong> ${dispute.transactionId}
                            </div>
                            <div class="mb-3">
                                <strong>Date Filed:</strong> ${formatDate(dispute.createdAt)}
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
                                <strong>Amount:</strong> ${formatAmount(dispute.transactionAmount)}
                            </div>
                            <div class="mb-3">
                                <strong>Reason:</strong><br>
                                <p class="text-muted">${dispute.reason || 'No reason provided'}</p>
                            </div>
                            ${dispute.adminNotes ? `
                            <div class="mb-3">
                                <strong>Admin Notes:</strong><br>
                                <p class="text-muted admin-notes">${formatAdminNotes(dispute.adminNotes)}</p>
                            </div>` : ''}
                        `;
                        
                        // Add refund information if available
                        if (dispute.refund) {
                            html += `
                                <div class="mb-3">
                                    <strong>Refund Information:</strong><br>
                                    <div class="text-muted">
                                        Amount: ${formatAmount(dispute.refund.amount)}<br>
                                        Status: <span class="badge bg-${getRefundStatusBadgeColor(dispute.refund.status)}">${dispute.refund.status}</span><br>
                                        Date: ${formatDate(dispute.refund.createdAt)}
                                    </div>
                                </div>
                            `;
                        }
                        
                        modalBody.innerHTML = html;
                        
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
                                            <textarea class="form-control" id="adminNotes" rows="3" placeholder="Add notes about this dispute"></textarea>
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
                            
                            modalBody.innerHTML += actionsHtml;
                        }
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load dispute details'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Failed to load dispute details</div>';
                });
        }

        // Helper function to format admin notes with line breaks preserved
        function formatAdminNotes(notes) {
            if (!notes) return '';
            // Replace newlines with <br> tags for proper display
            return notes.replace(/\n/g, '<br>');
        }

        // Helper function to get refund status badge color
        function getRefundStatusBadgeColor(status) {
            switch(status) {
                case 'pending': return 'warning';
                case 'processing': return 'info';
                case 'completed': return 'success';
                case 'failed': return 'danger';
                default: return 'secondary';
            }
        }

        function applyAdvancedFilter() {
            // Get all filter values
            advancedFilters = {
                dateFrom: document.getElementById('dateFrom').value,
                dateTo: document.getElementById('dateTo').value,
                status: document.getElementById('statusFilter').value,
                amountRange: document.getElementById('amountFilter').value
            };
            
            // Reset to first page and reload with filters
            currentPage = 1;
            loadTransactions(currentPage, currentFilter);
            
            // Update URL to preserve filter state
            const url = new URL(window.location.href);
            for (const [key, value] of Object.entries(advancedFilters)) {
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            }
            window.history.pushState({}, '', url);
        }
    </script>
</body>
</html>