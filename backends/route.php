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
	elseif ($link == 'user-deactivate') {
		deleteAccount();
	}
	elseif ($link == 'user-change-password') {
		changeUserPassword();
	}
	elseif ($link == 'home') {
		header("location: ".BASE);
	}
?>