<?php
require_once '../config.php';

/**
 * Script to register a webhook with the BigBlueButton server
 * This should be run once to configure the webhook
 */

// Check if this is an admin action or is being run in CLI
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    if (php_sapi_name() !== 'cli') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
}

// Initialize meeting management to use the BBB API
require_once BACKEND . 'meeting_management.php';
$meeting = new MeetingManagement();

// Webhook details from .env
$webhookUrl = BBB_WEBHOOK_URL ?? null;
$webhookSecret = BBB_SECRET ?? null;

// Fallback if env vars aren't loaded
if (empty($webhookUrl)) {
    // Read from the .env file directly
    $envFile = file_get_contents(__DIR__ . '/../../backends/.env');
    if (preg_match('/BBB_WEBHOOK_URL="([^"]+)"/', $envFile, $matches)) {
        $webhookUrl = $matches[1];
    } else {
        // Update default URL to include the correct path
        $webhookUrl = "https://techtutor.cfd/bigbluebutton/api/hooks";
    }
}

if (empty($webhookSecret)) {
    // Read from the .env file directly
    $envFile = file_get_contents(__DIR__ . '/../../backends/.env');
    if (preg_match('/BBB_SECRET="([^"]+)"/', $envFile, $matches)) {
        $webhookSecret = $matches[1];
    } else {
        $webhookSecret = "4dd7af870cc54df5efd67353e8bfddaf1d510997296089a3a806eb342fc56fa6";
    }
}

// Log the values being used
log_error("Using webhook URL: " . $webhookUrl, "webhooks");
log_error("Using webhook secret: " . substr($webhookSecret, 0, 5) . "..." . substr($webhookSecret, -5), "webhooks");

// Function to register webhook
function registerWebhook($bbbApiUrl, $bbbSecret, $webhookUrl, $webhookSecret) {
    // Make sure API URL ends with a slash
    if (substr($bbbApiUrl, -1) !== '/') {
        $bbbApiUrl .= '/';
    }
    
    // Create the API endpoint to register the webhook
    $apiEndpoint = $bbbApiUrl . "api/hooks/create";
    
    // Generate checksum for API authentication
    $params = [
        'callbackURL' => $webhookUrl,
        'meetingID' => '', // Empty for all meetings
        'getRaw' => 'true'
    ];
    
    // Sort parameters for checksum calculation
    ksort($params);
    
    $queryString = http_build_query($params);
    $checksum = sha1("create" . $queryString . $bbbSecret);
    
    // Build final URL
    $url = $apiEndpoint . '?' . $queryString . '&checksum=' . $checksum;
    
    // Make the API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    // Log the request details for debugging
    log_error("BBB Webhook Registration Request: $url", "webhooks");
    log_error("BBB Webhook Registration HTTP Code: $httpCode", "webhooks");
    
    // Parse the response
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    log_error("BBB Webhook Registration Response: " . $response, "webhooks");
    
    $xml = simplexml_load_string($response);
    if (!$xml) {
        return ['success' => false, 'error' => 'Invalid XML response', 'raw' => $response];
    }
    
    if ((string)$xml->returncode == 'SUCCESS') {
        return [
            'success' => true, 
            'message' => 'Webhook registered successfully',
            'hookID' => (string)$xml->hookID
        ];
    } else {
        return [
            'success' => false,
            'message' => (string)$xml->message,
            'messageKey' => (string)$xml->messageKey
        ];
    }
}

// Using values from meeting management class
$result = registerWebhook(BBB_API_URI, BBB_SECRET, $webhookUrl, $webhookSecret);

// Output the result
if (php_sapi_name() === 'cli') {
    echo "Registration Result:\n";
    print_r($result);
} else {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}

// Log the result
log_error("Webhook registration attempt: " . json_encode($result), "webhooks"); 