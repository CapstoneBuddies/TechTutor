<?php
require_once '../backends/main.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    log_error("Unauthorized access attempt to toggle class status", 'security');
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['classId']) || !isset($data['status'])) {
        throw new Exception("Missing required parameters");
    }

    $class_id = (int)$data['classId'];
    $status = $data['status'];

    // Validate status
    if (!in_array($status, ['active', 'restricted', 'completed', 'pending'])) {
        throw new Exception("Invalid status value");
    }

    // Start transaction
    $conn->begin_transaction();

    // Get class and TechGuru info before update
    $info_query = "SELECT c.class_name, u.uid as techguru_id FROM class c 
                   JOIN users u ON c.tutor_id = u.uid 
                   WHERE c.class_id = ?";
    $info_stmt = $conn->prepare($info_query);
    if (!$info_stmt) {
        throw new Exception("Failed to prepare info query: " . $conn->error);
    }

    $info_stmt->bind_param("i", $class_id);
    if (!$info_stmt->execute()) {
        throw new Exception("Failed to execute info query: " . $info_stmt->error);
    }

    $class_info = $info_stmt->get_result()->fetch_assoc();
    if (!$class_info) {
        throw new Exception("Class not found");
    }

    // Update class status
    $update_query = "UPDATE class SET status = ? WHERE class_id = ?";
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Failed to prepare update query: " . $conn->error);
    }

    $update_stmt->bind_param("si", $status, $class_id);
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update class status: " . $update_stmt->error);
    }

    if ($update_stmt->affected_rows === 0) {
        throw new Exception("No changes made to class status");
    }

    // Send notification to TechGuru
    $status_text = ucfirst($status);
    $message = "Your class \"{$class_info['class_name']}\" has been set to {$status_text} by an administrator";
    
    // Set icon based on status
    switch($status) {
        case 'active':
            $icon = 'bi-shield-check';
            $icon_color = 'text-success';
            break;
        case 'restricted':
            $icon = 'bi-shield-x';
            $icon_color = 'text-danger';
            break;
        case 'completed':
            $icon = 'bi-check-circle';
            $icon_color = 'text-info';
            break;
        case 'pending':
            $icon = 'bi-clock';
            $icon_color = 'text-warning';
            break;
        default:
            $icon = 'bi-info-circle';
            $icon_color = 'text-secondary';
    }
    
    if (!sendNotification(
        $class_info['techguru_id'],
        'TECHGURU',
        $message,
        "/dashboard/class/details?id={$class_id}",
        $class_id,
        $icon,
        $icon_color
    )) {
        throw new Exception("Failed to send notification");
    }

    // Log the action
    $admin_id = $_SESSION['user'];
    $log_message = "Admin (ID: {$admin_id}) changed class ID: {$class_id} status to {$status}";
    log_error($log_message, 'general');

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    log_error("Class status toggle failed: " . $e->getMessage(), 'database');
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while updating class status']);
} finally {
    if (isset($info_stmt)) $info_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
}
