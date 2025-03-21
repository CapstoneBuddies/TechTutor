<?php
require_once '../backends/main.php';
require_once ROOT_PATH . '/backends/paymongo_config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE . "login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Payment</title>

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">

    <style>
        .payment-card {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .payment-method-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method-option:hover {
            border-color: #4154f1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(65, 84, 241, 0.1);
        }
        .payment-method-option.selected {
            border-color: #4154f1;
            background-color: #f8f9ff;
        }
        .payment-method-option img {
            height: 35px;
            width: auto;
            object-fit: contain;
            margin-right: 1rem;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <div class="payment-card">
            <h4 class="text-center mb-4">Make a Payment</h4>
            
            <!-- Amount Input -->
            <div class="mb-4">
                <label for="amount" class="form-label">Amount (PHP)</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="amount" min="100" step="0.01" required>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="form-label">Payment Description</label>
                <textarea class="form-control" id="description" rows="2" required></textarea>
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
                
                <div class="payment-method-option" data-method="maya">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo IMG; ?>payment/maya.png" alt="Maya">
                            <span>Maya</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>
                
                <div class="payment-method-option" data-method="grabpay">
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
                Pay Now
            </button>
        </div>
    </main>

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

        // Process Payment
        function processPayment() {
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;

            if (!amount || amount < 100) {
                showAlert('error', 'Please enter a valid amount (minimum ₱100)');
                return;
            }

            if (!description) {
                showAlert('error', 'Please enter a payment description');
                return;
            }

            if (!selectedMethod) {
                showAlert('error', 'Please select a payment method');
                return;
            }

            // Show loading modal
            loadingModal.show();

            // Create payment intent
            $.ajax({
                url: '<?php echo BASE; ?>create-payment',
                type: 'POST',
                data: {
                    amount: amount,
                    description: description,
                    payment_method: selectedMethod
                },
                success: function(response) {
                    if (response.success) {
                        if (selectedMethod === 'card') {
                            // Handle card payment
                            processCardPayment(response.clientKey);
                        } else {
                            // Redirect to e-wallet payment page
                            window.location.href = response.checkoutUrl;
                        }
                    } else {
                        loadingModal.hide();
                        showAlert('error', response.message || 'Payment failed. Please try again.');
                    }
                },
                error: function() {
                    loadingModal.hide();
                    showAlert('error', 'An error occurred. Please try again.');
                }
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

            // Process card payment using PayMongo
            $.ajax({
                url: '<?php echo BASE; ?>process-card-payment',
                type: 'POST',
                data: {
                    client_key: clientKey,
                    card_number: cardNumber,
                    expiry_date: expiryDate,
                    cvv: cvv
                },
                success: function(response) {
                    loadingModal.hide();
                    if (response.success) {
                        showAlert('success', 'Payment successful!');
                        setTimeout(() => {
                            window.location.href = '<?php echo BASE; ?>transactions';
                        }, 2000);
                    } else {
                        showAlert('error', response.message || 'Payment failed. Please try again.');
                    }
                },
                error: function() {
                    loadingModal.hide();
                    showAlert('error', 'An error occurred. Please try again.');
                }
            });
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
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
