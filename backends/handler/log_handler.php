<?php
require_once '../main.php';

// Enforce admin access
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
    $_SESSION['msg'] = "Unauthorized Access";
    log_error("Unauthorized access to log handler", 'security');
    header("location: " . BASE . "login");
    exit();
}

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : '';
$response = ['success' => false];
log_error($action);
// Process the requested action
switch ($action) {
    case 'clear':
        // Validate file parameter
        if (!isset($_GET['file']) || empty($_GET['file'])) {
            $_SESSION['error'] = "Missing file parameter";
            log_error("T1");
            header("Location: " . BASE . "dashboard/a/logs");
            exit();
        }
        
        $file = $_GET['file'];
        
        // Get directory parameter (optional)
        $dir = isset($_GET['dir']) ? $_GET['dir'] : '';
        $dir = str_replace('..', '', $dir); // Prevent directory traversal
        
        // Sanitize file name to prevent directory traversal attacks
        $file = basename($file);
        
        // Check file extension to ensure only log files can be cleared
        if (!preg_match('/\.(log|txt)$/i', $file)) {
            $_SESSION['error'] = "Invalid file type. Only log files can be cleared.";
            log_error("T2");
            header("Location: " . BASE . "dashboard/a/logs" . ($dir ? "?dir=" . urlencode($dir) : ""));
            exit();
        }
        
        $logDir = ROOT_PATH . '/logs/';
        
        // Build correct file path
        $filePath = $logDir;
        if (!empty($dir)) {
            $filePath .= $dir . '/';
        }
        $filePath .= $file;
        
        // Verify file exists and is a regular file
        if (!file_exists($filePath) || !is_file($filePath)) {
            $_SESSION['error'] = "Log file not found";
            log_error("T3");
            header("Location: " . BASE . "dashboard/a/logs" . ($dir ? "?dir=" . urlencode($dir) : ""));
            exit();
        }
        
        // Create backup before clearing
        $backupDir = $logDir . 'backup/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Add directory structure to backup if needed
        if (!empty($dir)) {
            $backupDir .= $dir . '/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
        }
        
        $backupFile = $backupDir . $file . '.' . date('Y-m-d-His') . '.bak';
        if (!copy($filePath, $backupFile)) {
            log_error("Failed to create backup of log file: " . $file . " in " . $dir, 'admin');
            // Still proceed with clearing the log
        }
        
        // Clear the log file
        if (file_put_contents($filePath, '') !== false) {
            // Log the action
            log_error("Log file cleared: " . $file . " in " . $dir . " by admin " . $_SESSION['user'], 'admin');
            $_SESSION['success'] = "Log file cleared successfully. A backup was created.";
        } else {
            $_SESSION['error'] = "Failed to clear log file. Check file permissions.";
            log_error("Failed to clear log file: " . $file . " in " . $dir, 'admin');
        }
        
        // Redirect back to the log viewer
        $redirectUrl = BASE . "dashboard/a/logs?";
        if (!empty($dir)) {
            log_error("T4");
            $redirectUrl .= "dir=" . urlencode($dir) . "&";
        }
        $redirectUrl .= "file=" . urlencode($file);
        log_error("T5");
        
        header("Location: " . $redirectUrl);
        exit();
        break;
        
    case 'download-all':
    log_error($action);
        
        
        // Create a zip archive of all log files
        $logDir = ROOT_PATH . '/logs/';
        
        // Get current directory if specified
        $dir = isset($_GET['dir']) ? $_GET['dir'] : '';
        $dir = str_replace('..', '', $dir); // Prevent directory traversal
        
        // Build the directory path to zip
        $sourceDir = $logDir;
        if (!empty($dir)) {
            $sourceDir .= $dir . '/';
        }
        
        // Create descriptive zip filename
        $zipFileName = 'logs';
        if (!empty($dir)) {
            $zipFileName .= '_' . str_replace('/', '_', $dir);
        }
        $zipFileName .= '_' . date('Y-m-d-His') . '.zip';
        
        $zipFile = tempnam(sys_get_temp_dir(), 'logs_');
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            // Create a recursive directory iterator
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            $count = 0;
            foreach ($files as $name => $file) {
                // Skip directories and special files
                if ($file->isDir() || $file->getFilename() === '.' || $file->getFilename() === '..' || $file->getFilename() === '.gitkeep') {
                    continue;
                }
                
                // Get real path and relative path
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($logDir));
                
                // Only include log files
                if (preg_match('/\.(log|txt|bak)$/i', $filePath)) {
                    $zip->addFile($filePath, $relativePath);
                    $count++;
                }
            }
            
            $zip->close();
            
            if ($count > 0) {
                // Log the action
                log_error("Downloaded " . $count . " log files as zip archive", 'admin');
                
                // Set headers and output the zip file
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename=' . $zipFileName);
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
                
                // Delete the temporary file
                unlink($zipFile);
                exit();
            } else {
                $_SESSION['error'] = "No log files found to download";
                header("Location: " . BASE . "dashboard/a/logs" . ($dir ? "?dir=" . urlencode($dir) : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Failed to create zip archive of log files";
            header("Location: " . BASE . "dashboard/a/logs" . ($dir ? "?dir=" . urlencode($dir) : ""));
            exit();
        }
        break;
        
    default:
        $_SESSION['error'] = "Invalid action";
        header("Location: " . BASE . "dashboard/a/logs");
        exit();
} 