<?php 
	// Sending of email

	function remember_me() {
		// Generate and Set cookie
		$token = bin2hex(random_bytes(16));
		setcookie('remember_me', $token, time() + (7 * 24 * 60 * 60), "/", "", true, true);

		// store cookie
	 	$user_id = $_SESSION['user_id'];
	}
?>