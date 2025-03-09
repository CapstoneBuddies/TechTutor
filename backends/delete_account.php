<?php
// Include database connection and other required files
require_once '../config/config.php';
require_once '../config/db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You are not logged in'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get form data
$password = $_POST['password'] ?? '';

// Validate input
if (empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password is required to confirm account deletion'
    ]);
    exit;
}

try {
    // Get user's current password from database
    $user_id = $_SESSION['user'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password is incorrect'
        ]);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete user's profile picture if it exists and is not the default
        $profile_pic = $_SESSION['profile'] ?? '';
        if ($profile_pic && $profile_pic !== IMG . 'users/default.png') {
            $file_path = str_replace(BASE, '../', $profile_pic);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete user's data from related tables first (to maintain referential integrity)
        // This will depend on your database structure - adjust as needed
        
        // Example: Delete from techguru table if user is a techguru
        if ($_SESSION['role'] === 'TECHGURU') {
            $delete_guru = $conn->prepare("DELETE FROM techguru WHERE user_id = ?");
            $delete_guru->bind_param("i", $user_id);
            $delete_guru->execute();
        }
        
        // Example: Delete from techkid table if user is a techkid
        if ($_SESSION['role'] === 'TECHKID') {
            $delete_kid = $conn->prepare("DELETE FROM techkid WHERE user_id = ?");
            $delete_kid->bind_param("i", $user_id);
            $delete_kid->execute();
        }
        
        // Delete user from users table
        $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_user->bind_param("i", $user_id);
        $delete_user->execute();
        
        if ($delete_user->affected_rows > 0) {
            // Commit transaction
            $conn->commit();
            
            // Destroy session
            session_destroy();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Your account has been successfully deleted'
            ]);
        } else {
            // Rollback transaction
            $conn->rollback();
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete account'
            ]);
        }
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
