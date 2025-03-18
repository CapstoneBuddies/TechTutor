<?php
    require_once '../main.php'; 
    require_once BACKEND . 'admin_management.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['role'])) {
        $userId = $_POST['id'];
        $newRole = $_POST['role'];

        // Validate role
        $validRoles = ['ADMIN', 'TECHGURU', 'TECHKID'];
        if (!in_array($newRole, $validRoles)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            exit();
        }

        // Update role in database
        if (updateUserRole($userId, $newRole)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
?>