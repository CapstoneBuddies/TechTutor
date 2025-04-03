<?php
// Simple script to test webhook configuration

// Capture request info
$request_info = [
    'time' => date('Y-m-d H:i:s'),
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
    'headers' => getallheaders(),
    'raw_post' => file_get_contents('php://input'),
    'get_params' => $_GET,
    'post_params' => $_POST
];

// Log request info
$log_file = __DIR__ . '/logs/webhook_test.log';
file_put_contents($log_file, print_r($request_info, true) . "\n\n", FILE_APPEND);

// Send a valid response to BigBlueButton
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Webhook test endpoint - Request received and logged',
    'timestamp' => date('Y-m-d H:i:s')
]); 