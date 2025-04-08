<?php
require_once '../main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'meeting_management.php';
require_once BACKEND.'rating_management.php';
require_once BACKEND.'student_management.php';

// Ensure user is logged in and is an ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize managers
$meetingManager = new MeetingManagement();
$ratingManager = new RatingManagement();

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    
    // Response array
    $response = ['success' => false];
    
    switch ($action) {
        // Class details actions
        case 'get_class_details':
            $classDetails = getClassDetails($class_id);
            if ($classDetails) {
                $response = ['success' => true, 'data' => $classDetails];
            } else {
                $response = ['success' => false, 'message' => 'Class not found'];
            }
            break;
            
        case 'update_class':
            $data = [
                'class_id' => $class_id,
                'class_name' => $_POST['class_name'] ?? '',
                'class_desc' => $_POST['class_desc'] ?? '',
                'subject_id' => $_POST['subject_id'] ?? '',
                'tutor_id' => $_POST['tutor_id'] ?? '',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'class_size' => $_POST['class_size'] ?? null,
                'price' => $_POST['price'] ?? 0,
                'is_free' => $_POST['is_free'] ?? 0,
                'status' => $_POST['status'] ?? 'pending',
                'updated_by' => $_SESSION['user']
            ];
            
            $result = updateClass($data);
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Class updated successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
            break;
            
        // Student enrollment actions
        case 'enroll_student':
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $result = enrollStudent($student_id, $class_id);
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Student enrolled successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
            break;
            
        case 'remove_student':
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $reason = "Remove from class by an ADMIN";
            $result = dropClass($student_id, $class_id, $reason); 
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Student removed from class'];
            } else {
                $response = ['success' => false, 'message' => $result['message'] ?? 'Failed to remove student from class'];
            }
            break;
            
        // Meeting recordings actions
        case 'archive_recording':
            $recording_id = $_POST['recording_id'] ?? '';
            $archive = filter_var($_POST['archive'] ?? true, FILTER_VALIDATE_BOOLEAN);
            
            $result = $meetingManager->archiveRecording($recording_id, $archive);
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Recording ' . ($archive ? 'archived' : 'unarchived') . ' successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
            break;
            
        case 'delete_recording':
            $recording_id = $_POST['recording_id'] ?? '';
            $result = $meetingManager->deleteRecording($recording_id);
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Recording deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
            break;
            
        // Feedback management actions
        case 'archive_feedback':
            $feedback_id = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
            $result = $ratingManager->archiveFeedback($feedback_id);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Feedback archived successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to archive feedback'];
            }
            break;
            
        case 'unarchive_feedback':
            $feedback_id = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
            $result = $ratingManager->unarchiveFeedback($feedback_id);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Feedback unarchived successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to unarchive feedback'];
            }
            break;
            
        case 'delete_feedback':
            $feedback_id = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
            $result = $ratingManager->deleteFeedback($feedback_id);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Feedback deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete feedback'];
            }
            break;
            
        // Session management actions
        case 'update_session_status':
            $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
            $status = $_POST['status'] ?? '';
            
            if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
                $response = ['success' => false, 'message' => 'Invalid status'];
                break;
            }
            
            $result = updateScheduleStatus($schedule_id, $status);
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Session status updated'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
            break;
            
        case 'reschedule_session':
            $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
            $session_date = $_POST['session_date'] ?? '';
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            
            $result = rescheduleClassSession($schedule_id, $session_date, $start_time, $end_time);
            
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Session rescheduled successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Function to update schedule status
function updateScheduleStatus($schedule_id, $status) {
    global $conn;
    
    try {
        // First check if schedule exists
        $check = $conn->prepare("SELECT schedule_id FROM class_schedule WHERE schedule_id = ?");
        $check->bind_param("i", $schedule_id);
        $check->execute();
        
        if ($check->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Session not found'];
        }
        
        // Update the status
        $stmt = $conn->prepare("UPDATE class_schedule SET status = ? WHERE schedule_id = ?");
        $stmt->bind_param("si", $status, $schedule_id);
        
        if ($stmt->execute()) {
            // If status is completed, we also update the completion timestamp
            if ($status === 'completed') {
                $update = $conn->prepare("UPDATE class_schedule SET completed_at = NOW() WHERE schedule_id = ?");
                $update->bind_param("i", $schedule_id);
                $update->execute();
            }
            
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Database message'];
        }
    } catch (Exception $e) {
        message_log("message updating schedule status: " . $e->getMessage());
        return ['success' => false, 'message' => 'System message'];
    }
}

// Function to reschedule a class session
function rescheduleClassSession($schedule_id, $session_date, $start_time, $end_time) {
    global $conn;
    
    try {
        // Validate date and time inputs
        if (!strtotime($session_date) || !strtotime($start_time) || !strtotime($end_time)) {
            return ['success' => false, 'message' => 'Invalid date or time format'];
        }
        
        // Format times for MySQL
        $formatted_date = date('Y-m-d', strtotime($session_date));
        $formatted_start = date('H:i:s', strtotime($start_time));
        $formatted_end = date('H:i:s', strtotime($end_time));
        
        // First check if schedule exists
        $check = $conn->prepare("SELECT cs.schedule_id, c.class_id, c.tutor_id 
                                FROM class_schedule cs
                                JOIN class c ON cs.class_id = c.class_id
                                WHERE cs.schedule_id = ?");
        $check->bind_param("i", $schedule_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Session not found'];
        }
        
        $sessionData = $result->fetch_assoc();
        $class_id = $sessionData['class_id'];
        $tutor_id = $sessionData['tutor_id'];
        
        // Update the schedule
        $stmt = $conn->prepare("UPDATE class_schedule 
                              SET session_date = ?, start_time = ?, end_time = ?
                              WHERE schedule_id = ?");
        $stmt->bind_param("sssi", $formatted_date, $formatted_start, $formatted_end, $schedule_id);
        
        if ($stmt->execute()) {
            // Notify students about the reschedule
            $students = getClassStudents($class_id);
            foreach ($students as $student) {
                insertNotification(
                    $student['uid'],
                    'TECHKID',
                    'One of your class sessions has been rescheduled. Please check your calendar.',
                    BASE . 'dashboard/s/class/details?id=' . $class_id,
                    $class_id,
                    'bi-calendar-event',
                    'text-primary'
                );
            }
            
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Database message'];
        }
    } catch (Exception $e) {
        message_log("message rescheduling session: " . $e->getMessage());
        return ['success' => false, 'message' => 'System message'];
    }
}