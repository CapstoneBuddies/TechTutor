<?php
require_once '../../backends/main.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    $notif_id = intval($_POST['notification_id']);

    try {
        // Prepare delete query
        $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
        $stmt->bind_param("i", $notif_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notification deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete notification.']);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
