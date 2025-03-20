<?php
require_once '../../backends/main.php';
require_once BACKEND.'meeting_management.php';

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

    global $conn;
    
    // Get schedule details
    $stmt = $conn->prepare("
        SELECT 
            cs.*, 
            c.class_name,
            c.tutor_id,
            CONCAT(u.first_name, ' ', u.last_name) as tutor_name
        FROM class_schedule cs
        JOIN class c ON cs.class_id = c.class_id
        JOIN users u ON c.tutor_id = u.uid
        WHERE cs.schedule_id = ? AND cs.status = 'confirmed'
    ");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->get_result()->fetch_assoc();

    if (!$schedule) {
        throw new Exception('Invalid schedule or not confirmed');
    }

    // Verify the tutor owns this class
    if ($schedule['tutor_id'] != $_SESSION['user']) {
        throw new Exception('Unauthorized to create meeting for this class');
    }

    // Generate unique meeting ID
    $meetingId = 'class_' . $schedule['class_id'] . '_' . $scheduleId . '_' . time();

    // Create meeting
    $meeting = new MeetingManagement();
    $options = [
        'welcome' => "Welcome to {$schedule['class_name']}!",
        'duration' => ceil((strtotime($schedule['end_time']) - strtotime($schedule['start_time'])) / 60),
        'record' => 'true',
        'autoStartRecording' => 'false',
        'allowStartStopRecording' => 'true'
    ];

    $result = $meeting->createMeeting($meetingId, $schedule['class_name'], $options);

    if (!$result['success']) {
        throw new Exception('Failed to create meeting: ' . ($result['error'] ?? 'Unknown error'));
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Save meeting details
        $stmt = $conn->prepare("
            INSERT INTO meetings (
                meeting_uid, schedule_id, meeting_name, 
                attendee_pw, moderator_pw, createtime, 
                is_running, end_time
            ) VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP(), true, ?)
        ");
        $stmt->execute([
            $meetingId,
            $scheduleId,
            $schedule['class_name'],
            $result['attendeePW'],
            $result['moderatorPW'],
            date('Y-m-d H:i:s', strtotime($schedule['end_time']))
        ]);
        // Update schedule status
        $stmt = $conn->prepare("
            UPDATE class_schedule 
            SET status = 'confirmed' 
            WHERE schedule_id = ?
        ");
        $stmt->execute([$scheduleId]);

        // Create notification for students
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                recipient_id, recipient_role, class_id,
                message, icon, icon_color
            ) SELECT 
                user_id, 'TECHKID', cs.class_id,
                CONCAT('Your class \"', c.class_name, '\" is starting soon!'),
                'bi-camera-video-fill',
                'text-success'
            FROM class_schedule cs
            JOIN class c ON cs.class_id = c.class_id
            WHERE cs.schedule_id = ? AND cs.role = 'STUDENT'
        ");
        $stmt->execute([$scheduleId]);

        sendClassSessionLink($scheduleId);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Meeting created successfully',
            'data' => [
                'meeting_id' => $meetingId,
                'moderator_url' => $meeting->getJoinUrl($meetingId, $schedule['tutor_name'], $result['moderatorPW'])
            ]
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    log_error("Meeting creation error: " . $e->getMessage(), "meeting");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
