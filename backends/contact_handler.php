<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        throw new Exception('All fields are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // ✅ Send to Admin
    $mail = getMailerInstance();
    $mail->addAddress('admin@techtutor.cfd');
    $mail->Subject = "Contact Form Message from $name";
    $mail->Body = "
        <h3>New Contact Form Submission</h3>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Message:</strong><br>$message</p>
    ";

    if (!$mail->send()) {
        throw new Exception('Failed to send email to admin');
    }

    // ✅ Send a receipt to the user
    $mail->clearAddresses(); // Remove previous recipient
    $mail->addAddress($email);
    $mail->Subject = "We Received Your Message - TechTutor";
    $mail->Body = "
        <h3>Thank You for Contacting Us!</h3>
        <p>Dear $name,</p>
        <p>We have received your message and our team will respond as soon as possible.</p>
        <p><strong>Your Message:</strong><br>$message</p>
        <p>For urgent inquiries, you may contact us at <a href='mailto:support@techtutor.cfd'>support@techtutor.cfd</a>.</p>
        <p>Best regards,<br>The TechTutor Team</p>
    ";

    if (!$mail->send()) {
        log_error("Failed to send receipt email: " . $mail->ErrorInfo, 'mail');
    }

    echo json_encode(['status' => 'success', 'message' => 'Message sent successfully']);

} catch (Exception $e) {
    log_error("Contact Form Error: " . $e->getMessage(), 'mail');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message. Please try again later.']);
}
