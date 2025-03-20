<?php
require_once '../../backends/main.php';
require_once BACKEND.'user_management.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get email from request
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}

try {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if user exists and get user info
    $stmt = $conn->prepare("SELECT uid, first_name, last_name, role FROM users WHERE email = ? AND status = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'User not found or account is inactive']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Don't allow resetting password for current admin user
    if ($user['uid'] == $_SESSION['user'] && $user['role'] === 'ADMIN') {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'You cannot reset your own password through this interface']);
        exit();
    }
    
    // Generate reset token (32 characters)
    $reset_token = bin2hex(random_bytes(16));
    
    // Set token expiry (24 hours from now)
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Update user with reset token and expiry
    $stmt = $conn->prepare("INSERT INTO login_tokens(user_id, token, expiration_date, type) VALUES(?,?,?,'reset')");
    $stmt->bind_param("iss", $user['uid'], $reset_token, $expiry);
    $stmt->execute();
    
    if ($stmt->affected_rows <= 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to generate reset token']);
        exit();
    }
    
    // Create reset link
    $reset_link = 'http://'.BASE . "reset?token=" . $reset_token;
    
    // Send notification to the user
    $message = "A password reset has been initiated for your account by an administrator.";
    sendNotification($user['uid'], $user['role'], $message, "reset_password.php?token={$reset_token}", null, 'bi-key', 'text-warning');
    
    // Log the action
    $admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
    $log_message = "Admin {$admin_name} initiated password reset for user {$user_name} (ID: {$user['uid']})";
    log_error($log_message,3);
    
    // Commit transaction
    $conn->commit();
    
    // Send email with reset link
    $subject = "TechTutor Password Reset";
    $email_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
                h2 { color: #007bff; }
                p { line-height: 1.6; }
                a { color: #007bff; text-decoration: none; font-weight: bold; }
                .footer { margin-top: 20px; font-size: 0.9em; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Password Reset Request</h2>
                <p>Dear <strong>{$user['first_name']}</strong>,</p>
                <p>An administrator has requested a password reset for your <strong>TechTutor</strong> account.</p>
                <p>To reset your password, please click the button below or copy and paste the link into your browser:</p>
                <p><a href='{$reset_link}' target='_blank'>{$reset_link}</a></p>
                <p><strong>Note:</strong> This link will expire in <span style='color: red;'>24 hours</span>.</p>
                <p>If you did not request this password reset, please contact our support team immediately.</p>
                <p class='footer'>Best regards,<br><strong>The TechTutor Team</strong></p>
            </div>
        </body>
        </html>";

    
    // Get mailer instance from config.php
    $mailer = getMailerInstance();
    $mailer->addAddress($email);
    $mailer->Subject = $subject;
    $mailer->Body = $email_message;
    
    try {
        $mailer->send();
    } catch (Exception $e) {
        log_error("Failed to send password reset email: " . $e->getMessage(),'mail');
        // Continue execution even if email fails, as we've already set the token
        echo json_encode(['success' => true, 'message' => 'Password reset link generated, but email could not be sent']);
        exit();
    }
    
    echo json_encode(['success' => true, 'message' => 'Password reset link has been sent to the user']);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("Error in send_password_reset.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing password reset']);
}
