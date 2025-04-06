<?php 
	include __DIR__ . '/../assets/vendor/tecnickcom/tcpdf/tcpdf.php';

    // Create a new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

$certificate = [
    'award_type' => 'Completion',
    'recipient_name' => 'John Doe',
    'award' => 'Introduction to Programming',
    'issue_date' => '2025-04-06',
    'donor_name' => 'Jane Smith',
    'subject_name' => 'Python Programming',
    'cert_uuid' => 'unique-uuid-123',
];


// Add custom font
    $fontPath = __DIR__ . '/../assets/vendor/tecnickcom/tcpdf/fonts/custom_fonts/BRUSHSCI.TTF';
    $fontname = 'brushscript';
    
    if (file_exists($fontPath)) {
        try {
            // Check if font is already added
            if (!file_exists(K_PATH_FONTS . $fontname . '.php')) {
                // Convert font
                $fontData = TCPDF_FONTS::addTTFfont(
                    $fontPath,            // Font file path
                    'TrueTypeUnicode',    // Font type
                    '',                   // Font encoding
                    32,                   // Font options
                    false                 // Don't create a symbolic link
                );
                if ($fontData) {
                    $fontname = $fontData;
                } else {
                    log_error("Failed to convert font");
                    $fontname = 'helvetica';
                }
            }
        } catch (Exception $e) {
            log_error("Failed to add custom font: " . $e->getMessage());
            $fontname = 'helvetica';
        }
    } else {
        log_error("Custom font file not found at: " . $fontPath);
        $fontname = 'helvetica';
    }

    // Set document information
    $pdf->SetCreator('TechTutor');
    $pdf->SetAuthor('TechTutor');
    $pdf->SetTitle('CERTIFICATE OF ' . (isset($certificate['award_type']) ? $certificate['award_type'] : 'Completion'));
    $pdf->SetSubject('Certificate');
    $pdf->SetKeywords('certificate, techtutor, online tutoring');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Add a page
    $pdf->AddPage('L', 'A4');

    // Get page dimensions
    $pageWidth = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();

    // Add background image
    $pdf->Image(__DIR__ .'/../docs/template/certificate_template.png', 0, 0, $pageWidth, $pageHeight, '', '', '', false, 300);

    // Certificate Title
    $pdf->SetFont('helvetica', 'B', 36);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pageHeight * 0.15);
    $pdf->Cell($pageWidth, 10, 'Certificate of', 0, 1, 'C');

    $pdf->SetFont('helvetica', 'B', 48);
    $pdf->SetTextColor(243, 156, 18); // Orange color (#f39c12)
    $pdf->SetXY(0, $pageHeight * 0.22);
    $pdf->Cell($pageWidth, 15, strtoupper(htmlspecialchars($certificate['award_type'])), 0, 1, 'C');

        // Recipient Name
    $pdf->SetFont($fontname, '', 72);
    $pdf->SetTextColor(51, 51, 51); // #333333
    $pdf->SetXY(0, $pageHeight * 0.35);
    $pdf->Cell($pageWidth, 20, htmlspecialchars($certificate['recipient_name']), 0, 1, 'C');

    // Certificate Description
    $pdf->SetFont('helvetica', '', 16);
    $pdf->SetXY($pageWidth * 0.2, $pageHeight * 0.5);
    $description = 'This is to certify that ' . htmlspecialchars($certificate['recipient_name']) . 
                  ' has successfully completed all ' . htmlspecialchars($certificate['award']) . 
                  ' lessons and fulfilled all requirements on TechTutor, an authorized one-on-one online tutoring platform.';
    $pdf->MultiCell($pageWidth * 0.6, 10, $description, 0, 'C');

    // Date
    $pdf->SetFont('helvetica', '', 14);
    $issue_date = strtotime($certificate['issue_date']);
    $formatted_date = date('jS', $issue_date) . ' day of ' . date('F, Y', $issue_date);
    $pdf->SetXY(0, $pageHeight * 0.63);
    $pdf->Cell($pageWidth, 10, 'Given this ' . $formatted_date, 0, 1, 'C');


    // Award Ribbon
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(255, 157, 15); // #ff9d0f
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($pageWidth * 0.95 - 50, $pageHeight * 0.865);
    $pdf->MultiCell(50, 8, strtoupper(htmlspecialchars($certificate['subject_name'])), 1, 'C', 1);

    // Verification Text
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(85, 85, 85); // #555555
    $verification_link = 'certificate/' . $certificate['cert_uuid'];
    $verification_text = "Verify at:\n" . $verification_link . "\nTechTutor has authenticated the individual's identity and officially confirmed their participation in the course.";
    $pdf->SetXY($pageWidth * 0.452, $pageHeight * 0.829);
    $pdf->MultiCell($pageWidth * 0.36, 5, $verification_text, 0, 'L');

    $pdf->Output();
?>