<?php
require_once '../config.php';

/**
 * Script to manage BigBlueButton webhooks
 * - List all webhooks
 * - Get details of a specific webhook
 * - Delete a webhook
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

/**
 * Function to make BigBlueButton API calls
 */
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
    
    // Log the request for debugging
    log_error("BBB API Request: $url", "webhooks-debug");
    
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
    
    log_error("BBB API Response HTTP Code: $httpCode", "webhooks-debug");
    
    // Parse the response
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    log_error("BBB API Response: " . $response, "webhooks-debug");
    
    $xml = simplexml_load_string($response);
    if (!$xml) {
        return ['success' => false, 'error' => 'Invalid XML response', 'raw' => $response];
    }
    
    return $xml;
}

/**
 * Function to list all webhooks
 */
function listWebhooks($bbbApiUrl, $bbbSecret) {
    $result = callBbbApi('hooks/list', [], $bbbApiUrl, $bbbSecret);
    
    if (isset($result->returncode) && (string)$result->returncode == 'SUCCESS') {
        $hooks = [];
        if (isset($result->hooks->hook)) {
            foreach ($result->hooks->hook as $hook) {
                $hooks[] = [
                    'hookID' => (string)$hook->hookID,
                    'callbackURL' => (string)$hook->callbackURL,
                    'meetingID' => (string)$hook->meetingID,
                    'getRaw' => (string)$hook->getRaw
                ];
            }
        }
        return ['success' => true, 'hooks' => $hooks];
    } else {
        return [
            'success' => false,
            'message' => isset($result->message) ? (string)$result->message : 'Unknown error',
            'messageKey' => isset($result->messageKey) ? (string)$result->messageKey : ''
        ];
    }
}

/**
 * Function to get webhook details
 */
function getWebhookInfo($bbbApiUrl, $bbbSecret, $hookId) {
    $params = ['hookID' => $hookId];
    $result = callBbbApi('hooks/info', $params, $bbbApiUrl, $bbbSecret);
    
    if (isset($result->returncode) && (string)$result->returncode == 'SUCCESS') {
        return [
            'success' => true,
            'hook' => [
                'hookID' => (string)$result->hook->hookID,
                'callbackURL' => (string)$result->hook->callbackURL,
                'meetingID' => (string)$result->hook->meetingID,
                'getRaw' => (string)$result->hook->getRaw
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => isset($result->message) ? (string)$result->message : 'Unknown error',
            'messageKey' => isset($result->messageKey) ? (string)$result->messageKey : ''
        ];
    }
}

/**
 * Function to delete a webhook
 */
function deleteWebhook($bbbApiUrl, $bbbSecret, $hookId) {
    $params = ['hookID' => $hookId];
    $result = callBbbApi('hooks/destroy', $params, $bbbApiUrl, $bbbSecret);
    
    if (isset($result->returncode) && (string)$result->returncode == 'SUCCESS') {
        return [
            'success' => true,
            'message' => 'Webhook deleted successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => isset($result->message) ? (string)$result->message : 'Unknown error',
            'messageKey' => isset($result->messageKey) ? (string)$result->messageKey : ''
        ];
    }
}

// Process the request based on the action parameter
$action = $_GET['action'] ?? 'list';
$hookId = $_GET['hookID'] ?? null;

switch ($action) {
    case 'list':
        $result = listWebhooks(BBB_API_URI, BBB_SECRET);
        break;
    case 'info':
        if (!$hookId) {
            $result = ['success' => false, 'message' => 'Hook ID is required'];
        } else {
            $result = getWebhookInfo(BBB_API_URI, BBB_SECRET, $hookId);
        }
        break;
    case 'delete':
        if (!$hookId) {
            $result = ['success' => false, 'message' => 'Hook ID is required'];
        } else {
            $result = deleteWebhook(BBB_API_URI, BBB_SECRET, $hookId);
        }
        break;
    default:
        $result = ['success' => false, 'message' => 'Invalid action'];
}
    
// Log the operation
log_error("Webhook $action operation: " . json_encode($result), "webhooks"); 

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);