<?php
require_once 'backends/config.php';

// Webhook URL
$webhookUrl = "https://techtutor.cfd/webhooks";
$webhookSecret = "vsJTVvEsf3hSkK6b4amA7mW04Eiql4G0zJ3eRzbMLc";

// Create a test payload
$timestamp = time() * 1000;
$meetingId = 'test_meeting_' . time();

$payload = json_encode([
    'event' => 'meeting-created',
    'meetingId' => $meetingId,
    'timestamp' => $timestamp
]);

// Calculate checksum correctly
$checksumString = "hook/event=meeting-created&meetingId={$meetingId}&timestamp={$timestamp}{$webhookSecret}";
$checksum = sha1($checksumString);

log_error("String: ".$checksumString,'webhooks');
log_error("checksumString: ".$checksum,'webhooks');

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
    'Accept: application/json',
    'X-Checksum: ' . $checksum
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Print response for debugging
if ($response === false) {
    echo "cURL Error: $error\n";
} else {
    echo "HTTP Response Code: $httpCode\n";
    echo "Response: $response\n";
}
?>
