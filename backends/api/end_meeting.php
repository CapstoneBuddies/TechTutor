<?php
require_once '../config.php';
require_once '../main.php';
require_once '../meeting_management.php';

header('Content-Type: application/json');

try {
    // Verify user is logged in and is a TechGuru
    if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHGURU') {
        throw new Exception('Unauthorized access');
    }

    // Validate required parameters
    $scheduleId = $_POST['schedule_id'] ?? null;
    if (!$scheduleId) {
        throw new Exception('Schedule ID is required');
    }

    $pdo = getConnection();
    
    // Get meeting details and verify ownership
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            c.tutor_id,
            c.class_name
        FROM meetings m
        JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
        JOIN class c ON cs.class_id = c.class_id
        WHERE m.schedule_id = ? AND m.is_running = true
    ");
    $stmt->execute([$scheduleId]);
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meeting) {
        throw new Exception('Meeting not found or already ended');
    }

    // Verify the tutor owns this class
    if ($meeting['tutor_id'] != $_SESSION['user']) {
        throw new Exception('Unauthorized to end this meeting');
    }

    // Initialize BigBlueButton meeting manager
    $bbb = new MeetingManagement();

    // End the meeting
    $result = $bbb->endMeeting($meeting['meeting_uid'], $meeting['moderator_pw']);

    if (!$result['success']) {
        throw new Exception('Failed to end meeting: ' . ($result['error'] ?? 'Unknown error'));
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Update meeting status
        $stmt = $pdo->prepare("
            UPDATE meetings 
            SET is_running = false, 
                end_time = CURRENT_TIMESTAMP 
            WHERE meeting_uid = ?
        ");
        $stmt->execute([$meeting['meeting_uid']]);

        // Update schedule status
        $stmt = $pdo->prepare("
            UPDATE class_schedule 
            SET status = 'completed' 
            WHERE schedule_id = ?
        ");
        $stmt->execute([$scheduleId]);

        // Create notifications for students
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                recipient_id, recipient_role, class_id,
                message, icon, icon_color
            ) SELECT 
                user_id, 'TECHKID', cs.class_id,
                CONCAT('Your class \"', ?, '\" has ended. Check your dashboard for updates.'),
                'bi-check-circle-fill',
                'text-success'
            FROM class_schedule cs
            WHERE cs.schedule_id = ? AND cs.role = 'STUDENT'
        ");
        $stmt->execute([$meeting['class_name'], $scheduleId]);

        // Log meeting end
        log_error(
            "Meeting {$meeting['meeting_uid']} ended by tutor {$_SESSION['user']}", 
            "meeting"
        );

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Meeting ended successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    log_error("Meeting end error: " . $e->getMessage(), "meeting");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
