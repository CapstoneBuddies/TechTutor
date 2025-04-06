<?php
require_once '../main.php';
require_once BACKEND.'meeting_management.php';

// Initialize meeting management
$meeting = new MeetingManagement();
global $conn;

// Initial receipt logging
log_error("========= NEW WEBHOOK REQUEST =========", "webhooks");
log_error("Request Time: " . date('Y-m-d H:i:s'), "webhooks-debug");

// Log headers
$headers = getallheaders();
log_error("Received Headers: " . json_encode($headers), "webhooks-debug");

// Get and log raw data
$rawData = file_get_contents("php://input");
log_error("Raw Data: " . $rawData, "webhooks-debug");

// Security verification - Use only one verification method
$signature = isset($headers['X-Checksum']) ? $headers['X-Checksum'] : '';
$secret = BBB_SECRET;

// Log signature check
log_error("Received Signature: " . $signature, "webhooks-debug");
$calculatedSignature = hash('sha1', $rawData . $secret);
log_error("Calculated Signature: " . $calculatedSignature, "webhooks-debug");

// Verify signature
if ($signature !== $calculatedSignature) {
    log_error("Signature verification failed!", "error");
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

// Process the data
$data = json_decode($rawData, true);

// Extract event name with fallbacks for different formats
$eventName = $data['event']['name'] ?? $data['event'] ?? $data['name'] ?? 'unknown';
log_error("Processed Event Type: " . $eventName, "webhooks");

// Extract meeting ID with fallbacks for different formats
$meetingId = $data['meetingId'] ?? $data['meeting_id'] ?? $data['data']['meetingId'] ?? $data['data']['meeting_id'] ?? null;

if (!$meetingId) {
    log_error("Could not find meeting ID in webhook data", "error");
    http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing meeting ID']);
    exit;
}
try {
    // Verify database tables exist
    $checkTable = $conn->query("SHOW TABLES LIKE 'meeting_analytics'");
    if ($checkTable->num_rows == 0) {
        log_error("Required table 'meeting_analytics' does not exist", "error");
    }
    
    $checkTable = $conn->query("SHOW TABLES LIKE 'meetings'");
    if ($checkTable->num_rows == 0) {
        log_error("Required table 'meetings' does not exist", "error");
    }
    // Process the event based on standard BigBlueButton event names
    // Note: Changed from periods to hyphens in event names to match BBB standard
    switch ($eventName) {
        case 'meeting-created':
        case 'meeting-started':
            $startTime = date('Y-m-d H:i:s');
            
            $query = "INSERT INTO meeting_analytics 
                     (meeting_id, tutor_id, start_time) 
                     SELECT meeting_id, tutor_id, ? 
                     FROM meetings 
                     WHERE meeting_id = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $startTime, $meetingId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert meeting start data: " . $stmt->error);
            }
            $result->insert_id;
            $stmt->close();
            log_error($result);
            log_error("Meeting started: $meetingId", "webhooks");
            break;
        case 'meeting-ended':
            $endTime = date('Y-m-d H:i:s');
            
            // Get final meeting info
            $query = "SELECT moderator_pw FROM meetings WHERE meeting_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("s", $meetingId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                try {
                    $meetingInfo = $meeting->getMeetingInfo($meetingId, $row['moderator_pw']);
                
                $query = "UPDATE meeting_analytics 
                         SET end_time = ?,
                             participant_count = ?,
                             duration = ?
                         WHERE meeting_id = ?";
                    
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Database prepare error: " . $conn->error);
                    }
                    
                    $participantCount = $meetingInfo['participantCount'] ?? 0;
                    $duration = $meetingInfo['duration'] ?? 0;
                    $stmt->bind_param("siis", $endTime, $participantCount, $duration, $meetingId);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update meeting end data: " . $stmt->error);
                    }
                    $stmt->close();
                    
                    log_error("Meeting ended: $meetingId", "webhooks");
                } catch (Exception $e) {
                    // If meeting info not available, still record end time
                    $query = "UPDATE meeting_analytics SET end_time = ? WHERE meeting_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $endTime, $meetingId);
                    $stmt->execute();
                    $stmt->close();
                    
                    log_error("Meeting ended (info unavailable): " . $e->getMessage(), "webhooks-debug");
                }
            } else {
                log_error("Meeting ended but no moderator password found: $meetingId", "error");
            }
            break;
        case 'recording-ready':
            $query = "UPDATE meeting_analytics 
                     SET recording_available = TRUE 
                     WHERE meeting_id = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("s", $meetingId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update recording status: " . $stmt->error);
            }
            $stmt->close();
            
            log_error("Recording ready for meeting: $meetingId", "webhooks");
            break;
        case 'user-joined':
            // Track user joined event
            $userId = $data['userId'] ?? $data['user_id'] ?? $data['data']['userId'] ?? null;
            $userName = $data['userName'] ?? $data['user_name'] ?? $data['data']['userName'] ?? 'Unknown';
            
            log_error("User $userName ($userId) joined meeting: $meetingId", "webhooks");
            
            // Optional: Insert into a user_meeting_activity table if you want to track detailed user activity
            break;
        case 'user-left':
            // Track user left event
            $userId = $data['userId'] ?? $data['user_id'] ?? $data['data']['userId'] ?? null;
            $userName = $data['userName'] ?? $data['user_name'] ?? $data['data']['userName'] ?? 'Unknown';
            
            log_error("User $userName ($userId) left meeting: $meetingId", "webhooks");
            break;
        default:
            // Handle any other events by logging them
            log_error("Received unhandled webhook event: $eventName", "webhooks-debug");
            break;
    }

    // Send success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully',
        'event' => $eventName,
        'meetingId' => $meetingId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    log_error("========= WEBHOOK PROCESSING COMPLETE =========", "webhooks");

} catch (Exception $e) {
    log_error("Webhook error: " . $e->getMessage(), "error");
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'event' => $eventName,
        'meetingId' => $meetingId
    ]);
} 