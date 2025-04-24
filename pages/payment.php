<?php
require_once '../backends/main.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';
require_once BACKEND . 'transactions_management.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE . "login");
    exit();
}

// Get user token balance if available
$token_balance = 0;
try {
    $query = "SELECT token_balance FROM users WHERE uid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $token_balance = $row['token_balance'] ?? 0;
    }
} catch (Exception $e) {
    log_error("Error fetching token balance: " . $e->getMessage(), 'database');
}

// Get redirect parameters if coming from class enrollment
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$required_tokens = isset($_GET['tokens']) ? intval($_GET['tokens']) : 0;

// If tokens were specified in URL, use them as default amount
$default_amount = $required_tokens > 0 ? $required_tokens : '';

// Get class details if coming from class enrollment
$class_name = '';
if ($class_id > 0) {
    try {
        $query = "SELECT class_name FROM class WHERE class_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['class_name'] = $class_name = $row['class_name'];
            $_SESSION['transaction_type'] = 'class';
        }
    } catch (Exception $e) {
        log_error("Error fetching class details: " . $e->getMessage(), 'database');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<style>
        .payment-method-option {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .payment-method-option:hover {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateY(-2px);
        }
        
        .payment-method-option.selected {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.1);
        }
        
        .payment-method-option img {
            height: 28px;
            width: auto;
            margin-right: 12px;
        }
        
        .payment-method-option span {
            font-weight: 500;
        }
        
        .payment-method-option .bi-chevron-right {
            transition: transform 0.2s ease;
        }
        
        .payment-method-option:hover .bi-chevron-right {
            transform: translateX(3px);
        }
        
        .payment-method-option.selected .bi-chevron-right {
            transform: translateX(3px);
        }
        
        .transaction-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.25rem;
        }
        
        .token-icon {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
    </style>
<body>
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Topbar with tokens -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Purchase Tokens</h4>
                            <div class="badge bg-primary-subtle text-primary px-3 py-2">
                                <i class="bi bi-coin me-1"></i> Balance: <?php echo number_format($token_balance, 0); ?> Tokens
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

        <?php
        // Check for pending transactions
        require_once ROOT_PATH . '/backends/handler/payment_handlers.php';
        $pendingCheck = checkRecentPendingTransactions($conn, $_SESSION['user']);
        if ($pendingCheck['hasPending']): 
            $tx = $pendingCheck['transaction'];
            $minutesRemaining = $tx['minutes_remaining'];
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Pending Transaction In Progress</h5>
                            <p class="mb-0">You have a pending transaction (#<?php echo $tx['id']; ?> for ₱<?php echo number_format($tx['amount'], 2); ?>) that was created <?php echo $tx['minutes_ago']; ?> minutes ago.</p>
                            <p class="mb-2">Please wait <?php echo $minutesRemaining; ?> more <?php echo ($minutesRemaining == 1) ? 'minute' : 'minutes'; ?> before attempting a new purchase, or check your <a href="<?php echo BASE; ?>dashboard/<?php echo strtolower($_SESSION['role'][0]); ?>/transactions" class="alert-link">transaction history</a>.</p>
                            
                            <div class="mt-3">
                                <a href="<?php echo BASE; ?>dashboard/<?php echo strtolower($_SESSION['role'][0]); ?>/transactions" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-clock-history me-1"></i> View Transaction History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif (isset($pendingCheck['hasRecentSuccess']) && $pendingCheck['hasRecentSuccess']): 
            $tx = $pendingCheck['transaction'];
            $minutesSince = $tx['minutes_since_success'];
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-info-circle-fill fs-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Recent Successful Transaction</h5>
                            <p class="mb-0">You have a recent successful transaction (#<?php echo $tx['id']; ?> for ₱<?php echo number_format($tx['amount'], 2); ?>) that was completed <?php echo $minutesSince; ?> minutes ago.</p>
                            <p class="mb-2">Please check your token balance and <a href="<?php echo BASE; ?>dashboard/<?php echo strtolower($_SESSION['role'][0]); ?>/transactions" class="alert-link">transaction history</a> before making another purchase to avoid double payments.</p>
                            
                            <div class="mt-3 d-flex gap-2">
                                <a href="<?php echo BASE; ?>dashboard/<?php echo strtolower($_SESSION['role'][0]); ?>/transactions" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-clock-history me-1"></i> View Transaction History
                                </a>
                                <button id="continueAnyway" class="btn btn-sm btn-outline-secondary">
                                    Continue Anyway
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($class_id > 0 && !empty($class_name)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-primary">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-mortarboard fs-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Purchase Tokens for Class Enrollment</h5>
                            <p class="mb-0">You need more tokens to enroll in <strong><?php echo htmlspecialchars($class_name); ?></strong>. After purchasing tokens, you'll be redirected back to complete your enrollment.</p>
                            <?php if ($required_tokens > 0): ?>
                            <p class="mb-0 mt-2">
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-coin me-1"></i> Required: <?php echo number_format($required_tokens, 0); ?> tokens
                                </span>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Payment Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Token Amount Input -->
            <div class="mb-4">
                            <label for="amount" class="form-label">Token Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-coin"></i></span>
                                <input type="number" class="form-control" id="amount" min="25" step="1" value="<?php echo $default_amount; ?>" required>
                                <span class="input-group-text">tokens</span>
                            </div>
                            <small class="text-muted">Minimum amount: 25 tokens (₱25)</small>
                            
                            <!-- Hidden redirect parameters -->
                            <?php if (!empty($redirect_url) && $class_id > 0): ?>
                            <input type="hidden" id="redirectUrl" value="<?php echo htmlspecialchars($redirect_url); ?>">
                            <input type="hidden" id="classId" value="<?php echo $class_id; ?>">
                            <?php endif; ?>
            </div>

            <!-- Payment Summary Section -->
            <div class="card mb-4 bg-light">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Payment Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Base Amount:</span>
                        <span>₱<span id="baseAmount">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>VAT (10%):</span>
                        <span>₱<span id="vatAmount">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Service Charge (0.2%):</span>
                        <span>₱<span id="serviceAmount">0.00</span></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Amount:</span>
                        <span>₱<span id="totalAmount">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-primary">
                        <span>Tokens to receive:</span>
                        <span><span id="tokensToReceive">0</span> tokens</span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="mb-4">
                <label class="form-label">Select Payment Method</label>
                
                <div class="payment-method-option" data-method="gcash">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/gcash.png" alt="GCash">
                            <span>GCash</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>
                
                            <div class="payment-method-option" data-method="paymaya">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/maya.png" alt="Maya">
                            <span>Maya</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>
                
                            <div class="payment-method-option" data-method="grab_pay">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/grabpay.png" alt="GrabPay">
                            <span>GrabPay</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>

                <div class="payment-method-option" data-method="qrph">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/qrph.png" alt="QR PH">
                            <span>QR PH</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>

                <div class="payment-method-option" data-method="card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/card.png" alt="Credit/Debit Card">
                            <span>Credit/Debit Card</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>
            </div>

            <!-- Card Details (initially hidden) -->
            <div id="cardDetails" class="mb-4 d-none">
                <div class="mb-3">
                    <label for="cardNumber" class="form-label">Card Number</label>
                    <input type="text" class="form-control" id="cardNumber" placeholder="4123 4567 8901 2345">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="expiryDate" class="form-label">Expiry Date</label>
                        <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cvv" class="form-label">CVV</label>
                        <input type="text" class="form-control" id="cvv" placeholder="123">
                    </div>
                </div>
            </div>

            <!-- Pay Button -->
            <button id="payButton" class="btn btn-primary w-100" onclick="processPayment()">
                            Purchase Tokens
            </button>
                    </div>
                </div>

                <!-- Payment Information Card -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Test Mode</h6>
                            <p class="mb-0">You are currently in test mode. No actual payments will be processed.</p>
                        </div>
                        
                        <div class="test-cards-section">
                            <h6 class="mb-3">Test Cards</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Card Number</th>
                                            <th>Expiry</th>
                                            <th>CVV</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>4343434343434345</td>
                                            <td>Any future date</td>
                                            <td>Any 3 digits</td>
                                            <td><span class="badge bg-success">Success</span></td>
                                        </tr>
                                        <tr>
                                            <td>4571736000000075</td>
                                            <td>Any future date</td>
                                            <td>Any 3 digits</td>
                                            <td><span class="badge bg-success">Success (Visa debit)</span></td>
                                        </tr>
                                        <tr>
                                            <td>5555444444444457</td>
                                            <td>Any future date</td>
                                            <td>Any 3 digits</td>
                                            <td><span class="badge bg-success">Success (Mastercard)</span></td>
                                        </tr>
                                        <tr>
                                            <td>4120000000000007</td>
                                            <td>Any future date</td>
                                            <td>Any 3 digits</td>
                                            <td><span class="badge bg-primary">3DS Authentication</span></td>
                                        </tr>
                                        <tr>
                                            <td>4200000000000018</td>
                                            <td>Any future date</td>
                                            <td>Any 3 digits</td>
                                            <td><span class="badge bg-danger">Expired Card</span></td>
                                        </tr>
                                        <tr>
                                            <td>5100000000000198</td>
                                            <td>Any future date</td>
                                            <td>Any 3 digits</td>
                                            <td><span class="badge bg-warning text-dark">Insufficient Funds</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="small text-muted mt-2">These are official PayMongo test cards. Use them to simulate different payment scenarios.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mb-0">Processing your payment...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        let selectedMethod = null;
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        
        // Check if there's a pending transaction and disable payment form
        <?php if (isset($pendingCheck) && $pendingCheck['hasPending']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Disable payment method selection
            const paymentOptions = document.querySelectorAll('.payment-method-option');
            paymentOptions.forEach(option => {
                option.classList.add('disabled');
                option.style.opacity = '0.6';
                option.style.cursor = 'not-allowed';
                // Remove click events
                option.style.pointerEvents = 'none';
            });
            
            // Disable Pay button
            const payButton = document.getElementById('payButton');
            payButton.disabled = true;
            payButton.innerHTML = '<i class="bi bi-clock-history me-2"></i>Transaction Pending';
            payButton.classList.add('btn-secondary');
            payButton.classList.remove('btn-primary');
            
            // Disable amount input
            const amountInput = document.getElementById('amount');
            amountInput.disabled = true;
            
            // Add countdown script
            let remainingMinutes = <?php echo $pendingCheck['transaction']['minutes_remaining']; ?>;
            let remainingSeconds = remainingMinutes * 60;
            
            const countdownTimer = setInterval(function() {
                remainingSeconds--;
                
                if (remainingSeconds <= 0) {
                    clearInterval(countdownTimer);
                    location.reload();
                    return;
                }
                
                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;
                
                payButton.innerHTML = 
                    `<i class="bi bi-clock-history me-2"></i>Please Wait: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        });
        <?php elseif (isset($pendingCheck['hasRecentSuccess']) && $pendingCheck['hasRecentSuccess']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle the "Continue Anyway" button for recent successful transactions
            const continueButton = document.getElementById('continueAnyway');
            if (continueButton) {
                continueButton.addEventListener('click', function() {
                    // Hide the alert
                    const alertElement = this.closest('.alert').parentElement.parentElement;
                    alertElement.style.display = 'none';
                    
                    // Show a brief toast notification
                    showToast('Proceeding with new transaction', 'info');
                    
                    // Set a flag in session storage to ignore recent success
                    sessionStorage.setItem('ignore_recent_success', 'true');
                });
            }
        });
        <?php endif; ?>
        
        // Toast display function
        function showToast(message, type = 'info') {
            // Check if a toast container exists, if not create one
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                toastContainer.style.zIndex = '5';
                document.body.appendChild(toastContainer);
            }
            
            // Create a unique ID for the toast
            const toastId = 'toast-' + Date.now();
            
            // Set the bootstrap color class based on type
            let bgClass = 'bg-primary';
            switch(type) {
                case 'success': bgClass = 'bg-success'; break;
                case 'warning': bgClass = 'bg-warning text-dark'; break;
                case 'error': bgClass = 'bg-danger'; break;
                case 'info': bgClass = 'bg-info text-dark'; break;
            }
            
            // Create the toast HTML
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${bgClass} text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            // Add toast to container
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            // Initialize and show the toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
            toast.show();
            
            // Remove toast from DOM after it's hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
        
        // Loading modal display functions
        function showLoading(message = 'Processing...') {
            const loadingModalBody = document.querySelector('#loadingModal .modal-body');
            if (loadingModalBody) {
                const messageElement = loadingModalBody.querySelector('p.text-muted');
                if (messageElement) {
                    messageElement.textContent = message;
                }
            }
            loadingModal.show();
        }
        
        function hideLoading() {
            loadingModal.hide();
        }
        
        // Payment Method Selection
        document.querySelectorAll('.payment-method-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-method-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                selectedMethod = this.dataset.method;
                
                // Show/hide card details
                const cardDetails = document.getElementById('cardDetails');
                if (selectedMethod === 'card') {
                    cardDetails.classList.remove('d-none');
                } else {
                    cardDetails.classList.add('d-none');
                }
            });
        });

        // Calculate payment summary on amount change
        document.getElementById('amount').addEventListener('input', calculatePaymentSummary);
        
        // Initial calculation
        calculatePaymentSummary();
        
        // Function to calculate payment summary
        function calculatePaymentSummary() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            
            // Base amount (same as tokens)
            const baseAmount = amount;
            // VAT (10%)
            const vatAmount = baseAmount * 0.1;
            // Service charge (0.2%)
            const serviceAmount = baseAmount * 0.002;
            // Total amount
            const totalAmount = baseAmount + vatAmount + serviceAmount;
            
            // Update the display
            document.getElementById('baseAmount').textContent = baseAmount.toFixed(2);
            document.getElementById('vatAmount').textContent = vatAmount.toFixed(2);
            document.getElementById('serviceAmount').textContent = serviceAmount.toFixed(2);
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
            document.getElementById('tokensToReceive').textContent = amount.toFixed(0);
        }

        // Format card number with spaces
        document.getElementById('cardNumber')?.addEventListener('input', function(e) {
            const target = e.target;
            let value = target.value.replace(/\s+/g, '');
            if (value.length > 0) {
                value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
            }
            target.value = value;
        });

        // Format expiry date with slash
        document.getElementById('expiryDate')?.addEventListener('input', function(e) {
            const target = e.target;
            let value = target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            target.value = value;
        });

        // Limit CVV to 3 digits
        document.getElementById('cvv')?.addEventListener('input', function(e) {
            const target = e.target;
            let value = target.value.replace(/\D/g, '');
            if (value.length > 3) {
                value = value.substring(0, 3);
            }
            target.value = value;
        });
        
        // Function to handle payment processing
        function processPayment(ignoreRecentSuccess = false) {
            const amount = document.getElementById('amount').value;
            const baseAmount = parseFloat(amount);
            const vatAmount = baseAmount * 0.1;
            const serviceAmount = baseAmount * 0.002;
            const totalAmount = baseAmount + vatAmount + serviceAmount;
            <?php if(!empty($class_id) && !empty($required_tokens) ): ?>
            let transactionType = 'class';
            <?php else: ?>
            let transactionType = 'token';
            <?php endif; ?>

            const selectedMethod = document.querySelector('.payment-method-option.selected');
            
            if (!selectedMethod) {
                showToast('Please select a payment method', 'warning');
                return;
            }
            
            if (baseAmount < 25) {
                showToast('Minimum token amount is 25 tokens', 'warning');
                return;
            }
            
            const paymentMethod = selectedMethod.dataset.method;
            
            // Get redirect parameters if they exist
            const redirectUrl = document.getElementById('redirectUrl')?.value || '';
            const classId = document.getElementById('classId')?.value || '';
            
            if (paymentMethod === 'card') {
                // For card payments, validate card inputs
                const cardNumber = document.getElementById('cardNumber').value;
                const expiryDate = document.getElementById('expiryDate').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || !expiryDate || !cvv) {
                    showToast('Please fill in all card details', 'warning');
                    return;
                }
                
                // Show loading state
                showLoading('Creating payment...');
                
                // Create payment intent
                fetch('<?php echo BASE; ?>create-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'create_payment',
                        amount: totalAmount.toFixed(2),
                        token_amount: baseAmount,
                        description: 'Token purchase',
                        payment_method: 'card',
                        transaction_type: transactionType,
                        class_id: classId,
                        ignore_recent_success: ignoreRecentSuccess ? 'true' : 'false'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    // Handle case where there's a recent successful transaction
                    if (!data.success && data.shouldIgnore) {
                        // Use confirm dialog to ask if they want to proceed
                        const confirmMsg = data.message + "\n\nDo you want to proceed with a new purchase anyway?";
                        if (confirm(confirmMsg)) {
                            // Call processPayment again but with ignoreRecentSuccess=true
                            processPayment(true);
                            return;
                        } else {
                            return; // User cancelled
                        }
                    }
                    
                    if (data.success) {
                        if (data.already_paid) {
                            showToast(data.message, 'success');
                            setTimeout(() => {
                                <?php if(!empty($class_id) && !empty($required_tokens)): ?>
                                window.location.href = '<?php echo BASE.'payment-success?class_id='.urlencode($class_id).'&amount='.urlencode($required_tokens); ?>';
                                <?php else: ?>
                                window.location.href = '<?php echo BASE; ?>payment-success';
                                <?php endif; ?>
                            }, 2000);
                            return;
                        }
                        // Process card payment with token
                        processCardPayment(data.clientKey, cardNumber, expiryDate, cvv, data.transactionId);
                    } else {
                        showToast(data.message || 'Failed to create payment. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                });
            } else {
                // For e-wallets, create source and redirect
                showLoading('Creating payment...');
                
                fetch('<?php echo BASE; ?>create-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'create_payment',
                        amount: totalAmount.toFixed(2),
                        token_amount: baseAmount,
                        description: 'Token purchase',
                        payment_method: paymentMethod,
                        transaction_type: transactionType,
                        class_id: classId,
                        ignore_recent_success: ignoreRecentSuccess ? 'true' : 'false'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    // Handle case where there's a recent successful transaction
                    if (!data.success && data.shouldIgnore) {
                        // Use confirm dialog to ask if they want to proceed
                        const confirmMsg = data.message + "\n\nDo you want to proceed with a new purchase anyway?";
                        if (confirm(confirmMsg)) {
                            // Call processPayment again but with ignoreRecentSuccess=true
                            processPayment(true);
                            return;
                        } else {
                            return; // User cancelled
                        }
                    }
                    
                    if (data.success) {
                        if (data.already_paid) {
                            showToast(data.message, 'success');
                            setTimeout(() => {
                                <?php if(!empty($class_id) && !empty($required_tokens)): ?>
                                window.location.href = '<?php echo BASE.'payment-success?class_id='.urlencode($class_id).'&amount='.urlencode($required_tokens); ?>';
                                <?php else: ?>
                                window.location.href = '<?php echo BASE; ?>payment-success';
                                <?php endif; ?>
                            }, 2000);
                            return;
                        }
                        
                        if (data.checkoutUrl) {
                            window.location.href = data.checkoutUrl;
                        } else {
                            showToast('Failed to create payment link', 'error');
                        }
                    } else {
                        showToast(data.message || 'Failed to create payment. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                });
            }
        }

        function processCardPayment(clientKey, cardNumber, expiryDate, cvv, transactionId) {
            if (!cardNumber || !expiryDate || !cvv) {
                showToast('Please fill in all card details', 'warning');
                return;
            }

            console.log('Processing card payment with client key:', clientKey);
            
            // Show loading modal
            loadingModal.show();

            // Process card payment using fetch
            fetch('<?php echo BASE; ?>process-card-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'process_card_payment',
                    'payment_intent_id': clientKey,
                    'card_number': cardNumber,
                    'expiry_date': expiryDate,
                    'cvv': cvv,
                    'transaction_id': transactionId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Card payment response:', data);
                loadingModal.hide();
                
                if (data.success) {
                    showToast('Payment successful!', 'success');
                    
                    setTimeout(() => {

                        <?php if(!empty($class_id) && !empty($required_tokens)): ?>
                        window.location.href = '<?php echo BASE.'payment-success?class_id='.urlencode($class_id).'&amount='.urlencode($required_tokens); ?>';
                        <?php else: ?>
                        window.location.href = '<?php echo BASE; ?>payment-success';
                        <?php endif; ?>
                        
                    }, 2000);
                } else {
                    // If payment is already processed, handle as success
                    if (data.message && data.message.includes('already been processed')) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            <?php if(!empty($class_id) && !empty($required_tokens)): ?>
                            window.location.href = '<?php echo BASE.'payment-success?class_id='.urlencode($class_id).'&amount='.urlencode($required_tokens); ?>';
                            <?php else: ?>
                            window.location.href = '<?php echo BASE; ?>payment-success';
                            <?php endif; ?>
                        }, 2000);
                    } else {
                        showToast(data.message || 'Payment failed. Please try again.', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Card payment error:', error);
                loadingModal.hide();
                showToast('An error occurred. Please try again.', 'error');
            });
        }
    </script>
</body>
</html>
