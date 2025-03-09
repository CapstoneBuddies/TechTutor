<?php 
	require_once 'config.php';
	require_once 'db.php';
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	$request_uri =  trim($_SERVER['REQUEST_URI'], "/");
	
	$link = basename($request_uri);
	$page = basename($_SERVER['PHP_SELF']);
	$excluded_page = ['index.php', 'login.php', 'signup.php', 'verify.php', 'route.php'];
	$excluded_link = ['user-login','user-register','home'];
	$logged_excluded_page = ['dashboard','profile','settings'];
	$approved_link = ['user-logout','user-profile-update','user-change-password','user-deactivate'];

	// Check if user is logged but trying to access unauthorized link
	if(isset($_SESSION['user']) && in_array($link,$excluded_link) && isset($_COOKIE['role'])) {
		$_SESSION['err-msg'] = "Invalid Link Accessed!";
		error_log('Unauthorized link');
		header("location: ".BASE."dashboard");
		exit();
	}

	// Check if user is logged in but trying to access login pages
	if (isset($_SESSION['user']) && isset($_SESSION['role']) && in_array($page, $excluded_page) && !in_array($link,$approved_link)) {
		if(isset($_COOKIE['role'])) {
		    header("location: ".BASE."dashboard");
		    exit();	
		}
		else {
		setcookie('role', $_SESSION['role'], time() + (3 * 60 * 60), "/", "", true, true);
		header("location: ".BASE."dashboard");
	    exit();
		}
	}

	// Clean up role cookie if no session exists
	if(isset($_COOKIE['role']) && !isset($_SESSION['user'])) {
		error_log("I run here! cookie clean");
		setcookie('role','',time() - 3600, '/');
		unset($_COOKIE['role']);
		header("location: ".BASE."login");
		exit();
	}
	// Create role cookie user and role session exist
	if(!isset($_COOKIE['role']) && isset($_SESSION['user']) && isset($_SESSION['role']) && $link == 'dashboard') {
		error_log("I run here! cookie clean");
		setcookie('role',$_SESSION['role'],time() + (3 * 60 * 60),"/","",true,true);
		header("location: ".BASE."dashboard");
		exit();
	}

	// Check if user is not logged in but trying to access protected pages
	if (!isset($_SESSION['user']) && in_array($link, $logged_excluded_page)) {
		error_log("was accessed this");
	    $_SESSION['msg'] = "Please log in to access this page.";
	    header("location: ".BASE."login");
	    exit();
	}

	// Check if user is not logged on but was set to autologin
	if(!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
		error_log("I run here!");
		try { 
			global $conn;
			$token = $_COOKIE['remember_me'];

			$token_id = rememberTokenVerifier($token);


					error_log("Token ID: " . $token_id." Token: ".$token);

			if (isset($token_id)) { 
				// Token matched, retrieve user details
				$user_stmt = $conn->prepare("SELECT u.`uid`, u.`role`, ud.`first_name`, ud.`last_name`, u.`email`, ud.`profile_picture`, u.`is_verified` FROM users u JOIN user_details ud ON ud.`user_id` = u.`uid` WHERE uid = (SELECT user_id FROM login_tokens WHERE token_id = ?)");
				$user_stmt->bind_param("i", $token_id);
				$user_stmt->execute();
				$user = $user_stmt->get_result()->fetch_assoc();
				if ($user) {
					// Check if user is verified
					if($user['is_verified'] == 0) {
						$_SESSION['user'] = $user['uid'];
						$_SESSION['email'] = $user['email'];
						$_SESSION['status'] = $user['is_verified'];
						$_SESSION['msg'] = "Your account is not verified. Please check your email for a verification link.";
						header("Location: verify");
						exit();
					}

					// set session information
					$_SESSION['user'] = $user['uid'];
					$_SESSION['name'] = $user['first_name'].' '.$user['last_name'];
					$_SESSION['first-name'] = $user['first_name'];
					$_SESSION['last-name'] = $user['last_name'];
					$_SESSION['email'] = $user['email'];
					$_SESSION['role'] = $user['role'];
					$_SESSION['profile'] = USER_IMG.$user['profile_picture'];
					setcookie('role', $user['role'], time() + (24 * 60 * 60), "/", "", true, true);
				}
			}
			else {
				setcookie('remember_me','',(time() - 7200));
				unset($_COOKIE['remember_me']);
				throw new Exception("Invalid Cookie");
			}
		}
		catch (Exception $e) {
			log_error("Remember Me: ".$e->getMessage(),'error.log');
		}
		header("location: ".BASE."dashboard");
		exit();
	}

	/**
	 * Normalizes numeric status (1/0) to 'active' or 'inactive'
	 * @param mixed $status The status from database (1 for active, 0 for inactive)
	 * @return string Normalized status ('active' or 'inactive')
	 */
	function normalizeStatus($status) {
	    return $status == 1 ? 'active' : 'inactive';
	}

	/**
	 * Gets the CSS class for status badges
	 * @param mixed $status The status from database (1 for active, 0 for inactive)
	 * @return string CSS class for the status badge
	 */
	function getStatusBadgeClass($status) {
	    $normalizedStatus = normalizeStatus($status);
	    return 'status-badge status-' . $normalizedStatus;
	}

	function sendVerificationCode(PHPMailer $mail, $email, $code) {
		try {
			$mail->addAddress($email);
			$mail->Subject = "Your Verification Code";
			$mail->Body = "
				<p>Hello,</p>
				<p>Thank you for registering with us! To complete your verification process, please use the following verification code:</p>
				<p><b>$code</b></p>
				<p>Please note, this code is valid for the next 3 minutes. If you do not enter the code in time, it will expire, and you will need to request a new one.</p>
				<p>If you did not request this verification code or believe this is an error, please ignore this email.</p>
				<p>Thank you for being part of our community!</p>
				<p>Best regards,<br>Your Company Name</p>
			";
			$mail->send();
			return true;
		}
		catch (Exception $e) {
			$_SESSION['msg'] = "An error occurred, Please try again later!";
			log_error("Mailer Error: " . $mail->ErrorInfo, 'mail.log');
			return false;
		}
	}

	// /verify
	function verify() {
		global $conn;
		if (isset($_GET['token'])) {
			$success = verifyEmailToken($_GET['token']);
			if($success) {
				header("location: login");
				exit();
			}
		}
		$id = $_SESSION['user'];
		$email = $_SESSION['email'];

		if(checkVCodeStatus($id)) {}
		else {
			$_SESSION['msg'] = "A new code has been sent!";
			$mail = getMailerInstance();
			$code = generateVerificationCode($id);
			sendVerificationCode($mail, $email, $code);
		}
	}

	function deleteAccount() {
		global $conn;
		
		if (!isset($_SESSION['user'])) {
			echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
			exit();
		}

		$password = $_POST['password'] ?? '';
		if (empty($password)) {
			echo json_encode(['status' => 'error', 'message' => 'Password is required']);
			exit();
		}

		$userId = $_SESSION['user'];
		
		// Verify password
		$stmt = $conn->prepare("SELECT password FROM users WHERE uid = ?");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		
		if (!$user || !password_verify($password, $user['password'])) {
			echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
			exit();
		}

		// Delete user's profile picture if it exists
		$stmt = $conn->prepare("SELECT profile_picture FROM user_details WHERE user_id = ?");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		$profile = $result->fetch_assoc();
		
		if ($profile && $profile['profile_picture'] !== 'default.png') {
			$picturePath = ROOT_PATH . '/assets/img/users/' . $profile['profile_picture'];
			if (file_exists($picturePath)) {
				unlink($picturePath);
			}
		}

		// Start transaction
		$conn->begin_transaction();
		try {
			// Update user status to 2 and set email to 'deleted'
			$email = 'deleted';
			$counter = 1;
			while (true) {
				$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
				$deletedEmail = $email . $counter;
				$stmt->bind_param("s", $deletedEmail);
				$stmt->execute();
				$result = $stmt->get_result();
				$count = $result->fetch_row()[0];
				if ($count == 0) {
					break;
				}
				$counter++;
			}
			$stmt = $conn->prepare("UPDATE users SET status = 2, email = ? WHERE uid = ?");
			$stmt->bind_param("si", $deletedEmail, $userId);
			$stmt->execute();
			
			$conn->commit();
			
			// Clear session and cookies
			session_destroy();
			if (isset($_COOKIE['role'])) {
				setcookie('role', '', time() - 3600, '/');
			}
			if (isset($_COOKIE['remember_me'])) {
				setcookie('remember_me', '', time() - 3600, '/');
			}

			// Set session message for logout and alert
			$_SESSION['msg'] = "Your account has been deleted successfully.";
			
			// Alert message for account deletion
			echo json_encode(['status' => 'success', 'alert' => 'We\'re sad to see you go—your account has been deleted.']);
			exit();
		} catch (Exception $e) {
			$conn->rollback();
			error_log("Delete account error: " . $e->getMessage());
			echo json_encode(['status' => 'error', 'message' => 'Failed to delete account']);
			exit();
		}
	}

	function changeUserPassword() {
		global $conn;
		
		if (!isset($_SESSION['user'])) {
			echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
			exit();
		}

		$currentPassword = $_POST['current_password'] ?? '';
		$newPassword = $_POST['new_password'] ?? '';
		$confirmPassword = $_POST['confirm_password'] ?? '';

		if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
			echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
			exit();
		}

		if ($newPassword !== $confirmPassword) {
			echo json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
			exit();
		}

		if (strlen($newPassword) < 8) {
			echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
			exit();
		}

		$userId = $_SESSION['user'];
		
		// Verify current password
		$stmt = $conn->prepare("SELECT password FROM users WHERE uid = ?");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();

		if (!$user || !password_verify($currentPassword, $user['password'])) {
			echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
			exit();
		}

		// Update password
		$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
		$stmt = $conn->prepare("UPDATE users SET password = ? WHERE uid = ?");
		$stmt->bind_param("si", $hashedPassword, $userId);
		
		if ($stmt->execute()) {
			echo json_encode(['status' => 'success']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
		}
		exit();
	}
?>