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
	$approved_link = ['user-logout','user-profile-update','user-change-password','user-deactivate','admin-restrict-user','admin-delete-user','admin-activate-user'];

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
				$user_stmt = $conn->prepare("SELECT u.`uid`, u.`role`, u.`first_name`, u.`last_name`, u.`email`, u.`profile_picture`, u.`is_verified` FROM users u WHERE uid = (SELECT user_id FROM login_tokens WHERE token_id = ?)");
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
		$mail = getMailerInstance();
		$code = generateVerificationCode($id);
		sendVerificationCode($mail, $email, $code);
		$_SESSION['msg'] = "A new code has been sent!";
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
			$countQuery = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email LIKE 'deleted%'");
			$countQuery->execute();
			$countResult = $countQuery->get_result()->fetch_assoc();
			if(empty($countResult)) {
				$counter = null;
			}
			else {
				$counter = $countResult['count'] + 1;
			}
			// Create deleted email format
			$deletedEmail = "deleted" . $counter;

			$stmt = $conn->prepare("UPDATE users SET status = 2, email = ?, password='' WHERE uid = ?");
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

	function adminUpdateAccount($userId, $action) {
		global $conn;
		
		try {
			// Check if user exists and is not an admin
			$checkUser = $conn->prepare("SELECT email, status FROM users WHERE uid = ? AND role != 'ADMIN'");
			$checkUser->bind_param("i", $userId);
			$checkUser->execute();
			$result = $checkUser->get_result();
			
			if ($result->num_rows === 0) {
				return ["success" => false, "message" => "User not found or cannot modify admin account"];
			}
			
			$userData = $result->fetch_assoc();
			
			switch($action) {
				case 'restrict':
					// Update user status to inactive (0)
					$stmt = $conn->prepare("UPDATE users SET status = 0 WHERE uid = ?");
					$stmt->bind_param("i", $userId);
					$success = $stmt->execute();
					
					if ($success) {
						return ["success" => true, "message" => "Account restricted successfully"];
					} else {
						return ["success" => false, "message" => "Failed to restrict account: " . $conn->error];
					}
					break;
					
				case 'activate':
					// Update user status to active (1)
					$stmt = $conn->prepare("UPDATE users SET status = 1 WHERE uid = ?");
					$stmt->bind_param("i", $userId);
					$success = $stmt->execute();
					
					if ($success) {
						return ["success" => true, "message" => "Account activated successfully"];
					} else {
						return ["success" => false, "message" => "Failed to activate account: " . $conn->error];
					}
					break;
					
				case 'delete':
					// Get count of deleted emails to handle repetition
					$countQuery = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email LIKE 'deleted%'");
					$countQuery->execute();
					$countResult = $countQuery->get_result()->fetch_assoc();
					$counter = $countResult['count'] + 1;
					
					// Create deleted email format
					$deletedEmail = "deleted" . $counter;
					
					// Update user record (status = 2 for deleted, modify email)
					$stmt = $conn->prepare("UPDATE users SET status = 2, email = ?, password='' WHERE uid = ?");
					$stmt->bind_param("si", $deletedEmail, $userId);
					$success = $stmt->execute();
					
					if ($success) {
						return ["success" => true, "message" => "Account deleted successfully"];
					}
					break;
					
				default:
					return ["success" => false, "message" => "Invalid action specified"];
			}
			
			return ["success" => false, "message" => "Failed to update account"];
			
		} catch (Exception $e) {
			return ["success" => false, "message" => "Error: " . $e->getMessage()];
		}
	}

	/**
	 * Send a notification to a user or role
	 * 
	 * @param int|null $recipient_id The user ID to send to, or null for role-wide notifications
	 * @param string $recipient_role The role to send to (ADMIN, TECHGURU, TECHKID, ALL)
	 * @param string $message The notification message
	 * @param string|null $link Optional link for the notification
	 * @param int|null $class_id Optional class ID if notification is related to a class
	 * @param string $icon Bootstrap icon class (e.g., 'bi-person-check')
	 * @param string $icon_color Bootstrap color class (e.g., 'text-success')
	 * @return bool True if notification was sent successfully
	 */
	function sendNotification($recipient_id, $recipient_role, $message, $link = null, $class_id = null, $icon = 'bi-bell', $icon_color = 'text-primary') {
	    return insertNotification($recipient_id, $recipient_role, $message, $link, $class_id, $icon, $icon_color);
	}
	/**
	 * Example
	 * sendNotification(
	    $userId,              // specific user ID
	    'TECHKID',           // user's role
	    'Your assignment has been graded!',
	    '/dashboard/grades',  // link to grades
	    $classId,            // related class ID
	    'bi-check-circle',   // Bootstrap icon
	    'text-success'       // Bootstrap color
		);
	**/ 

	/**
	 * Mark all notifications as read for the current user based on their role
	 * 
	 * @return bool True if notifications were marked as read successfully
	 */
	function markAllNotificationsAsRead() {
	    if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
	        return false;
	    }
	    return updateNotificationsReadStatus($_SESSION['user'], $_SESSION['role']);
	}

	/**
	 * Get notifications for a user based on their role and access level
	 * 
	 * @param int $user_id The user ID
	 * @param string $role The user's role (ADMIN, TECHGURU, TECHKID)
	 * @return array Array of notifications
	 */
	function getUserNotifications($user_id, $role) {
	    return fetchUserNotifications($user_id, $role);
	}

	/**
	 * Format a timestamp into a human-readable time ago string
	 * 
	 * @param string $timestamp The timestamp to format
	 * @return string Formatted time ago string (e.g., "2 hours ago")
	 */
	function getTimeAgo($timestamp) {
	    $time_ago = strtotime($timestamp);
	    $current_time = time();
	    $time_difference = $current_time - $time_ago;
	    
	    $seconds = $time_difference;
	    $minutes = round($seconds / 60);
	    $hours = round($seconds / 3600);
	    $days = round($seconds / 86400);
	    $weeks = round($seconds / 604800);
	    $months = round($seconds / 2629440);
	    $years = round($seconds / 31553280);
	    
	    if ($seconds <= 60) {
	        return "Just now";
	    } else if ($minutes <= 60) {
	        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
	    } else if ($hours <= 24) {
	        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
	    } else if ($days <= 7) {
	        return ($days == 1) ? "Yesterday" : "$days days ago";
	    } else if ($weeks <= 4.3) {
	        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
	    } else if ($months <= 12) {
	        return ($months == 1) ? "1 month ago" : "$months months ago";
	    } else {
	        return ($years == 1) ? "1 year ago" : "$years years ago";
	    }
	}
?>