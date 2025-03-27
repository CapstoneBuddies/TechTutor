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
    $role = $_POST['role'] ?? $_SESSION['role'];
    if (!$scheduleId) {
        throw new Exception('Schedule ID is required');
    }

    global $conn;
    
    // Get meeting and schedule details
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            cs.user_id,
            cs.class_id,
            u.role as participant_role
        FROM meetings m
        JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
        JOIN users u ON cs.user_id = u.uid
        WHERE m.schedule_id = ?
    ");
    $stmt->execute([$scheduleId]);
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
        
        // Rerun the meeting instead of throwing an error
        $options = [
            'attendeePW' => $meeting['attendee_pw'],
            'moderatorPW' => $meeting['moderator_pw'],
            'duration' => 0, // No duration limit
            'record' => false,
        ];
        
        $result = $bbb->createMeeting(
            $meeting['meeting_uid'],
            $meeting['meeting_name'],
            $options
        );
        
        if (!$result['success']) {
            throw new Exception('Failed to restart meeting: ' . ($result['error'] ?? 'Unknown error'));
        }
        
        // Update meeting status in database
        $stmt = $conn->prepare("
            UPDATE meetings 
            SET is_running = true 
            WHERE meeting_uid = ?
        ");
        $stmt->execute([$meeting['meeting_uid']]);
        
        log_error("Meeting {$meeting['meeting_uid']} has been restarted", "meeting");
    }
    
    // Get join URL based on role
    $password = $meeting['participant_role'] === 'TECHGURU' ? 
                $meeting['moderator_pw'] : 
                $meeting['attendee_pw'];

    // Get Return Link
    $link = null;
    if($role === 'TECHGURU') {
        $link = "https://".$_SERVER['SERVER_NAME']."/dashboard/t/class/details?id={$meeting['class_id']}&ended=1";   
    }
    elseif($role === 'TECHKID') {
        $link = "https://".$_SERVER['SERVER_NAME']."/dashboard/s/class/details?id={$meeting['class_id']}&ended=1";
    }

    $joinUrl = $bbb->getJoinUrl(
        $meeting['meeting_uid'],
        $_SESSION['first_name'].' '.$_SESSION['last_name'],
        $password,

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
