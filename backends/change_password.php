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
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode([
        'status' => 'error',
        'message' => 'New passwords do not match'
    ]);
    exit;
}

// Password strength validation
if (strlen($new_password) < 8) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters long'
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
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password in database
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user_id);
    $update_stmt->execute();
    
    if ($update_stmt->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update password'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
