<?php
require_once '../../backends/main.php';
require_once BACKEND.'user_management.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get query parameter
$query = isset($_POST['query']) ? $_POST['query'] : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Query parameter is required']);
    exit();
}

try {
    global $conn;
    
    // Search for users by email or name
    $stmt = $conn->prepare("SELECT uid, email, first_name, last_name FROM users 
                           WHERE (email LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?) 
                           AND status = 1 
                           ORDER BY last_name, first_name 
                           LIMIT 10");
    
    $searchParam = "%{$query}%";
    $stmt->bind_param("ss", $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    error_log("Error in get_user_emails.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving user emails']);
}
