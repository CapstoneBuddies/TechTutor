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
            $class_name = $row['class_name'];
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
                            <div class="d-flex align-items-center">
                                <div class="me-4">
                                    <span class="text-muted me-2">Current Balance:</span>
                                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                        <i class="bi bi-coin me-1"></i> <?php echo number_format($token_balance, 0); ?> Tokens
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="paymentOptions" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear"></i> Options
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="paymentOptions">
                                        <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>transactions">View Transactions</a></li>
                                        <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>disputes">View Disputes</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

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
                                <input type="number" class="form-control" id="amount" min="100" step="1" value="<?php echo $default_amount; ?>" required>
                                <span class="input-group-text">tokens</span>
                            </div>
                            <small class="text-muted">Minimum amount: 100 tokens (₱100)</small>
                            
                            <!-- Hidden redirect parameters -->
                            <?php if (!empty($redirect_url) && $class_id > 0): ?>
                            <input type="hidden" id="redirectUrl" value="<?php echo htmlspecialchars($redirect_url); ?>">
                            <input type="hidden" id="classId" value="<?php echo $class_id; ?>">
                            <?php endif; ?>
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
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <h5 class="modal-title">Processing Payment</h5>
                    <p class="text-muted mb-0">Please wait while we process your payment...</p>
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
        
        // Process payment function
        function processPayment() {
            const amount = parseFloat(document.getElementById('amount').value);
            const redirectUrl = document.getElementById('redirectUrl')?.value || '';
            const classId = document.getElementById('classId')?.value || '';
            
            // Always token purchase
            const transactionType = 'token';
            const description = `Token purchase - ${amount} tokens`;

            if (!amount || amount < 100) {
                showAlert('error', 'Please enter a valid amount (minimum 100 tokens)');
                return;
            }

            if (!selectedMethod) {
                showAlert('error', 'Please select a payment method');
                return;
            }

            // Show loading modal
            loadingModal.show();

            // Create payment intent using fetch
            fetch('<?php echo BASE; ?>create-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'amount': amount,
                    'description': description,
                    'payment_method': selectedMethod,
                    'transaction_type': transactionType,
                    'redirect_url': redirectUrl,
                    'class_id': classId,
                    'action': 'create_payment'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Payment intent created:', data);
                
                if (data.success) {
                        if (selectedMethod === 'card') {
                            // Handle card payment
                        processCardPayment(data.clientKey);
                    } else {
                        // Redirect to e-wallet payment page
                        window.location.href = data.checkoutUrl;
                    }
                } else {
                    loadingModal.hide();
                    showAlert('error', data.message || 'Payment failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Payment creation error:', error);
                loadingModal.hide();
                showAlert('error', 'An error occurred. Please try again.');
            });
        }

        function processCardPayment(clientKey) {
            const cardNumber = document.getElementById('cardNumber').value;
            const expiryDate = document.getElementById('expiryDate').value;
            const cvv = document.getElementById('cvv').value;

            if (!cardNumber || !expiryDate || !cvv) {
                loadingModal.hide();
                showAlert('error', 'Please fill in all card details');
                return;
            }

            console.log('Processing card payment with client key:', clientKey);

            // Process card payment using fetch
            fetch('<?php echo BASE; ?>process-card-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'client_key': clientKey,
                    'card_number': cardNumber,
                    'expiry_date': expiryDate,
                    'cvv': cvv,
                    'action': 'process_card_payment'
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
                        showAlert('success', 'Payment successful!');
                        setTimeout(() => {
                        window.location.href = '<?php echo BASE; ?>payment-success';
                        }, 2000);
                    } else {
                    showAlert('error', data.message || 'Payment failed. Please try again.');
                    }
            })
            .catch(error => {
                console.error('Card payment error:', error);
                    loadingModal.hide();
                    showAlert('error', 'An error occurred. Please try again.');
            });
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
