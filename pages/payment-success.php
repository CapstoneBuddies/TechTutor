<?php
require_once  '../backends/main.php';
require_once BACKEND . 'transactions_management.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE . "login");
    exit();
}

// Get user token balance
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

// Get transaction info from session if available
$transaction_type = isset($_SESSION['transaction_type']) ? $_SESSION['transaction_type'] : 'general';
$transaction_amount = isset($_GET['amount']) ? $_GET['amount'] : 0;
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : 0;
$class_name = isset($_SESSION['class_name']) ? $_SESSION['class_name'] : '';

// Clear session variables after using them
if (isset($_SESSION['transaction_type'])) unset($_SESSION['transaction_type']);
if (isset($_GET['amount'])) unset($_SESSION['amount']);
if (isset($_GET['class_id'])) unset($_GET['class_id']);
if (isset($_SESSION['class_name'])) unset($_SESSION['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body>
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <div class="success-card">
            <div class="confetti-container">
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
                <div class="confetti"></div>
            </div>
            <div class="success-icon-container">
            <i class="bi bi-check-circle-fill success-icon"></i>
            </div>
            <h2 class="mb-3">Payment Successful!</h2>
            
            <?php if ($transaction_type == 'token'): ?>
                <!-- Token Purchase Success -->
                <div class="transaction-type mb-3">
                    <span class="badge bg-primary px-3 py-2">
                        <i class="bi bi-coin me-1"></i> Token Purchase
                    </span>
                </div>
                <p class="text-muted mb-4">Your token purchase was successful. Your account has been credited with tokens.</p>
                
                <!-- Payment Summary -->
                <div class="payment-summary-container mb-4">
                    <div class="payment-summary-card">
                        <h5 class="summary-header">Payment Summary</h5>
                        <?php
                            // Calculate components
                            $totalAmount = (float)$transaction_amount;
                            $VAT_RATE = 0.1;  // 10%
                            $SERVICE_RATE = 0.002;  // 0.2%
                            // Calculate base amount from total paid
                            $baseAmount = $totalAmount / (1 + $VAT_RATE + $SERVICE_RATE);
                            $vatAmount = $baseAmount * $VAT_RATE;
                            $serviceAmount = $baseAmount * $SERVICE_RATE;
                            $tokensReceived = round($baseAmount);
                        ?>
                        <div class="summary-row">
                            <span>Tokens Received:</span>
                            <span class="value"><?php echo number_format($tokensReceived, 0); ?> Tokens</span>
                        </div>
                        <div class="summary-row">
                            <span>Base Amount:</span>
                            <span class="value">₱<?php echo number_format($baseAmount, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>VAT (10%):</span>
                            <span class="value">₱<?php echo number_format($vatAmount, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Service Charge (0.2%):</span>
                            <span class="value">₱<?php echo number_format($serviceAmount, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount Paid:</span>
                            <span class="value">₱<?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Token Balance Card -->
                <div class="token-update-container mb-4">
                    <div class="token-balance-card">
                        <div class="token-icon">
                            <i class="bi bi-coin"></i>
                        </div>
                        <div class="token-details">
                            <h5>Current Balance</h5>
                            <div class="token-amount"><?php echo number_format((int)$token_balance, 0); ?> Tokens</div>
                            <?php if ($transaction_amount > 0): ?>
                                <p class="token-message">Your account has been credited with <?php echo number_format($tokensReceived, 0); ?> tokens!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($transaction_type == 'class'): ?>
                <!-- Class Enrollment Success -->
                <div class="transaction-type mb-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-mortarboard me-1"></i> Class Enrollment
                    </span>
                </div>
                <p class="text-muted mb-4">Your payment for class enrollment was successful.</p>
                
                <?php if (!empty($class_name)): ?>
                <div class="class-details-container mb-4">
                    <div class="class-details-card">
                        <div class="class-icon">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <div class="class-info">
                            <h5>Enrolled Class</h5>
                            <div class="class-name"><?php echo htmlspecialchars($class_name); ?></div>
                            <p class="class-message">You can now complete your enrollment process</p>
                        </div>
                    </div>
                </div>
                
                <!-- Class-specific action button -->
                <div class="d-grid mb-4">
                    <a href="<?php echo BASE; ?>dashboard/s/enrollments/class?id=<?php echo $class_id; ?>" class="btn btn-success">
                        <i class="bi bi-mortarboard me-2"></i>Complete Enrollment
                    </a>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- General payment success -->
            <p class="text-muted mb-4">Your payment has been processed successfully. You can view your transaction details in your transaction history.</p>
            <?php endif; ?>
            
            <div class="d-grid gap-2">
                <a href="<?php echo BASE . ($_SESSION['role'] === 'ADMIN' ? 'dashboard/a' : ($_SESSION['role'] === 'TECHGURU' ? 'dashboard/t' : 'dashboard/s')); ?>/transactions" class="btn btn-primary">View Transactions</a>
                <a href="<?php echo BASE; ?>dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
            
            <div class="redirect-notice mt-4">
                <p>Redirecting to dashboard in <span id="countdown">10</span> seconds</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Vendor JS Files -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    
    <script>
        // Countdown timer for redirection
        document.addEventListener('DOMContentLoaded', function() {
            let seconds = 10;
            const countdownElement = document.getElementById('countdown');
            const progressBar = document.querySelector('.progress-bar');
            
            const countdownInterval = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                // Update progress bar
                const percentage = (seconds / 10) * 100;
                progressBar.style.width = percentage + '%';
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    // Check if we have a class_id to redirect to
                    <?php if (isset($class_id) && $class_id > 0): ?>
                        window.location.href = '<?php echo BASE; ?>dashboard/s/enrollments/class?id=<?php echo $class_id; ?>';
                    <?php else: ?>
                        window.location.href = '<?php echo BASE; ?>dashboard';
                    <?php endif; ?>
                }
            }, 1000);
        });
    </script>

    <style>
        .success-card {
            max-width: 550px;
            margin: 2rem auto;
            padding: 2.5rem;
            text-align: center;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            position: relative;
            overflow: hidden;
        }
        
        .transaction-type {
            margin-bottom: 1.5rem;
        }
        
        .success-icon-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            animation: bounceIn 1s;
        }
        
        .token-update-container, .class-details-container, .payment-summary-container {
            margin: 1.5rem 0;
        }
        
        .token-balance-card, .class-details-card {
            display: flex;
            align-items: center;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
            animation: slideInUp 0.5s;
        }
        
        .payment-summary-card {
            background-color: rgba(13, 110, 253, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.1);
            animation: slideInUp 0.5s;
            text-align: left;
        }
        
        .summary-header {
            margin-bottom: 1rem;
            font-weight: 600;
            color: #0d6efd;
            border-bottom: 1px solid rgba(13, 110, 253, 0.2);
            padding-bottom: 0.75rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        
        .summary-row.total {
            border-top: 1px solid rgba(13, 110, 253, 0.2);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: #0d6efd;
        }
        
        .summary-row .value {
            font-weight: 500;
        }
        
        .token-icon, .class-icon {
            background-color: #28a745;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.75rem;
        }
        
        .class-icon {
            background-color: #007bff;
        }
        
        .token-details, .class-info {
            text-align: left;
            flex: 1;
        }
        
        .token-details h5, .class-info h5 {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        .token-amount, .class-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 0.25rem;
        }
        
        .class-name {
            color: #007bff;
        }
        
        .token-message, .class-message {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .redirect-notice {
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .progress {
            height: 6px;
            margin-top: 0.5rem;
        }
        
        .confetti-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f0f;
            opacity: 0.6;
            top: -10px;
            transform-origin: center;
            border-radius: 0;
            animation: confetti-fall 5s ease-out infinite;
        }
        
        .confetti:nth-child(1) {
            left: 10%;
            background-color: #f0f;
            animation-delay: 0.1s;
        }
        
        .confetti:nth-child(2) {
            left: 20%;
            background-color: #f80;
            animation-delay: 0.3s;
        }
        
        .confetti:nth-child(3) {
            left: 30%;
            background-color: #0cf;
            animation-delay: 0.5s;
        }
        
        .confetti:nth-child(4) {
            left: 40%;
            background-color: #f0f;
            animation-delay: 0.7s;
        }
        
        .confetti:nth-child(5) {
            left: 50%;
            background-color: #fc0;
            animation-delay: 0.9s;
        }
        
        .confetti:nth-child(6) {
            left: 60%;
            background-color: #0c0;
            animation-delay: 1.1s;
        }
        
        .confetti:nth-child(7) {
            left: 70%;
            background-color: #c0f;
            animation-delay: 1.3s;
        }
        
        .confetti:nth-child(8) {
            left: 80%;
            background-color: #0ff;
            animation-delay: 1.5s;
        }
        
        .confetti:nth-child(9) {
            left: 90%;
            background-color: #f80;
            animation-delay: 1.7s;
        }
        
        .confetti:nth-child(10) {
            left: 95%;
            background-color: #08f;
            animation-delay: 1.9s;
        }
        
        @keyframes confetti-fall {
            0% {
                top: -10px;
                transform: translateX(0) rotateZ(0);
                opacity: 0.6;
            }
            100% {
                top: 105%;
                transform: translateX(20px) rotateZ(360deg);
                opacity: 0;
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</body>
</html>
