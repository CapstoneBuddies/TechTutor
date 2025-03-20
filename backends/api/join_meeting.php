<?php
require_once '../main.php';
require_once BACKEND.'meeting_management.php';

header('Content-Type: application/json');

try {
    // Verify user is logged in
    if (!isset($_SESSION['user'])) {
        throw new Exception('Unauthorized access');
    }

    // Validate required parameters
    $scheduleId = $_POST['schedule_id'] ?? null;
    if (!$scheduleId) {
        throw new Exception('Schedule ID is required');
    }

    global $conn;
    
    // Get meeting and schedule details
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            cs.user_id,
            cs.role as participant_role,
            CONCAT(u.first_name, ' ', u.last_name) as participant_name
        FROM meetings m
        JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
        JOIN users u ON cs.user_id = u.uid
        WHERE m.schedule_id = ? AND cs.user_id = ? AND m.is_running = true
    ");
    $stmt->execute([$scheduleId, $_SESSION['user']]);
    $meeting = $stmt->get_result()->fetch_assoc();

    if (!$meeting) {
        throw new Exception('Meeting not found or not running');
    }

    // Initialize BigBlueButton meeting manager
    $bbb = new MeetingManagement();

    // Check if meeting is actually running on BBB server
    if (!$bbb->isMeetingRunning($meeting['meeting_uid'])) {
        // Update meeting status in database
        $stmt = $conn->prepare("
            UPDATE meetings 
            SET is_running = false 
            WHERE meeting_uid = ?
        ");
        $stmt->execute([$meeting['meeting_uid']]);
        
        throw new Exception('Meeting has ended');
    }
    log_error("I RUN HERE!");
    // Get join URL based on role
    $password = $meeting['participant_role'] === 'TUTOR' ? 
                $meeting['moderator_pw'] : 
                $meeting['attendee_pw'];

    $joinUrl = $bbb->getJoinUrl(
        $meeting['meeting_uid'],
        $meeting['participant_name'],
        $password
    );

    // Log meeting join attempt
    log_error(
        "User {$_SESSION['user']} attempting to join meeting {$meeting['meeting_uid']} as {$meeting['participant_role']}", 
        "meeting");

    echo json_encode([
        'success' => true,
        'message' => 'Join URL generated successfully',
        'data' => [
            'join_url' => $joinUrl
        ]
    ]);

} catch (Exception $e) {
    log_error("Meeting join error: " . $e->getMessage(), "meeting");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
