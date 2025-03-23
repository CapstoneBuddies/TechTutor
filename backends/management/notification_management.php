function checkNewNotifications($user_id, $last_id = 0) {
    global $conn;
    try {
        // Get new notifications
        $query = "SELECT id, message, created_at 
                 FROM notifications 
                 WHERE user_id = ? AND id > ? AND is_read = 0
                 ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $last_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'message' => $row['message'],
                'created_at' => $row['created_at']
            ];
        }
        
        // Get total unread count
        $count_query = "SELECT COUNT(*) as unread_count 
                       FROM notifications 
                       WHERE user_id = ? AND is_read = 0";
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $unread_count = $count_result->fetch_assoc()['unread_count'];
        
        return [
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ];
    } catch (Exception $e) {
        log_error($e->getMessage(), "notification_check");
        return [
            'success' => false,
            'error' => 'Failed to check notifications'
        ];
    }
} 