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
                     OR n.class_id IN (SELECT class_id FROM enrollments WHERE student_id = ? AND status = 'active') 
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
function markNotificationsAsRead($notification_id = null) {
    global $conn;

    if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
        return false;
    }

    try {
        if ($notification_id !== null) {
            // Mark a specific notification as read
            $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $notification_id);
        } else {
            // Mark all relevant notifications as read
            $query = "UPDATE notifications SET is_read = 1 WHERE (recipient_id = ? OR recipient_role = ? OR recipient_role = 'ALL')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $_SESSION['user'], $_SESSION['role']);
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    } catch (Exception $e) {
        log_error("Error marking notification(s) as read: " . $e->getMessage());
        return false;
    }
}


/**
 * Get notifications for a user based on their role and access level
 * 
 * @param int $user_id The user ID
 * @param string $role The user's role (ADMIN, TECHGURU, TECHKID)
 * @return array Array of notifications
 */
function getUserNotifications($user_id, $role) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT n.*, c.class_name FROM notifications n LEFT JOIN class c ON n.class_id = c.class_id WHERE (? = 'ADMIN') OR n.recipient_id = ? OR n.recipient_role = ? OR n.recipient_role = 'ALL' ORDER BY n.created_at DESC LIMIT 50");
        $stmt->bind_param("sii", $role, $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        log_error("Failed to get user notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Send a notification to a user or role
 * 
 * @param int|null $recipient_id The user ID to send to, or null for role-wide notifications
 * @param string $recipient_role The role to send to (ADMIN, TECHGURU, TECHKID, ALL)
 * @param string $message The notification message
 * @param string|null $link Optional link for the notification
 * @param int|null $class_id Optional class ID if notification is related to a class
 * @param string $icon Bootstrap icon class (e.g., 'bi-person-check')
 * @param string $icon_color Bootstrap color class (e.g., 'text-success')
 * @return bool True if notification was sent successfully
 */
function sendNotification($recipient_id, $recipient_role, $message, $link = null, $class_id = null, $icon = 'bi-bell', $icon_color = 'text-primary') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, recipient_role, message, link, class_id, icon, icon_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiss", $recipient_id, $recipient_role, $message, $link, $class_id, $icon, $icon_color);
        return $stmt->execute();
    } catch (Exception $e) {
        log_error("Failed to send notification: " . $e->getMessage());
        return false;
    }
}
// Function to send enrollment email
function sendEnrollmentEmail($to, $name, $class_name, $tutor_name) {
    $mail = getMailerInstance();
    try {
        $mail->addAddress($to, $name);
        $mail->Subject = "Enrollment Confirmation - $class_name";
        $mail->Body = '
        <div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
            <div style="background: #0dcaf0; padding: 20px; text-align: center; color: white;">
                <h2 style="margin: 0; font-size: 22px;">Enrollment Confirmation</h2>
            </div>
            <div style="padding: 20px; background: #f9f9f9;">
                <p style="font-size: 16px; color: #333;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p style="font-size: 16px; color: #555;">
                    🎉 You have successfully enrolled in <strong>' . htmlspecialchars($class_name) . '</strong> with 
                    <strong>' . htmlspecialchars($tutor_name) . '</strong>.
                </p>
                <p style="font-size: 16px; color: #555;">
                    📅 Check your dashboard for class details and schedule.
                </p>
                <div style="text-align: center; margin: 20px 0;">
                    <a href="' . BASE . 'dashboard/class" 
                       style="background: #0dcaf0; color: white; padding: 10px 20px; text-decoration: none; font-size: 16px; border-radius: 5px;">
                        Go to My Classes
                    </a>
                </div>
            </div>
            <div style="background: #ddd; padding: 10px; text-align: center; font-size: 14px; color: #666;">
                Best regards,<br>
                <strong>The TechTutor Team</strong>
            </div>
        </div>';

        $mail->send();
    } catch (Exception $e) {
        log_error("Failed to send enrollment email: " . $e->getMessage(), 'mail');
        return;
    }
}

// Function to send Class Session link
function sendClassSessionLink($scheduleId) {
    global $conn;

    try {
        // Fetch meeting details
        $stmt = $conn->prepare("
            SELECT m.meeting_uid, m.attendee_pw, c.class_id, c.class_name, CONCAT(u.first_name,' ',u.last_name) AS 'tutor',
            u.email
            FROM meetings m
            JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
            JOIN class c ON cs.class_id = c.class_id
            JOIN users u ON c.tutor_id = u.uid
            WHERE m.schedule_id = ?
        ");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $meetingData = $stmt->get_result()->fetch_assoc();

        if (!$meetingData) {
            throw new Exception("Meeting not found for this session.");
        }

        // Fetch enrolled students
        $stmt = $conn->prepare("
            SELECT u.uid, u.email, CONCAT(u.first_name, ' ', u.last_name) AS student_name
            FROM enrollments e
            JOIN class_schedule cs ON e.class_id = cs.class_id
            JOIN users u ON e.student_id = u.uid
            WHERE cs.schedule_id = ?;
        ");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($students)) {
            throw new Exception("No students enrolled in this session.");
        }

        // Initialize meeting class to generate join links
        $meeting = new MeetingManagement();

        // Loop through each student and send the meeting link
        foreach ($students as $student) {
            $joinUrl = $meeting->getJoinUrl($meetingData['meeting_uid'], $student['student_name'], $meetingData['attendee_pw']);

            // Send email notification (using PHPMailer)
            $mail = getMailerInstance();
            $subject = "Class Session Link - {$meetingData['class_name']}";
            $body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Class Session Invitation</title>
            </head>
            <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;'>

                <table style='max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);'>
                    <!-- Header -->
                    <tr>
                        <td style='text-align: center;'>
                            <h2 style='color: #0052cc; margin-bottom: 10px;'>📚 TechTutor Class Session</h2>
                            <p style='color: #555;'>Your class session is starting soon!</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #333;'>Hello <strong>{$student['student_name']}</strong>,</p>
                            <p style='font-size: 16px; color: #333;'>
                                You have a scheduled class for <strong>{$meetingData['class_name']}</strong>.
                            </p>
                            <p style='font-size: 16px; color: #333;'>Click below to join:</p>
                            <div style='text-align: center; margin: 20px 0;'>
                                <a href='{$joinUrl}' style='background-color: #0052cc; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 16px; display: inline-block;'>Join Now</a>
                            </div>
                            <p style='font-size: 14px; color: #666;'>If the button doesn't work, copy and paste this link into your browser:</p>
                            <p style='word-wrap: break-word; background: #f1f1f1; padding: 10px; border-radius: 5px; font-size: 14px; color: #333;'>{$joinUrl}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='text-align: center; padding-top: 20px;'>
                            <p style='font-size: 14px; color: #888;'>Best regards,<br><strong>TechTutor Team</strong></p>
                        </td>
                    </tr>
                </table>

            </body>
            </html>
            ";
            $mail->setFrom($meetingData['email'], $meetingData['tutor']);
            $mail->addAddress($student['email'],$student['student_name']);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            // Insert system notification
            $stmt = $conn->prepare("
                INSERT INTO notifications (recipient_id, recipient_role, class_id, message, icon, icon_color)
                VALUES (?, 'TECHKID', ?, ?, 'bi-camera-video-fill', 'text-primary')
            ");
            $notifMessage = "Your class <b>{$meetingData['class_name']}</b> is starting soon! <a href='{$joinUrl}'>Join now</a>";
            $stmt->bind_param("iis", $student['uid'], $meetingData['class_id'], $notifMessage);
            $stmt->execute();
        }

        return ['success' => true, 'message' => 'Session links sent successfully.'];

    } catch (Exception $e) {
        log_error("Failed to send class session link: " . $e->getMessage(), "meeting");
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

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
?>