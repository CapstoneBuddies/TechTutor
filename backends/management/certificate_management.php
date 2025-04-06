<?php
/**
 * Certificate management functions for TechTutor platform
 * Handles all certificate-related database operations
 */

// Include notifications management
require_once __DIR__ . '/notifications_management.php';

/**
 * Get certificates issued by a TechGuru
 * 
 * @param int $donor_id The ID of the TechGuru who issued the certificates
 * @return array Array of certificates with recipient details
 */
function getTechGuruCertificates($donor_id) {
    global $conn;
    
    $query = "SELECT 
                c.*,
                CONCAT(u.first_name, ' ', u.last_name) as recipient_name,
                u.email as recipient_email,
                u.profile_picture as recipient_profile,
                cl.class_name,
                cl.class_id,
                s.subject_name
             FROM certificate c 
             JOIN users u ON c.recipient = u.uid 
             LEFT JOIN class cl ON c.class_id = cl.class_id
             LEFT JOIN subject s ON cl.subject_id = s.subject_id
             WHERE c.donor = ? 
             ORDER BY c.issue_date DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $donor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting TechGuru certificates: " . $e->getMessage());
        return [];
    }
}

/**
 * Get certificates received by a student
 * 
 * @param int $recipient_id The ID of the student who received the certificates
 * @return array Array of certificates with donor details
 */
function getStudentCertificatesDetails($recipient_id) {
    global $conn;
    
    $query = "SELECT 
                c.*,
                CONCAT(u.first_name, ' ', u.last_name) as donor_name,
                u.email as donor_email,
                u.profile_picture as donor_profile,
                cl.class_name,
                cl.class_id,
                s.subject_name
             FROM certificate c 
             JOIN users u ON c.donor = u.uid 
             LEFT JOIN class cl ON c.donor = cl.tutor_id
             LEFT JOIN subject s ON cl.subject_id = s.subject_id
             WHERE c.recipient = ? 
             ORDER BY c.issue_date DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $recipient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting student certificates: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new certificate
 * 
 * @param int $donor_id The ID of the TechGuru issuing the certificate
 * @param int $recipient_id The ID of the student receiving the certificate
 * @param string $award The name/title of the certificate award
 * @param int $class_id (Optional) Related class ID
 * @return bool|string The certificate UUID if successful, false otherwise
 */
function createCertificate($donor_id, $recipient_id, $award, $class_id = null) {
    global $conn;
    
    // Generate a unique UUID for the certificate
    $cert_uuid = bin2hex(random_bytes(16));
    $issue_date = date('Y-m-d');
    
    try {
        $stmt = $conn->prepare("INSERT INTO certificate (cert_uuid, recipient, award, donor, issue_date, class_id) VALUES ( ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssi", $cert_uuid, $recipient_id, $award, $donor_id, $issue_date, $class_id);
        
        if ($stmt->execute()) {
            // Create notification for recipient
            $notification_message = "You've received a new certificate for '$award'";
            $notification_link = BASE . 'dashboard/certificates';
            
            insertNotification(
                $recipient_id,
                'TECHKID',
                $notification_message,
                $notification_link,
                $class_id,
                'bi-award-fill',
                'text-success'
            );
            
            return $cert_uuid;
        } else {
            error_log("Error creating certificate: " . $stmt->error);
            return false;
        }
    } catch (Exception $e) {
        error_log("Error creating certificate: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a certificate
 * 
 * @param string $cert_uuid The UUID of the certificate to delete
 * @param int $donor_id The ID of the TechGuru who issued the certificate (for verification)
 * @return bool True if deletion was successful, false otherwise
 */
function deleteCertificate($cert_uuid, $donor_id) {
    global $conn;
    
    try {
        // First verify the certificate belongs to this TechGuru
        $verify = $conn->prepare("SELECT donor FROM certificate WHERE cert_uuid = ?");
        $verify->bind_param("s", $cert_uuid);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        
        if (!$result || $result['donor'] != $donor_id) {
            return false;
        }
        
        // Delete the certificate
        $stmt = $conn->prepare("DELETE FROM certificate WHERE cert_uuid = ?");
        $stmt->bind_param("s", $cert_uuid);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error deleting certificate: " . $e->getMessage());
        return false;
    }
}

/**
 * Get students eligible for certificates (completed a class)
 * 
 * @param int $tutor_id The ID of the TechGuru
 * @return array Array of eligible students with their class details
 */
function getEligibleStudentsForCertificates($tutor_id) { 
    global $conn;
    
    // Get students who completed a class but don't have a certificate for it yet
    $query = "SELECT 
                e.student_id,
                e.class_id,
                CONCAT(u.first_name, ' ', u.last_name) as student_name,
                u.email as student_email,
                u.profile_picture as student_profile,
                c.class_name,
                s.subject_name
             FROM enrollments e 
             JOIN users u ON e.student_id = u.uid
             JOIN class c ON e.class_id = c.class_id
             JOIN subject s ON c.subject_id = s.subject_id
             WHERE c.tutor_id = ? 
             AND e.status = 'completed'
             AND NOT EXISTS (
                SELECT 1 FROM certificate cert 
                WHERE cert.recipient = e.student_id 
                AND cert.class_id = e.class_id
             )
             ORDER BY c.class_name, u.first_name";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting eligible students: " . $e->getMessage());
        return [];
    }
}

/**
 * Get certificate details by UUID
 * 
 * @param string $cert_uuid The UUID of the certificate
 * @return array|null The certificate details or null if not found
 */
function getCertificateByUUID($cert_uuid) {
    global $conn;
    
    $query = "SELECT 
                c.*,
                CONCAT(recipient_user.first_name, ' ', recipient_user.last_name) as recipient_name,
                recipient_user.email as recipient_email,
                CONCAT(donor_user.first_name, ' ', donor_user.last_name) as donor_name,
                donor_user.email as donor_email,
                cl.class_name,
                s.subject_name
             FROM certificate c 
             JOIN users recipient_user ON c.recipient = recipient_user.uid 
             JOIN users donor_user ON c.donor = donor_user.uid
             LEFT JOIN class cl ON c.class_id = cl.class_id
             LEFT JOIN subject s ON cl.subject_id = s.subject_id
             WHERE c.cert_uuid = ?";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $cert_uuid);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting certificate details: " . $e->getMessage());
        return null;
    }
}

/**
 * Get certificate details by UUID - alias for getCertificateByUUID
 * Used in certificate viewing pages
 * 
 * @param string $cert_uuid The UUID of the certificate
 * @return array|null The certificate details or null if not found
 */
function getCertificateDetails($cert_uuid) {
    return getCertificateByUUID($cert_uuid);
} 

/**
 * Get certificate description by UUID
 * 
 * @param string $cert_uuid The UUID of the certificate
 * @return array|null The certificate details or null if not found
 */
function getCertDescription($cert_uuid) {
    global $conn;

    // $recipientName, $source

    $stmt = $conn->prepare("SELECT 
                c.*,
                CONCAT(recipient_user.first_name, ' ', recipient_user.last_name) as recipient_name,
                recipient_user.email as recipient_email,
                CONCAT(donor_user.first_name, ' ', donor_user.last_name) as donor_name,
                donor_user.email as donor_email,
                cl.class_name,
                s.subject_name
             FROM certificate c 
             JOIN users recipient_user ON c.recipient = recipient_user.uid 
             JOIN users donor_user ON c.donor = donor_user.uid
             LEFT JOIN class cl ON c.class_id = cl.class_id
             LEFT JOIN subject s ON cl.subject_id = s.subject_id
             WHERE c.cert_uuid = ?");
    


    // "This is to certify that <strong>" .$recipientName. "</strong> has successfully completed all <strong>".$source. "</strong> lessons and fulfilled all requirements on TechTutor, an authorized one-on-one online tutoring platform.";
    // "We hereby certify that <strong>" .$recipientName. "</strong> has successfully completed the <strong>" .$source"</strong> game on TechTutor. This achievement demonstrates both your engagement and your ability to learn through interactive play. Well done!";
}