<?php 
	include "db.php";

	if($_SERVER['REQUEST_METHOD'] == 'GET' && empty($_SERVER['QUERY_STRING'])) {
		// $_SESSION['msg'] = "Invalid Action";
		header("location: login");
		exit();
	}
	$request_uri =  $_SERVER['REQUEST_URI'];
	$parts = explode("/", $request_uri);
	$len = count($parts);
	$extracted_text = isset($parts[$len-1]) ? $parts[$len-1] : '';

	if($extracted_text == 'user-login') {
		login();
	}
	else if($extracted_text == 'user-register') {
		register();
	}
	elseif ($extracted_text == 'user-logout') {
		logout();
	}

	elseif ($extracted_text == 'home') {
		header("location: ".BASE);
	}
?>