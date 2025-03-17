<?php
require_once '../../backends/config.php';
require_once '../../backends/main.php';
require_once '../../backends/meeting_management.php';

// Check if user is logged in and is a TECHKID
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
    header("Location: " . BASE . "login");
    exit();
}

// Get token from URL
$token = $_GET['token'] ?? null;

if (!$token) {
    $_SESSION['msg'] = "Invalid meeting link.";
    header("Location: " . BASE . "techkid/classes");
    exit();
}

try {
    // Join meeting
    $result = joinMeeting($token);
    
    if ($result['success']) {
        // Redirect to meeting
        header("Location: " . $result['join_url']);
        exit();
    } else {
        $_SESSION['msg'] = "Failed to join meeting: " . $result['error'];
        header("Location: " . BASE . "techkid/classes");
        exit();
    }
} catch (Exception $e) {
    log_error("Error joining meeting: " . $e->getMessage());
    $_SESSION['msg'] = "An error occurred while joining the meeting.";
    header("Location: " . BASE . "techkid/classes");
    exit();
}
?>
