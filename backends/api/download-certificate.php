<?php
require_once '../backends/main.php';
require_once BACKEND.'techkid_management.php';

// Verify user is logged in and is a TechKid
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
    http_response_code(403);
    exit('Unauthorized');
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Certificate ID is required');
    }

    $certId = intval($_GET['id']);
    $studentId = $_SESSION['user'];

    // Get certificate details
    $db = getConnection();
    $stmt = $db->prepare("SELECT pdf_url FROM student_certificates WHERE id = ? AND student_id = ?");
    $stmt->execute([$certId, $studentId]);
    $cert = $stmt->fetch();

    if (!$cert) {
        throw new Exception('Certificate not found');
    }

    $pdfPath = ROOT_PATH . '/' . $cert['pdf_url'];
    if (!file_exists($pdfPath)) {
        throw new Exception('Certificate file not found');
    }

    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="certificate.pdf"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($pdfPath);
    exit();

} catch (Exception $e) {
    log_error("Certificate download error: " . $e->getMessage());
    http_response_code(400);
    exit($e->getMessage());
}
