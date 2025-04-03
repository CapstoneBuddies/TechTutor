<?php
require_once '../config.php';

/**
 * Script to send a test webhook directly to your webhook handler
 * This is useful for testing if your webhook handler is working
 */

if (php_sapi_name() !== 'cli' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get webhook URL from env or fallback
$webhookUrl = BBB_WEBHOOK_URL ?? null;
$webhookSecret = BBB_WEBHOOK_SECRET ?? null;

// Fallback if env vars aren't loaded
if (empty($webhookUrl)) {
    // Read from the .env file directly
    $envFile = file_get_contents(__DIR__ . '/../../backends/.env');
    if (preg_match('/BBB_WEBHOOK_URL="([^"]+)"/', $envFile, $matches)) {
        $webhookUrl = $matches[1];
    } else {
        $webhookUrl = "https://techtutor.cfd/backends/handler/meeting_webhook.php";
    }
}

if (empty($webhookSecret)) {
    // Read from the .env file directly
    $envFile = file_get_contents(__DIR__ . '/../../backends/.env');
    if (preg_match('/BBB_WEBHOOK_SECRET="([^"]+)"/', $envFile, $matches)) {
        $webhookSecret = $matches[1];
    } else {
        $webhookSecret = "4dd7af870cc54df5efd67353e8bfddaf1d510997296089a3a806eb342fc56fa6";
    }
}

echo "Sending test webhook to: $webhookUrl\n";

// Create a test webhook payload simulating a meeting-created event
$meetingId = 'test_meeting_' . time();
$payload = json_encode([
    'event' => 'meeting-created',
    'meetingId' => $meetingId,
    'timestamp' => time() * 1000,
    'data' => [
        'meetingId' => $meetingId,
        'name' => 'Test Meeting',
        'internalMeetingId' => 'internal-' . $meetingId,
        'createTime' => time() * 1000,
        'createDate' => date('Y-m-d\TH:i:s'),
        'voiceBridge' => rand(10000, 99999),
        'dialNumber' => '',
        'attendeePW' => 'attendee123',
        'moderatorPW' => 'moderator123',
        'duration' => 0,
        'recording' => true,
        'isBreakout' => false
    ]
]);

// Calculate checksum
$checksum = hash('sha1', $payload . $webhookSecret);

// Send the webhook
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload),
    'X-Checksum: ' . $checksum
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "HTTP Response Code: $httpCode\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Successfully sent test webhook!\n";
    echo "Response: $response\n";
} else {
    echo "❌ Error sending test webhook\n";
    if ($error) {
        echo "cURL Error: $error\n";
    }
    echo "Response: $response\n";
}

// Also log the result
log_error("Test webhook sent - HTTP $httpCode - Response: $response", "webhooks");

// Output based on context
if (php_sapi_name() === 'cli') {
    echo "\nCheck your webhook logs for details.\n";
} else {
    // Return JSON for web requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'httpCode' => $httpCode,
        'response' => $response,
        'error' => $error ?: null,
        'message' => ($httpCode >= 200 && $httpCode < 300) 
            ? 'Test webhook sent successfully' 
            : 'Failed to send test webhook'
    ]);
} 