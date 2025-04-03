<?php
/**
 * Admin Certificate Management Handler
 * Handles all certificate-related operations for admin users
 */

require_once '../../backends/main.php';
require_once BACKEND . 'certificate_management.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Handle different actions based on request
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];
switch ($action) {
    case 'create_certificate':
        // Get data from request
        $donor_id = $_POST['donor_id'] ?? null;
        $recipient_id = $_POST['recipient_id'] ?? null;
        $award = $_POST['award'] ?? '';
        $class_id = isset($_POST['class_id']) && !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        
        if (empty($donor_id) || empty($recipient_id) || empty($award)) {
            $response = ['success' => false, 'message' => 'Missing required fields'];
            break;
        }
        
        $cert_uuid = createCertificate($donor_id, $recipient_id, $award);

        if ($cert_uuid) {
            $response = [
                'success' => true, 
                'message' => 'Certificate created successfully',
                'cert_uuid' => $cert_uuid
            ];
            
            // Log the certificate creation
            log_error("Certificate created by admin: {$_SESSION['user']} for recipient: {$recipient_id}", "info");
        } else {
            $response = ['success' => false, 'message' => 'Failed to create certificate'];
        }
        break;
        
    case 'delete_certificate':
        $cert_uuid = $_POST['cert_uuid'] ?? '';
        
        if (empty($cert_uuid)) {
            $response = ['success' => false, 'message' => 'Certificate ID is required'];
            break;
        }
        
        // For admin, we'll delete any certificate without donor verification
        try {
            global $conn;
            $stmt = $conn->prepare("DELETE FROM certificate WHERE cert_uuid = ?");
            $stmt->bind_param("s", $cert_uuid);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Certificate deleted successfully'];
                
                // Log the certificate deletion
                log_error("Certificate deleted by admin: {$_SESSION['user']}, certificate: {$cert_uuid}", "info");
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete certificate'];
            }
        } catch (Exception $e) {
            log_error("Error deleting certificate: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'An error occurred while deleting the certificate'];
        }
        break;
        
    case 'get_all_certificates':
        try {
            global $conn;
            $query = "SELECT 
                        c.*,
                        CONCAT(r.first_name, ' ', r.last_name) as recipient_name,
                        r.email as recipient_email,
                        r.profile_picture as recipient_profile,
                        CONCAT(d.first_name, ' ', d.last_name) as donor_name,
                        d.email as donor_email,
                        cl.class_name,
                        cl.class_id,
                        s.subject_name
                     FROM certificate c 
                     JOIN users r ON c.recipient = r.uid 
                     JOIN users d ON c.donor = d.uid
                     LEFT JOIN class cl ON d.uid = cl.tutor_id
                     LEFT JOIN subject s ON cl.subject_id = s.subject_id
                     ORDER BY c.issue_date DESC";
                     
            $result = $conn->query($query);
            $certificates = $result->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'certificates' => $certificates
            ];
        } catch (Exception $e) {
            log_error("Error getting all certificates: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Failed to retrieve certificates'];
        }
        break;
        
    case 'assign_certificate':
        // Get data from request
        $donor_id = $_POST['donor_id'] ?? null;
        $recipient_id = $_POST['recipient_id'] ?? null;
        $award = $_POST['award'] ?? '';
        $class_id = isset($_POST['class_id']) && !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        
        if (empty($donor_id) || empty($recipient_id) || empty($award)) {
            $response = ['success' => false, 'message' => 'Missing required fields'];
            break;
        }
        
        $cert_uuid = createCertificate($donor_id, $recipient_id, $award, $class_id);
        
        if ($cert_uuid) {
            $response = [
                'success' => true, 
                'message' => 'Certificate assigned successfully',
                'cert_uuid' => $cert_uuid
            ];
            
            // Log the certificate assignment
            log_error("Certificate assigned by admin: {$_SESSION['user']} from donor: {$donor_id} to recipient: {$recipient_id}", "info");
        } else {
            $response = ['success' => false, 'message' => 'Failed to assign certificate'];
        }
        break;
        
    case 'get_certificate':
        $cert_uuid = $_POST['cert_uuid'] ?? '';
        
        if (empty($cert_uuid)) {
            $response = ['success' => false, 'message' => 'Certificate ID is required'];
            break;
        }
        
        $certificate = getCertificateByUUID($cert_uuid);
        
        if ($certificate) {
            $response = [
                'success' => true,
                'certificate' => $certificate
            ];
        } else {
            $response = ['success' => false, 'message' => 'Certificate not found'];
        }
        break;
        
    case 'get_eligible_students':
        try {
            global $conn;
            $query = "SELECT 
                        e.student_id,
                        e.class_id,
                        CONCAT(u.first_name, ' ', u.last_name) as student_name,
                        u.email as student_email,
                        u.profile_picture as student_profile,
                        c.class_name,
                        s.subject_name,
                        c.tutor_id,
                        CONCAT(t.first_name, ' ', t.last_name) as tutor_name
                     FROM enrollments e 
                     JOIN users u ON e.student_id = u.uid
                     JOIN class c ON e.class_id = c.class_id
                     JOIN subject s ON c.subject_id = s.subject_id
                     JOIN users t ON c.tutor_id = t.uid
                     WHERE e.status = 'completed'
                     AND NOT EXISTS (
                        SELECT 1 FROM certificate cert 
                        WHERE cert.recipient = e.student_id
                     )
                     ORDER BY c.class_name, u.first_name";
                     
            $result = $conn->query($query);
            $students = $result->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'students' => $students
            ];
        } catch (Exception $e) {
            log_error("Error getting eligible students: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Failed to retrieve eligible students'];
        }
        break;
        
    case 'get_tutors':
        try {
            global $conn;
            $query = "SELECT 
                        u.uid,
                        CONCAT(u.first_name, ' ', u.last_name) as tutor_name,
                        u.email
                     FROM users u 
                     WHERE u.role = 'TECHGURU' AND u.status = 1
                     ORDER BY u.first_name, u.last_name";
                     
            $result = $conn->query($query);
            $tutors = $result->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'tutors' => $tutors
            ];
        } catch (Exception $e) {
            log_error("Error getting tutors: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Failed to retrieve tutors'];
        }
        break;
        
    case 'get_students':
        try {
            global $conn;
            $query = "SELECT 
                        u.uid,
                        CONCAT(u.first_name, ' ', u.last_name) as student_name,
                        u.email
                     FROM users u 
                     WHERE u.role = 'TECHKID' AND u.status = 1
                     ORDER BY u.first_name, u.last_name";
                     
            $result = $conn->query($query);
            $students = $result->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'students' => $students
            ];
        } catch (Exception $e) {
            log_error("Error getting students: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Failed to retrieve students'];
        }
        break;
        
    case 'get_classes':
        try {
            global $conn;
            $query = "SELECT 
                        c.class_id,
                        c.class_name,
                        s.subject_name,
                        c.tutor_id,
                        CONCAT(u.first_name, ' ', u.last_name) as tutor_name
                     FROM class c
                     JOIN subject s ON c.subject_id = s.subject_id
                     JOIN users u ON c.tutor_id = u.uid
                     WHERE c.status IN ('active', 'completed')
                     ORDER BY c.class_name";
                     
            $result = $conn->query($query);
            $classes = $result->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'classes' => $classes
            ];
        } catch (Exception $e) {
            log_error("Error getting classes: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Failed to retrieve classes'];
        }
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit(); 