<?php
require_once '../../backends/main.php';
require_once ROOT_PATH.'/backends/user_management.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


// Get parameters from request
$role = isset($_POST['role']) ? $_POST['role'] : 'all';
$search = isset($_POST['search']) ? $_POST['search'] : '';

try {
    // Get users based on role and search criteria
    $users = [];
    
    if ($role === 'all') {
        // Get all users with search filter
        if (!empty($search)) {
            $users = searchUsers($search);
        } else {
            // Get users from all roles
            $adminUsers = getUserByRole('ADMIN');
            $techguruUsers = getUserByRole('TECHGURU');
            $techkidUsers = getUserByRole('TECHKID');
            
            $users = array_merge($adminUsers, $techguruUsers, $techkidUsers);
            
            // Sort by last name, first name
            usort($users, function($a, $b) {
                $lastNameCompare = strcmp($a['last_name'], $b['last_name']);
                if ($lastNameCompare !== 0) {
                    return $lastNameCompare;
                }
                return strcmp($a['first_name'], $b['first_name']);
            });
        }
    } else {
        // Get users by specific role with search filter
        if (!empty($search)) {
            $users = searchUsersByRole($role, $search);
        } else {
            $users = getUserByRole($role);
        }
    }
    
    // Format status for display
    foreach ($users as &$user) {
        $user['status_text'] = normalizeStatus($user['status']);
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    error_log("Error in get_users.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving users']);
}
