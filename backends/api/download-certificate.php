<?php
require_once '../main.php';
require_once BACKEND . 'certificate_management.php';

// Set headers for JSON response in case of error
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$cert_uuid = isset($data['cert_uuid']) ? $data['cert_uuid'] : null;

if (!$cert_uuid) {
    http_response_code(400);
    echo json_encode(['error' => 'Certificate UUID is required']);
    exit();
}

try {
    // Get certificate details
    $certificate = getCertificateByUUID($cert_uuid);
    
    if (!$certificate) {
        http_response_code(404);
        echo json_encode(['error' => 'Certificate not found']);
        exit();
    }
    
    // Create new PDF document
    require_once ROOT_PATH . '/assets/vendor/autoload.php';
    
    // Add error logging to check if TCPDF class exists
    if (!class_exists('TCPDF')) {
        log_error("TCPDF class not found. Check autoloader.");
        http_response_code(500);
        echo json_encode(['error' => 'PDF generator not available']);
        exit();
    }
    
    class MYPDF extends TCPDF {
        public function Header() {
            // Empty header
        }
        
        public function Footer() {
            // Empty footer
        }
    }
    
    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(SITE_NAME);
    $pdf->SetTitle($certificate['award'] . ' - Certificate');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage('L', 'A4');
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Certificate border
    $pdf->SetLineStyle(array('width' => 2, 'color' => array(244, 208, 63)));
    $pdf->Rect(15, 15, $pdf->getPageWidth() - 30, $pdf->getPageHeight() - 30);
    
    // Inner border
    $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(44, 62, 80)));
    $pdf->Rect(20, 20, $pdf->getPageWidth() - 40, $pdf->getPageHeight() - 40);
    
    // Logo
    $logo_path = ROOT_PATH . '/assets/images/stand_alone_logo.png';
    if (file_exists($logo_path)) {
        $pdf->Image($logo_path, 120, 30, 50);
    }
    
    // Title
    $pdf->SetY(80);
    $pdf->SetFont('helvetica', 'B', 36);
    $pdf->Cell(0, 0, 'Certificate of Achievement', 0, 1, 'C');
    
    // Main text
    $pdf->SetY(110);
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 0, 'This certifies that', 0, 1, 'C');
    
    // Recipient name
    $pdf->SetY(130);
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->Cell(0, 0, $certificate['recipient_name'], 0, 1, 'C');
    
    // Award text
    $pdf->SetY(150);
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 0, 'has successfully completed', 0, 1, 'C');
    
    $pdf->SetY(165);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 0, $certificate['award'], 0, 1, 'C');
    
    if (!empty($certificate['class_name'])) {
        $pdf->SetY(180);
        $pdf->SetFont('helvetica', '', 16);
        $pdf->Cell(0, 0, 'in', 0, 1, 'C');
        
        $pdf->SetY(195);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 0, $certificate['class_name'], 0, 1, 'C');
    }
    
    // Date and signatures
    $pdf->SetY(-70);
    $pdf->SetFont('helvetica', '', 12);
    
    // Left signature (Instructor)
    $pdf->SetX(60);
    $pdf->Cell(80, 0, str_repeat('_', 30), 0, 0, 'C');
    
    // Right signature (TechTutor)
    $pdf->SetX($pdf->GetPageWidth() - 140);
    $pdf->Cell(80, 0, str_repeat('_', 30), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Names under signatures
    $pdf->SetX(60);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(80, 0, $certificate['donor_name'], 0, 0, 'C');
    
    $pdf->SetX($pdf->GetPageWidth() - 140);
    $pdf->Cell(80, 0, SITE_NAME, 0, 1, 'C');
    
    $pdf->Ln(3);
    
    // Titles under names
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetX(60);
    $pdf->Cell(80, 0, 'Instructor', 0, 0, 'C');
    
    $pdf->SetX($pdf->GetPageWidth() - 140);
    $pdf->Cell(80, 0, 'Official Certificate', 0, 1, 'C');
    
    // Certificate ID and date at the bottom
    $pdf->SetY(-30);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 0, 'Certificate ID: ' . $certificate['cert_uuid'], 0, 1, 'L');
    $pdf->Cell(0, 0, 'Issue Date: ' . date('F d, Y', strtotime($certificate['issue_date'])), 0, 1, 'L');
    
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    try {
        // Create a temporary file for the PDF
        $temp_file = tempnam(sys_get_temp_dir(), 'cert_');
        
        // Save PDF to the temporary file
        $pdf->Output($temp_file, 'F');
        
        if (!file_exists($temp_file)) {
            throw new Exception("Failed to create temporary PDF file");
        }
        
        // Set response headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_filename($certificate['award']) . '_Certificate.pdf"');
        header('Cache-Control: max-age=0');
        header('Content-Length: ' . filesize($temp_file));
        
        // Read and output the file
        readfile($temp_file);
        
        // Delete the temporary file
        unlink($temp_file);
        exit();
    } catch (Exception $e) {
        log_error("Error outputting PDF: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate certificate PDF: ' . $e->getMessage()]);
        exit();
    }
    
} catch (Exception $e) {
    log_error("Error generating certificate PDF: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate certificate']);
    exit();
}

/**
 * Sanitize filename by removing invalid characters
 */
function sanitize_filename($filename) {
    // Remove any character that isn't a letter, number, dot, hyphen or underscore
    $filename = preg_replace("/[^a-zA-Z0-9.-_]/", "_", $filename);
    // Remove any multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}
