<?php 
	require_once "main.php";
	require_once 'management/user_management.php';

	if($link == 'user-login') {
		login();
	}
	else if($link == 'user-register') {
		register();
	}
	elseif ($link == 'user-logout') {
		logout();
	}
	elseif ($link == 'forgot-password') {
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
		$userId = isset($_POST['userId']) ? $_POST['userId'] : null;
		if ($userId) {
			$result = adminUpdateAccount($userId, 'restrict');
			http_response_code(200);
			echo json_encode($result);
		} else {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
		}
		exit();
	}
	elseif ($link == 'admin-activate-user') {
		$userId = isset($_POST['userId']) ? $_POST['userId'] : null;
		if ($userId) {
			$result = adminUpdateAccount($userId, 'activate');
			http_response_code(200);
			echo json_encode($result);
		} else {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
		}
		exit();
	}
	elseif ($link == 'admin-delete-user') {
		$userId = isset($_POST['userId']) ? $_POST['userId'] : null;
		if ($userId) {
			$result = adminUpdateAccount($userId, 'delete');
			http_response_code(200);
			echo json_encode($result);
		} else {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
		}
		exit();
	}
	// elseif ($link == 'add-course' || $link == 'toggle-subject-status' || $link == 'add-subject') {
	// 	// Redirect old routes to new course handler
	// 	$_POST['action'] = $link;
	// 	require_once 'admin/course_handler.php';
	// 	exit();
	// }
	elseif ($link == 'get-transactions' || $link == 'get-transaction-details' || $link == 'export-transactions') {
		require_once 'transaction_handlers.php';
		// transaction_handlers.php will handle the logic and exit
	}
	elseif ($link == 'create-payment' || $link == 'process-card-payment') {
		if (!isset($_SESSION['user'])) {
			http_response_code(401);
			echo json_encode(['success' => false, 'message' => 'Please login to continue']);
			exit;
		}

		require_once 'handler/paymongo_config.php';
		require_once 'handler/payment_handlers.php';
		
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
	
	else {
		// If no route matches, return 404
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'Route not found']);
		exit();
	}
?>