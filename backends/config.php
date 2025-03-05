<?php  
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
	if($_SERVER['HTTP_HOST'] == 'localhost') {
	define('BASE', '/capstone/');
	}
	else {
	define('BASE', '/');
	}
	define('CSS', 'assets/css/');
	define('JS', 'assets/js/');
	define('USER_IMG', 'assets/img/users/');
	define('CLASS_IMG', 'assets/img/class/');

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

	// Set Default time to Philippines
	date_default_timezone_set('Asia/Manila');


	//Setting Up error_log setup
	ini_set('error_log', LOG_PATH.'error.log');
	ini_set('log_errors', 1);
	ini_set('display_errors', 0);

	// Initializing PHPMailer
	$mail = new PHPMailer(true);
	$mail->isSMTP();
	$mail->Host = MAIL_HOST;
	$mail->SMTPAuth = true;
	$mail->Username = MAIL_USER;
	$mail->Password = MAIL_PASS;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
	$mail->Port = MAIL_PORT;
	$mail->setFrom(MAIL_USER, MAIL_HOST);
	$mail->isHTML(true);

	// SET
	if (session_status() == PHP_SESSION_NONE) {
    	session_start();
	}
	
	function log_error($message, $destination) {
		 $file_path = LOG_PATH . $destination;

		  if (!file_exists($file_path)) {
        // Attempt to create the file if it doesn't exist
	        touch($file_path);  // Create the file if it does not exist
	        chmod($file_path, 0666);  // Make the file writable by PHP (optional, for permission issues)
	    }
    	error_log($message."\n", 3, $file_path);
	}

	// provide a clone for mailing instance
	function getMailerInstance() {
	    global $mail;
	    return clone $mail; // Returns a fresh copy of $mail
	}

	// Sending of Email Verification
	function sendVerificationEmail(PHPMailer $mail, $email, $token) {
		$verification_link = "https://".$_SERVER['SERVER_NAME']."/verify?token=$token";
		$mail = getMailerInstance();

		try {
			$mail->addAddress($email);
			$mail->Subject = "Verify Your Email";
			$mail->Body = "Click the link below to verify your email:<br><a href='$verification_link'>$verification_link</a>";

			$mail->send();
			return true;
		} catch (Exception $e) {
			return "Mailer Error: " . $mail->ErrorInfo;
		}
	}
?>