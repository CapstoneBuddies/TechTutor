<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($name) || empty($email) || empty($message)) {
        throw new Exception('All fields are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $mail = getMailerInstance();
    $mail->addAddress('admin@techtutor.cfd'); // Send to platform admin
    $mail->Subject = "Contact Form Message from $name";
    $mail->Body = "
        <h3>New Contact Form Submission</h3>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Message:</strong><br>$message</p>
    ";

    if (!$mail->send()) {
        throw new Exception('Failed to send email');
    }

    echo json_encode(['status' => 'success', 'message' => 'Message sent successfully']);

} catch (Exception $e) {
    log_error("Contact Form Error: " . $e->getMessage(), 'mail');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message. Please try again later.']);
}
