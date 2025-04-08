<?php
require_once '../../backends/main.php';
require_once BACKEND.'user_management.php';

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

// Don't allow deleting current user
if ($user_id == $_SESSION['user']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

$filePath = BASE.'logs/deleted_accounts/';

try {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get user info before deletion
    $stmt = $conn->prepare("SELECT email, first_name, last_name, role FROM users WHERE uid = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Get count for deleted user/s
    $count = $conn->prepare("SELECT email FROM users WHERE email LIKE 'deleted%' ");
    $count->execute();
    $result = $count->get_result();
    $counter = 0;
    if ($result->num_rows > 0) { 
        $counter = $result->num_rows;
    }
    $deleted_status = ($counter > 0) ? 'deleted'.$counter : 'deleted';

    // Update User status to 2 and set Email to unknown
    $delete = $conn->prepare("UPDATE users SET status=2, email=? WHERE uid = ?");
    $delete->bind_param("si", $deleted_status, $user_id);
    $delete->execute();

    // Log the action
    $admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $user_name = $user['first_name'] . ' ' . $user['last_name'].' email:'.$user['email'];
    $log_message = "Admin {$admin_name} deleted user {$user_name} (ID: {$user_id})";
    error_log($log_message,3,$filePath);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'User has been deleted successfully']);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    log_error("Error in delete_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting user']);
}
/**
 * // Delete user's notifications
    $stmt = $conn->prepare("DELETE FROM notifications WHERE recipient_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Delete user's login tokens
    $stmt = $conn->prepare("DELETE FROM login_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Handle role-specific deletions
    if ($user['role'] === 'TECHGURU') {
        // Get classes taught by this tutor
        $stmt = $conn->prepare("SELECT class_id FROM class WHERE tutor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $class_ids = [];
        while ($row = $result->fetch_assoc()) {
            $class_ids[] = $row['class_id'];
        }
        
        // Delete class schedules for these classes
        if (!empty($class_ids)) {
            $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
            $stmt = $conn->prepare("DELETE FROM class_schedule WHERE class_id IN ($placeholders)");
            $types = str_repeat('i', count($class_ids));
            $stmt->bind_param($types, ...$class_ids);
            $stmt->execute();
            
            // Delete class files
            $stmt = $conn->prepare("DELETE FROM file_management WHERE class_id IN ($placeholders)");
            $stmt->bind_param($types, ...$class_ids);
            $stmt->execute();
            
            // Delete classes
            $stmt = $conn->prepare("DELETE FROM class WHERE class_id IN ($placeholders)");
            $stmt->bind_param($types, ...$class_ids);
            $stmt->execute();
        }
        
        // Delete ratings for this tutor
        $stmt = $conn->prepare("DELETE FROM session_feedback WHERE tutor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif ($user['role'] === 'TECHKID') {
        // Delete student enrollments
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete ratings given by this student
        $stmt = $conn->prepare("DELETE FROM session_feedback WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete certificates
        $stmt = $conn->prepare("DELETE FROM certificate WHERE recipient = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // Delete user's transactions
    $stmt = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Delete user's paymongo transactions
    $stmt = $conn->prepare("DELETE FROM paymongo_transactions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Finally, delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE uid = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows <= 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        exit();
    }
    */
?>