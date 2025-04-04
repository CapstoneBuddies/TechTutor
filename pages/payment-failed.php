<?php
require_once '../backends/main.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE . "login");
    exit();
}

// Get error message if available
$error_message = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : 'We couldn\'t process your payment. This could be due to insufficient funds, incorrect payment details, or a temporary issue.';
if (isset($_SESSION['payment_error'])) {
    unset($_SESSION['payment_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body>
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <div class="failed-card">
            <div class="failed-icon-container">
                <i class="bi bi-x-circle-fill failed-icon"></i>
            </div>
            <h2 class="mb-3">Payment Failed</h2>
            <p class="text-muted mb-4"><?php echo $error_message; ?></p>
            
            <div class="error-details-container mb-4">
                <div class="accordion" id="errorAccordion">
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="bi bi-info-circle me-2"></i> Common reasons for payment failure
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#errorAccordion">
                            <div class="accordion-body">
                                <ul class="text-start mb-0">
                                    <li>Insufficient funds in your account</li>
                                    <li>Card has expired or is not valid</li>
                                    <li>Incorrect card information entered</li>
                                    <li>Bank declined the transaction</li>
                                    <li>Transaction limit exceeded</li>
                                    <li>Internet connection issues during payment</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <a href="<?php echo BASE; ?>payment" class="btn btn-primary">Try Again</a>
                <a href="<?php echo BASE; ?>dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
            
            <div class="redirect-notice mt-4">
                <p>Redirecting to payment page in <span id="countdown">15</span> seconds</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 100%"></div>
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
            let seconds = 15;
            const countdownElement = document.getElementById('countdown');
            const progressBar = document.querySelector('.progress-bar');
            
            const countdownInterval = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                // Update progress bar
                const percentage = (seconds / 15) * 100;
                progressBar.style.width = percentage + '%';
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = '<?php echo BASE; ?>payment';
                }
            }, 1000);
        });
    </script>

    <style>
        .failed-card {
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
        
        .failed-icon-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
            animation: shake 0.8s;
        }
        
        .failed-icon {
            font-size: 5rem;
            color: #dc3545;
            animation: fadeIn 1s;
        }
        
        .error-details-container {
            margin: 1.5rem 0;
        }
        
        .accordion-button:not(.collapsed) {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        
        .redirect-notice {
            margin-top: 2rem;
            animation: fadeIn 1s;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            margin-top: 8px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background-color: #dc3545;
            transition: width 1s linear;
        }
        
        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            10%, 30%, 50%, 70%, 90% {transform: translateX(-10px);}
            20%, 40%, 60%, 80% {transform: translateX(10px);}
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .failed-card {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .failed-icon {
                font-size: 4rem;
            }
        }
    </style>
</body>
</html>
