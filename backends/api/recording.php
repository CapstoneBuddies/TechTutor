<?php
/**
 * API endpoint for recording management
 * Handles operations like archiving, unarchiving, and toggling visibility of recordings
 */
require_once '../main.php';
require_once BACKEND.'meeting_management.php';

// Default response
$response = ['success' => false];

// Check if user is logged in and is a TECHGURU
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHGURU') {
    $response['error'] = 'Unauthorized access';
    log_error("Unauthorized recording API access attempt", 'security');
    echo json_encode($response);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Get query action parameter - determines what operation to perform
$action = isset($_GET['action']) ? $_GET['action'] : (isset($input['action']) ? $input['action'] : '');

// Initialize meeting management class
$meeting = new MeetingManagement();

switch ($action) {
    case 'archive-recording':
        // Archive or unarchive a recording
        if (!isset($input['record_id'])) {
            $response['error'] = 'Recording ID is required';
            break;
        }

        $record_id = $input['record_id'];
        $archive = isset($input['archive']) ? (bool)$input['archive'] : true;

        $result = $meeting->archiveRecording($record_id, $archive);
        
        if ($result['success']) {
            $response = $result;
        } else {
            $response['error'] = $result['error'] ?? 'Failed to update recording archive status';
            log_error("Failed to archive recording: {$response['error']}", 'meeting');
        }
        break;

    case 'toggle-visibility':
        // Toggle student visibility of a recording
        if (!isset($input['record_id']) || !isset($input['class_id']) || !isset($input['visible'])) {
            $response['error'] = 'Recording ID, class ID, and visibility flag are required';
            break;
        }

        $record_id = $input['record_id'];
        $class_id = (int)$input['class_id'];
        $visible = (bool)$input['visible'];

        // Update visibility in database
        try {
            global $conn;
            
            // First check if class belongs to this tutor
            $stmt = $conn->prepare("SELECT class_id FROM class WHERE class_id = ? AND tutor_id = ?");
            $stmt->bind_param("ii", $class_id, $_SESSION['user']);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Unauthorized to modify this class's recordings");
            }
            
            // Check if recording visibility entry exists
            $stmt = $conn->prepare("SELECT id FROM recording_visibility WHERE recording_id = ?");
            $stmt->bind_param("s", $record_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing entry
                $stmt = $conn->prepare("UPDATE recording_visibility SET is_visible = ?, updated_at = NOW() WHERE recording_id = ?");
                $stmt->bind_param("is", $visible, $record_id);
            } else {
                // Create new entry
                $stmt = $conn->prepare("INSERT INTO recording_visibility (recording_id, class_id, is_visible, created_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siis", $record_id, $class_id, $visible, $_SESSION['user']);
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Recording visibility updated successfully';
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            log_error("Recording visibility update error: " . $e->getMessage(), 'meeting');
        }
        break;

    case 'get-all-recordings':
        // Get all recordings for a techguru across all classes
        try {
            global $conn;
            
            // Get all classes for this tutor
            $stmt = $conn->prepare("SELECT class_id FROM class WHERE tutor_id = ?");
            $stmt->bind_param("i", $_SESSION['user']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['success'] = true;
                $response['recordings'] = [];
                break;
            }
            
            $allRecordings = [];
            
            while ($class = $result->fetch_assoc()) {
                $classRecordings = $meeting->getClassRecordings($class['class_id']);
                if ($classRecordings['success'] && !empty($classRecordings['recordings'])) {
                    $allRecordings = array_merge($allRecordings, $classRecordings['recordings']);
                }
            }
            
            // Sort recordings by date (newest first)
            usort($allRecordings, function($a, $b) {
                return strtotime($b['session_date']) - strtotime($a['session_date']);
            });
            
            $response['success'] = true;
            $response['recordings'] = $allRecordings;
            
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            log_error("Error getting all recordings: " . $e->getMessage(), 'meeting');
        }
        break;

    default:
        $response['error'] = 'Invalid action specified';
        log_error("Invalid recording API action: {$action}", 'security');
        break;
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 