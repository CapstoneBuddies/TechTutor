<?php
require_once '../main.php';
require_once BACKEND.'meeting_management.php';

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
    case 'toggle_recording':
        if (!isset($input['record_id']) || !isset($input['publish'])) {
            $response['error'] = 'Record ID and publish status are required';
            break;
        }

        try {
            $result = $meeting->updateRecordingPublishStatus(
                $input['record_id'],
                $input['publish']
            );

            if ($result['returncode'] === 'SUCCESS') {
                $response['success'] = true;
            } else {
                $response['error'] = $result['message'] ?? 'Failed to update recording status';
            }
        } catch (Exception $e) {
            log_error("Error updating recording status: " . $e->getMessage(), "meeting");
            $response['error'] = 'Failed to update recording status';
        }
        break;

    case 'delete_recording':
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
                $query = "SELECT cs.user_id 
                         FROM meetings m 
                         JOIN class_schedule cs ON m.schedule_id = cs.schedule_id 
                         WHERE m.meeting_uid = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$input['record_id']]);
                $result = $stmt->get_result()->fetch_assoc();

                if (!$result || $result['user_id'] !== $_SESSION['user']) {
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

    case 'archive_recording':
        if (!isset($input['record_id']) || !isset($input['archive'])) {
            $response['error'] = 'Record ID and archive status are required';
            break;
        }

        try {
            $result = $meeting->archiveRecording(
                $input['record_id'],
                $input['archive']
            );

            if ($result['success']) {
                $response['success'] = true;
            } else {
                $response['error'] = $result['error'] ?? 'Failed to update archive status';
            }
        } catch (Exception $e) {
            log_error("Error updating recording archive status: " . $e->getMessage(), "meeting");
            $response['error'] = 'Failed to update archive status';
        }
        break;

    case 'get_analytics':
        $tutor_id = $_GET['tutor_id'] ?? '';
        
        if (empty($tutor_id)) {
            $response['error'] = 'Tutor ID is required';
            break;
        }

        try {
            // Get all meetings for the tutor from the database
            $query = "SELECT m.*, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name, u.email AS tutor_email FROM meetings m JOIN class_schedule cs ON cs.schedule_id = m.schedule_id JOIN users u ON u.uid = cs.user_id WHERE m.tutor_id = ? ORDER BY m.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$tutor_id]);
            $meetings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $total_sessions = 0;
            $total_hours = 0;
            $total_participants = 0;
            $total_recordings = 0;
            $recent_sessions = [];
            $session_activity = [];
            $engagement_data = [];
            $duration_distribution = [
                '0-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0
            ];

            foreach ($meetings as $meeting_data) {
                // Get detailed meeting info from BigBlueButton
                $meeting_info = $meeting->getMeetingInfo($meeting_data['meeting_id'], $meeting_data['moderator_pw']);
                
                if ($meeting_info['returncode'] === 'SUCCESS') {
                    $total_sessions++;
                    
                    // Calculate duration in hours
                    $duration = isset($meeting_info['duration']) ? $meeting_info['duration'] / 60 : 0;
                    $total_hours += $duration;
                    
                    // Count participants
                    $participants = isset($meeting_info['participantCount']) ? (int)$meeting_info['participantCount'] : 0;
                    $total_participants += $participants;
                    
                    // Get recordings
                    $recordings = $meeting->getRecordings($meeting_data['meeting_id']);
                    $total_recordings += count($recordings);

                    // Add to recent sessions
                    if (count($recent_sessions) < 5) {
                        $recent_sessions[] = [
                            'name' => $meeting_data['name'],
                            'start_time' => $meeting_data['created_at'],
                            'status' => isset($meeting_info['running']) && $meeting_info['running'] === 'true' ? 'active' : 'completed',
                            'participants' => $participants
                        ];
                    }

                    // Add to session activity data
                    $date = date('Y-m-d', strtotime($meeting_data['created_at']));
                    if (!isset($session_activity[$date])) {
                        $session_activity[$date] = 0;
                    }
                    $session_activity[$date]++;

                    // Add to engagement data
                    if (!isset($engagement_data[$participants])) {
                        $engagement_data[$participants] = 0;
                    }
                    $engagement_data[$participants]++;

                    // Add to duration distribution
                    if ($duration <= 30) {
                        $duration_distribution['0-30']++;
                    } elseif ($duration <= 60) {
                        $duration_distribution['31-60']++;
                    } elseif ($duration <= 90) {
                        $duration_distribution['61-90']++;
                    } else {
                        $duration_distribution['90+']++;
                    }
                }
            }

            // Format session activity for chart
            $formatted_activity = [];
            $dates = array_keys($session_activity);
            sort($dates);
            foreach ($dates as $date) {
                $formatted_activity['labels'][] = $date;
                $formatted_activity['data'][] = $session_activity[$date];
            }

            // Format engagement data for chart
            $formatted_engagement = [
                'labels' => array_keys($engagement_data),
                'data' => array_values($engagement_data)
            ];

            $response = [
                'success' => true,
                'total_sessions' => $total_sessions,
                'total_hours' => round($total_hours, 1),
                'total_participants' => $total_participants,
                'total_recordings' => $total_recordings,
                'recent_sessions' => $recent_sessions,
                'session_activity' => $formatted_activity,
                'engagement_data' => $formatted_engagement,
                'duration_distribution' => [
                    'labels' => array_keys($duration_distribution),
                    'data' => array_values($duration_distribution)
                ]
            ];

        } catch (Exception $e) {
            log_error("Error fetching meeting analytics: " . $e->getMessage(), "meeting");
            $response['error'] = 'Failed to fetch analytics data';
        }
        break;

    default:
        $response['error'] = 'Invalid action';
        break;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
