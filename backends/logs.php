<?php
	require_once 'main.php'; //Connect to config file

	header('Content-Type: application/json');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
	    exit;
	}

	// Get the JSON data from the request
	$input = file_get_contents("php://input");
	$data = json_decode($input, true);

	// Check if the necessary fields exist
	if (!isset($data['error'], $data['component'], $data['action'])) {
	    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
	    exit;
	}

	// Extract data
	$errorMessage = htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8');
	$component = htmlspecialchars($data['component'], ENT_QUOTES, 'UTF-8');
	$action = htmlspecialchars($data['action'], ENT_QUOTES, 'UTF-8');
	$ipAddress = $_SERVER['REMOTE_ADDR'];
	$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

	$logMsg = $errorMessage.' '.$component.' '.$action.' User Agent: '.$userAgent;

	// Call your custom logging function
	if (function_exists('log_error')) {
	    log_error($logMsg, 6);
	    echo json_encode(["status" => "success", "message" => "Error logged successfully"]);
	} else {
	    echo json_encode(["status" => "error", "message" => "log_error() function not found"]);
	}
	exit;
?>
