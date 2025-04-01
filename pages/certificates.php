<?php 
require_once '../backends/main.php';
require_once BACKEND . 'certificate_management.php';

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
    // We'll generate a PDF and force download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="certificate_' . substr($cert_uuid, 0, 8) . '.pdf"');
    
    // In a real implementation, you would generate a PDF here
    // For now, we'll just include the certificate page content
    // and let the browser handle it (this won't actually download as PDF)
    // You would need a PDF library like TCPDF, FPDF, or mPDF for actual PDF generation
}

$title = $certificate ? "Certificate: " . $certificate['award'] : "Certificate Not Found";
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-base="<?php echo BASE; ?>">
    
    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
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
                        <div class="certificate-inner">
                            <div class="certificate-content text-center">
                                <div class="certificate-corner certificate-corner-tl"></div>
                                <div class="certificate-corner certificate-corner-tr"></div>
                                <div class="certificate-corner certificate-corner-bl"></div>
                                <div class="certificate-corner certificate-corner-br"></div>
                                
                                <div class="certificate-header mb-4">
                                    <img src="<?php echo IMG; ?>stand_alone_logo.png" alt="TechTutor" class="certificate-logo">
                                    <h1 class="certificate-title mt-3">Certificate of Achievement</h1>
                                </div>
                                
                                <div class="certificate-body">
                                    <p class="certificate-text">This certifies that</p>
                                    <h2 class="recipient-name"><?php echo htmlspecialchars($certificate['recipient_name']); ?></h2>
                                    <p class="certificate-text mt-4">has successfully completed</p>
                                    <h3 class="award-name"><?php echo htmlspecialchars($certificate['award']); ?></h3>
                                    
                                    <?php if (!empty($certificate['class_name'])): ?>
                                    <p class="certificate-text mt-3">
                                        for the class "<?php echo htmlspecialchars($certificate['class_name']); ?>"
                                        <?php if (!empty($certificate['subject_name'])): ?>
                                            in <?php echo htmlspecialchars($certificate['subject_name']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <p class="certificate-date mt-4">
                                        Issued on <?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?>
                                    </p>
                                </div>
                                
                                <div class="certificate-footer mt-5">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <div class="signature-line"></div>
                                            <p class="signature-name"><?php echo htmlspecialchars($certificate['donor_name']); ?></p>
                                            <p class="signature-title">Instructor</p>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="certificate-seal">
                                                <i class="bi bi-patch-check-fill"></i>
                                            </div>
                                            <div class="signature-line"></div>
                                            <p class="signature-name"><?php echo SITE_NAME; ?></p>
                                            <p class="signature-title">Official Certificate</p>
                                        </div>
                                    </div>
                                    
                                    <div class="certificate-id mt-4">
                                        Certificate ID: <?php echo $certificate['cert_uuid']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo BASE; ?>certificate/<?php echo $certificate['cert_uuid']; ?>?download=1" 
                           class="btn btn-primary">
                            <i class="bi bi-download me-2"></i> Download Certificate
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <style>
        .certificate-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px 0;
        }
        
        .certificate-inner {
            border: 20px solid #f8f9fa;
            background-color: #fff;
            position: relative;
            background-image: 
                repeating-linear-gradient(45deg, #f8f9fa 0, #f8f9fa 5px, transparent 5px, transparent 10px),
                repeating-linear-gradient(135deg, #f8f9fa 0, #f8f9fa 5px, transparent 5px, transparent 10px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            padding: 20px;
        }
        
        .certificate-inner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid #ddd;
            pointer-events: none;
        }
        
        .certificate-content {
            padding: 40px;
            background-color: #fff;
            background-image: 
                radial-gradient(circle at 10px 10px, #f8f9fa 2px, transparent 2px),
                radial-gradient(circle at 30px 30px, #f8f9fa 2px, transparent 2px);
            background-size: 40px 40px;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            position: relative;
            overflow: hidden;
        }
        
        .certificate-content::before {
            content: '<?php echo SITE_NAME; ?>';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(200, 200, 200, 0.1);
            white-space: nowrap;
            pointer-events: none;
        }
        
        /* Add another subtle design element */
        .certificate-corner {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.5;
        }
        .certificate-corner-tl {
            top: 0;
            left: 0;
            border-top: 5px solid #2980b9;
            border-left: 5px solid #2980b9;
            border-top-left-radius: 15px;
        }
        .certificate-corner-tr {
            top: 0;
            right: 0;
            border-top: 5px solid #2980b9;
            border-right: 5px solid #2980b9;
            border-top-right-radius: 15px;
        }
        .certificate-corner-bl {
            bottom: 0;
            left: 0;
            border-bottom: 5px solid #2980b9;
            border-left: 5px solid #2980b9;
            border-bottom-left-radius: 15px;
        }
        .certificate-corner-br {
            bottom: 0;
            right: 0;
            border-bottom: 5px solid #2980b9;
            border-right: 5px solid #2980b9;
            border-bottom-right-radius: 15px;
        }
        
        .certificate-logo {
            max-height: 80px;
            margin-bottom: 15px;
        }
        
        .certificate-title {
            font-size: 36px;
            color: #2c3e50;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 20px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .certificate-text {
            font-size: 18px;
            color: #555;
            margin-bottom: 5px;
        }
        
        .recipient-name {
            font-size: 32px;
            color: #2980b9;
            font-weight: 700;
            margin: 15px 0;
            font-family: 'Brush Script MT', cursive;
        }
        
        .award-name {
            font-size: 24px;
            color: #333;
            font-weight: 600;
            margin: 15px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .certificate-date {
            font-size: 16px;
            color: #555;
            font-style: italic;
        }
        
        .signature-line {
            width: 200px;
            height: 1px;
            background-color: #000;
            margin: 15px auto;
        }
        
        .signature-name {
            font-size: 20px;
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .signature-title {
            font-size: 14px;
            color: #666;
        }
        
        .certificate-seal {
            font-size: 50px;
            color: #e67e22;
            margin-bottom: 10px;
        }
        
        .certificate-id {
            font-size: 12px;
            color: #999;
        }
        
        /* Print styles */
        @media print {
            body {
                background-color: #fff;
            }
            
            .certificate-container {
                box-shadow: none;
                margin: 0;
                page-break-inside: avoid;
            }
            
            header, footer, .btn {
                display: none;
            }
            
            main {
                padding: 0 !important;
            }
        }
        
        /* Additional print styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            
            body.printing {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background-color: white;
            }
            
            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .certificate-container {
                width: 190mm;
                height: 277mm;
                margin: 10mm auto !important;
                box-shadow: none !important;
                border: none !important;
            }
            
            .certificate-inner {
                padding: 10mm !important;
            }
            
            .certificate-content {
                min-height: 257mm !important;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable printing when download button is clicked
            const downloadBtn = document.querySelector('a[href*="download=1"]');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Prepare for print
                    document.body.classList.add('printing');
                    
                    // Print after a small delay to ensure styles are applied
                    setTimeout(function() {
                        window.print();
                        
                        // Reset after printing
                        setTimeout(function() {
                            document.body.classList.remove('printing');
                        }, 1000);
                    }, 300);
                });
            }
        });
    </script>
</body>
</html> 