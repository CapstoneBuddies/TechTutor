<?php 
	require_once 'db.php';
		
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	$request_uri =  trim($_SERVER['REQUEST_URI'], "/");

	$link = basename($request_uri);
	$page = basename($_SERVER['PHP_SELF']);
	$excluded_page = ['index.php', 'login.php', 'signup.php', 'verify.php', 'route.php'];
	$excluded_link = ['user-login','user-register','home'];
	$logged_excluded_page = ['dashboard','profile','settings'];
	$approved_link = ['user-logout','user-profile-update','user-change-password','user-deactivate','admin-restrict-user','admin-delete-user','admin-activate-user', 'get-transaction-details', 'get-transactions', 'export-transactions', 'create-payment', 'process-card-payment','add-course','add-subject','toggle-subject-status'];

	// Check if user is logged but trying to access unauthorized link
	if(isset($_SESSION['user']) && in_array($link,$excluded_link) && isset($_COOKIE['role'])) {
		$_SESSION['err-msg'] = "Invalid Link Accessed!";
		log_error('Unauthorized link');
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
		log_error("I run here! cookie clean");
		setcookie('role','',time() - 3600, '/');
		unset($_COOKIE['role']);
		header("location: ".BASE."login");
		exit();
	}
	// Create role cookie user and role session exist
	if(!isset($_COOKIE['role']) && isset($_SESSION['user']) && isset($_SESSION['role']) && $link == 'dashboard') {
		log_error("I run here! cookie clean");
		setcookie('role',$_SESSION['role'],time() + (3 * 60 * 60),"/","",true,true);
		header("location: ".BASE."dashboard");
		exit();
	}

	// Check if user is not logged in but trying to access protected pages
	if (!isset($_SESSION['user']) && in_array($link, $logged_excluded_page)) {
		log_error("was accessed this");
	    $_SESSION['msg'] = "Please log in to access this page.";
	    header("location: ".BASE."login");
	    exit();
	}

	// Check if user is not logged on but was set to autologin
	if(!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
		log_error("I run here!");
		try { 
			global $conn;
			$token = $_COOKIE['remember_me'];

			$token_id = tokenVerifier($token);

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
	 * Format a timestamp into a human-readable time ago string
	 * 
	 * @param string $timestamp The timestamp to format
	 * @return string Formatted time ago string (e.g., "2 hours ago")
	 */
	function getTimeAgo($timestamp) {
	    $datetime = new DateTime($timestamp);
	    $now = new DateTime();
	    $interval = $datetime->diff($now);
	    
	    if ($interval->i < 1) {
	        return "Just now";
	    }
	    
	    if ($interval->i < 60) {
	        return ($interval->i == 1) ? "1 minute ago" : "$interval->i minutes ago";
	    }
	    
	    if ($interval->h < 24) {
	        return ($interval->h == 1) ? "1 hour ago" : "$interval->h hours ago";
	    }
	    
	    if ($interval->d < 30) {
	        return ($interval->d == 1) ? "1 day ago" : "$interval->d days ago";
	    }
	    
	    if ($interval->m < 12) {
	        return ($interval->m == 1) ? "1 month ago" : "$interval->m months ago";
	    }
	    
	    $years = $interval->y;
	    return ($years == 1) ? "1 year ago" : "$years years ago";
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
        global $conn;
        
        try {
            $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, recipient_role, message, link, class_id, icon, icon_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiss", $recipient_id, $recipient_role, $message, $link, $class_id, $icon, $icon_color);
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Failed to send notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for the current user based on their role
     * 
     * @return bool True if notifications were marked as read successfully
     */
    function markAllNotificationsAsRead() {
        global $conn;
        
        if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
            return false;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE (recipient_id = ? OR recipient_role = ? OR recipient_role = 'ALL')");
            $stmt->bind_param("is", $_SESSION['user'], $_SESSION['role']);
            return $stmt->execute();
        } catch (Exception $e) {
            log_error("Failed to mark notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a user based on their role and access level
     * 
     * @param int $user_id The user ID
     * @param string $role The user's role (ADMIN, TECHGURU, TECHKID)
     * @return array Array of notifications
     */
    function getUserNotifications($user_id, $role) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("SELECT n.*, c.class_name FROM notifications n LEFT JOIN class c ON n.class_id = c.class_id WHERE (? = 'ADMIN') OR n.recipient_id = ? OR n.recipient_role = ? OR n.recipient_role = 'ALL' ORDER BY n.created_at DESC LIMIT 50");
            $stmt->bind_param("sii", $role, $user_id, $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            log_error("Failed to get user notifications: " . $e->getMessage());
            return [];
        }
    }
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
?>