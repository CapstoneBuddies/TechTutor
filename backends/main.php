<?php 
	include_once 'db.php';
	session_start();
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	$current_page = basename($_SERVER['PHP_SELF']);
	$excluded_page = ['index.php', 'login.php', 'signup.php'];
	// Check if user is already logged in
	if (isset($_SESSION['user']) && isset($_COOKIE['role']) && in_array($current_page, $excluded_page)) {
	    header("location: ".BASE."dashboard");
	    exit();
	}
	if (isset($_SESSION['user']) && !isset($_COOKIE['role']) && !isset($_SESSION['status']) ) {
	    header("location: ".BASE."user-logout");
	}

	// Check if user is not logged on but was set to autologin
	if(isset($_COOKIE['remember_me'])) {
		try { 
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			$token = $_COOKIE['remember_me'];

			$token_id = rememberTokenVerifier($token);

			if (isset($token_id)) { 
				// Token matched, retrieve user details
				$user_stmt = $conn->prepare("SELECT uid, role, first_name, last_name, email, profile_picture FROM users WHERE uid = (SELECT user_id FROM login_tokens WHERE token_id = ?)");
				$user_stmt->bind_param("i", $token_id);
				$user_stmt->execute();
				$user = $user_stmt->get_result()->fetch_assoc();
				if ($user) {
					// set session information
					$_SESSION['user'] = $user['uid'];
					$_SESSION['name'] = $user['first_name'].' '.$user['last_name'];
					$_SESSION['first-name'] = $user['first_name'];
					$_SESSION['last-name'] = $user['last_name'];
					$_SESSION['email'] = $user['email'];
					$_SESSION['profile'] = USER_IMG.$user['profile_picture'];
					setcookie('role', $user['role'], time() + (24 * 60 * 60), "/", "", true, true);
				}
			}
			else {
				unset($_COOKIE['remember_me']);
				setcookie('remember_me','',time() - 3600, '/');
				throw new Exception("Invalid Cookie");
			}
		}
		catch (Exception $e) {
			log_error("Remember Me: ".$e->getMessage(),'error.log');
		}
		header("location: ".BASE."dashboard");
		exit();
	}

	function sendVerificationCode(PHPMailer $mail, $email, $code) {
		try {
			$mail->addAddress($email);
			$mail->Subject = "Your Verification Code";
	        $mail->Body = "Your verification code is: <b>$code</b>. It will expire in 3 minutes.";
	        $mail->send();
	        return true;
	    } catch (Exception $e) {
	    	$_SESSION['msg'] = "An error occured, Please try again later!";
	        log_error("Mailer Error: " . $mail->ErrorInfo,'error.log');
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


?>