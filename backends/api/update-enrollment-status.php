<?php
require_once '../../backends/main.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($data['class_id']) || !is_numeric($data['class_id'])) {
        throw new Exception('Invalid class ID.');
    }

    if (empty($data['action']) || !in_array($data['action'], ['accept', 'decline'])) {
        throw new Exception('Invalid action. Must be "accept" or "decline".');
    }

    $class_id = (int) $data['class_id'];
    $student_id = $_SESSION['user'];
    $action = $data['action'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check if the enrollment exists and is pending
        $stmt = $conn->prepare("SELECT e.enrollment_id, e.status, c.class_name, u.first_name, u.last_name, u.email 
                              FROM enrollments e
                              JOIN class c ON e.class_id = c.class_id
                              JOIN users u ON c.tutor_id = u.uid
                              WHERE e.class_id = ? AND e.student_id = ? AND e.status = 'pending'");
        $stmt->bind_param("ii", $class_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('No pending invitation found for this class.');
        }

        $enrollment = $result->fetch_assoc();
        $enrollment_id = $enrollment['enrollment_id'];
        $class_name = $enrollment['class_name'];
        $tutor_name = $enrollment['first_name'] . ' ' . $enrollment['last_name'];
        $tutor_email = $enrollment['email'];

        if ($action === 'accept') {
            // Update enrollment status to active
            $stmt = $conn->prepare("UPDATE enrollments SET status = 'active' WHERE enrollment_id = ?");
            $stmt->bind_param("i", $enrollment_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update enrollment status.');
            }

            // Create attendance records for all scheduled sessions
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, schedule_id, status, created_at) 
                                  SELECT ?, schedule_id, 'pending', NOW() 
                                  FROM class_schedule 
                                  WHERE class_id = ? 
                                  AND NOT EXISTS (
                                      SELECT 1 FROM attendance 
                                      WHERE student_id = ? AND schedule_id = class_schedule.schedule_id
                                  )");
            $stmt->bind_param("iii", $student_id, $class_id, $student_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to create attendance records.');
            }

            // Send notification to student
            sendNotification(
                $student_id, 
                'TECHKID', 
                "You are now enrolled in '{$class_name}'", 
                BASE . "dashboard/s/class/details?id=" . $class_id, 
                $class_id, 
                'bi-mortarboard', 
                'text-success'
            );

            // Send notification to tutor
            $student_name = $_SESSION['name'];
            sendNotification(
                null, 
                'TECHGURU', 
                "{$student_name} has accepted your invitation to '{$class_name}'", 
                BASE . "dashboard/t/class/details?id=" . $class_id, 
                $class_id, 
                'bi-person-check', 
                'text-success'
            );

            // Commit transaction
            $conn->commit();
            
            log_error("Enrollment accepted: Student ID {$student_id} accepted invitation for class ID {$class_id}", "info");
            echo json_encode([
                'success' => true, 
                'message' => 'You have successfully enrolled in the class.',
                'enrollment_id' => $enrollment_id,
                'class_id' => $class_id
            ]);
        } else {
            // Decline invitation - Delete the enrollment
            $stmt = $conn->prepare("DELETE FROM enrollments WHERE enrollment_id = ?");
            $stmt->bind_param("i", $enrollment_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to decline invitation.');
            }

            // Send notification to tutor
            $student_name = $_SESSION['name'];
            sendNotification(
                null, 
                'TECHGURU', 
                "{$student_name} has declined your invitation to '{$class_name}'", 
                BASE . "dashboard/t/class/details?id=" . $class_id, 
                $class_id, 
                'bi-person-x', 
                'text-danger'
            );

            // Commit transaction
            $conn->commit();
            
            log_error("Enrollment declined: Student ID {$student_id} declined invitation for class ID {$class_id}", "info");
            echo json_encode([
                'success' => true, 
                'message' => 'You have declined the class invitation.',
                'class_id' => $class_id
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Enrollment Status Update Error: " . $e->getMessage(), 'database');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} catch (Exception $e) {
    log_error("Enrollment Status Validation Error: " . $e->getMessage(), 'database');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 