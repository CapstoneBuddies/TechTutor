<?php
/**
 * Get users by role with pagination
 */
function getUserByRole($role, $page = 1, $limit = 8) {
    global $conn;
    
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT u.uid, u.first_name, u.last_name, u.email, u.profile_picture, IF(u.role = 'TECHKID', (SELECT COUNT(*) FROM class_schedule cs WHERE cs.user_id = u.uid AND cs.role = 'STUDENT'), (SELECT COUNT(*) FROM class c WHERE c.tutor_id = u.uid)) AS `num_classes`, u.status, u.last_login FROM users u WHERE u.role = ? AND u.status IN (0,1) ORDER BY u.last_name, u.first_name  LIMIT ? OFFSET ?;");

    $stmt->bind_param("sii", $role, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } 
    return [];
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

    $stmt = $conn->prepare("INSERT INTO login_tokens(verification_code, expiration_date, user_id, type) VALUES(?,?,?,'email_verification')");
    $stmt->bind_param("ssi", $code, $expiresAt, $userId);
    $stmt->execute();
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

/**
 * Search users by name or email across all roles
 */
function searchUsers($search) {
    global $conn;
    
    $search = "%{$search}%";
    
    $stmt = $conn->prepare("SELECT u.*, 
                           IF(u.role = 'TECHKID', 
                              (SELECT COUNT(*) FROM class_schedule cs WHERE cs.user_id = u.uid AND cs.role = 'STUDENT'), 
                              (SELECT COUNT(*) FROM class c WHERE c.tutor_id = u.uid)) AS `num_classes`,
                           u.status, u.last_login 
                           FROM users u 
                           WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?) 
                           AND u.status IN (0,1) 
                           ORDER BY u.last_name, u.first_name");
    
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

/**
 * Search users by name or email within a specific role
 */
function searchUsersByRole($role, $search) {
    global $conn;
    
    $search = "%{$search}%";
    
    $stmt = $conn->prepare("SELECT u.*, 
                           IF(u.role = 'TECHKID', 
                              (SELECT COUNT(*) FROM class_schedule cs WHERE cs.user_id = u.uid AND cs.role = 'STUDENT'), 
                              (SELECT COUNT(*) FROM class c WHERE c.tutor_id = u.uid)) AS `num_classes`,
                           u.status, u.last_login 
                           FROM users u 
                           WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?) 
                           AND u.role = ? 
                           AND u.status IN (0,1) 
                           ORDER BY u.last_name, u.first_name");
    
    $stmt->bind_param("sssss", $search, $search, $search, $search, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

/**
 * Generate password reset token
 */
function generatePasswordResetToken($user_id) {
    global $conn;
    $token = bin2hex(random_bytes(32));
    $hashed_token = password_hash($token, PASSWORD_DEFAULT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+24 hours")); // 24-hour expiry for password resets

    // Delete any existing reset tokens for this user
    $stmt = $conn->prepare("DELETE FROM login_tokens WHERE user_id = ? AND type = 'reset'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Create new reset token
    $stmt = $conn->prepare("INSERT INTO login_tokens (user_id, type, token, expiration_date) VALUES (?, 'reset', ?, ?)");
    $stmt->bind_param("iss", $user_id, $hashed_token, $expires_at);
    if (!$stmt->execute()) {
        log_error($stmt->error, 'database_error.log');
        return null;
    }
    return $token;
}

/**
 * Forgot Password
 */
function forgotPassword() {
    global $conn;

    if (isset($_POST['send_reset_code'])) {
        $email = $_POST['email'] ?? '';

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['msg'] = "Please enter a valid email address.";
            header("location: ".BASE."forgot");
            exit();
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT uid, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['msg'] = "No account found with that email address.";
            header("location: ".BASE."forgot");
            exit();
        }

        $user = $result->fetch_assoc();
        $user_id = $user['uid'];
        $first_name = htmlspecialchars($user['first_name']); // Prevent XSS

        // Generate verification token
        $token = generatePasswordResetToken($user_id);
        $reset_link = "https://".$_SERVER['SERVER_NAME']."/reset-password?token=" . $token . "&email=" . urlencode($email);

        // Email setup
        $mail = getMailerInstance();
        $mail->addAddress($email);
        $mail->Subject = 'TechTutor | Password Reset Request';
        
        // Styled email body (HTML format)
        $mail->isHTML(true);
        $mail->AddEmbeddedImage(__DIR__.'/../assets/img/stand_alone_logo.png','logo','TechTutor Logo');
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; padding: 20px;'>
                <div style='text-align: center;'>
                    <img src='cid:logo' alt='TechTutor Logo' style='max-width: 150px; margin-bottom: 10px;'>
                </div>
                <h2 style='color: #007bff; text-align: center;'>Password Reset Request</h2>
                <p>Dear <strong>$first_name</strong>,</p>
                <p>We received a request to reset your password. Click the button below to set a new password:</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='$reset_link' style='background-color: #007bff; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;'>Reset Password</a>
                </div>
                <p>If you did not request this, please ignore this email or contact our support team.</p>
                <hr style='border: 0; border-top: 1px solid #ddd; margin: 20px 0;'>
                <p style='text-align: center; font-size: 12px; color: #777;'>This is an automated email from TechTutor. Please do not reply.</p>
            </div>
        ";

        // Send email
        if (!$mail->send()) {
            log_error("Mailer Error: " . $mail->ErrorInfo, 'email.log');
            $_SESSION['msg'] = "Failed to send reset email. Please try again later.";
            header("location: ".BASE."forgot");
            exit();
        }

        $_SESSION['msg'] = "A reset link has been sent to your email address.";
        header("location: ".BASE."login");
        exit();
    }
}

function updateProfile() {
        global $conn;
        $response = array('success' => false, 'message' => '');

        if (!isset($_SESSION['user'])) {
            $response['message'] = 'Not authorized';
            echo json_encode($response);
            exit();
        }

        $userId = $_SESSION['user'];

        // Handle profile picture removal via POST parameter
        if (isset($_POST['removeProfilePicture']) && $_POST['removeProfilePicture'] === 'true') {
            // Get current profile picture
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentPicture = $result->fetch_assoc()['profile_picture'];
            $stmt->close();
            
            // Delete current picture if it's not the default
            if ($currentPicture !== 'default.jpg') {
                $picturePath = ROOT_PATH . '/assets/img/users/' . $currentPicture;
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
                
                // Reset to default picture in database
                $defaultPic = 'default.jpg';
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE uid = ?");
                $stmt->bind_param("si", $defaultPic, $userId);
                if (!$stmt->execute()) {
                    error_log("Failed to reset profile picture in database: " . $stmt->error);
                    $response['message'] = 'Failed to reset profile picture';
                    echo json_encode($response);
                    exit();
                }
                $stmt->close();
                
                $_SESSION['profile'] = USER_IMG.'default.jpg';
                $response['success'] = true;
                $response['message'] = 'Profile picture removed successfully';
                echo json_encode($response);
                exit();
            }
            else {
                $response['message'] = 'Failed to reset profile picture';
                echo json_encode($response);
                exit();
            }
        }

        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : null;
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : null;
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $countryCode = isset($_POST['countryCode']) ? trim($_POST['countryCode']) : '+63';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

        // Handle profile picture upload
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profilePicture'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed extensions
            $allowedExt = array('jpg', 'jpeg', 'png', 'gif');
            
            // Validate file type and size
            if (!in_array($fileExt, $allowedExt)) {
                $response['message'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedExt);
                echo json_encode($response);
                exit();
            }
            
            if ($fileSize > 5242880) { // 5MB in bytes
                $response['message'] = 'File size too large. Maximum size: 5MB';
                echo json_encode($response);
                exit();
            }
            
            // Create new filename with user ID
            $newFileName = $userId . '.' . $fileExt;
            $uploadPath = ROOT_PATH . '/assets/img/users/' . $newFileName;
            
            // Get current profile picture
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentPicture = $result->fetch_assoc()['profile_picture'];
            $stmt->close();
            
            // Delete old profile picture if it's not the default
            if ($currentPicture !== 'default.jpg') {
                $oldPicturePath = ROOT_PATH . '/assets/img/users/' . $currentPicture;
                if (file_exists($oldPicturePath)) {
                    unlink($oldPicturePath);
                }
            }
            
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Update profile picture in database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE uid = ?");
                $stmt->bind_param("si", $newFileName, $userId);
                if (!$stmt->execute()) {
                    error_log("Failed to update profile picture in database: " . $stmt->error);
                    $response['message'] = 'Failed to update profile picture in database';
                    echo json_encode($response);
                    exit();
                }
                $stmt->close();
                
                $_SESSION['profile'] = USER_IMG . $newFileName;
            } else {
                log_error("Failed to move uploaded file from $fileTmp to $uploadPath", 'database_error.log');
                $response['message'] = 'Failed to upload profile picture';
                echo json_encode($response);
                exit();
            }
        }

        // Validate first name and last name if provided
        if ($firstName !== null && (strlen($firstName) < 2 || strlen($firstName) > 50)) {
            $response['message'] = 'First name must be between 2 and 50 characters';
            echo json_encode($response);
            exit();
        }

        if ($lastName !== null && (strlen($lastName) < 2 || strlen($lastName) > 50)) {
            $response['message'] = 'Last name must be between 2 and 50 characters';
            echo json_encode($response);
            exit();
        }

        // Validate phone number if provided
        if (!empty($phone)) {
            // Remove any existing hyphens for validation
            $cleanPhone = str_replace('-', '', $phone);
            
            // Check if it's exactly 10 digits
            if (!preg_match('/^[0-9]{10}$/', $cleanPhone)) {
                $response['message'] = 'Phone number must be exactly 10 digits';
                echo json_encode($response);
                exit();
            }

            // Check if country code is valid (starts with + and has 1-3 digits)
            if (!preg_match('/^\+[0-9]{1,3}$/', $countryCode)) {
                $response['message'] = 'Invalid country code';
                echo json_encode($response);
                exit();
            }

            // Format phone number with hyphens and country code
            $phone = $countryCode . substr($cleanPhone, 0, 3) . '-' . 
                    substr($cleanPhone, 3, 3) . '-' . 
                    substr($cleanPhone, 6);
        }

        // Validate address if provided
        if (!empty($address) && strlen($address) > 100) {
            $response['message'] = 'Address must not exceed 100 characters';
            echo json_encode($response);
            exit();
        }

        // Update user details
        $query = "UPDATE users SET first_name = ?, last_name = ?, address = ?, contact_number = ? WHERE uid = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $firstName, $lastName, $address, $phone, $userId);

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['name'] = $firstName . ' ' . $lastName;
            $_SESSION['address'] = $address;
            $_SESSION['phone'] = $phone;

            $response['success'] = true;
            $response['message'] = 'Profile updated successfully';
        } else {
            error_log("Profile update failed: " . $stmt->error);
            $response['message'] = 'Failed to update profile: ' . $stmt->error;
        }

        $stmt->close();
        echo json_encode($response);
        exit();
    }

    function deactivateAccount($userId) {
        global $conn;
        $response = array('success' => false, 'message' => '');

        // Check if user exists and is active
        $stmt = $conn->prepare("SELECT status FROM users WHERE uid = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['message'] = 'User not found';
            return $response;
        }
        
        $user = $result->fetch_assoc();
        if (!$user['status']) {
            $response['message'] = 'Account is already inactive';
            return $response;
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Set status to 0 (inactive)
            $stmt = $conn->prepare("UPDATE users SET status = 0 WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to deactivate account");
            }
            
            // Commit transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Account deactivated successfully';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Account deactivation failed: " . $e->getMessage());
            $response['message'] = 'Failed to deactivate account: ' . $e->getMessage();
        }
        
        $stmt->close();
        return $response;
    }

?>
