<?php  
	// Initialize session at the very beginning
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}

	// File system root path (optional, useful for includes or file uploads)
	define('ROOT_PATH', realpath(__DIR__ . '/..'));

	require_once ROOT_PATH.'/assets/vendor/autoload.php';

	use Dotenv\Dotenv;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;

	$dotenv = Dotenv::createImmutable(__DIR__);
	$dotenv->load();

	// Base URL configuration
	if(strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
		define('BASE', '/capstone-1/');
	} else {
		define('BASE', '/');
	}

	define('CSS', BASE.'assets/css/');
	define('JS', BASE.'assets/js/');
	define('IMG', BASE.'assets/img/');
	define('CLASS_IMG', BASE.'assets/img/class/');
	define('SUBJECT_IMG', BASE.'assets/img/subjects/');
	define('USER_IMG', BASE.'assets/img/users/');
	define('BACKEND', ROOT_PATH.'/backends/management/');
	define('SITE_NAME', 'Tech Tutor'); 

	// Define LOG_PATH based on fixed ROOT_PATH
	define('LOG_PATH', ROOT_PATH . '/logs/');

	// Get .env Information
	define('DB_HOST',$_ENV['DB_HOST']);
	define('DB_USER',$_ENV['DB_USER']);
	define('DB_PASSWORD',$_ENV['DB_PASS']);
	define('DB_NAME',$_ENV['DB_NAME']);
	define('DB_PORT',$_ENV['DB_PORT']);

	define('BBB_API_URI',$_ENV['BBB_URI']);
	define('BBB_SECRET',$_ENV['BBB_SECRET']);

	define('BBB_WEBHOOK_URL',$_ENV['BBB_WEBHOOK_URL']);
	
	define('SMTP_HOST',$_ENV['MAIL_HOST']);
	define('SMTP_USER',$_ENV['MAIL_USER']);
	define('SMTP_PASSWORD',$_ENV['MAIL_PASS']);
	define('SMTP_PORT',$_ENV['MAIL_PORT']);

	define('SMTP_USER_2',$_ENV['MAIL_USER_2']);
	define('SMTP_PASSWORD_2',$_ENV['MAIL_PASS_2']);

	// Set Default time to Philippines
	date_default_timezone_set('Asia/Manila');


	//Setting Up error_log setup
	ini_set('error_log', LOG_PATH.'error.log' );
	ini_set('log_errors', 1);
	ini_set('display_errors', 0);
	error_reporting(E_ALL);

	// Initializing PHPMailer
	$mail = new PHPMailer(true);
	$mail->isSMTP();
	$mail->Host = SMTP_HOST;
	$mail->SMTPAuth = true;
	$mail->Username = SMTP_USER;
	$mail->Password = SMTP_PASSWORD;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	$mail->Port = SMTP_PORT;
	$mail->isHTML(true);



	/* IMPORTANT FUNCTIONS */
	function log_error($message, $type = 1) {
	    // Determine log filename
	    if ($type === 'meeting' || $type === 7) {
	        $path = LOG_PATH . 'meeting.log';
	    } 
	    elseif ($type === 'webhooks' || $type === 10 || $type === 'webhooks-debug') {
	        $path = LOG_PATH . 'webhook.log';
	    }
	    elseif (isset($_SESSION['email'])) {
		    if ($type === 'analytics' || $type === 5) {
		        $path = LOG_PATH .'analytics/'. $_SESSION['email'] . '-analytics.log';
		    } else {
		        $path = LOG_PATH . $_SESSION['email'] . '.log';
		    } 
	    } 
		else {
		    $path = LOG_PATH . 'unknown.log';
		}

	    // Log type mappings
	    $logTypes = [
	        1 => 'general',
	        2 => 'database',
	        3 => 'mail',
	        4 => 'security',
	        5 => 'analytics',
	        6 => 'front',
	        7 => 'meeting',
	        8 => 'info',
	        9 => 'class',
	        10 => 'webhooks',
	    ];

	    // Alias mapping for string log types
	    $logAliases = array_flip($logTypes); // Reverse mapping for easy lookup

	    // Convert string type to corresponding number if applicable
	    if (is_string($type) && isset($logAliases[$type])) {
	        $type = $logAliases[$type];
	    }

	    // Ensure type is valid, otherwise default to 'general' (1)
	    $logType = $logTypes[$type] ?? 'general';

	    // Get user IP
	    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

	    // Current timestamp
	    $date = date('Y-m-d H:i:s');

	    // Log entry format
	    $logEntry = "[({$date})::{$ip}] TYPE={$logType} {$message}\n";

	    // Append log entry to file
	    file_put_contents($path, $logEntry, FILE_APPEND);
	}


	// Provide a clone for mailing instance with dynamic "From" name
	function getMailerInstance($fromName = "The Techtutor Team") {
	    global $mail;
	    // Set a different "From" name for the cloned instance
	    $cloneMail = clone $mail;
	    $cloneMail->setFrom(SMTP_USER, $fromName); // Dynamically set the "From" name
	    return $cloneMail;
	}

	// Sending of Email Verification
	function sendVerificationEmail(PHPMailer $mail, $email, $token, $name) {
		$verification_link = "https://".$_SERVER['SERVER_NAME']."/verify?token=".urlencode($token);

		try {
			$mail->addAddress($email);
			$mail->Subject = "Verify Your Email Address";
			$mail->Body = "
				<p>Hello, '$name'</p>
				<p>Thank you for registering with us! To complete the registration process and activate your account, please verify your email address by clicking the link below:</p>
				<p><a href='$verification_link' style='color: #4CAF50;'>Verify Your Email</a></p>
				<p>If you did not create an account with us, please ignore this email. Your email address will not be used for any other purpose.</p>
				<p>If you encounter any issues or have questions, feel free to contact our support team.</p>
				<p>Best regards,<br>Techtutor</p>
				<p><i>This is an automated message. Please do not reply.</i></p>
			";

			$mail->send();
			return true;
		} catch (Exception $e) {
			log_error("Mailer Error: " . $mail->ErrorInfo,'mail');
			return false;
		}
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
			<p>Best regards,<br>Techtutor</p>
			";
			$mail->send();
			return true;
		}
		catch (Exception $e) {
			$_SESSION['msg'] = "An error occurred, Please try again later!";
			log_error("Mailer Error: " . $mail->ErrorInfo, 'mail');
			return false;
		}
	}
?>