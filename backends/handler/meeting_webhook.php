<?php
// Error reporting (comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force content type for response
header('Content-Type: application/json');

// Try to include required files
try {
    require_once '../main.php';
    require_once BACKEND.'meeting_management.php';
} catch (Exception $e) {
    // Fallback if includes fail
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load dependencies',
        'message' => $e->getMessage()
    ]);
    exit;
}

// Create a manual log function in case regular logging fails
function manual_log($message) {
    $logFile = __DIR__ . '/../../logs/webhook_manual.log';
    $entry = date('Y-m-d H:i:s') . " - " . $message . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

// Start of webhook handling
manual_log("New webhook request received from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Initialize meeting management
try {
    $meeting = new MeetingManagement();
    global $conn;
} catch (Exception $e) {
    manual_log("Failed to initialize: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Initialization failed']);
    exit;
}

// Initial receipt logging
try {
    log_error("========= NEW WEBHOOK REQUEST =========", "webhooks");
    log_error("Request Time: " . date('Y-m-d H:i:s'), "webhooks");
} catch (Exception $e) {
    manual_log("Failed to log: " . $e->getMessage());
}

// Log headers
try {
    $headers = getallheaders();
    log_error("Received Headers: " . json_encode($headers), "webhooks-debug");
} catch (Exception $e) {
    manual_log("Failed to get headers: " . $e->getMessage());
    // Fallback for getallheaders if it doesn't exist
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
}

// Get and log raw data
try {
    $rawData = file_get_contents("php://input");
    log_error("Raw Data: " . $rawData, "webhooks-debug");
} catch (Exception $e) {
    manual_log("Failed to get raw data: " . $e->getMessage());
    $rawData = '';
}

// Check if this is a test/browser request or an actual webhook
$isTestRequest = empty($rawData) && 
                (!isset($headers['X-Checksum']) || empty($headers['X-Checksum'])) && 
                (isset($headers['User-Agent']) && strpos($headers['User-Agent'], 'Mozilla') !== false);

if ($isTestRequest) {
    // This appears to be a browser request or test
    echo json_encode([
        'status' => 'test_mode',
        'message' => 'This is the BigBlueButton webhook endpoint. Direct browser access is informational only.',
        'info' => 'When accessed by BigBlueButton, this endpoint receives meeting events with proper authentication.',
        'webhook_url' => $_ENV['BBB_WEBHOOK_URL'] ?? 'https://techtutor.cfd/backends/handler/meeting_webhook.php',
        'time' => date('Y-m-d H:i:s'),
        'headers' => $headers,
        'server' => $_SERVER,
        'data' => $rawData
    ]);
    exit;
}

// Security verification for real webhook requests
$signature = isset($headers['X-Checksum']) ? $headers['X-Checksum'] : '';
if (empty($signature) && isset($headers['x-checksum'])) {
    $signature = $headers['x-checksum']; // Try lowercase version
}

// Get webhook secret from env with fallback
$secret = $_ENV['BBB_WEBHOOK_SECRET'] ?? null;
if (empty($secret)) {
    // Read from the .env file directly
    $envFile = file_get_contents(__DIR__ . '/../../backends/.env');
    if (preg_match('/BBB_WEBHOOK_SECRET="([^"]+)"/', $envFile, $matches)) {
        $secret = $matches[1];
    } else {
        $secret = "4dd7af870cc54df5efd67353e8bfddaf1d510997296089a3a806eb342fc56fa6";
    }
}

// Log signature check
try {
    log_error("Received Signature: " . $signature, "webhooks-debug");
    $calculatedSignature = hash('sha1', $rawData . $secret);
    log_error("Calculated Signature: " . $calculatedSignature, "webhooks-debug");
} catch (Exception $e) {
    manual_log("Failed to calculate signature: " . $e->getMessage());
}

// Verify signature
if ($signature !== $calculatedSignature) {
    try {
        log_error("Signature verification failed!", "error");
    } catch (Exception $e) {
        manual_log("Signature verification failed! Failed to log error: " . $e->getMessage());
    }
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

// Process the data
try {
    $data = json_decode($rawData, true);
} catch (Exception $e) {
    manual_log("Failed to decode JSON: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Log the complete data structure to understand format
try {
    log_error("Full webhook data structure: " . print_r($data, true), "webhooks-debug");
} catch (Exception $e) {
    manual_log("Failed to log data structure: " . $e->getMessage());
}

// Extract event name with fallbacks for different formats
$eventName = $data['event']['name'] ?? $data['event'] ?? $data['name'] ?? 'unknown';
try {
    log_error("Processed Event Type: " . $eventName, "webhooks");
} catch (Exception $e) {
    manual_log("Event type: " . $eventName . " - Failed to log: " . $e->getMessage());
}

// Extract meeting ID with fallbacks for different formats
$meetingId = $data['meetingId'] ?? $data['meeting_id'] ?? $data['data']['meetingId'] ?? $data['data']['meeting_id'] ?? null;

if (!$meetingId) {
    try {
        log_error("Could not find meeting ID in webhook data", "error");
    } catch (Exception $e) {
        manual_log("Could not find meeting ID in webhook data - Failed to log: " . $e->getMessage());
    }
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
            $stmt->close();
            
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
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'event' => $eventName,
        'meetingId' => $meetingId
    ]);
} 