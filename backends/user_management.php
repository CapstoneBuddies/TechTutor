<?php
/**
 * Get users by role with pagination
 */
function getUserByRole($role, $page = 1, $limit = 8) {
    global $conn;
    
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT `u`.*, `s`.`subject_name` AS 'subject', 
                           (SELECT COUNT(*) FROM `class_schedule` cs 
                            WHERE cs.`user_id` = `u`.`uid` AND cs.`role` = 'STUDENT') AS 'enrolled-classes',
                           (SELECT COUNT(*) FROM `class_schedule` cs2 
                            WHERE cs2.`class_id` = cs.`class_id` AND cs2.`role` = 'STUDENT') AS 'enrolled-students' 
                           FROM `users` `u` 
                           LEFT JOIN `class_schedule` cs ON `u`.`uid` = cs.`user_id` 
                           LEFT JOIN `class` c ON cs.`class_id` = c.`class_id` 
                           LEFT JOIN `subject` s ON c.`subject_id` = s.`subject_id` 
                           WHERE `u`.`role` = ? AND `u`.`status` in (0,1) 
                           LIMIT ? OFFSET ?");

    $stmt->bind_param("sii", $role, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } 
    return [];
}

/**
 * Get active classes for a subject and tutor
 */
function getActiveClassesForSubject($subject_id, $tutor_id) {
    global $conn;
    
    $sql = "SELECT c.*, 
            (SELECT COUNT(DISTINCT cs.user_id) 
             FROM class_schedule cs 
             WHERE cs.class_id = c.class_id AND cs.role = 'STUDENT') as student_count
            FROM class c
            WHERE c.subject_id = ? AND c.tutor_id = ? AND c.is_active = TRUE
            ORDER BY c.start_date ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $subject_id, $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get subject details by course ID or subject name
 */
function getSubjectDetails($identifier, $by = 'course_id') {
    global $conn;

    if ($by === 'course_id') {
        // For listing subjects in a course
        $stmt = $conn->prepare("SELECT 
            s.subject_id, s.subject_name, s.subject_desc, s.image, 
            COUNT(DISTINCT c.class_id) AS class_count, 
            COUNT(DISTINCT cs.user_id) AS student_count
        FROM subject s
        LEFT JOIN class c ON s.subject_id = c.subject_id AND c.is_active = 1
        LEFT JOIN class_schedule cs ON cs.class_id = c.class_id AND cs.role = 'STUDENT'
        WHERE s.is_active = 1 AND s.course_id = ?
        GROUP BY s.subject_id");
        $stmt->bind_param("i", $identifier);
    } else {
        // For getting detailed subject information
        $stmt = $conn->prepare("SELECT s.*, c.course_name, c.course_desc,
            (SELECT COUNT(DISTINCT cl.class_id) 
             FROM class cl 
             WHERE cl.subject_id = s.subject_id AND cl.is_active = TRUE) as active_classes,
            (SELECT COUNT(DISTINCT cs.user_id) 
             FROM class cl 
             JOIN class_schedule cs ON cl.class_id = cs.class_id 
             WHERE cl.subject_id = s.subject_id AND cs.role = 'STUDENT') as total_students,
            (SELECT AVG(r.rating) 
             FROM class cl 
             JOIN ratings r ON cl.tutor_id = r.tutor_id 
             WHERE cl.subject_id = s.subject_id) as average_rating,
            (SELECT COUNT(DISTINCT cs.user_id) * 100.0 / NULLIF(COUNT(DISTINCT cs2.user_id), 0)
             FROM class cl 
             JOIN class_schedule cs ON cl.class_id = cs.class_id AND cs.status = 'completed'
             LEFT JOIN class_schedule cs2 ON cl.class_id = cs2.class_id
             WHERE cl.subject_id = s.subject_id AND cs.role = 'STUDENT') as completion_rate
        FROM subject s
        JOIN course c ON s.course_id = c.course_id
        WHERE s.subject_name = ?");
        $stmt->bind_param("s", $identifier);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($by === 'course_id') {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}
/**
 * Verify a remember me token
 */
function rememberTokenVerifier($hashed_token) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT token_id, token FROM login_tokens WHERE type = 'remember_me' AND expiration_date > NOW()");
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()) { 
            if (password_verify($hashed_token, $row['token'])) { 
                return $row['token_id'];
            }
        }
        throw new Exception("Token not Exist");
    }
    catch(Exception $e) {
        log_error("Error on line " . $e->getLine() . " in " . $e->getFile() . ": SQL Error: " . $e->getMessage(), 'database_error.log');
        return null;
    }
}

/**
 * Generate email verification token
 */
function generateVerificationToken($user_id) {
    global $conn;
    $token = bin2hex(random_bytes(32));
    $hashed_token = password_hash($token, PASSWORD_DEFAULT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $conn->prepare("INSERT INTO login_tokens (user_id, type, token, expiration_date) VALUES (?, 'email_verification', ?, ?)");
    $stmt->bind_param("iss", $user_id, $hashed_token, $expires_at);
    if (!$stmt->execute()) {
        log_error($stmt->error, 'database_error.log');
        return null;
    }
    return $token;
}

/**
 * Generate verification code for email verification
 */
function generateVerificationCode($userId) {
    global $conn;
    $code = rand(100000, 999999);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+3 minutes'));

    $stmt = $conn->prepare("UPDATE login_tokens SET verification_code = ?, expiration_date = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $code, $expiresAt, $userId);
    if (!$stmt->execute()) {
        log_error($stmt->error, 'database_error.log');
        $stmt = $conn->prepare("INSERT INTO login_tokens(verification_code, expiration_date, user_id, type) VALUES(?,?,?,?)");
        $stmt->bind_param("ssis", $code, $expiresAt, $userId, 'email_verification');
        if (!$stmt->execute()) {
            log_error($stmt->error, 'database_error.log');
            $_SESSION['msg'] = "An error occured during verifcation. Please contact the System Administrator";
            header("location: login");
            exit();
        }
    }
    $stmt->close();
    return $code;
}

/**
 * Check verification code status
 */
function checkVCodeStatus($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT verification_code FROM login_tokens WHERE user_id = ? AND type = 'email_verification' AND expiration_date > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return ($result->num_rows > 0);
}

/**
 * Verify email token
 */
function verifyEmailToken($token) {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT user_id, token FROM login_tokens WHERE type = 'email_verification' AND expiration_date > NOW()");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (password_verify($token, $row['token'])) {
                $user_id = $row['user_id'];

                // Update user verification status
                $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE uid = ?");
                $update_stmt->bind_param("i", $user_id);
                $update_stmt->execute();

                // Delete the verification token
                $delete_stmt = $conn->prepare("DELETE FROM login_tokens WHERE user_id = ? AND type = 'email_verification'");
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();

                $_SESSION['msg'] = "Email verified successfully! You can now log in.";
                return true;
            }
        }
        $_SESSION['msg'] = "Invalid or expired token.";
        return false;
    } catch (Exception $e) {
        log_error("Error verifying email: " . $e->getMessage(),'mail.log');
        $_SESSION['msg'] = "An error occured, Please try again later";
        return false;
    }
}

/**
 * Verify verification code
 */
function verifyCode() {
    global $conn;
    try {
        if (isset($_POST['code']) && is_array($_POST['code'])) {
            $verification_code = implode('', $_POST['code']);
            if (strlen($verification_code) === 6 && ctype_digit($verification_code)) {
                $stmt = $conn->prepare("SELECT user_id FROM login_tokens WHERE verification_code = ? AND type = 'email_verification' AND expiration_date > NOW()");
                $stmt->bind_param("s", $verification_code);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $user_id = $user['user_id'];

                    // Update user's verification status
                    $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE uid = ?");
                    $update_stmt->bind_param("i", $user_id);
                    $update_stmt->execute();

                    // Get user information
                    $stmt = $conn->prepare("SELECT * FROM users WHERE uid = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user_result = $stmt->get_result();

                    if ($user_result->num_rows > 0) {
                        $user = $user_result->fetch_assoc();

                        // Remove verification tokens
                        $del_stmt = $conn->prepare("DELETE FROM login_tokens WHERE user_id = ?");
                        $del_stmt->bind_param("i", $user_id);
                        $del_stmt->execute();

                        // Set session and cookie
                        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['first-name'] = $user['first_name'];
                        $_SESSION['profile'] = USER_IMG . $user['profile_picture'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        setcookie('role', $user['role'], time() + (3 * 60 * 60), "/", "", true, true);

                        $_SESSION['msg'] = "Account Verification has been successful!";
                        header("location: dashboard");
                        exit();
                    }
                }

                $_SESSION['msg'] = "Invalid Verification code!";
                header("location: verify");
                exit();
            }
        }
    } catch (Exception $e) {
        log_error($e->getMessage(), 'error.log');
        $_SESSION['msg'] = "Unexpected Error Occurred";
        header("location: verify");
        exit();
    }
}
?>
