<?php
require_once '../main.php';
require_once BACKEND.'student_management.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'rating_management.php';
require_once BACKEND.'transactions_management.php';
require_once ROOT_PATH.'/assets/vendor/tecnickcom/tcpdf/tcpdf.php';

// Ensure user is logged in 
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'TECHKID' && $_SESSION['role'] !== 'TECHGURU')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request with correct action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_pdf_report') {
    try {
        // Get user data
        $user_id = $_SESSION['user'];
        $user_name = $_SESSION['name'];
        $user_role = $_SESSION['role'];
        
        // Create new PDF document
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('TechTutor Learning Platform');
        $pdf->SetAuthor('TechTutor System');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);
        
        // Set image scale factor
        $pdf->setImageScale(1.25);
        
        // Set font
        $pdf->SetFont('helvetica', '', 11);
        
        // Generate appropriate report based on user role
        if ($user_role === 'TECHKID') {
            // Get student data for TechKid report
            $learning_stats = getStudentLearningStats($user_id);
            $class_history = getStudentClassHistory($user_id);
            $recent_activities = getStudentRecentActivities($user_id);
            
            // Process images
            $learning_progress_image = isset($_POST['learning_progress_image']) ? $_POST['learning_progress_image'] : null;
            $performance_image = isset($_POST['performance_image']) ? $_POST['performance_image'] : null;
            
            // Set title
            $pdf->SetTitle('Learning Progress Report');
            $pdf->SetSubject('Learning Progress Report for ' . $user_name);
            
            // Add a page
            $pdf->AddPage();
            
            // Title
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->Cell(0, 10, 'Learning Progress Report', 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 8, 'Student: ' . $user_name, 0, 1, 'C');
            $pdf->Cell(0, 8, 'Date: ' . date('F d, Y'), 0, 1, 'C');
            
            $pdf->Ln(5);
            
            // Statistics Summary
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Learning Statistics', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(60, 8, 'Total Classes:', 0, 0, 'L');
            $pdf->Cell(30, 8, $learning_stats['total_classes'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Completed Classes:', 0, 0, 'L');
            $pdf->Cell(30, 8, $learning_stats['completed_classes'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Total Learning Hours:', 0, 0, 'L');
            $pdf->Cell(30, 8, $learning_stats['total_hours'], 0, 1, 'L');
            
            $pdf->Ln(5);
            
            // Learning Progress Chart
            if ($learning_progress_image) {
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Learning Progress Over Time', 0, 1, 'L');
                
                // Remove header data from base64 image
                $image_parts = explode(';base64,', $learning_progress_image);
                $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $learning_progress_image;
                
                // Add image to PDF
                $pdf->Image('@'.base64_decode($image_base64), 15, null, 180, 80, 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
                
                $pdf->Ln(85); // Add space after the chart
            }
            
            // Performance Distribution Chart
            if ($performance_image) {
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Performance Distribution', 0, 1, 'L');
                
                // Remove header data from base64 image
                $image_parts = explode(';base64,', $performance_image);
                $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $performance_image;
                
                // Add image to PDF
                $pdf->Image('@'.base64_decode($image_base64), 60, null, 90, 90, 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
                
                $pdf->Ln(95); // Add space after the chart
            }
            
            // Class History
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Class History', 0, 1, 'L');
            
            if (empty($class_history)) {
                $pdf->SetFont('helvetica', 'I', 11);
                $pdf->Cell(0, 8, 'No class history available.', 0, 1, 'L');
            } else {
                // Table header
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(60, 8, 'Class Name', 1, 0, 'L', true);
                $pdf->Cell(40, 8, 'Subject', 1, 0, 'L', true);
                $pdf->Cell(25, 8, 'Progress', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'Performance', 1, 0, 'C', true);
                $pdf->Cell(30, 8, 'Status', 1, 1, 'C', true);
                
                // Table content
                $pdf->SetFont('helvetica', '', 10);
                $fill = false;
                foreach ($class_history as $class) {
                    $pdf->Cell(60, 7, substr($class['class_name'], 0, 28), 1, 0, 'L', $fill);
                    $pdf->Cell(40, 7, substr($class['subject_name'], 0, 19), 1, 0, 'L', $fill);
                    $pdf->Cell(25, 7, $class['progress'] . '%', 1, 0, 'C', $fill);
                    $pdf->Cell(25, 7, $class['performance'] . '%', 1, 0, 'C', $fill);
                    $pdf->Cell(30, 7, ucfirst($class['status']), 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
            }
            
            $pdf->Ln(5);
            
            // Recent Activities
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Recent Activities', 0, 1, 'L');
            
            if (empty($recent_activities)) {
                $pdf->SetFont('helvetica', 'I', 11);
                $pdf->Cell(0, 8, 'No recent activities available.', 0, 1, 'L');
            } else {
                $pdf->SetFont('helvetica', '', 10);
                
                foreach ($recent_activities as $index => $activity) {
                    if ($index > 9) break; // Limit to 10 activities
                    
                    $icon = '•';
                    if (isset($activity['type'])) {
                        switch ($activity['type']) {
                            case 'class_completion':
                                $icon = '✓';
                                break;
                            case 'class_rating':
                                $icon = '★';
                                break;
                            case 'file_upload':
                                $icon = '↑';
                                break;
                            case 'attendance':
                                $icon = '🗓';
                                break;
                        }
                    }
                    
                    $pdf->SetFont('helvetica', 'B', 10);
                    $pdf->Cell(10, 8, $icon, 0, 0, 'C');
                    $pdf->Cell(100, 8, substr($activity['title'], 0, 40), 0, 0, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->Cell(70, 8, $activity['timestamp'], 0, 1, 'R');
                    
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->Cell(10, 6, '', 0, 0, 'C');
                    $pdf->Cell(170, 6, substr($activity['description'], 0, 80), 0, 1, 'L');
                    
                    $pdf->Ln(2);
                }
            }
            
            $filename = 'Learning_Progress_Report_' . str_replace(' ', '_', $user_name) . '.pdf';
            
        } else if ($user_role === 'TECHGURU') {
            // Get tutor data for TechGuru report
            $teaching_stats = getTechGuruStats($user_id);
            $class_performance = getClassPerformanceData($user_id);
            $student_progress = getStudentProgressByTutor($user_id);
            $rating_data = getTutorRatingStats($user_id);
            
            // Process images
            $performance_image = isset($_POST['performance_image']) ? $_POST['performance_image'] : null;
            $rating_image = isset($_POST['rating_image']) ? $_POST['rating_image'] : null;
            
            // Set title
            $pdf->SetTitle('Teaching Performance Report');
            $pdf->SetSubject('Teaching Performance Report for ' . $user_name);
            
            // Add a page
            $pdf->AddPage();
            
            // Title
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->Cell(0, 10, 'Teaching Performance Report', 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 8, 'Tutor: ' . $user_name, 0, 1, 'C');
            $pdf->Cell(0, 8, 'Date: ' . date('F d, Y'), 0, 1, 'C');
            
            $pdf->Ln(5);
            
            // Statistics Summary
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Teaching Statistics', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(60, 8, 'Total Classes:', 0, 0, 'L');
            $pdf->Cell(30, 8, $teaching_stats['total_classes'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Active Classes:', 0, 0, 'L');
            $pdf->Cell(30, 8, $teaching_stats['active_classes'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Completed Classes:', 0, 0, 'L');
            $pdf->Cell(30, 8, $teaching_stats['completed_classes'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Total Students:', 0, 0, 'L');
            $pdf->Cell(30, 8, $teaching_stats['total_students'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Teaching Hours:', 0, 0, 'L');
            $pdf->Cell(30, 8, $teaching_stats['total_hours'], 0, 1, 'L');
            
            $pdf->Cell(60, 8, 'Average Rating:', 0, 0, 'L');
            $pdf->Cell(30, 8, number_format($rating_data['average_rating'], 1) . ' / 5.0', 0, 1, 'L');
            
            $pdf->Ln(5);
            
            // Performance Chart
            if ($performance_image) {
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Teaching Performance Trends', 0, 1, 'L');
                
                // Remove header data from base64 image
                $image_parts = explode(';base64,', $performance_image);
                $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $performance_image;
                
                // Add image to PDF
                $pdf->Image('@'.base64_decode($image_base64), 15, null, 180, 80, 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
                
                $pdf->Ln(85); // Add space after the chart
            }
            
            // Rating Distribution Chart
            if ($rating_image) {
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Rating Distribution', 0, 1, 'L');
                
                // Remove header data from base64 image
                $image_parts = explode(';base64,', $rating_image);
                $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $rating_image;
                
                // Add image to PDF
                $pdf->Image('@'.base64_decode($image_base64), 60, null, 90, 90, 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
                
                $pdf->Ln(95); // Add space after the chart
            }
            
            // Class Performance Table
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Class Performance', 0, 1, 'L');
            
            if (empty($class_performance)) {
                $pdf->SetFont('helvetica', 'I', 11);
                $pdf->Cell(0, 8, 'No class performance data available.', 0, 1, 'L');
            } else {
                // Table header
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(50, 8, 'Class Name', 1, 0, 'L', true);
                $pdf->Cell(35, 8, 'Subject', 1, 0, 'L', true);
                $pdf->Cell(20, 8, 'Students', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'Performance', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'Completion', 1, 0, 'C', true);
                $pdf->Cell(20, 8, 'Rating', 1, 0, 'C', true);
                $pdf->Cell(20, 8, 'Status', 1, 1, 'C', true);
                
                // Table content
                $pdf->SetFont('helvetica', '', 9);
                $fill = false;
                foreach ($class_performance as $index => $class) {
                    if ($index > 14) break; // Limit to 15 classes
                    
                    $pdf->Cell(50, 7, substr($class['class_name'], 0, 23), 1, 0, 'L', $fill);
                    $pdf->Cell(35, 7, substr($class['subject_name'], 0, 17), 1, 0, 'L', $fill);
                    $pdf->Cell(20, 7, $class['student_count'], 1, 0, 'C', $fill);
                    $pdf->Cell(25, 7, number_format($class['avg_performance'], 1) . '%', 1, 0, 'C', $fill);
                    $pdf->Cell(25, 7, number_format($class['completion_rate'], 1) . '%', 1, 0, 'C', $fill);
                    $pdf->Cell(20, 7, number_format($class['rating'], 1) . ' / 5', 1, 0, 'C', $fill);
                    $pdf->Cell(20, 7, ucfirst($class['status']), 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
            }
            
            $pdf->Ln(5);
            
            // Student Progress
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Student Progress Overview', 0, 1, 'L');
            
            if (empty($student_progress)) {
                $pdf->SetFont('helvetica', 'I', 11);
                $pdf->Cell(0, 8, 'No student progress data available.', 0, 1, 'L');
            } else {
                // Table header
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(60, 8, 'Student', 1, 0, 'L', true);
                $pdf->Cell(30, 8, 'Classes Enrolled', 1, 0, 'C', true);
                $pdf->Cell(30, 8, 'Avg. Performance', 1, 0, 'C', true);
                $pdf->Cell(30, 8, 'Attendance Rate', 1, 0, 'C', true);
                $pdf->Cell(40, 8, 'Last Active', 1, 1, 'C', true);
                
                // Table content
                $pdf->SetFont('helvetica', '', 9);
                $fill = false;
                foreach ($student_progress as $index => $student) {
                    if ($index > 14) break; // Limit to 15 students
                    
                    $pdf->Cell(60, 7, substr($student['name'], 0, 28), 1, 0, 'L', $fill);
                    $pdf->Cell(30, 7, $student['classes_enrolled'], 1, 0, 'C', $fill);
                    $pdf->Cell(30, 7, number_format($student['avg_performance'], 1) . '%', 1, 0, 'C', $fill);
                    $pdf->Cell(30, 7, number_format($student['attendance_rate'], 1) . '%', 1, 0, 'C', $fill);
                    $pdf->Cell(40, 7, date('M d, Y', strtotime($student['last_active'])), 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
            }
            
            $filename = 'Teaching_Performance_Report_' . str_replace(' ', '_', $user_name) . '.pdf';
        }
        
        // Add footer with page number
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 1, 'C');
        
        // Output PDF as string
        $pdf_content = $pdf->Output($filename, 'S');
        
        // Send PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo $pdf_content;
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()]);
        exit();
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
} 