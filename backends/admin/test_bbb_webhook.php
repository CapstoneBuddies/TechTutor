<?php
require_once '../config.php';

/**
 * Script to test BigBlueButton webhook connectivity
 * This can be run from command line to diagnose issues with webhooks
 */

if (php_sapi_name() !== 'cli' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Initialize meeting management to use the BBB API
require_once BACKEND . 'meeting_management.php';
$meeting = new MeetingManagement();

// Get webhook details from .env
$webhookUrl = BBB_WEBHOOK_URL ?? null;
$webhookSecret = BBB_SECRET ?? null;

// Fallback if env vars aren't loaded
if (empty($webhookUrl)) {
    // Read from the .env file directly
    $envFile = file_get_contents(__DIR__ . '/../../backends/.env');
    if (preg_match('/BBB_WEBHOOK_URL="([^"]+)"/', $envFile, $matches)) {
        $webhookUrl = $matches[1];
    } else {
        $webhookUrl = "https://techtutor.cfd/bigbluebutton/api/hooks";
    }
}

// Output diagnostic information
echo "BigBlueButton Webhook Test\n";
echo "==========================\n\n";
echo "BBB API URL: " . BBB_API_URI . "\n";
echo "BBB Secret: " . substr(BBB_SECRET, 0, 5) . "..." . substr(BBB_SECRET, -5) . "\n";
echo "Webhook URL: " . $webhookUrl . "\n";
echo "Webhook Secret: " . substr($webhookSecret, 0, 5) . "..." . substr($webhookSecret, -5) . "\n\n";

// Test 1: Check if webhook URL is reachable
echo "Test 1: Checking if webhook URL is reachable...\n";
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Webhook URL is reachable (HTTP $httpCode)\n";
    echo "Response: " . substr($response, 0, 100) . (strlen($response) > 100 ? "..." : "") . "\n";
} else {
    echo "❌ Cannot reach webhook URL (HTTP $httpCode)\n";
    if ($error) {
        echo "Error: $error\n";
    }
}
curl_close($ch);
echo "\n";

// Test 2: List existing webhooks
echo "Test 2: Listing existing webhooks...\n";

function callBbbApi($action, $params, $apiUrl, $secret) {
    // Make sure API URL ends with a slash
    if (substr($apiUrl, -1) !== '/') {
        $apiUrl .= '/';
    }
    
    // Create the API endpoint
    $apiEndpoint = $apiUrl . "api/" . $action;
    
    // Sort parameters for checksum calculation
    ksort($params);
    
    $queryString = http_build_query($params);
    $checksum = sha1($action . $queryString . $secret);
    
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
    
    echo "API URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    
    // Parse the response
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    $xml = simplexml_load_string($response);
    if (!$xml) {
        return ['success' => false, 'error' => 'Invalid XML response', 'raw' => $response];
    }
    
    return $xml;
}

$result = callBbbApi('hooks/list', [], BBB_API_URI, BBB_SECRET);

if (isset($result->returncode) && (string)$result->returncode == 'SUCCESS') {
    echo "✅ Successfully connected to BBB API\n";
    
    if (isset($result->hooks) && isset($result->hooks->hook) && count($result->hooks->hook) > 0) {
        echo "Found " . count($result->hooks->hook) . " registered webhooks:\n";
        
        foreach ($result->hooks->hook as $hook) {
            echo "- Hook ID: " . (string)$hook->hookID . "\n";
            echo "  URL: " . (string)$hook->callbackURL . "\n";
            echo "  Meeting ID: " . ((string)$hook->meetingID ?: "All meetings") . "\n";
            echo "  Raw: " . ((string)$hook->getRaw == 'true' ? "Yes" : "No") . "\n";
            
            // Check if this matches our configured webhook
            if ((string)$hook->callbackURL == $webhookUrl) {
                echo "  ✅ This matches our configured webhook URL\n";
            }
            
            echo "\n";
        }
    } else {
        echo "❌ No webhooks are currently registered with the BigBlueButton server\n";
        echo "Run the webhook registration script to register your webhook:\n";
        echo "php backends/admin/register_bbb_webhook.php\n";
    }
} else {
    echo "❌ Failed to connect to BBB API\n";
    if (isset($result->message)) {
        echo "Error: " . (string)$result->message . "\n";
    } elseif (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
        if (isset($result['raw'])) {
            echo "Raw response: " . $result['raw'] . "\n";
        }
    }
}
echo "\n";

// Test 3: Try to register a test webhook
echo "Test 3: Attempting to register a test webhook...\n";

// Create a unique test webhook ID to avoid duplicates
$testId = uniqid();
$testWebhookUrl = $webhookUrl . "?test=" . $testId;

$params = [
    'callbackURL' => $testWebhookUrl,
    'meetingID' => 'test_' . $testId,
    'getRaw' => 'true'
];

$result = callBbbApi('hooks/create', $params, BBB_API_URI, BBB_SECRET);

if (isset($result->returncode) && (string)$result->returncode == 'SUCCESS') {
    echo "✅ Successfully registered test webhook\n";
    echo "Hook ID: " . (string)$result->hookID . "\n";
    
    // Clean up the test webhook
    echo "Cleaning up test webhook...\n";
    $deleteParams = ['hookID' => (string)$result->hookID];
    $deleteResult = callBbbApi('hooks/destroy', $deleteParams, BBB_API_URI, BBB_SECRET);
    
    if (isset($deleteResult->returncode) && (string)$deleteResult->returncode == 'SUCCESS') {
        echo "✅ Successfully removed test webhook\n";
    } else {
        echo "❌ Failed to remove test webhook\n";
    }
} else {
    echo "❌ Failed to register test webhook\n";
    if (isset($result->message)) {
        echo "Error: " . (string)$result->message . "\n";
    } elseif (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    }
}

echo "\nTest complete. Check the logs for more details.\n"; 