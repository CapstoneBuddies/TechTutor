<?php
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

header('Content-Type: application/json');

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get single schedule details
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $schedule_id = intval($_GET['id']);
                
                // Get schedule details
                $stmt = $conn->prepare("
                    SELECT schedule_id, session_date, start_time, end_time, status, class_id 
                    FROM class_schedule 
                    WHERE schedule_id = ?
                ");
                $stmt->bind_param("i", $schedule_id);
                $stmt->execute();
                $schedule = $stmt->get_result()->fetch_assoc();
                
                if (!$schedule) {
                    throw new Exception("Schedule not found");
                }
                
                // Verify the user owns this schedule
                $stmt = $conn->prepare("SELECT tutor_id FROM class WHERE class_id = ?");
                $stmt->bind_param("i", $schedule['class_id']);
                $stmt->execute();
                $class = $stmt->get_result()->fetch_assoc();
                
                if (!$class || $class['tutor_id'] != $_SESSION['user']) {
                    throw new Exception("Unauthorized access to schedule");
                }
                
                echo json_encode([
                    'success' => true,
                    'date' => $schedule['session_date'],
                    'start_time' => date('h:i A', strtotime($schedule['start_time'])),
                    'end_time' => date('h:i A', strtotime($schedule['end_time'])),
                    'status' => $schedule['status']
                ]);
            } else {
                throw new Exception("Invalid schedule ID");
            }
            break;
            
        case 'PUT':
            // Update single schedule
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $schedule_id = intval($_GET['id']);
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate required fields
                if (empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
                    throw new Exception("Missing required fields");
                }
                
                // Convert 12-hour format to 24-hour
                $start_time = date('H:i:s', strtotime($data['start_time']));
                $end_time = date('H:i:s', strtotime($data['end_time']));
                
                // Verify ownership and update
                $stmt = $conn->prepare("
                    UPDATE class_schedule cs
                    JOIN class c ON cs.class_id = c.class_id
                    SET cs.session_date = ?,
                        cs.start_time = ?,
                        cs.end_time = ?
                    WHERE cs.schedule_id = ?
                    AND c.tutor_id = ?
                    AND cs.status NOT IN ('completed', 'cancelled')
                ");
                $stmt->bind_param("sssii", 
                    $data['date'],
                    $start_time,
                    $end_time,
                    $schedule_id,
                    $_SESSION['user']
                );
                
                if (!$stmt->execute() || $stmt->affected_rows === 0) {
                    throw new Exception("Failed to update schedule or unauthorized access");
                }
                
                echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
            } else {
                throw new Exception("Invalid schedule ID");
            }
            break;
            
        case 'DELETE':
            // Delete multiple schedules
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['ids']) || !is_array($data['ids'])) {
                throw new Exception("No schedules selected for deletion");
            }
            
            // Convert IDs to integers and create placeholders
            $ids = array_map('intval', $data['ids']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Verify ownership of all schedules
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM class_schedule cs
                    JOIN class c ON cs.class_id = c.class_id
                    WHERE cs.schedule_id IN ($placeholders)
                    AND c.tutor_id = ?
                    AND cs.status NOT IN ('completed', 'cancelled')
                ");
                
                $params = array_merge($ids, [$_SESSION['user']]);
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['count'] !== count($ids)) {
                    throw new Exception("Some schedules cannot be deleted or unauthorized access");
                }
                
                // Delete the schedules
                $stmt = $conn->prepare("
                    DELETE cs FROM class_schedule cs
                    JOIN class c ON cs.class_id = c.class_id
                    WHERE cs.schedule_id IN ($placeholders)
                    AND c.tutor_id = ?
                ");
                
                $stmt->bind_param($types, ...$params);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete schedules");
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Schedules deleted successfully']);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        default:
            throw new Exception("Method not allowed");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    log_error("Schedule API Error: " . $e->getMessage(), "database");
}
