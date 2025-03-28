<?php
require_once '../main.php';

$isTransactionActive = false;

try {
    // Check if user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        throw new Exception('Unauthorized access');
    }

    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    if (!isset($data['class_id']) || !isset($data['student_ids']) || !is_array($data['student_ids'])) {
        throw new Exception('Missing required fields');
    }

    $class_id = intval($data['class_id']);
    $student_ids = array_map('intval', $data['student_ids']);

    // Verify class belongs to the logged-in TechGuru
    $stmt = $conn->prepare("
        SELECT class_name, tutor_id, class_size, 
               (SELECT COUNT(*) FROM enrollments WHERE class_id = c.class_id AND status = 'active') as enrolled_count
        FROM class c 
        WHERE class_id = ? AND tutor_id = ?
    ");
    $stmt->bind_param('ii', $class_id, $_SESSION['user']);
    $stmt->execute();
    $class = $stmt->get_result()->fetch_assoc();

    if (!$class) {
        throw new Exception('Class not found or unauthorized');
    }

    // Check if class has reached its size limit
    if ($class['class_size'] && ($class['enrolled_count'] + count($student_ids)) > $class['class_size']) {
        throw new Exception('Class size limit would be exceeded');
    }

    // Begin transaction
    $conn->begin_transaction();
    $isTransactionActive = true;

    // Prepare invitation insert statement
    $stmt = $conn->prepare("
        INSERT INTO enrollments (class_id, student_id, status, enrollment_date) 
        VALUES (?, ?, 'pending', NOW())
    ");

    // Send invitations to each student
    foreach ($student_ids as $student_id) {
        // Check if student is already enrolled or invited
        $check = $conn->prepare("
            SELECT status 
            FROM enrollments 
            WHERE class_id = ? AND student_id = ?
        ");
        $check->bind_param('ii', $class_id, $student_id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            if ($existing['status'] === 'active') {
                continue; // Skip if already enrolled
            } elseif ($existing['status'] === 'pending') {
                continue; // Skip if already invited
            }
        }

        // Create enrollment record
        $stmt->bind_param('ii', $class_id, $student_id);
        $stmt->execute();

        // Send notification to student
        insertNotification(
            $student_id,
            'TECHKID',
            "You've been invited to join the class: " . htmlspecialchars($class['class_name']),
            BASE . "dashboard/s/class-details?id=" . $class_id,
            $class_id,
            'bi-envelope-paper',
            'text-primary'
        );

        // Get the needed info
        $stmt = $conn->prepare("
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) AS tutor_name,
                s.email AS student_email,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name
            FROM 
                users u
            JOIN 
                class c ON c.tutor_id = u.uid
            JOIN 
                enrollments e ON e.class_id = c.class_id
            JOIN 
                users s ON s.uid = e.student_id
            WHERE 
                c.class_id = ?
                AND s.uid = ?
        ");
        $stmt->bind_param("ii",$class_id,$student_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_assoc();

        // Send Email Notice
        $mail = getMailerInstance($students['tutor_name']);
        $mail->addAddress($students['student_email']);

        // Email Subject
        $mail->Subject = "You're Invited to Join the Class: " . htmlspecialchars($class['class_name']);
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f8f9fa; color: #333; padding: 20px; }
                .container { background-color: #ffffff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); padding: 20px; }
                h1 { color: #007bff; }
                p { font-size: 16px; line-height: 1.6; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                .btn:hover { background-color: #0056b3; }
                .footer { font-size: 12px; color: #6c757d; text-align: center; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>You've Been Invited!</h1>
                <p>Hello ". htmlspecialchars($students['student_name']) .",</p>
                <p>You have been invited to join the class <strong>" . htmlspecialchars($class['class_name']) . "</strong> by your tutor.</p>
                <p>Click the button below to view more details and accept the invitation:</p>
                <a href='https://" . $_SERVER['SERVER_NAME'].BASE . "dashboard/s/enrollments/class?id=" . $class_id . "' class='btn'>View Class Details</a>
                <p>If you have any questions or need assistance, feel free to reach out to us.</p>
                <p>Best regards,<br>The Techtutor Team</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Techtutor. All rights reserved.</p>
            </div>
        </body>
        </html>
        ";

        // Set email body
        $mail->Body = $body;

        // Send the email
        try {
            $mail->send();
        } catch (Exception $e) {
            log_error('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo,'mail');
        }
    }

    // Log successful invitations
    log_error("Invitations sent for class {$class_id} to students: " . implode(', ', $student_ids), "info");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Invitations sent successfully'
    ]);
} catch (Exception $e) {
    if ($isTransactionActive) {
        $conn->rollback();
    }
    log_error("Invitation error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 