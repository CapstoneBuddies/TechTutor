<?php
require_once '../config.php';
require_once '../main.php';
require_once '../meeting_management.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user'])) {
        throw new Exception('Unauthorized access');
    }

    // Validate required parameters
    $scheduleId = $_GET['schedule_id'] ?? null;
    if (!$scheduleId) {
        throw new Exception('Schedule ID is required');
    }

    $pdo = getConnection();
    
    // Get meeting details and verify access
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            cs.user_id,
            cs.role as participant_role,
            c.tutor_id,
            c.class_name
        FROM meetings m
        JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
        JOIN class c ON cs.class_id = c.class_id
        WHERE m.schedule_id = ? AND (
            cs.user_id = ? OR 
            c.tutor_id = ? OR 
            ? = 'ADMIN'
        )
    ");
    $stmt->execute([
        $scheduleId, 
        $_SESSION['user'], 
        $_SESSION['user'],
        $_SESSION['role']
    ]);
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meeting) {
        throw new Exception('Meeting not found or unauthorized access');
    }

    // Initialize BigBlueButton meeting manager
    $bbb = new MeetingManagement();

    // Get recording information
    try {
        $recordings = $bbb->getRecordings($meeting['meeting_uid']);

        // Update recording URL in database if available
        if (!empty($recordings) && empty($meeting['recording_url'])) {
            $stmt = $pdo->prepare("
                UPDATE meetings 
                SET recording_url = ? 
                WHERE meeting_uid = ?
            ");
            $stmt->execute([
                $recordings[0]['url'] ?? null,
                $meeting['meeting_uid']
            ]);
        }

        // Format recording data for response
        $formattedRecordings = [];
        foreach ($recordings as $recording) {
            $formattedRecordings[] = [
                'title' => $meeting['class_name'] . ' - ' . date('F j, Y', strtotime($meeting['createtime'])),
                'url' => $recording['url'],
                'duration' => $recording['duration'] ?? 0,
                'size' => formatFileSize($recording['size'] ?? 0),
                'created_at' => date('Y-m-d H:i:s', $recording['created_at'] ?? time())
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'meeting_name' => $meeting['class_name'],
                'recordings' => $formattedRecordings
            ]
        ]);
    } catch (Exception $e) {
        // Log the error but don't expose it to the user
        log_error("Failed to get recordings: " . $e->getMessage(), "meeting");
        
        echo json_encode([
            'success' => true,
            'data' => [
                'meeting_name' => $meeting['class_name'],
                'recordings' => []
            ]
        ]);
    }

} catch (Exception $e) {
    log_error("Recording retrieval error: " . $e->getMessage(), "meeting");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
