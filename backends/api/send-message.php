<?php
/**
 * API endpoint for sending messages to students in a class
 */
require_once '../main.php';
require_once BACKEND.'class_management.php';

// Default response
$response = ['success' => false];

// Check if user is logged in and is a TECHGURU
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHGURU') {
    $response['error'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required parameters
$class_id = isset($input['class_id']) ? intval($input['class_id']) : 0;
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$send_email = isset($input['send_email']) ? (bool)$input['send_email'] : false;
$selected_students = isset($input['selected_students']) && is_array($input['selected_students']) 
    ? array_map('intval', $input['selected_students']) 
    : [];

// Validate inputs
if (empty($class_id)) {
    $response['error'] = 'Class ID is required';
    echo json_encode($response);
    exit();
}

if (empty($subject)) {
    $response['error'] = 'Subject is required';
    echo json_encode($response);
    exit();
}

if (empty($message)) {
    $response['error'] = 'Message is required';
    echo json_encode($response);
    exit();
}

try {
    global $conn;
    
    // Verify the tutor owns this class
    $stmt = $conn->prepare("SELECT class_name FROM class WHERE class_id = ? AND tutor_id = ?");
    $stmt->bind_param("ii", $class_id, $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Unauthorized to send messages for this class");
    }
    
    $class = $result->fetch_assoc();
    $class_name = $class['class_name'];
    
    // Get enrolled students
    $students = getEnrolledStudents($class_id);
    
    if (empty($students)) {
        throw new Exception("No students are enrolled in this class");
    }
    
    // Filter students if specific students were selected
    if (!empty($selected_students)) {
        $students = array_filter($students, function($student) use ($selected_students) {
            return in_array($student['uid'], $selected_students);
        });
    }
    
    if (empty($students)) {
        throw new Exception("No selected students found in the class");
    }
    
    $conn->begin_transaction();
    
    try {
        // Get tutor information
        $stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE uid = ?");
        $stmt->bind_param("i", $_SESSION['user']);
        $stmt->execute();
        $tutor = $stmt->get_result()->fetch_assoc();
        $tutor_name = $tutor['first_name'] . ' ' . $tutor['last_name'];
        
        $notifications_sent = 0;
        $emails_sent = 0;
        
        foreach ($students as $student) {
            // Insert system notification
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    recipient_id, recipient_role, class_id, message, icon, icon_color
                ) VALUES (?, 'TECHKID', ?, ?, 'bi-envelope-fill', 'text-primary')
            ");
            
            $notification_message = "<strong>Message from {$tutor_name}:</strong><br>" . 
                                   "<strong>Subject:</strong> {$subject}<br>" . 
                                   "<p>{$message}</p>";
            
            $stmt->bind_param("iis", $student['uid'], $class_id, $notification_message);
            $stmt->execute();
            $notifications_sent++;
            
            // Send email if requested
            if ($send_email) {
                $mail = getMailerInstance();
                $mail->setFrom($tutor['email'], $tutor_name);
                $mail->addAddress($student['email'], $student['first_name'] . ' ' . $student['last_name']);
                $mail->Subject = "[TechTutor] {$subject} - {$class_name}";
                
                // Create HTML email body
                $email_body = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Message from Your TechGuru</title>
                </head>
                <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;'>
                    <table style='max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);'>
                        <tr>
                            <td style='text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee;'>
                                <h2 style='color: #0052cc; margin-bottom: 5px;'>Message from Your TechGuru</h2>
                                <p style='color: #666; margin-top: 0;'>Class: {$class_name}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 20px 0;'>
                                <p style='font-size: 16px; color: #333;'>Hello <strong>{$student['first_name']}</strong>,</p>
                                <p style='font-size: 16px; color: #333;'>Your tutor <strong>{$tutor_name}</strong> has sent you a message:</p>
                                
                                <div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0052cc; margin: 15px 0;'>
                                    <h3 style='margin-top: 0; color: #0052cc;'>{$subject}</h3>
                                    <div style='color: #333;'>{$message}</div>
                                </div>
                                
                                <p style='font-size: 14px; color: #666;'>You can reply to this message by logging into your TechTutor dashboard and using the messaging feature.</p>
                            </td>
                        </tr>
                        <tr>
                            <td style='text-align: center; padding-top: 20px; border-top: 1px solid #eee;'>
                                <p style='font-size: 14px; color: #888;'>Best regards,<br><strong>TechTutor Team</strong></p>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
                ";
                
                $mail->Body = $email_body;
                $mail->send();
                $emails_sent++;
            }
        }
        
        $conn->commit();
        
        $response = [
            'success' => true,
            'message' => 'Messages sent successfully',
            'data' => [
                'notifications_sent' => $notifications_sent,
                'emails_sent' => $emails_sent,
                'recipients' => count($students)
            ]
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    log_error("Error sending messages: " . $e->getMessage(), "messaging");
    $response['error'] = $e->getMessage();
}

// Send response
header('Content-Type: application/json');
echo json_encode($response);
