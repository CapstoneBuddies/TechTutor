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
                     WHERE u.status = 1
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
                     AND u.status = 1
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
                     AND u.status = 1
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
            $query = "UPDATE notifications SET is_read = 1 WHERE (recipient_id = ? OR recipient_role = ? OR recipient_role = 'ALL') AND is_read = 0";
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
        $stmt = $conn->prepare("SELECT n.*, c.class_name FROM notifications n LEFT JOIN class c ON n.class_id = c.class_id WHERE (? = 'ADMIN') OR n.recipient_id = ? OR n.recipient_role = ? OR n.recipient_role = 'ALL' AND n.is_read = 0 ORDER BY n.created_at DESC LIMIT 50");
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
            WHERE cs.schedule_id = ? AND e.status = 'active' AND u.status = 1;
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
                 WHERE user_id = ? AND id > ? AND is_read = 0 AND is_read = 0
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
                       WHERE user_id = ? AND is_read = 0 AND is_read = 0";
        
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

/**
 * Send a message from a TechGuru to a TechKid
 * 
 * @param int $sender_id The ID of the TechGuru sending the message
 * @param int $recipient_id The ID of the TechKid receiving the message
 * @param int $class_id The class ID related to the message
 * @param string $subject The subject of the message
 * @param string $message The message content
 * @param bool $send_email Whether to send an email notification
 * @return array Success status and message
 */
function sendTechGuruMessage($sender_id, $recipient_id, $class_id, $subject, $message, $send_email = false) {
    global $conn;
    
    try {
        // Verify the sender is a TechGuru
        $stmt = $conn->prepare("SELECT role FROM users WHERE uid = ? AND role = 'TECHGURU' AND status = 1");
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        // Verify the recipient is a TechKid enrolled in the class
        $stmt = $conn->prepare("
            SELECT u.first_name, u.last_name, u.email 
            FROM users u 
            JOIN enrollments e ON u.uid = e.student_id 
            WHERE u.uid = ? AND e.class_id = ? AND u.role = 'TECHKID' AND u.status = 1
        ");
        $stmt->bind_param("ii", $recipient_id, $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Recipient is not enrolled in this class'];
        }
        
        $recipient = $result->fetch_assoc();
        
        // Get class name and sender info
        $stmt = $conn->prepare("
            SELECT c.class_name, u.first_name, u.last_name, u.email
            FROM class c 
            JOIN users u ON c.tutor_id = u.uid
            WHERE c.class_id = ? AND c.tutor_id = ? AND u.status = 1
        ");
        $stmt->bind_param("ii", $class_id, $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Class not found or you are not the tutor'];
        }
        
        $data = $result->fetch_assoc();
        $class_name = $data['class_name'];
        $sender_name = $data['first_name'] . ' ' . $data['last_name'];
        $sender_email = $data['email'];
        
        $conn->begin_transaction();
        
        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                recipient_id, recipient_role, class_id, message, icon, icon_color
            ) VALUES (?, 'TECHKID', ?, ?, 'bi-envelope-fill', 'text-primary')
        ");
        
        $notification_message = "<strong>Message from {$sender_name} (TechGuru):</strong><br>" . 
                               "<strong>Subject:</strong> {$subject}<br>" . 
                               "<p>{$message}</p>";
        
        $stmt->bind_param("iis", $recipient_id, $class_id, $notification_message);
        $stmt->execute();
        
        // Send email if requested
        if ($send_email) {
            try {
                $mail = getMailerInstance();
                $mail->setFrom($sender_email, $sender_name);
                $mail->addAddress($recipient['email'], $recipient['first_name'] . ' ' . $recipient['last_name']);
                $mail->Subject = "[TechTutor] {$subject} - {$class_name}";
                
                // Create HTML email body
                $email_body = createEmailTemplate(
                    'Message from Your TechGuru',
                    $class_name,
                    $recipient['first_name'],
                    $sender_name,
                    $subject,
                    $message,
                    'TechGuru'
                );
                
                $mail->Body = $email_body;
                $mail->send();
            } catch (Exception $e) {
                log_error("Failed to send email: " . $e->getMessage(), "messaging");
                // Continue execution even if email fails
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'Message sent successfully',
            'data' => [
                'recipient' => $recipient['first_name'] . ' ' . $recipient['last_name'],
                'class_name' => $class_name
            ]
        ];
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        log_error("Error in sendTechGuruMessage: " . $e->getMessage(), "messaging");
        return ['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()];
    }
}

/**
 * Send a message from a TechKid to a TechGuru
 * 
 * @param int $sender_id The ID of the TechKid sending the message
 * @param int $class_id The class ID related to the message
 * @param string $subject The subject of the message
 * @param string $message The message content
 * @return array Success status and message
 */
function sendTechKidMessage($sender_id, $class_id, $subject, $message) {
    global $conn;
    
    try {
        // Verify the sender is a TechKid
        $stmt = $conn->prepare("
            SELECT u.role, u.first_name, u.last_name, u.email 
            FROM users u 
            WHERE u.uid = ? AND u.role = 'TECHKID' AND u.status = 1
        ");
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $sender = $result->fetch_assoc();
        $sender_name = $sender['first_name'] . ' ' . $sender['last_name'];
        $sender_email = $sender['email'];
        
        // Get class and tutor info
        $stmt = $conn->prepare("
            SELECT c.class_name, c.tutor_id, u.first_name, u.last_name, u.email
            FROM class c 
            JOIN users u ON c.tutor_id = u.uid
            JOIN enrollments e ON c.class_id = e.class_id
            WHERE c.class_id = ? AND e.student_id = ? AND e.status = 'active' AND u.status = 1
        ");
        $stmt->bind_param("ii", $class_id, $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Class not found or you are not enrolled'];
        }
        
        $data = $result->fetch_assoc();
        $class_name = $data['class_name'];
        $tutor_id = $data['tutor_id'];
        $tutor_name = $data['first_name'] . ' ' . $data['last_name'];
        $tutor_email = $data['email'];
        
        $conn->begin_transaction();
        
        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                recipient_id, recipient_role, class_id, message, icon, icon_color
            ) VALUES (?, 'TECHGURU', ?, ?, 'bi-envelope-fill', 'text-info')
        ");
        
        $notification_message = "<strong>Message from {$sender_name} (Student):</strong><br>" . 
                               "<strong>Subject:</strong> {$subject}<br>" . 
                               "<p>{$message}</p>";
        
        $stmt->bind_param("iis", $tutor_id, $class_id, $notification_message);
        $stmt->execute();
        
        // Try to send an email notification to the tutor
        try {
            $mail = getMailerInstance($sender_name); 
            $mail->addAddress($tutor_email, $tutor_name);
            $mail->Subject = "[TechTutor Student Message] {$subject} - {$class_name}";
            
            // Create HTML email body
            $email_body = createEmailTemplate(
                'Message from Your Student',
                $class_name,
                $tutor_name,
                $sender_name,
                $subject,
                $message,
                'Student'
            );
            $mail->Body = $email_body;
            $mail->send();
        } catch (Exception $e) {
            // Log email error but continue with the notification
            log_error("Failed to send email notification: " . $e->getMessage(), "mail");
        }
        
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'Message sent successfully to your TechGuru',
            'data' => [
                'tutor_name' => $tutor_name,
                'class_name' => $class_name
            ]
        ];
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        log_error("Error in sendTechKidMessage: " . $e->getMessage(), "messaging");
        return ['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()];
    }
}

/**
 * Create an email template for messages
 * 
 * @param string $title The email title
 * @param string $class_name The class name
 * @param string $recipient_first_name The recipient's first name
 * @param string $sender_name The sender's name
 * @param string $subject The message subject
 * @param string $message The message content
 * @param string $sender_role The role of the sender (TechGuru or Student)
 * @return string HTML email template
 */
function createEmailTemplate($title, $class_name, $recipient_first_name, $sender_name, $subject, $message, $sender_role) {
    $email_template = '
    <div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
        <div style="background: #0dcaf0; padding: 20px; text-align: center; color: white;">
            <h2 style="margin: 0; font-size: 22px;">' . htmlspecialchars($title) . '</h2>
        </div>
        <div style="padding: 20px; background: #f9f9f9;">
            <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($recipient_first_name) . '</strong>,</p>
            <p style="font-size: 16px; color: #555;">
                You have received a new message from <strong>' . htmlspecialchars($sender_name) . ' (' . htmlspecialchars($sender_role) . ')</strong> 
                regarding the class <strong>' . htmlspecialchars($class_name) . '</strong>.
            </p>
            <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #0dcaf0; background: #f0f0f0;">
                <h3 style="margin-top: 0; color: #444; font-size: 18px;">Subject: ' . htmlspecialchars($subject) . '</h3>
                <div style="color: #555; font-size: 16px;">
                    ' . nl2br(htmlspecialchars($message)) . '
                </div>
            </div>
            <p style="font-size: 15px; color: #666;">
                You can view all your notifications and messages by logging into your TechTutor account.
            </p>
            <div style="text-align: center; margin: 25px 0 15px;">
                <a href="' . BASE . 'dashboard" 
                   style="background: #0dcaf0; color: white; padding: 10px 20px; text-decoration: none; font-size: 16px; border-radius: 5px;">
                    Go to Dashboard
                </a>
            </div>
        </div>
        <div style="background: #ddd; padding: 10px; text-align: center; font-size: 14px; color: #666;">
            <p style="margin: 5px 0;">This is an automated message, please do not reply directly to this email.</p>
            <p style="margin: 5px 0;">Best regards,<br><strong>The TechTutor Team</strong></p>
        </div>
    </div>';
    
    return $email_template;
}
?>