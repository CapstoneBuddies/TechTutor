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
                             u.first_name, u.last_name, u.email, u.uid,
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

    // If class is not free, check if user has enough tokens
    if (!$class['is_free']) {
        // Get user's token balance
        $stmt = $conn->prepare("SELECT token_balance FROM users WHERE uid = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        $token_balance = $user['token_balance'] ?? 0;
        $class_price = $class['price'];
        
        // If user doesn't have enough tokens, redirect to payment
        if ($token_balance < $class_price) {
            echo json_encode([
                'success' => false,
                'insufficient_tokens' => true,
                'message' => 'You don\'t have enough tokens for this class. Please add tokens to your account.',
                'redirect_to_payment' => true,
                'class_id' => $class_id,
                'price' => $class_price,
                'token_balance' => $token_balance
            ]);
            exit;
        }
        
        // User has enough tokens, deduct from their balance
        $conn->begin_transaction();
        try {
            // Deduct tokens from user's balance
            $stmt = $conn->prepare("UPDATE users SET token_balance = token_balance - ? WHERE uid = ?");
            $stmt->bind_param("di", $class_price, $student_id);
            $stmt->execute();
            
            // Create a transaction record
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, currency, status, payment_method_type, description) 
                                   VALUES (?, ?, 'TOKENS', 'succeeded', 'token', ?)");
            $description = "Class enrollment for " . $class['class_name'] . " (Class #{$class_id})";
            $stmt->bind_param("ids", $student_id, $class_price, $description);
            $stmt->execute();
            
            log_error("Tokens used: Student ID {$student_id} used {$class_price} tokens for class ID {$class_id}", "info");

            // Add the percentage to the tutor
            $add_token_stmt = $conn->prepare("UPDATE users JOIN class ON class.tutor_id = users.uid SET users.token_balance = (? * 0.8) WHERE class.class_id = ?");
            $add_token_stmt->bind_param('ii',$class_price,$data['class_id']);
            $add_token_stmt->execute();

            // Create a transaction record for updating the tutor's token
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, currency, status, payment_method_type, description) 
                                   VALUES (?, (?*0.8), 'TOKENS', 'succeeded', 'token', ?)");
            $description = "Enrollment Fee for class: " . $class['class_name'] . " by {$_SESSION['name']}";
            $stmt->bind_param("ids", $class['uid'], $class_price, $description);
            $stmt->execute();

        } catch (Exception $e) {
            $conn->rollback();
            log_error("Token deduction error: " . $e->getMessage(), 'database');
            throw new Exception('Error processing token payment. Please try again.');
        }
    }

    // Begin enrollment transaction
    if (!isset($conn->in_transaction) || !$conn->in_transaction) {
        $conn->begin_transaction();
    }

    try {
        // Check if student has record for enrollment for this class and check the status
        $check_stmt = $conn->prepare("SELECT enrollment_id, status FROM enrollments WHERE class_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $class_id, $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $enrollment_id = 0;

        if($check_result->num_rows > 0){
            $check_row = $check_result->fetch_assoc();
            if($check_row['status'] === 'dropped') {
                $update_stmt = $conn->prepare("UPDATE enrollments SET status = 'active' WHERE class_id = ? AND student_id = ?");
                $update_stmt->bind_param("ii", $class_id, $student_id);
                if (!$update_stmt->execute()) {
                    throw new Exception('Failed to update enrollment record.');
                }
                $enrollment_id = $check_row['enrollment_id'];
                log_error($enrollment_id);
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
            $_SESSION['user'], 
            'TECHGURU', 
            "New student {$student_name} enrolled in '{$class['class_name']}'", 
            BASE . "dashboard/t/class", 
            $class_id, 
            'bi-person-plus', 
            'text-primary'
        );

        // Send email confirmation to student
        sendEnrollmentEmail($_SESSION['email'], $_SESSION['name'], $class['class_name'], $class['first_name'] . ' ' . $class['last_name']);

        // Commit transaction
        $conn->commit();


        // Check if student has already taken the diagnostic exam for this class
        $stmt = $conn->prepare("SELECT diagnostics_taken FROM enrollments WHERE class_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $class_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $diagnostics_taken = false;
        if ($row = $result->fetch_assoc()) {
            $diagnostics_taken = (bool)$row['diagnostics_taken'];
        }
        $stmt->close();
    
        if (!$diagnostics_taken) {
            echo json_encode([
                'success' => false,
                'require_exam' => true,
                'redirect' => BASE . "dashboard/s/enrollments/class/exams?id=" . $class_id,
                'message' => "You must take the diagnostic exam before enrolling. (Don't worry this wont affect anything)"
            ]);
            exit;
        }
        

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
