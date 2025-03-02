<?php  
	// File system root path (optional, useful for includes or file uploads)
	define('ROOT_PATH', realpath(__DIR__ . '/..'));
	

	require_once ROOT_PATH.'/assets/vendor/autoload.php';

	use Dotenv\Dotenv;

	$dotenv = Dotenv::createImmutable(__DIR__);
	$dotenv->load();

	// Base URL configuration
	define('CSS', '/assets/css/');
	define('JS', '/assets/js/');
	define('IMG', '/assets/images/');

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

	$timeout_duration = 3600;
	$current_page = basename($_SERVER['PHP_SELF']);

	if (session_status() == PHP_SESSION_NONE) {
    	session_start();
	}

	// Check if user is already logged in
	if (isset($_SESSION['user']) && ($current_page == 'login.php' || $current_page == 'signup.php') ) {
	    header("location: dashboard");
	    exit();
	}

	// Check if user is not logged on but was set to autologin
	if(!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
		try {
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			$token = $_COOKIE['remember_me'];

			// Retrieve and verify current token
		    $stmt = $conn->prepare("SELECT uid, role, first_name, last_name, email FROM users WHERE uid = (SELECT user_id FROM login_tokens WHERE token = ?)");
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
		    }	
		} catch (Exception $e) {
			log_error("Remember Me: ".$e->getMessage(),'error.log');
		}
	}


	function log_error($message, $destination) {
		 $file_path = LOG_PATH . $destination;

		  if (!file_exists($file_path)) {
        // Attempt to create the file if it doesn't exist
	        touch($file_path);  // Create the file if it does not exist
	        chmod($file_path, 0666);  // Make the file writable by PHP (optional, for permission issues)
	    }

    	error_log("\n" . $message, 3, $file_path);
	}
	
?>