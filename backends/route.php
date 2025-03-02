<?php 
	include "db.php";

	$request_uri =  $_SERVER['REQUEST_URI'];

	$parts = explode("/", $request_uri);
	$extracted_text = isset($parts[2]) ? $parts[2] : '';

	if($extracted_text == 'user-login') {
		login();
	}
	else if($extracted_text == 'user-register') {
		register();
	}
	elseif ($extracted_text == 'user-logout') {
		logout();
	}
?>