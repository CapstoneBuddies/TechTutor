<?php 
	require_once "main.php";

	if($link == 'user-login') {
		login();
	}
	else if($link == 'user-register') {
		register();
	}
	elseif ($link == 'user-logout') {
		logout();
	}
	elseif ($link == 'forgot_password') {
		forgotPassword();
	}
	elseif ($link == 'user-profile-update') {
		updateProfile();
	}
	elseif ($link == 'verify_code') {
		verifyCode();
	}
	elseif ($link == 'resend-verification-code') {
		if(isset($_SESSION['user'])) {
			$mail = getMailerInstance();
			$code = generateVerificationCode($_SESSION['user']);
			sendVerificationCode($mail, $_SESSION['email'], $code);
			$_SESSION['msg'] = "A new code has been sent!";
			header("location:".BASE."verify");
			exit();
		}
		else {
			$_SESSION['msg'] = "Invalid Action";
			header("location: login");
			exit();
		}
	}
	elseif ($link == 'user-deactivate') {
		deleteAccount();
	}
	elseif ($link == 'user-change-password') {
		changeUserPassword();
	}
	elseif ($link == 'admin-restrict-user') {
		log_error('Was run here!');
		$userId = isset($_POST['userId']) ? $_POST['userId'] : null;
		if ($userId) {
			$result = adminUpdateAccount($userId, 'restrict');
			echo json_encode($result);
		} else {
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
		}
	}
	elseif ($link == 'admin-activate-user') {
		$userId = isset($_POST['userId']) ? $_POST['userId'] : null;
		if ($userId) {
			$result = adminUpdateAccount($userId, 'activate');
			echo json_encode($result);
		} else {
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
		}
	}
	elseif ($link == 'admin-delete-user') {
		$userId = isset($_POST['userId']) ? $_POST['userId'] : null;
		if ($userId) {
			$result = adminUpdateAccount($userId, 'delete');
			echo json_encode($result);
		} else {
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
		}
	}
	elseif ($link == 'get-transactions' || $link == 'get-transaction-details' || $link == 'export-transactions') {
		require_once 'transaction_handlers.php';
		// transaction_handlers.php will handle the logic and exit
	}
	elseif ($link == 'create-payment' || $link == 'process-card-payment') {
		if (!isset($_SESSION['user'])) {
			echo json_encode(['success' => false, 'message' => 'Please login to continue']);
			exit;
		}

		require_once 'paymongo_config.php';
		require_once 'payment_handlers.php';
		
		if ($link == 'create-payment') {
			handleCreatePayment($conn);
		} else {
			handleCardPayment($conn);
		}
		exit;
	}
	elseif ($link == 'home') {
		header("location: ".BASE);
	}
	
?>