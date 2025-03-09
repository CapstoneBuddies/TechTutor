<?php 
	require_once "config.php";
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
	elseif ($link == 'user-profile-update') {
		updateProfile();
	}
	elseif ($link == 'verify_code') {
		verifyCode();
	}
	elseif ($link == 'home') {
		header("location: ".BASE);
	}
?>