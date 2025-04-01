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

    $class_id = (int) $data['class_id'];
    $student_id = $_SESSION['user'];

    // Check if class exists and is active
    $stmt = $conn->prepare("SELECT c.class_id, c.class_name, c.class_size, c.is_free, c.price, 
                             u.first_name, u.last_name, u.email,
                             cs.schedule_id, cs.start_time, cs.end_time,
                             (SELECT COUNT(*) FROM enrollments WHERE class_id = c.class_id AND status = 'active') as enrolled_count 
                             FROM class c 
                             JOIN users u ON c.tutor_id = u.uid 
                             LEFT JOIN class_schedule cs ON c.class_id = cs.class_id
                             WHERE c.class_id = ? AND c.status = 'active'");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class = $result->fetch_assoc();
    $stmt->close();

    if (!$class) {
        throw new Exception('Class is not available for enrollment.');
    }

    // Check if class size limit is reached
    if ($class['class_size'] && $class['enrolled_count'] >= $class['class_size']) {
        throw new Exception('This class has reached its maximum capacity.');
    }

    // Check if student is already enrolled in this class
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE class_id = ? AND student_id = ? AND status != 'dropped'");
    $stmt->bind_param("ii", $class_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_enrolled = ($result->num_rows > 0);
    $stmt->close();
    
    if ($is_enrolled) {
        throw new Exception('You are already enrolled in this class.');
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check if student has record for enrollment for this class and check the status
        $check_stmt = $conn->prepare("SELECT enrollment_id, status FROM enrollments WHERE class_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $class_id, $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $enrollment_id = 0;
        
        if($check_result->num_rows > 0){
            $check_row = $check_result->fetch_assoc();
            if($check_row['status'] == 'dropped') {
                $update_stmt = $conn->prepare("UPDATE enrollments SET status = 'active' WHERE class_id = ? AND student_id = ?");
                $update_stmt->bind_param("ii", $class_id, $student_id);
                if (!$update_stmt->execute()) {
                    throw new Exception('Failed to update enrollment record.');
                }
                $enrollment_id = $check_row['enrollment_id'];
                $update_stmt->close();
            }
        }
        else {
            // Enroll the student in the class
            $enroll_stmt = $conn->prepare("INSERT INTO enrollments (class_id, student_id, status) VALUES (?, ?, 'active')");
            $enroll_stmt->bind_param("ii", $class_id, $student_id);
            if (!$enroll_stmt->execute()) {
                throw new Exception('Failed to create enrollment record.');
            }
            $enrollment_id = $enroll_stmt->insert_id;
            $enroll_stmt->close();
        }
        
        $check_stmt->close();

        // Create initial attendance records for all scheduled sessions
        $attend_stmt = $conn->prepare("INSERT INTO attendance (student_id, schedule_id, status, created_at) 
                              SELECT ?, schedule_id, 'pending', NOW() 
                              FROM class_schedule 
                              WHERE class_id = ?");
        $attend_stmt->bind_param("ii", $_SESSION['user'], $class_id);
        if (!$attend_stmt->execute()) {
            throw new Exception('Failed to create attendance records.');
        }
        $attend_stmt->close();

        // Send notification to student
        sendNotification(
            $student_id, 
            'TECHKID', 
            "You have been enrolled in '{$class['class_name']}'", 
            BASE . "dashboard/s/class", 
            $class_id, 
            'bi-mortarboard', 
            'text-success'
        );

        // Send notification to tutor with more details
        $student_name = $_SESSION['name'];
        sendNotification(
            null, 
            'TECHGURU', 
            "New student {$student_name} enrolled in '{$class['class_name']}'", 
            BASE . "dashboard/t/class", 
            $class_id, 
            'bi-person-plus', 
            'text-primary'
        );

        // Send email confirmation to student
        sendEnrollmentEmail($_SESSION['email'], $_SESSION['name'], $class['class_name'], $class['first_name'] . ' ' . $class['last_name']);

        // If class is paid, handle payment (placeholder for future implementation)
        if (!$class['is_free']) {
            // Log this enrollment for payment processing
            log_error("Paid class enrollment: Student ID {$student_id} enrolled in class ID {$class_id} for ₱{$class['price']}", "info");
        }

        // Commit transaction
        $conn->commit();

        log_error("Successful enrollment: Student ID {$student_id} enrolled in class ID {$class_id} with attendance records created", "info");
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully enrolled in the class.',
            'enrollment_id' => $enrollment_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        log_error("Enrollment Error: " . $e->getMessage(), 'database');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} catch (Exception $e) {
    log_error("Enrollment Validation Error: " . $e->getMessage(), 'database');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
