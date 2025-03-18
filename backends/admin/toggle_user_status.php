<?php
require_once '../../backends/main.php';
require_once ROOT_PATH.'/backends/user_management.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get user ID from request
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Don't allow toggling status of current user
if ($user_id == $_SESSION['user']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own status']);
    exit();
}

try {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get current user status
    $stmt = $conn->prepare("SELECT status, email, first_name, last_name, role FROM users WHERE uid = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    $current_status = $user['status'];
    $new_status = $current_status == 1 ? 0 : 1;
    $status_text = $new_status == 1 ? 'activated' : 'restricted';
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE uid = ?");
    $stmt->bind_param("ii", $new_status, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows <= 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
        exit();
    }
    
    // Send notification to the user
    $message = "Your account has been {$status_text} by an administrator.";
    $icon = $new_status == 1 ? 'bi-check-circle' : 'bi-slash-circle';
    $icon_color = $new_status == 1 ? 'text-success' : 'text-danger';
    
    sendNotification($user_id, $user['role'], $message, null, null, $icon, $icon_color);
    
    // Log the action
    $admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
    $log_message = "Admin {$admin_name} {$status_text} user {$user_name} (ID: {$user_id})";
    log_error($log_message,'security');
    
    // Commit transaction
    $conn->commit();
    
    // Send email notification
    $subject = "TechTutor Account Status Update";
    $email_message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
                .header { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
                .content { font-size: 16px; }
                .footer { margin-top: 20px; font-size: 14px; color: #777; }
                .btn { display: inline-block; background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
                .btn:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class='container'>
                <p class='header'>Hello, {$user['first_name']},</p>
                
                <p class='content'>
                    We wanted to inform you that your <strong>TechTutor account</strong> has been <strong>{$status_text}</strong> by an administrator.
                </p>
                
                " . ($new_status == 0 
                    ? "<p class='content'>If you believe this was done in error, please don't hesitate to contact our <a href='mailto:support@techtutor.cfd'>support team</a> for assistance.</p>" 
                    : "<p class='content'>You can now log in and enjoy full access to all features.</p>
                       <a href='" . BASE . "login' class='btn'>Login to Your Account</a>") . "
                
                <p class='footer'>Best regards,<br><strong>The TechTutor Team</strong></p>
            </div>
        </body>
        </html>";

    
    // Get mailer instance from config.php
    $mailer = getMailerInstance();
    $mailer->addAddress($user['email']);
    $mailer->Subject = $subject;
    $mailer->Body = $email_message;
    
    try {
        $mailer->send();
    } catch (Exception $e) {
        log_error("Failed to send status update email: " . $e->getMessage(),'mail');
        // Continue execution even if email fails
    }
    echo json_encode(['success' => true, 'message' => "User has been {$status_text} successfully"]);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    log_error("Error in toggle_user_status.php: " . $e->getMessage(),'security');
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating user status']);
}
