<?php
// Simple test file to check if webhook endpoint is accessible
header('Content-Type: application/json');

// Log the access attempt
$logPath = __DIR__ . '/../../logs/webhook_test.log';
$message = date('Y-m-d H:i:s') . " - Webhook test accessed from IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
file_put_contents($logPath, $message, FILE_APPEND);

// Output request information
$requestData = [
    'success' => true,
    'message' => 'Webhook test endpoint is accessible',
    'method' => $_SERVER['REQUEST_METHOD'],
    'time' => date('Y-m-d H:i:s'),
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'raw' => file_get_contents("php://input")
];

echo json_encode($requestData, JSON_PRETTY_PRINT); 