<?php
require_once '../config.php';
require_once '../db.php';
require_once '../main.php';
require_once '../management/meeting_management.php';

// Security check - must be logged in
if (!isset($_SESSION['user'])) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

// Get parameters
$recordingId = $_GET['id'] ?? '';
$filename = $_GET['name'] ?? 'recording.mp4';

if (empty($recordingId)) {
    header('HTTP/1.1 400 Bad Request');
    die('Missing recording ID');
}

// Sanitize filename
$filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
if (!str_ends_with(strtolower($filename), '.mp4')) {
    $filename .= '.mp4';
}

// Initialize meeting management
$meetingManager = new MeetingManagement();

try {
    // Direct URL construction - BBB 2.7.x format
    $baseUrl = 'https://bbb.techtutor.cfd';
    $downloadUrl = $baseUrl . '/recording/' . $recordingId . '.mp4';
    
    // Log the download attempt
    log_error("Downloading recording: $recordingId, URL: $downloadUrl", "info");
    
    // Use cURL to get headers first to check if file exists
    $ch = curl_init($downloadUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $headers = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // If file doesn't exist with direct format, try to get recording info
    if ($httpCode != 200) {
        // Get recording info
        $recordings = $meetingManager->getRecordings($recordingId);
        
        if (empty($recordings)) {
            header('HTTP/1.1 404 Not Found');
            die('Recording not found in BigBlueButton server');
        }
        
        $recording = $recordings[0];
        $downloadUrl = $meetingManager->getRecordingDownloadUrl($recording);
        
        if (empty($downloadUrl)) {
            header('HTTP/1.1 404 Not Found');
            die('Download URL not available for this recording');
        }
    }
    
    // Set headers for download
    header('Content-Type: video/mp4');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Stream the file from BBB server to client using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $downloadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Stream directly to output
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
        echo $data;
        return strlen($data);
    });
    
    $success = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$success || $httpCode != 200) {
        log_error("Failed to download recording from BigBlueButton server. HTTP Code: $httpCode, Error: $error", "error");
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            die('Failed to download recording from BigBlueButton server');
        }
    }
    
    exit;
    
} catch (Exception $e) {
    log_error("Error downloading recording: " . $e->getMessage(), "error");
    header('HTTP/1.1 500 Internal Server Error');
    die('Error: ' . $e->getMessage());
} 