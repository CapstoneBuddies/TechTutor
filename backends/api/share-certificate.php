<?php
require_once '../backends/config.php';
require_once '../backends/db.php';
require_once '../backends/techkid_management.php';

// Verify user is logged in and is a TechKid
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

try {
    // Validate input
    if (!isset($_POST['id']) || !isset($_POST['email'])) {
        throw new Exception('Certificate ID and email are required');
    }

    $certId = intval($_POST['id']);
    $studentId = $_SESSION['user'];
    $recipientEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$recipientEmail) {
        throw new Exception('Invalid email format');
    }

    // Get certificate details
    $db = getConnection();
    $stmt = $db->prepare("SELECT c.*, co.name as course_name, u.first_name, u.last_name 
                         FROM student_certificates c
                         JOIN courses co ON c.course_id = co.id
                         JOIN users u ON c.student_id = u.uid
                         WHERE c.id = ? AND c.student_id = ?");
    $stmt->execute([$certId, $studentId]);
    $cert = $stmt->fetch();

    if (!$cert) {
        throw new Exception('Certificate not found');
    }

    // Prepare email
    $mail = getMailerInstance();
    $mail->addAddress($recipientEmail);
    $mail->Subject = $cert['first_name'] . ' ' . $cert['last_name'] . ' shared a TechTutor certificate with you';
    
    // Email body
    $body = "
    <h2>TechTutor Certificate Share</h2>
    <p>{$cert['first_name']} {$cert['last_name']} has shared their certificate for completing {$cert['course_name']}.</p>
    <p>Achievement Date: " . date('F j, Y', strtotime($cert['completion_date'])) . "</p>
    <p>You can view and download the certificate using the link below:</p>
    <p><a href='" . BASE . "certificates/{$cert['pdf_url']}'>View Certificate</a></p>
    <hr>
    <p><small>This email was sent from TechTutor. Please do not reply to this email.</small></p>";
    
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    // Send email
    if (!$mail->send()) {
        throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
    }

    echo json_encode(['success' => true, 'message' => 'Certificate shared successfully']);

} catch (Exception $e) {
    log_error("Certificate share error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
