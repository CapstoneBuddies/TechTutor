<?php
require_once '../backends/main.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';

// Check if token is provided
if (empty($token)) {
    $error = 'Invalid or missing reset token. Please request a new password reset link.';
} else {
    // Verify token and check if it's not expired
    global $conn;
    $stmt = $conn->prepare("SELECT u.uid, u.email, u.first_name, u.last_name, u.role FROM users u 
                           INNER JOIN login_tokens l ON u.uid = l.user_id 
                           WHERE l.token = ? AND l.type = 'reset' AND l.expiration_date > NOW() AND u.status = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = 'Invalid reset token. Please request a new password reset link.';
    } else {
        $user = $result->fetch_assoc();
        // No need to check expiry separately as it's handled in the SQL query
    }
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && empty($error)) {
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate passwords
    if (empty($password)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if (!$hashed_password) {
            log_error('Password hashing failed', 'security.log');
            $error = 'An error occurred while securing your password. Please try again.';
        } else {
            try {
                $conn->begin_transaction();

                // Get user ID from token
                $stmt = $conn->prepare("SELECT user_id FROM login_tokens WHERE token = ? AND type = 'reset' AND expiration_date > NOW()");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $user_id = $row['user_id'];

                    // Update user's password
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE uid = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    $stmt->execute();

                    // Delete reset token
                    $stmt = $conn->prepare("DELETE FROM login_tokens WHERE token = ? AND type = 'reset'");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();

                    $conn->commit();
                    $success = 'Your password has been reset successfully. You can now log in with your new password.';

                    // Fetch user details for notification
                    $stmt = $conn->prepare("SELECT first_name, email, role FROM users WHERE uid = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();

                    // Send notification
                    $message = "Your password has been reset successfully.";
                    sendNotification($user_id, $user['role'], $message, null, null, 'bi-check-circle', 'text-success');

                    // Send email confirmation
                    if ($user['role'] !== 'ADMIN') {
                        $subject = "TechTutor Password Reset Confirmation";
                        $email_message = "Dear {$user['first_name']},\n\n";
                        $email_message .= "Your TechTutor account password has been successfully reset.\n\n";
                        $email_message .= "If you did not perform this action, please contact us at support@techtutor.cfd immediately.\n\n";
                        $email_message .= "Best regards,\nThe TechTutor Team";

                        $mailer = getMailerInstance();
                        $mailer->addAddress($user['email']);
                        $mailer->Subject = $subject;
                        $mailer->Body = nl2br($email_message);

                        try {
                            $mailer->send();
                        } catch (Exception $e) {
                            log_error("Failed to send password reset confirmation email: " . $e->getMessage(), 'email.log');
                        }
                    }
                } else {
                    $error = 'Invalid or expired reset token.';
                    $conn->rollback();
                }
            } catch (Exception $e) {
                $conn->rollback();
                log_error($e->getMessage(), 'database.log');
                $error = 'An error occurred while resetting your password. Please try again later.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Reset Password</title>
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
    <link href="<?php echo CSS; ?>main.css" rel="stylesheet">
    
    <style>
        .reset-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .password-feedback {
            font-size: 0.8rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-password-container">
            <div class="text-center mb-4">
                <img src="<?php echo IMG; ?>stand_alone_logo.png" alt="TechTutor Logo" style="max-width: 200px;">
                <h2 class="mt-3">Reset Your Password</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                    <div class="mt-3 text-center">
                        <a href="<?php echo BASE; ?>login" class="btn btn-primary">Go to Login</a>
                    </div>
                </div>
            <?php elseif (empty($error)): ?>
                <form method="POST" id="resetPasswordForm">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength bg-light"></div>
                        <div class="password-feedback text-muted"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" name="reset_password">Reset Password</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="<?php echo BASE; ?>login" class="text-decoration-none">Back to Login</a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                });
            });
            
            // Password strength meter
            const passwordInput = document.getElementById('password');
            const strengthBar = document.querySelector('.password-strength');
            const feedback = document.querySelector('.password-feedback');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let message = '';
                    
                    if (password.length >= 8) {
                        strength += 25;
                    }
                    
                    if (password.match(/[A-Z]/)) {
                        strength += 10;
                    }
                    
                    if (password.match(/[0-9]/)) {
                        strength += 10;
                    }
                    
                    if (password.match(/[^A-Za-z0-9]/)) {
                        strength += 30;
                    }

                    if (password.match(/[^_*!]/)) {
                        strength += 25;
                    }
                    
                    // Update strength bar
                    strengthBar.style.width = strength + '%';
                    
                    // Set color based on strength
                    if (strength <= 25) {
                        strengthBar.style.backgroundColor = '#dc3545'; // red
                        message = 'Weak password';
                    } else if (strength <= 50) {
                        strengthBar.style.backgroundColor = '#ffc107'; // yellow
                        message = 'Moderate password';
                    } else if (strength <= 75) {
                        strengthBar.style.backgroundColor = '#0dcaf0'; // cyan
                        message = 'Good password';
                    } else {
                        strengthBar.style.backgroundColor = '#198754'; // green
                        message = 'Strong password';
                    }
                    
                    // Update feedback message
                    feedback.textContent = message;
                });
            }
            
            // Form validation
            const resetForm = document.getElementById('resetPasswordForm');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                    }
                    
                    if (passwordInput.value.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long!');
                    }
                });
            }
            if (document.querySelector('.alert-success')) {
                setTimeout(() => {
                    window.location.href = "<?php echo BASE; ?>login";
                }, 5000); // Redirect to login after 5 seconds
            }

        });
    </script>
</body>
</html>
