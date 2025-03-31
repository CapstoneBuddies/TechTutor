<?php
require_once '../main.php';
require_once BACKEND.'meeting_management.php';
require_once BACKEND.'class_management.php';

// Initialize meeting management
$meeting = new MeetingManagement();

// Get input data (support both GET and POST)
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';
$response = ['success' => false];
global $conn;

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $response['error'] = 'Authentication required';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

switch ($action) {
    case 'create-meeting':
        try {
            // Verify user is logged in and is a TechGuru
            if ($_SESSION['role'] !== 'TECHGURU') {
                throw new Exception('Unauthorized access');
            }

            // Validate required parameters
            $scheduleId = $_POST['schedule_id'] ?? $input['schedule_id'] ?? null;
            if (!$scheduleId) {
                throw new Exception('Schedule ID is required');
            }
            
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
                WHERE cs.schedule_id = ? AND cs.status = 'pending'
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
            $options = [
                'welcome' => "Welcome to {$schedule['class_name']}!",
                'duration' => ceil((strtotime($schedule['end_time']) - strtotime($schedule['start_time'])) / 60),
                'record' => 'true',
                'autoStartRecording' => 'true',
                'allowStartStopRecording' => 'true',
                'disableRecording' => 'false',
                'muteOnStart' => 'true',
                // Add custom meta data to indicate this is a recorded class
                'meta_bbb-recording-ready-url' => 'https://' . $_SERVER['SERVER_NAME'] . '/api/meeting?action=recording-ready',
                'meta_recording-name' => $schedule['class_name'] . ' - ' . date('Y-m-d', strtotime($schedule['session_date']))
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
                        student_id, 'TECHKID', c.class_id,
                        CONCAT('Your class \"', c.class_name, '\" is starting soon!'),
                        'bi-camera-video-fill',
                        'text-success'
                    FROM enrollments e
                    JOIN class c ON e.class_id = c.class_id
                    JOIN class_schedule cs ON cs.class_id = c.class_id
                    WHERE cs.schedule_id = ? AND e.status = 'active'
                ");
                $stmt->execute([$scheduleId]);

                // Optional: send class session link
                if (function_exists('sendClassSessionLink')) {
                    sendClassSessionLink($scheduleId);
                } else {
                    // Include the notifications management file and then call the function
                    require_once BACKEND . 'notifications_management.php';
                    sendClassSessionLink($scheduleId);
                }

                $conn->commit();

                $response = [
                    'success' => true,
                    'message' => 'Meeting created successfully',
                    'data' => [
                        'meeting_id' => $meetingId,
                        'moderator_url' => $meeting->getJoinUrl(
                            $meetingId, 
                            $schedule['tutor_name'], 
                            $result['moderatorPW'],
                            $schedule['tutor_id'],
                            'https://' . $_SERVER['SERVER_NAME'] . '/dashboard/t/class/details?id=' . $schedule['class_id'] . '&ended=' . $scheduleId
                        )
                    ]
                ];
            } catch (Exception $e) {
                log_error($e->getMessage(), "meeting");
                $conn->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            log_error("Meeting creation error: " . $e->getMessage(), "meeting");
            $response['error'] = $e->getMessage();
        }
        break;

    case 'end-meeting':
        try {
            // Verify user is logged in and is a TechGuru
            if ($_SESSION['role'] !== 'TECHGURU') {
                throw new Exception('Unauthorized access');
            }

            // Validate required parameters
            $scheduleId = $_POST['schedule_id'] ?? $input['schedule_id'] ?? null;
            if (!$scheduleId) {
                throw new Exception('Schedule ID is required');
            }
            
            // Get meeting details and verify ownership
            $stmt = $conn->prepare("
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
            $meeting_data = $stmt->get_result()->fetch_assoc();

            if (!$meeting_data) {
                throw new Exception('Meeting not found or already ended');
            }

            // Verify the tutor owns this class
            if ($meeting_data['tutor_id'] != $_SESSION['user']) {
                throw new Exception('Unauthorized to end this meeting');
            }

            // End the meeting
            $result = $meeting->endMeeting($meeting_data['meeting_uid'], $meeting_data['moderator_pw']);

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Update meeting status
                $stmt = $conn->prepare("
                    UPDATE meetings 
                    SET is_running = false, 
                        end_time = CURRENT_TIMESTAMP 
                    WHERE meeting_uid = ?
                ");
                $stmt->execute([$meeting_data['meeting_uid']]);

                // Update schedule status
                $stmt = $conn->prepare("
                    UPDATE class_schedule 
                    SET status = 'completed' 
                    WHERE schedule_id = ?
                ");
                $stmt->execute([$scheduleId]);

                // Get meeting analytics from BBB
                try {
                    $meetingInfo = $meeting->getMeetingInfo($meeting_data['meeting_uid'], $meeting_data['moderator_pw']);
                    
                    // Calculate duration and other metrics
                    $startTime = isset($meetingInfo['startTime']) ? 
                        date('Y-m-d H:i:s', strtotime($meetingInfo['startTime'])) : 
                        date('Y-m-d H:i:s', strtotime($meeting_data['created_at']));
                    
                    $endTime = date('Y-m-d H:i:s');
                    $participantCount = isset($meetingInfo['participantCount']) ? 
                        intval($meetingInfo['participantCount']) : 0;
                    
                    // Get the duration in minutes
                    $durationInMinutes = isset($meetingInfo['duration']) ? 
                        intval($meetingInfo['duration']) : 
                        round((time() - strtotime($startTime)) / 60);
                    
                    // Check if recordings are available
                    $hasRecordings = isset($meetingInfo['recording']) && $meetingInfo['recording'] === 'true' ? 1 : 0;
                    
                    // Save analytics data
                    $analytics = [
                        'participant_count' => $participantCount,
                        'duration' => $durationInMinutes,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'recording_available' => $hasRecordings
                    ];
                    
                    updateMeetingAnalytics($meeting_data['meeting_id'], $_SESSION['user'], $analytics);
                } catch (Exception $analyticsError) {
                    // Log the error but continue with the request
                    log_error("Failed to collect meeting analytics: " . $analyticsError->getMessage(), "meeting");
                }

                // Create notifications for students
                $stmt = $conn->prepare("
                    INSERT INTO notifications (
                        recipient_id, recipient_role, class_id,
                        message, icon, icon_color
                    ) SELECT 
                        student_id, 'TECHKID', c.class_id,
                        CONCAT('Your class \"', c.class_name, '\" has ended. Check your dashboard for updates.'),
                        'bi-check-circle-fill',
                        'text-success'
                    FROM enrollments e
                    JOIN class c ON e.class_id = c.class_id
                    JOIN class_schedule cs ON cs.class_id = c.class_id
                    WHERE cs.schedule_id = ? AND e.status = 'active'
                ");
                $stmt->execute([$scheduleId]);

                // Log meeting end
                log_error(
                    "Meeting {$meeting_data['meeting_uid']} ended by tutor {$_SESSION['user']}", 
                    "meeting"
                );

                $conn->commit();

                $response = [
                    'success' => true,
                    'message' => 'Meeting ended successfully'
                ];
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            log_error("Meeting end error: " . $e->getMessage(), "meeting");
            $response['error'] = $e->getMessage();
        }
        break;

    case 'join-meeting':
        try {
            // Validate required parameters
            $scheduleId = $_POST['schedule_id'] ?? $input['schedule_id'] ?? null;
            $role = $_POST['role'] ?? $input['role'] ?? $_SESSION['role'];
            if (!$scheduleId) {
                throw new Exception('Schedule ID is required');
            }
            
            // Get meeting and schedule details
            $stmt = $conn->prepare("
                SELECT 
                    m.*,
                    cs.class_id,
                    c.tutor_id
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                WHERE m.schedule_id = ?
            ");
            $stmt->execute([$scheduleId]);
            $meeting_data = $stmt->get_result()->fetch_assoc();

            if (!$meeting_data) {
                $stmt = $conn->prepare("
                    UPDATE class_schedule
                    SET status = 'pending'
                    WHERE schedule_id = ?
                ");
                $stmt->execute([$scheduleId]);
                throw new Exception('Meeting not found');
            }

            // Check if user is authorized to join 
            $isTeacher = ($_SESSION['role'] === 'TECHGURU' && $_SESSION['user'] == $meeting_data['tutor_id']);
            $isStudent = false;
            
            if ($_SESSION['role'] === 'TECHKID') {
                $stmt = $conn->prepare("
                    SELECT 1 FROM enrollments 
                    WHERE student_id = ? AND class_id = ? AND status = 'active'
                ");
                $stmt->execute([$_SESSION['user'], $meeting_data['class_id']]);
                $isStudent = ($stmt->get_result()->num_rows > 0);
            }
            
            $isAdmin = ($_SESSION['role'] === 'ADMIN');
            
            if (!$isTeacher && !$isStudent && !$isAdmin) {
                throw new Exception('You are not authorized to join this meeting');
            }

            // Check if meeting is actually running on BBB server
            if (!$meeting->isMeetingRunning($meeting_data['meeting_uid'])) {
                // Update meeting status in database
                $stmt = $conn->prepare("
                    UPDATE meetings 
                    SET is_running = false 
                    WHERE meeting_uid = ?
                ");
                $stmt->execute([$meeting_data['meeting_uid']]);
                
                if ($isTeacher) {
                    // Rerun the meeting if the teacher is joining
                    $options = [
                        'attendeePW' => $meeting_data['attendee_pw'],
                        'moderatorPW' => $meeting_data['moderator_pw'],
                        'duration' => 0, // No duration limit
                        'record' => 'true',
                        'autoStartRecording' => 'true',
                        'allowStartStopRecording' => 'true',
                        'disableRecording' => 'false',
                        'meta_recording-name' => $meeting_data['meeting_name'] . ' - ' . date('Y-m-d')
                    ];
                    
                    $result = $meeting->createMeeting(
                        $meeting_data['meeting_uid'],
                        $meeting_data['meeting_name'],
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
                    $stmt->execute([$meeting_data['meeting_uid']]);
                    
                    log_error("Meeting {$meeting_data['meeting_uid']} has been restarted", "meeting");
                } else {
                    throw new Exception('This meeting is not currently running');
                }
            }
            
            // Get join URL based on role
            $password = $isTeacher || $isAdmin ? $meeting_data['moderator_pw'] : $meeting_data['attendee_pw'];

            // Set the appropriate logout URL based on user role
            $logoutUrl = null;
            if ($_SESSION['role'] === 'TECHGURU') {
                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard/t/class/details?id=' . $meeting_data['class_id'] . '&ended=' . $scheduleId;
            } elseif ($_SESSION['role'] === 'TECHKID') {
                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard/s/class/details?id=' . $meeting_data['class_id'] . '&ended=' . $scheduleId;
            } elseif ($_SESSION['role'] === 'ADMIN') {
                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard/a/class/details?id=' . $meeting_data['class_id'] . '&ended=' . $scheduleId;
            }

            $joinUrl = $meeting->getJoinUrl(
                $meeting_data['meeting_uid'],
                $_SESSION['first_name'].' '.$_SESSION['last_name'],
                $password,
                $_SESSION['user'],
                $logoutUrl
            );

            // Log meeting join attempt
            log_error(
                "User {$_SESSION['user']} attempting to join meeting {$meeting_data['meeting_uid']} as {$_SESSION['role']}", 
                "meeting");
            
            $response = [
                'success' => true,
                'message' => 'Join URL generated successfully',
                'data' => [
                    'join_url' => $joinUrl
                ]
            ];
        } catch (Exception $e) {
            log_error("Meeting join error: " . $e->getMessage(), "meeting");
            $response['error'] = $e->getMessage();
        }
        break;

    case 'get-recordings':
        try {
            // Validate required parameters
            $scheduleId = $_GET['schedule_id'] ?? $input['schedule_id'] ?? null;
            if (!$scheduleId) {
                throw new Exception('Schedule ID is required');
            }
            
            // Get meeting details and verify access
            $stmt = $conn->prepare("
                SELECT 
                    m.*,
                    c.tutor_id,
                    c.class_name,
                    c.class_id
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                WHERE m.schedule_id = ?
            ");
            $stmt->execute([$scheduleId]);
            $meeting_data = $stmt->get_result()->fetch_assoc();

            if (!$meeting_data) {
                throw new Exception('Meeting not found');
            }

            // Check authorization
            $isTeacher = ($_SESSION['role'] === 'TECHGURU' && $_SESSION['user'] == $meeting_data['tutor_id']);
            $isStudent = false;
            
            if ($_SESSION['role'] === 'TECHKID') {
                $stmt = $conn->prepare("
                    SELECT 1 FROM enrollments 
                    WHERE student_id = ? AND class_id = ? AND status = 'active'
                ");
                $stmt->execute([$_SESSION['user'], $meeting_data['class_id']]);
                $isStudent = ($stmt->get_result()->num_rows > 0);
            }
            
            $isAdmin = ($_SESSION['role'] === 'ADMIN');
            
            if (!$isTeacher && !$isStudent && !$isAdmin) {
                throw new Exception('You are not authorized to access these recordings');
            }

            // Get recording information
            try {
                $recordings = $meeting->getRecordings($meeting_data['meeting_uid']);

                // Update recording URL in database if available
                if (!empty($recordings) && empty($meeting_data['recording_url'])) {
                    $stmt = $conn->prepare("
                        UPDATE meetings 
                        SET recording_url = ? 
                        WHERE meeting_uid = ?
                    ");
                    $stmt->execute([
                        $recordings[0]['url'] ?? null,
                        $meeting_data['meeting_uid']
                    ]);
                }

                // Format recording data for response
                $formattedRecordings = [];
                foreach ($recordings as $recording) {
                    $formattedRecordings[] = [
                        'title' => $meeting_data['class_name'] . ' - ' . date('F j, Y', strtotime($meeting_data['createtime'])),
                        'url' => $recording['url'] ?? '',
                        'duration' => $recording['duration'] ?? 0,
                        'size' => isset($recording['size']) ? formatFileSize($recording['size']) : '0 KB',
                        'created_at' => date('Y-m-d H:i:s', $recording['created_at'] ?? time())
                    ];
                }

                $response = [
                    'success' => true,
                    'data' => [
                        'meeting_name' => $meeting_data['class_name'],
                        'recordings' => $formattedRecordings
                    ]
                ];
            } catch (Exception $e) {
                // Log the error but don't expose it to the user
                log_error("Failed to get recordings: " . $e->getMessage(), "meeting");
                
                $response = [
                    'success' => true,
                    'data' => [
                        'meeting_name' => $meeting_data['class_name'],
                        'recordings' => []
                    ]
                ];
            }
        } catch (Exception $e) {
            log_error("Recording retrieval error: " . $e->getMessage(), "meeting");
            $response['error'] = $e->getMessage();
        }
        break;

    case 'toggle-recording':
        if (!isset($input['record_id']) || !isset($input['publish'])) {
            $response['error'] = 'Record ID and publish status are required';
            break;
        }

        try {
            log_error(print_r($input,true));
            $result = $meeting->updateRecordingPublishStatus(
                $input['record_id'],
                $input['publish']
            );
            if ($result['success']) {
                $response['success'] = true;
            } else {
                $response['error'] = $result['message'] ?? 'Failed to update recording status';
            }
        } catch (Exception $e) {
            log_error("Error updating recording status: " . $e->getMessage(), "meeting");
            $response['error'] = 'Failed to update recording status';
        }
        break;

    case 'delete-recording':
        // Check if user is admin or techguru
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'TECHGURU'])) {
            $response['error'] = 'Unauthorized access';
            break;
        }

        if (!isset($input['record_id'])) {
            $response['error'] = 'Record ID is required';
            break;
        }

        try {
            // If user is TECHGURU, verify they own the recording
            if ($_SESSION['role'] === 'TECHGURU') {
                // Get the recording details first
                $query = "SELECT c.tutor_id
                         FROM meetings m 
                         JOIN class_schedule cs ON m.schedule_id = cs.schedule_id 
                         JOIN class c ON cs.class_id = c.class_id
                         WHERE m.meeting_uid = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$input['record_id']]);
                $result = $stmt->get_result()->fetch_assoc();

                if (!$result || $result['tutor_id'] !== $_SESSION['user']) {
                    log_error("Unauthorized deletion attempt of recording {$input['record_id']} by user {$_SESSION['user']}", "security");
                    $response['error'] = 'You do not have permission to delete this recording';
                    break;
                }
            }

            // Proceed with deletion
            $result = $meeting->deleteRecording($input['record_id']);

            if ($result['success']) {
                log_error("Recording {$input['record_id']} deleted by {$_SESSION['role']} user {$_SESSION['user']}", "info");
                $response['success'] = true;
            } else {
                $response['error'] = $result['error'] ?? 'Failed to delete recording';
            }
        } catch (Exception $e) {
            log_error("Error deleting recording: " . $e->getMessage(), "meeting");
            $response['error'] = 'Failed to delete recording';
        }
        break;

    case 'archive-recording':
        // Archive or unarchive a recording
        try {
            // Verify user is logged in and is a TechGuru
            if ($_SESSION['role'] !== 'TECHGURU') {
                throw new Exception('Unauthorized access');
            }

            // Check for GET parameters first, then fall back to POST/JSON
            if (isset($_GET['recording_id'])) {
                $record_id = $_GET['recording_id'];
                $archive = isset($_GET['archive']) ? ($_GET['archive'] === 'true' || $_GET['archive'] === '1') : true;
            } else {
                // Validate required parameters from JSON input
                if (!isset($input['record_id'])) {
                    throw new Exception('Recording ID is required');
                }
                $record_id = $input['record_id'];
                $archive = isset($input['archive']) ? (bool)$input['archive'] : true;
            }

            $result = $meeting->archiveRecording($record_id, $archive);
            
            if ($result['success']) {
                $response = $result;
                log_error("Recording " . ($archive ? "archived" : "unarchived") . " successfully: {$record_id}", "meeting");
            } else {
                throw new Exception($result['error'] ?? 'Failed to update recording archive status');
            }
        } catch (Exception $e) {
            log_error("Failed to archive recording: " . $e->getMessage(), 'meeting');
            $response['error'] = $e->getMessage();
            $response['success'] = false;
        }
        break;

    case 'get-analytics':
        try {
            // Get required parameters
            $tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : null;
            $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
            
            // Ensure at least one filter is provided
            if (!$tutor_id && !$class_id) {
                throw new Exception('Either tutor_id or class_id must be provided');
            }
            
            // Verify authorization
            if ($_SESSION['role'] === 'TECHGURU' && $_SESSION['user'] != $tutor_id) {
                throw new Exception('Unauthorized access to analytics');
            }
            
            // Get analytics data using the class method
            $analytics = $meeting->getMeetingAnalytics($class_id, $tutor_id);
            
            $response = [
                'success' => true,
                'total_sessions' => $analytics['total_sessions'] ?? 0,
                'total_participants' => $analytics['total_participants'] ?? 0, 
                'total_hours' => $analytics['total_hours'] ?? 0,
                'total_recordings' => $analytics['total_recordings'] ?? 0,
                'recent_sessions' => $analytics['recent_sessions'] ?? [],
                'activity_data' => $analytics['activity_data'] ?? [],
                'engagement_data' => $analytics['engagement_data'] ?? [],
                'duration_data' => $analytics['duration_data'] ?? []
            ];
        } catch (Exception $e) {
            log_error("Error fetching meeting analytics: " . $e->getMessage(), "meeting");
            $response['error'] = $e->getMessage();
            $response['success'] = false;
        }
        break;

    case 'toggle-visibility':
        // Toggle student visibility of a recording
        try {
            // Verify user is logged in and is a TechGuru
            if ($_SESSION['role'] !== 'TECHGURU') {
                throw new Exception('Unauthorized access');
            }

            // Check GET parameters first, then POST/JSON
            if (isset($_GET['recording_id']) && isset($_GET['class_id'])) {
                $record_id = $_GET['recording_id'];
                $class_id = (int)$_GET['class_id'];
                $visible = isset($_GET['visible']) ? ($_GET['visible'] === 'true' || $_GET['visible'] === '1') : false;
            } else {
                // Validate required parameters from JSON input
                if (!isset($input['record_id']) || !isset($input['class_id']) || !isset($input['visible'])|| !isset($input['meeting_id'])) {
                    throw new Exception('Recording ID, class ID, visibility flag, and meeting ID are required');
                }
                $record_id = $input['record_id'];
                $class_id = (int)$input['class_id'];
                $visible = (bool)$input['visible'];
                $meeting_id = $input['meeting_id'];
            }

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
                $visibleValue = $visible ? 1 : 0;
                $stmt->bind_param("is", $visibleValue, $record_id);
            } else {
                // Select the connected schedule id
                $stmt_schedule = $conn->prepare("SELECT schedule_id FROM meetings WHERE meeting_uid = ?");
                $stmt_schedule->bind_param('s', $meeting_id);
                $stmt_schedule->execute();
                $results = $stmt->get_result()->fetch_assoc();

                // Create new entry
                $stmt = $conn->prepare("INSERT INTO recording_visibility (recording_id, class_id, is_visible, is_archived, created_by, schedule_id) VALUES (?, ?, ?, 0, ?, ?)");
                $visibleValue = $visible ? 1 : 0;
                $stmt->bind_param("siisi", $record_id, $class_id, $visibleValue, $_SESSION['user'], $results['schedule_id']);
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Recording visibility updated successfully';
                log_error("Recording visibility updated: {$record_id}, visible: " . ($visible ? 'true' : 'false'), "meeting");
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
        } catch (Exception $e) {
            log_error("Recording visibility update error: " . $e->getMessage(), 'meeting');
            $response['error'] = $e->getMessage();
            $response['success'] = false;
        }
        break;

    default:
        $response['error'] = 'Invalid action';
        break;
}

// Helper function for formatting file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
