<?php
// Simple script to test if the webhook endpoint is accessible
$webhookUrl = "https://techtutor.cfd/backends/handler/meeting_webhook.php";
$webhookSecret = "4dd7af870cc54df5efd67353e8bfddaf1d510997296089a3a806eb342fc56fa6";

// Create a test payload
$payload = json_encode([
    'event' => 'meeting-created',
    'meetingId' => 'test_meeting_' . time(),
    'timestamp' => time() * 1000
]);

// Calculate checksum
$checksum = hash('sha1', $payload . $webhookSecret);

// Send the request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Checksum: ' . $checksum
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "Success! Response: $response\n";
} else {
    echo "Error: $error\n";
    echo "Response: $response\n";
} 