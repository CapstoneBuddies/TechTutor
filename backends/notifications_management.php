<?php
/**
 * Insert a new notification into the database
 */
function insertNotification($recipient_id, $recipient_role, $message, $link, $class_id, $icon, $icon_color) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, recipient_role, message, link, class_id, icon, icon_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiss", $recipient_id, $recipient_role, $message, $link, $class_id, $icon, $icon_color);
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (Exception $e) {
        log_error("Error inserting notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Update read status for notifications based on user role
 */
function updateNotificationsReadStatus($user_id, $role) {
    global $conn;
    
    try {
        if ($role == 'ADMIN') {
            $query = "UPDATE notifications SET is_read = 1";
            $stmt = $conn->prepare($query);
        } else {
            $query = "UPDATE notifications SET is_read = 1 
                     WHERE recipient_id = ? 
                     OR recipient_role = ? 
                     OR recipient_role = 'ALL'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $user_id, $role);
        }
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (Exception $e) {
        log_error("Error updating notification read status: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch notifications for a user based on their role
 */
function fetchUserNotifications($user_id, $role) {
    global $conn;
    
    try {
        if ($role == 'ADMIN') {
            // Admins can see all notifications
            $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as recipient_name, c.class_name 
                     FROM notifications n 
                     LEFT JOIN users u ON n.recipient_id = u.uid 
                     LEFT JOIN class c ON n.class_id = c.class_id 
                     ORDER BY n.created_at DESC";
            $stmt = $conn->prepare($query);
        } elseif ($role == 'TECHGURU') {
            // TechGurus see their own and their class notifications
            $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as recipient_name, c.class_name 
                     FROM notifications n 
                     LEFT JOIN users u ON n.recipient_id = u.uid 
                     LEFT JOIN class c ON n.class_id = c.class_id 
                     WHERE n.recipient_id = ? 
                     OR n.class_id IN (SELECT class_id FROM class WHERE tutor_id = ?) 
                     OR n.recipient_role = 'ALL' 
                     ORDER BY n.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $user_id);
        } else {
            // TechKids see their own and enrolled class notifications
            $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as recipient_name, c.class_name 
                     FROM notifications n 
                     LEFT JOIN users u ON n.recipient_id = u.uid 
                     LEFT JOIN class c ON n.class_id = c.class_id 
                     WHERE n.recipient_id = ? 
                     OR n.class_id IN (SELECT class_id FROM class_schedule WHERE user_id = ? AND role = 'STUDENT') 
                     OR n.recipient_role = 'ALL' 
                     ORDER BY n.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $notifications;
    } catch (Exception $e) {
        log_error("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark a single notification as read
 * 
 * @param int $notification_id The ID of the notification to mark as read
 * @return bool True if successful, false otherwise
 */
function markNotificationAsRead($notification_id) {
    global $conn;
    
    try {
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $notification_id);
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (Exception $e) {
        log_error("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}
?>