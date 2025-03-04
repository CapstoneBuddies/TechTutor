<?php 
	$timeout_duration = 3600;
	$current_page = basename($_SERVER['PHP_SELF']);
	
	// Check if user is already logged in
	if (isset($_SESSION['user']) && isset($_COOKIE['role']) && in_array($current_page, ['index.php', 'login.php', 'signup.php'])) {
	    header("location: dashboard");
	    exit();
	}
	if (isset($_SESSION['user']) && !isset($_COOKIE['role'])) {
	    header("location: user-logout");
	}

	// Check if user is not logged on but was set to autologin
	if(!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
		try {
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			$token = $_COOKIE['remember_me'];

			// Retrieve and verify current token
		    $stmt = $conn->prepare("SELECT uid, role, first_name, last_name, email, profile_picture FROM users WHERE uid = (SELECT user_id FROM login_tokens WHERE token = ?)");
		    $stmt->bind_param("s",$token);
		    $stmt->execute();
			$result = $stmt->get_result();
			$user = $result->fetch_assoc();

		    if ($user) {
		    	// set session information
		        $_SESSION['user'] = $user['uid'];
				$_SESSION['name'] = $user['last_name'].', '.$user['first_name'];
				$_SESSION['email'] = $user['email'];
				$_SESSION['role'] = $user['role'];
				$_SESSION['profile'] = USER_IMG.$user['profile_picture'];
				setcookie('role', $user['role'], time() + (24 * 60 * 60), "/", "", true, true);
		    }	
		} catch (Exception $e) {
			log_error("Remember Me: ".$e->getMessage(),'error.log');
		}
		header("location: dashboard");
		exit();
	}
?>