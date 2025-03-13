<?php
require_once '../../backends/main.php';

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
    error_log($log_message);
    
    // Commit transaction
    $conn->commit();
    
    // Send email notification
    $subject = "TechTutor Account Status Update";
    $email_message = "Dear {$user['first_name']},\n\n";
    $email_message .= "Your TechTutor account has been {$status_text} by an administrator.\n\n";
    
    if ($new_status == 0) {
        $email_message .= "If you believe this is an error, please contact our support team for assistance.\n\n";
    } else {
        $email_message .= "You can now log in to your account and access all features.\n\n";
    }
    
    $email_message .= "Best regards,\nThe TechTutor Team";
    
    // Get mailer instance from config.php
    $mailer = getMailerInstance();
    $mailer->addAddress($user['email']);
    $mailer->Subject = $subject;
    $mailer->Body = $email_message;
    
    try {
        $mailer->send();
    } catch (Exception $e) {
        error_log("Failed to send status update email: " . $e->getMessage());
        // Continue execution even if email fails
    }
    
    echo json_encode(['success' => true, 'message' => "User has been {$status_text} successfully"]);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("Error in toggle_user_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating user status']);
}
