<?php
require_once '../main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the schedule IDs from POST data
$scheduleIds = $_POST['scheduleIds'] ?? [];
$classId = $_POST['classId'] ?? 0;

if (empty($scheduleIds) || !$classId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

try {
    global $conn;
    $conn->begin_transaction();

    // Get class details for notification
    $classDetails = getClassDetails($classId);
    if (!$classDetails || $classDetails['tutor_id'] != $_SESSION['user']) {
        throw new Exception('Invalid class or unauthorized access');
    }

    // Delete schedules
    $stmt = $conn->prepare("DELETE FROM class_schedule WHERE schedule_id IN (" . str_repeat('?,', count($scheduleIds) - 1) . "?) AND class_id = ?");
    $types = str_repeat('i', count($scheduleIds)) . 'i';
    $params = array_merge($scheduleIds, [$classId]);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete schedules');
    }

    $deletedCount = $stmt->affected_rows;
    
    // Send notification to enrolled students
    $message = "Schedule changes in {$classDetails['class_name']} - {$deletedCount} session(s) have been removed.";
    
    // Get enrolled students
    $stmt = $conn->prepare("SELECT DISTINCT u.uid, u.email, u.first_name, u.last_name 
                           FROM class_schedule cs 
                           JOIN users u ON cs.user_id = u.uid 
                           WHERE cs.class_id = ? AND cs.role = 'STUDENT'");
    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get mailer instance
    $mail = getMailerInstance();
    
    while ($student = $result->fetch_assoc()) {
        // Send system notification
        sendNotification(
            $student['uid'],
            'TECHKID',
            $message,
            "/dashboard/k/class/details?id={$classId}",
            $classId,
            'bi-calendar-x',
            'text-danger'
        );

        // Send email notification
        try {
            $mail->clearAddresses();
            $mail->addAddress($student['email']);
            $mail->Subject = "Class Schedule Update - {$classDetails['class_name']}";
            
            // Create HTML email content
            $emailContent = "
                <h2>Class Schedule Update</h2>
                <p>Dear {$student['first_name']},</p>
                <p>There has been a change in the schedule for your class <strong>{$classDetails['class_name']}</strong>.</p>
                <p>{$deletedCount} session(s) have been removed from the schedule.</p>
                <p>Please log in to your account to view the updated schedule.</p>
                <p>If you have any questions, please contact your instructor.</p>
                <br>
                <p>Best regards,<br>TechTutor Team</p>
            ";
            
            $mail->Body = $emailContent;
            $mail->send();
            
            // Log successful email
            log_error("Schedule deletion email sent to {$student['email']}", "mail");
        } catch (Exception $e) {
            // Log email error but continue with other students
            log_error("Failed to send schedule deletion email to {$student['email']}: " . $e->getMessage(), "mail");
        }
    }

    // Log the action
    log_error("Schedule deletion: {$deletedCount} schedules deleted from class {$classId}", "info");

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => "{$deletedCount} schedule(s) deleted successfully",
        'deletedCount' => $deletedCount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    log_error("Schedule deletion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete schedules']);
}
