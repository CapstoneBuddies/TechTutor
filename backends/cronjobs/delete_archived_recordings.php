<?php
/**
 * Cron job script to delete archived recordings that are older than 7 days
 * This script should be run daily via a cron job
 */

// Define root path
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../..'));

// Include required files
require_once ROOT_PATH . '/backends/main.php';
require_once BACKEND . 'meeting_management.php';

// Log script execution
log_error("Starting archived recording cleanup process", "cron");

try {
    // Initialize meeting management
    $meeting = new MeetingManagement();
    
    // Get all classes from the database
    $stmt = $conn->prepare("SELECT class_id FROM class");
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $totalDeleted = 0;
    $errors = [];
    
    // Process each class
    foreach ($classes as $class) {
        try {
            // Get recordings for this class
            $result = $meeting->getClassRecordings($class['class_id']);
            
            if (!$result['success'] || empty($result['recordings'])) {
                continue;
            }
            
            // Check each recording
            foreach ($result['recordings'] as $recording) {
                // Skip if not archived
                if (!isset($recording['archived']) || $recording['archived'] !== true) {
                    continue;
                }
                
                // Check if archive_date is set and is older than 7 days
                if (isset($recording['meta']['archive_date'])) {
                    $archiveDate = strtotime($recording['meta']['archive_date']);
                    $deleteDate = $archiveDate + (7 * 24 * 60 * 60); // 7 days in seconds
                    
                    if (time() >= $deleteDate) {
                        // Time to delete this recording
                        $deleteResult = $meeting->deleteRecording($recording['recordID']);
                        
                        if ($deleteResult['success']) {
                            $totalDeleted++;
                            log_error("Deleted archived recording {$recording['recordID']} (archived on " . date('Y-m-d', $archiveDate) . ")", "cron");
                        } else {
                            $errors[] = "Failed to delete recording {$recording['recordID']}: " . ($deleteResult['error'] ?? 'Unknown error');
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error processing class {$class['class_id']}: " . $e->getMessage();
            log_error("Error processing class {$class['class_id']}: " . $e->getMessage(), "cron");
            continue;
        }
    }
    
    // Log summary
    log_error("Archived recording cleanup complete. Deleted $totalDeleted recordings.", "cron");
    
    if (!empty($errors)) {
        log_error("Errors encountered during cleanup: " . implode(", ", $errors), "cron");
    }
    
} catch (Exception $e) {
    log_error("Error in archived recording cleanup: " . $e->getMessage(), "cron");
} 