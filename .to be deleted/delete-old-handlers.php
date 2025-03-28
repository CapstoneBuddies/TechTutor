<?php
/**
 * Script to delete old handler files after migration to unified file management
 */

require_once __DIR__ . '/../main.php';

// Files to be deleted
$filesToDelete = [
    __DIR__ . '/../api/upload-file.php',
    __DIR__ . '/../api/upload-material.php',
    __DIR__ . '/../api/delete-file.php',
    __DIR__ . '/../api/delete-material.php'
];

echo "Starting cleanup of old file management handlers...\n";

foreach ($filesToDelete as $file) {
    log_error($file);
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "Successfully deleted: " . basename($file) . "\n";
        } else {
            echo "Failed to delete: " . basename($file) . "\n";
        }
    } else {
        echo "File not found: " . basename($file) . "\n";
    }
}

echo "Cleanup completed!\n";
echo "The following files were consolidated into materials_handlers.php:\n";
echo "- upload-file.php\n";
echo "- upload-material.php\n";
echo "- delete-file.php\n";
echo "- delete-material.php\n";

echo "\nRemember to update any references to these files in your code to use materials_handlers.php instead.\n";
echo "Example usage:\n";
echo "- For file uploads: POST to materials_handlers.php?action=upload\n";
echo "- For file deletion: POST to materials_handlers.php?action=delete\n";
?> 