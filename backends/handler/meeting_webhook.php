<?php
require_once '../main.php';
require_once BACKEND.'meeting_management.php';

// Initialize meeting management
$meeting = new MeetingManagement();

// Security verification
$headers = getallheaders();
$signature = isset($headers['X-Checksum']) ? $headers['X-Checksum'] : '';
$secret = "YourSecretTokenHere"; // Same as in BBB config

// Get the raw POST data
$rawData = file_get_contents("php://input");

// Verify the webhook signature
$calculatedSignature = hash('sha1', $rawData . $secret);
if ($signature !== $calculatedSignature) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

// Process the data
$data = json_decode($rawData, true);

// Verify the request is from BigBlueButton
function verifyRequest() {
    $checksum = $_SERVER['HTTP_X_BBB_SIGNATURE'] ?? '';
    $payload = file_get_contents('php://input');
    $calculatedChecksum = hash('sha256', $payload . BBB_SECRET);
    
    return hash_equals($checksum, $calculatedChecksum);
}

try {
    // Verify the request
    if (!verifyRequest()) {
        log_error("Invalid webhook request signature", "security");
        http_response_code(403);
        exit();
    }

    // Process the event
    switch ($data['event'] ?? '') {
        case 'meeting.started':
            $meetingId = $data['meetingId'];
            $startTime = date('Y-m-d H:i:s');
            
            $query = "INSERT INTO meeting_analytics 
                     (meeting_id, tutor_id, start_time) 
                     SELECT meeting_id, tutor_id, ? 
                     FROM meetings 
                     WHERE meeting_id = ?";
            DB::run($query, [$startTime, $meetingId]);
            
            log_error("Meeting started: $meetingId", "info");
            break;

        case 'meeting.ended':
            $meetingId = $data['meetingId'];
            $endTime = date('Y-m-d H:i:s');
            
            // Get final meeting info
            $query = "SELECT moderator_pw FROM meetings WHERE meeting_id = ?";
            $result = DB::run($query, [$meetingId])->fetch();
            
            if ($result) {
                $meetingInfo = $meeting->getMeetingInfo($meetingId, $result['moderator_pw']);
                
                $query = "UPDATE meeting_analytics 
                         SET end_time = ?,
                             participant_count = ?,
                             duration = ?
                         WHERE meeting_id = ?";
                DB::run($query, [
                    $endTime,
                    $meetingInfo['participantCount'] ?? 0,
                    $meetingInfo['duration'] ?? 0,
                    $meetingId
                ]);
                
                log_error("Meeting ended: $meetingId", "info");
            }
            break;

        case 'recording.ready':
            $meetingId = $data['meetingId'];
            
            $query = "UPDATE meeting_analytics 
                     SET recording_available = TRUE 
                     WHERE meeting_id = ?";
            DB::run($query, [$meetingId]);
            
            log_error("Recording ready for meeting: $meetingId", "info");
            break;

        case 'user.joined':
            // Could track real-time participation here
            log_error("User joined meeting: " . ($data['meetingId'] ?? 'unknown'), "info");
            break;

        case 'user.left':
            // Could track real-time participation here
            log_error("User left meeting: " . ($data['meetingId'] ?? 'unknown'), "info");
            break;

        default:
            log_error("Unhandled webhook event: " . ($data['event'] ?? 'unknown'), "info");
            break;
    }

    // Send success response
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    log_error("Webhook error: " . $e->getMessage(), "error");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 