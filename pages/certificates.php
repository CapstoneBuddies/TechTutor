<?php 
require_once '../backends/main.php';
require_once BACKEND . 'certificate_management.php';
require_once ROOT_PATH . '/assets/vendor/autoload.php'; // Include composer autoload for TCPDF

// Get certificate UUID from URL parameter
$cert_uuid = isset($_GET['uuid']) ? $_GET['uuid'] : '';

// If no certificate UUID provided, check if it's in the URL path
if (empty($cert_uuid)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $url_path = parse_url($request_uri, PHP_URL_PATH);
    $path_segments = explode('/', trim($url_path, '/'));
    
    // If the URL is in format /certificate/UUID or /certificates/UUID
    if (in_array('certificate', $path_segments) || in_array('certificates', $path_segments)) {
        $cert_index = array_search('certificate', $path_segments);
        if ($cert_index === false) {
            $cert_index = array_search('certificates', $path_segments);
        }
        
        if (isset($path_segments[$cert_index + 1])) {
            $cert_uuid = $path_segments[$cert_index + 1];
        }
    }
}

// Get certificate details 
$certificate = null;
$error = null;

if (!empty($cert_uuid)) {
    $certificate = getCertificateByUUID($cert_uuid);
    
    if (!$certificate) {
        $error = "Certificate not found. The certificate may have been deleted or the URL is incorrect.";
    }
} else {
    $error = "No certificate specified. Please provide a valid certificate ID.";
}

// Handle download request
if (isset($_GET['download']) && $certificate) {
    // Create new PDF instance
    class MYPDF extends TCPDF {
        public function Header() {
            // Empty header
        }
        
        public function Footer() {
            // Empty footer
        }
    }
    
    // Create a new PDF document
    $pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);


    // Set document information
    $pdf->SetCreator('TechTutor');
    $pdf->SetAuthor('TechTutor');
    $pdf->SetTitle('Certificate of ' . (isset($certificate['award_type']) ? $certificate['award_type'] : 'Completion'));
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
    $pdf->Image(ROOT_PATH .'/docs/template/certificate_template.png', 0, 0, $pageWidth, $pageHeight, '', '', '', false, 300);

    // Certificate Title
    $pdf->SetFont('helvetica', 'B', 36);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pageHeight * 0.15);
    $pdf->Cell($pageWidth, 10, 'CERTIFICATE OF', 0, 1, 'C');

    $pdf->SetFont('helvetica', 'B', 48);
    $pdf->SetTextColor(243, 156, 18); // Orange color (#f39c12)
    $pdf->SetXY(0, $pageHeight * 0.22);
    $pdf->Cell($pageWidth, 15, strtoupper(htmlspecialchars((isset($certificate['award_type']) ? $certificate['award_type'] : 'Completion'))), 0, 1, 'C');

        // Recipient Name
    $pdf->SetFont('brushsci', '', 72);
    $pdf->SetTextColor(51, 51, 51); // #333333
    $pdf->SetXY(0, $pageHeight * 0.35);
    $pdf->Cell($pageWidth, 20, htmlspecialchars($certificate['recipient_name']), 0, 1, 'C');

    // Certificate Description
    $pdf->SetFont('helvetica', '', 16);

    if($certificate['type'] == 'class') {
        $description = 'This is to certify that <b>' . htmlspecialchars($certificate['recipient_name']) . 
                  '</b> has successfully completed all <b>' . htmlspecialchars($certificate['class_name']) . 
                  '</b> lessons and fulfilled all requirements on TechTutor, an authorized one-on-one online tutoring platform.';
    }
    else {
        $description = 'We hereby certify that <b>'. htmlspecialchars($certificate['recipient_name']) . ' has successfully completed the <b>' . htmlspecialchars($certificate['award']) . '</b> game on TechTutor. This achievement demonstrates both your engagement and your ability to learn through interactive play. ';
    }
    $pdf->writeHTMLCell($pageWidth * 0.6, 10, $pageWidth * 0.2, $pageHeight * 0.5, $description, 0, 1, false, true, 'C', true);

    // Date
    $pdf->SetFont('helvetica', '', 14);
    $issue_date = strtotime($certificate['issue_date']);
    $formatted_date = date('jS', $issue_date) . ' day of ' . date('F, Y', $issue_date);
    $pdf->SetXY(0, $pageHeight * 0.63);
    $pdf->Cell($pageWidth, 10, 'Given this ' . $formatted_date, 0, 1, 'C');

    // Signatures
    $pdf->SetFont('quattrocento', 'B', 20);
    $pdf->SetXY($pageWidth * 0.258, $pageHeight * 0.76);
    $pdf->Cell(0, 10, htmlspecialchars($certificate['donor_name']), 0, 0, 'L');

    // Award Ribbon
    $pdf->SetFont('helvetica', 'B', 11);
    
    // Set colors
    $pdf->setFillColor(225, 114, 2);
    $pdf->SetDrawColor(225, 114, 2);
    $pdf->SetTextColor(255, 255, 255);
    
    // Save current position
    $ribbonWidth = 50;
    $ribbonHeight = 8;
    $x = $pageWidth * 0.95 - $ribbonWidth;
    $y = $pageHeight * 0.865;
    
    // Draw filled rectangle first
    $pdf->Rect($x, $y, $ribbonWidth, $ribbonHeight, 'F');
    
    // Get text dimensions
    $text = strtoupper(htmlspecialchars($certificate['subject_name']));
    $fontSize = 11;
    $textWidth = $pdf->GetStringWidth($text);
    
    // Calculate text position to center it
    $textX = $x + ($ribbonWidth - $textWidth) / 2;
    $textY = $y + ($ribbonHeight - $fontSize * 0.3) / 2; // 0.3 is a factor to adjust vertical centering
    
    // Position for text and write it
    $pdf->SetXY($textX, $textY);
    $pdf->Write(0, $text);

    // Verification Text
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(85, 85, 85); // #555555
    $verification_link = "https://".$_SERVER['SERVER_NAME'] . '/certificate/' . $certificate['cert_uuid'];
    $verification_text = "Verify at:\n" .  $verification_link . "\nTechTutor has authenticated the individual's identity and officially confirmed their participation in the course.";
    $pdf->SetXY($pageWidth * 0.452, $pageHeight * 0.84);
    $pdf->MultiCell($pageWidth * 0.36, 5, $verification_text, 0, 'L');

    // Output PDF
    $pdf->Output('techtutor_' . substr($cert_uuid, 0, 8) . '.pdf', 'D');
    exit;
}

$title = $certificate ? "Certificate: " . $certificate['award'] : "Certificate Not Found";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
            font-family: 'Arial', sans-serif;
        }
        
        .container {
            max-width: 1000px;
        }
        
        .certificate-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px 0;
            position: relative;
        }
        
        .certificate-content {
            position: relative;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 0;
            padding-bottom: 70%; /* Aspect ratio for A4 landscape */
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
        }
        
        /* Certificate title */
        .certificate-title {
            position: absolute;
            top: 15%;
            left: 5%;
            width: 100%;
            text-align: center;
            z-index: 2;
        }
        
        .certificate-title-main {
            font-size: 3vw;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        
        .certificate-title-sub {
            font-size: 2.5vw;
            color: #f39c12; /* Orange */
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        
        /* Recipient name */
        .recipient-name {
            position: absolute;
            top: 38%;
            left: 0;
            width: 100%;
            text-align: center;
            font-family: 'Brush Script MT', cursive;
            font-size: 3vw;
            font-weight: 500;
            color: #333;
            z-index: 2;
        }
        
        /* Certificate description */
        .certificate-description {
            position: absolute;
            top: 50%;
            left: 20%;
            width: 60%;
            text-align: center;
            font-size: 1vw;
            color: #333;
            z-index: 2;
        }
        
        /* Date text */
        .certificate-date {
            position: absolute;
            top: 63%;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 1vw;
            color: #333;
            z-index: 2;
        }
        
        /* Signatures */
        .tutor-signature {
            position: absolute;
            bottom: 20%;
            left: 32%;
            transform: translateX(-50%);
            text-align: center;
            z-index: 2;
        }
        
        .ceo-signature {
            position: absolute;
            bottom: 25%;
            left: 75%;
            transform: translateX(-50%);
            text-align: center;
            z-index: 2;
        }
        
        .signature-name {
            font-weight: bold;
            font-size: 1.2vw;
            margin-bottom: 0;
        }
        
        .signature-title {
            font-size: 1vw;
            color: #666;
        }
        
        /* Award ribbon */
        .award-ribbon {
            position: absolute;
            bottom: 9%;
            right: 6%;
            font-weight: bold;
            font-size: 1w;
            color: white;
            z-index: 2;
            text-wrap: wrap;
            max-width: 9.2em;
            text-align: center;
            background-color: #ff9d0f;
            border-radius: 0.5em;
        }
        
        /* Verification text */
        .verification-text {
            position: absolute;
            bottom: 9%;
            right: 20%;
            width: 35%;
            text-align: left;
            font-size: 8pt;
            color: #555;
            z-index: 10;
        }
        
        /* Print styles */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            
            .container {
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .certificate-container {
                box-shadow: none;
                margin: 0;
                page-break-inside: avoid;
            }
            
            .btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="<?php echo BASE; ?>" class="btn btn-primary">Return to Home</a>
                    </div>
                <?php elseif ($certificate): ?>
                    <div class="certificate-container">
                        <div class="certificate-content" id="certificate-bg">
                            <!-- Certificate Title -->
                            <div class="certificate-title">
                                <p class="certificate-title-main">Certificate of</p>
                                <p class="certificate-title-sub"><?php 
                                    echo htmlspecialchars(isset($certificate['award_type']) ? $certificate['award_type'] : 'Completion'); 
                                ?></p>
                            </div>
                            
                            <!-- Recipient Name -->
                            <div class="recipient-name"><?php echo htmlspecialchars($certificate['recipient_name']); ?></div>
                            
                            <!-- Certificate Description -->
                            <div class="certificate-description">
                                
                                <?php if($certificate['type'] == 'class'): ?>
                                This is to certify that <strong><?php echo htmlspecialchars($certificate['recipient_name']); ?></strong> 
                                has successfully completed all <strong>`<?php echo htmlspecialchars($certificate['class_name']); ?>`</strong> lessons
                                and fulfilled all requirements on <strong>TechTutor</strong>, an authorized one-on-one online tutoring
                                platform.

                                <?php else: ?>
                                We hereby certify that <strong><?php echo htmlspecialchars($certificate['recipient_name']); ?></strong>  has successfully completed the <strong><?php echo htmlspecialchars($certificate['award']); ?></strong> game on TechTutor. This achievement demonstrates both your engagement and your ability to learn through interactive play. 
                                <?php endif; ?>
                            </div>
                            
                            <!-- Date -->
                            <div class="certificate-date">
                                Given this <?php echo date('jS', strtotime($certificate['issue_date'])); ?> day of 
                                <?php echo date('F, Y', strtotime($certificate['issue_date'])); ?>
                            </div>
                            
                            <!-- Signatures -->
                            <div class="tutor-signature">
                                <p class="signature-name"><?php echo htmlspecialchars($certificate['donor_name']); ?></p>
                            </div>
                            
                            <!-- Award Ribbon Text -->
                            <div class="award-ribbon">
                                <?php echo htmlspecialchars($certificate['subject_name']); ?>
                            </div>
                            
                            <!-- Verification Text -->
                            <div class="verification-text">
                                Verify at: <br/><a href="<?php echo "https://".$_SERVER['SERVER_NAME'] . '/certificate/' . $certificate['cert_uuid']; ?>"><?php echo "https://".$_SERVER['SERVER_NAME'] . '/certificate/' . $certificate['cert_uuid']; ?></a><br>
                                TechTutor has authenticated the individual's identity and officially confirmed their participation in the course.
                            </div>
                        </div>
                    </div>
                    
                    <?php if(!isset($_GET['view'])): ?>
                    <div class="text-center mt-4">
                        <a href="<?php echo BASE; ?>certificate/<?php echo $certificate['cert_uuid']; ?>?download=1" 
                           class="btn btn-primary">
                            <i class="bi bi-download me-2"></i> Download Certificate
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const certificateBg = document.getElementById('certificate-bg');
            if (certificateBg) {
                // Set the template image as background
                certificateBg.style.backgroundImage = "url('<?php echo BASE; ?>docs/template/certificate_template.png')";
            }
        });
    </script>
</body>
</html> 
