<?php
require_once '../config.php';
require_once '../main.php';
require_once '../meeting_management.php';

header('Content-Type: application/json');

try {
    // Verify user is logged in and is a TechGuru or ADMIN
    if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['TECHGURU', 'ADMIN'])) {
        throw new Exception('Unauthorized access');
    }

    // Validate required parameters
    $meetingId = $_POST['meeting_id'] ?? null;
    $recordingId = $_POST['recording_id'] ?? null;

    if (!$meetingId || !$recordingId) {
        throw new Exception('Meeting ID and Recording ID are required');
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
        WHERE m.meeting_uid = ?
    ");
    $stmt->execute([$meetingId]);
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meeting) {
        throw new Exception('Meeting not found');
    }

    // Verify user has permission to delete recording
    if ($_SESSION['role'] !== 'ADMIN' && $meeting['tutor_id'] != $_SESSION['user']) {
        throw new Exception('Unauthorized to delete this recording');
    }

    // Initialize BigBlueButton meeting manager
    $bbb = new MeetingManagement();

    // Delete recording
    $result = $bbb->deleteRecording($recordingId);

    if (!$result['success']) {
        throw new Exception('Failed to delete recording: ' . ($result['error'] ?? 'Unknown error'));
    }

    // Update recording URL in database if this was the only recording
    $recordings = $bbb->getRecordings($meetingId);
    if (empty($recordings)) {
        $stmt = $pdo->prepare("
            UPDATE meetings 
            SET recording_url = NULL 
            WHERE meeting_uid = ?
        ");
        $stmt->execute([$meetingId]);
    }

    // Log recording deletion
    log_error(
        "Recording {$recordingId} from meeting {$meetingId} deleted by user {$_SESSION['user']}", 
        "meeting"
    );

    echo json_encode([
        'success' => true,
        'message' => 'Recording deleted successfully'
    ]);

} catch (Exception $e) {
    log_error("Recording deletion error: " . $e->getMessage(), "meeting");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
