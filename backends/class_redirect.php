<?php 
	require_once 'config.php';
	require_once BACKEND.'meeting_management.php';

	if(!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
		$_SESSION['error'] = "Invalid Action";
		header("location: ".BASE.'login');
		exit();
	}

	if(!isset($_GET['id']) || !isset($_GET['ended']) || empty($_GET['id']) || empty($_GET['ended']) ) {
		$_SESSION['error'] = "Unexpected error occured.";
		
		switch($_SESSION['role']) {
			case 'TECHGURU':
				$link = BASE.'dashboard/t/class';
				break;
			case 'TECHKID':
				$link = BASE.'dashboard/s/class';
				break;
			default:
				$link = BASE.'dashboard/';
				break;
		}
		header("location: ".$link);
		exit();
	}

	$uri = $_SERVER['REQUEST_URI'];

	// Check User Role
	switch($_SESSION['role']) {
		case 'TECHGURU':
			$role = '/t/';
			break;
		case 'TECHKID':
			$role = '/s/';
			break;
		default:
			$role = null;
			break;
	}

	$new_link = str_replace('/u/class',$role.'class/details',$uri);
	header("location: ".$new_link);
	exit();
?>