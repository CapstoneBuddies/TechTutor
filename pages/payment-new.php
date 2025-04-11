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
                <label class="form-label">Payment Method</label>
                
                <div class="payment-method-option selected" data-method="qrph">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/qrph.png" alt="QRPH" style="height: 28px; margin-right: 12px;">
                            <span>QR Ph Payment</span>
                        </div>
                        <span class="badge bg-success">Available</span>
                    </div>
                </div>
                
                <div class="mt-3 alert alert-info">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi bi-info-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">QR Ph Payment Instructions</h6>
                            <p class="mb-0">After clicking "Purchase Tokens", you'll be redirected to a secure payment page where you can scan a QR code with your banking app that supports QR Ph to complete the payment.</p>
                        </div>
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
                        <div class="alert alert-success mb-4">
                            <h6 class="alert-heading"><i class="bi bi-shield-check me-2"></i>Secure Payment</h6>
                            <p class="mb-0">All payments are securely processed through PayMongo's payment gateway using QR Ph standard, which is accepted by all major banks and e-wallets in the Philippines.</p>
                        </div>
                        
                        <div class="qr-payment-guide">
                            <h6 class="mb-3">How QR Ph Payment Works</h6>
                            <div class="row mb-4">
                                <div class="col-md-4 text-center mb-3 mb-md-0">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                        <i class="bi bi-calculator-fill text-primary fs-4"></i>
                                    </div>
                                    <p class="mb-0 small">1. Enter amount and click "Purchase Tokens"</p>
                                </div>
                                <div class="col-md-4 text-center mb-3 mb-md-0">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                        <i class="bi bi-qr-code-scan text-primary fs-4"></i>
                                    </div>
                                    <p class="mb-0 small">2. Scan the QR Ph code with your banking app</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                        <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                                    </div>
                                    <p class="mb-0 small">3. Complete the payment in your banking app</p>
                                </div>
                            </div>
                            
                            <div class="alert alert-light border">
                                <h6 class="mb-2"><i class="bi bi-lightbulb me-2"></i>Supported Apps for QR Ph:</h6>
                                <div class="row mt-3 text-center">
                                    <div class="col-4 col-md-2 mb-3">
                                        <img src="<?php echo IMG; ?>payment/gcash.png" alt="GCash" class="img-fluid mb-2" style="height: 24px;">
                                        <p class="small mb-0">GCash</p>
                                    </div>
                                    <div class="col-4 col-md-2 mb-3">
                                        <img src="<?php echo IMG; ?>payment/maya.png" alt="Maya" class="img-fluid mb-2" style="height: 24px;">
                                        <p class="small mb-0">Maya</p>
                                    </div>
                                    <div class="col-4 col-md-2 mb-3">
                                        <img src="<?php echo IMG; ?>payment/bpi.png" alt="BPI" class="img-fluid mb-2" style="height: 24px;">
                                        <p class="small mb-0">BPI</p>
                                    </div>
                                    <div class="col-4 col-md-2 mb-3">
                                        <img src="<?php echo IMG; ?>payment/bdo.png" alt="BDO" class="img-fluid mb-2" style="height: 24px;">
                                        <p class="small mb-0">BDO</p>
                                    </div>
                                    <div class="col-4 col-md-2 mb-3">
                                        <img src="<?php echo IMG; ?>payment/unionbank.png" alt="UnionBank" class="img-fluid mb-2" style="height: 24px;">
                                        <p class="small mb-0">UnionBank</p>
                                    </div>
                                    <div class="col-4 col-md-2 mb-3">
                                        <span class="d-block text-muted mb-2" style="font-size: 24px;"><i class="bi bi-bank"></i></span>
                                        <p class="small mb-0">Others</p>
                                    </div>
                                </div>
                            </div>
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
        let selectedMethod = 'qrph'; // Default to qrph
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        
        // Check if there's a pending transaction and disable payment form
        <?php if (isset($pendingCheck) && $pendingCheck['hasPending']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Disable payment method selection
            const paymentOption = document.querySelector('.payment-method-option');
            paymentOption.classList.add('disabled');
            paymentOption.style.opacity = '0.6';
            paymentOption.style.cursor = 'not-allowed';
                // Remove click events
            paymentOption.style.pointerEvents = 'none';
            
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
            
            // Initialize payment summary calculation
            calculatePaymentSummary();
        });
        <?php else: ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize payment summary calculation
            calculatePaymentSummary();
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

        // Calculate payment summary on amount change
        document.getElementById('amount').addEventListener('input', calculatePaymentSummary);
        
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
        
        // Function to handle payment processing
        function processPayment(ignoreRecentSuccess = false) {
            const amount = document.getElementById('amount').value;
            const baseAmount = parseFloat(amount);
            
            if (!baseAmount || isNaN(baseAmount)) {
                showToast('Please enter a valid token amount', 'warning');
                return;
            }
            
            if (baseAmount < 25) {
                showToast('Minimum token amount is 25 tokens', 'warning');
                return;
            }
            
            const vatAmount = baseAmount * 0.1;
            const serviceAmount = baseAmount * 0.002;
            const totalAmount = baseAmount + vatAmount + serviceAmount;
            
            <?php if(!empty($class_id) && !empty($required_tokens) ): ?>
            let transactionType = 'class';
            <?php else: ?>
            let transactionType = 'token';
            <?php endif; ?>
            
            // Get redirect parameters if they exist
            const redirectUrl = document.getElementById('redirectUrl')?.value || '';
            const classId = document.getElementById('classId')?.value || '';
                
                // Show loading state
            showLoading('Creating QR Ph payment...');
                
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
                    payment_method: 'qrph',
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
    </script>

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
</body>
</html>
