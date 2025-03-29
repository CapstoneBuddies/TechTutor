<?php
/**
 * Cron job to update meeting analytics data
 * This script fetches analytics data from BigBlueButton for all classes
 * and updates the database with the latest information.
 * 
 * Recommended to run daily or weekly
 */

// Define the root path
$root = realpath(dirname(__FILE__) . '/../..');

// Include required files
require_once $root . '/backends/main.php';
require_once BACKEND . 'meeting_management.php';

// Log start of script execution
log_error("Starting meeting analytics update", "cron");

// Initialize meeting management
$meeting = new MeetingManagement();

try {
    global $conn;
    
    // Get all active classes
    $query = "SELECT class_id FROM class WHERE status = 'active'";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Failed to retrieve classes: " . $conn->error);
    }
    
    $totalClasses = $result->num_rows;
    $processedClasses = 0;
    $totalAnalyticsUpdated = 0;
    $errors = 0;
    
    log_error("Found $totalClasses active classes to process", "cron");
    
    // Process each class
    while ($row = $result->fetch_assoc()) {
        $class_id = $row['class_id'];
        
        try {
            // Fetch and update analytics for this class
            $analytics = $meeting->fetchMeetingAnalytics($class_id);
            
            if ($analytics['success']) {
                $processedClasses++;
                $totalAnalyticsUpdated += $analytics['analytics_updated'];
                log_error("Processed class ID $class_id: {$analytics['message']}", "cron");
            } else {
                $errors++;
                log_error("Error processing class ID $class_id: {$analytics['error']}", "cron");
            }
        } catch (Exception $e) {
            $errors++;
            log_error("Exception processing class ID $class_id: " . $e->getMessage(), "cron");
        }
    }
    
    // Log completion
    log_error("Meeting analytics update completed: Processed $processedClasses/$totalClasses classes, updated $totalAnalyticsUpdated analytics records, encountered $errors errors", "cron");
    
} catch (Exception $e) {
    log_error("Fatal error in meeting analytics update: " . $e->getMessage(), "cron");
} 