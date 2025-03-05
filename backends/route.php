<?php 
	include "main.php";
	session_start();
	
	$request_uri =  trim($_SERVER['REQUEST_URI'], "/");
	$page = basename($request_uri);

	$excluded_page = ['user-login','user-register','home'];

	if(isset($_SESSION['user']) && in_array($page,$excluded_page)) {
		header("location: ".BASE."dashboard");
		exit();
	}

	if($page == 'user-login') {
		login();
	}
	else if($page == 'user-register') {
		register();
	}
	elseif ($page == 'user-logout') {
		logout();
	}
	elseif ($page == 'verify_code') {
		verifyCode();
	}
	elseif ($page == 'home') {
		header("location: ".BASE);
	}
?>