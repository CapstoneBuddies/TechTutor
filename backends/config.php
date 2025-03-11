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
	if(strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
		define('BASE', '/capstone/');
	} else {
		define('BASE', '/');
	}

	define('CSS', BASE.'assets/css/');
	define('JS', BASE.'assets/js/');
	define('IMG', BASE.'assets/img/');
	define('USER_IMG', BASE.'assets/img/users/');
	define('CLASS_IMG', BASE.'assets/img/class/');

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
	
	define('SMTP_HOST',$_ENV['MAIL_HOST']);
	define('SMTP_USER',$_ENV['MAIL_USER']);
	define('SMTP_PASSWORD',$_ENV['MAIL_PASS']);
	define('SMTP_PORT',$_ENV['MAIL_PORT']);

	define('SMTP_USER_2',$_ENV['MAIL_USER_2']);
	define('SMTP_PASSWORD_2',$_ENV['MAIL_PASS_2']);

	// Set Default time to Philippines
	date_default_timezone_set('Asia/Manila');


	//Setting Up error_log setup
	ini_set('error_log', LOG_PATH.'error.log');
	ini_set('log_errors', 1);
	ini_set('display_errors', 0);

	// Initializing PHPMailer
	$mail = new PHPMailer(true);
	$mail->isSMTP();
	$mail->Host = SMTP_HOST;
	$mail->SMTPAuth = true;
	$mail->Username = SMTP_USER;
	$mail->Password = SMTP_PASSWORD;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	$mail->Port = SMTP_PORT;
	$mail->setFrom(SMTP_USER, SMTP_HOST);
	$mail->isHTML(true);



	/* IMPORTANT FUNCTIONS */
	function log_error($message, $destination = 'tracking.log') {
		 $file_path = LOG_PATH . $destination;

		  if (!file_exists($file_path)) {
        // Attempt to create the file if it doesn't exist
	        touch($file_path);  // Create the file if it does not exist
	        chmod($file_path, 0666);  // Make the file writable by PHP (optional, for permission issues)
	    }
    	error_log(date("Y-m-d H:i:s")." ".$message."\n", 3, $file_path);
	}

	// provide a clone for mailing instance
	function getMailerInstance() {
	    global $mail;
	    return clone $mail; // Returns a fresh copy of $mail
	}
	function getSecondaryMailInstance() {
	    $mail = new PHPMailer(true);
		$mail->isSMTP();										 
		$mail->Host	 = SMTP_HOST;				 
		$mail->SMTPAuth = true;							 
		$mail->Username = SMTP_USER_2;				 
		$mail->Password = SMTP_PASSWORD_2;					 
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;							 
		$mail->Port	 = SMTP_PORT; 
		$mail->setFrom(SMTP_USER_2, SMTP_HOST);
		$mail->isHTML(true);
	    return $mail;
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
			log_error("Mailer Error: " . $mail->ErrorInfo,'mail.log');
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
?>