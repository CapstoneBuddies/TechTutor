<?php 
    require_once '../../backends/main.php';
    
    if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
        $_SESSION['msg'] = "Invalid Action";
        log_error("Unauthorized access to log viewer",'security');
        header("location: ".BASE."login");
        exit();
    }
    
    $title = "View Logs";
    
    // Directory to scan for log files
    $logDir = ROOT_PATH . '/logs/';
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '';
    
    // Ensure the directory path is valid and within the logs directory
    $currentPath = $logDir;
    if (!empty($currentDir)) {
        $normalizedDir = str_replace('..', '', $currentDir); // Prevent directory traversal
        $currentPath = $logDir . $normalizedDir;
        if (!is_dir($currentPath)) {
            $currentDir = '';
            $currentPath = $logDir;
        }
    }
    
    // Add trailing slash if not present
    if (substr($currentPath, -1) != '/') {
        $currentPath .= '/';
    }
    
    // Get all log files and directories
    $logFiles = array();
    $directories = array();
    
    if (is_dir($currentPath)) {
        $files = scandir($currentPath);
        foreach ($files as $file) {
            if ($file == '.' || $file == '.gitkeep') {
                continue;
            }
            
            $filePath = $currentPath . $file;
            
            if ($file == '..' && $currentDir != '') {
                // Add parent directory for navigation
                $parentDir = dirname($currentDir);
                $parentDir = ($parentDir == '.') ? '' : $parentDir;
                $directories[] = array(
                    'name' => '..',
                    'path' => $parentDir
                );
            } elseif (is_dir($filePath) && $file != '..') {
                // Add subdirectory
                $dirPath = $currentDir . ($currentDir ? '/' : '') . $file;
                $directories[] = array(
                    'name' => $file,
                    'path' => $dirPath
                );
            } elseif (is_file($filePath) && $file != '.') {
                // Add log file
                $logFiles[] = array(
                    'name' => $file,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                );
            }
        }
        
        // Sort by most recently modified
        usort($logFiles, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
    }
    
    // Get contents of selected log file
    $selectedLog = isset($_GET['file']) ? $_GET['file'] : (count($logFiles) > 0 ? $logFiles[0]['name'] : '');
    $logContent = '';
    $lineCount = 0;
    
    // Apply filter if provided
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    $logLevel = isset($_GET['level']) ? $_GET['level'] : '';
    
    if ($selectedLog && file_exists($currentPath . $selectedLog)) {
        $logPath = $currentPath . $selectedLog;
        $lines = file($logPath, FILE_IGNORE_NEW_LINES);
        $lineCount = count($lines);
        
        // Apply filters if set
        if (!empty($filter) || !empty($logLevel)) {
            $filteredLines = array();
            foreach ($lines as $line) {
                $matchesFilter = empty($filter) || stripos($line, $filter) !== false;
                
                $matchesLevel = true;
                if (!empty($logLevel)) {
                    $matchesLevel = stripos($line, "TYPE={$logLevel}") !== false;
                }
                
                if ($matchesFilter && $matchesLevel) {
                    $filteredLines[] = $line;
                }
            }
            $lines = $filteredLines;
        }
        
        // Get specified number of lines or all if not specified
        $numLines = isset($_GET['lines']) ? intval($_GET['lines']) : count($lines);
        $lines = array_slice($lines, 0, $numLines);
        
        // Format log entries with syntax highlighting
        $logContent = implode("\n", $lines);
    }
    
    // Check for download request
    if (isset($_GET['download']) && $_GET['download'] == 'true' && !empty($selectedLog)) {
        $filePath = $currentPath . $selectedLog;
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .log-sidebar {
            height: calc(100vh - 60px);
            overflow-y: auto;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        
        .log-content {
            height: calc(100vh - 160px);
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 0.9rem;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .log-file-item {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .log-file-item:hover {
            background-color: #e9ecef;
        }
        
        .log-file-item.active {
            background-color: #e9ecef;
            border-left: 3px solid #0d6efd;
        }
        
        .log-file-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .log-toolbar {
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        .log-empty {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #6c757d;
            font-style: italic;
        }
        
        /* Responsive adjustments for mobile */
        @media (max-width: 767.98px) {
            .log-sidebar {
                height: auto;
                max-height: 200px;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
                margin-bottom: 15px;
            }
            
            .log-content {
                height: calc(100vh - 360px);
            }
        }
        
        /* Different log level colors */
        .log-error {
            color: #dc3545;
        }
        
        .log-warning {
            color: #ffc107;
        }
        
        .log-info {
            color: #0dcaf0;
        }
        
        .log-debug {
            color: #6c757d;
        }

        .text-purple {
            color: #8a2be2;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-primary {
            color: #0d6efd;
        }
        
        .bg-purple {
            background-color: #8a2be2;
            color: white;
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
        
        <!-- Main Dashboard Content -->
        <main class="dashboard-content">
            <div class="container-fluid py-4">
                <!-- Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">System Logs</h1>
                        <p class="text-muted">View and analyze application logs</p>
                    </div>
                    <div>
                        <a href="<?php echo BASE; ?>admin/logs?action=download-all<?php echo !empty($currentDir) ? '&dir=' . urlencode($currentDir) : ''; ?>" class="btn btn-primary">
                            <i class="bi bi-download me-1"></i> Download <?php echo !empty($currentDir) ? 'Directory' : 'All Logs'; ?>
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Log Files Sidebar -->
                    <div class="col-md-3 mb-4 mb-md-0">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Log Files</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (count($logFiles) > 0 || count($directories) > 0): ?>
                                    <div class="log-sidebar">
                                        <!-- Always show breadcrumbs navigation -->
                                        <div class="log-directory-path mb-2 p-2 bg-light border-bottom">
                                            <nav aria-label="breadcrumb">
                                                <ol class="breadcrumb mb-0 small">
                                                    <li class="breadcrumb-item <?php echo empty($currentDir) ? 'active' : ''; ?>">
                                                        <?php if (empty($currentDir)): ?>
                                                            logs
                                                        <?php else: ?>
                                                            <a href="?dir=">logs</a>
                                                        <?php endif; ?>
                                                    </li>
                                                    <?php
                                                        if (!empty($currentDir)) {
                                                            $pathParts = explode('/', $currentDir);
                                                            $currentPath = '';
                                                            foreach ($pathParts as $i => $part) {
                                                                $currentPath .= ($i > 0 ? '/' : '') . $part;
                                                                if ($i < count($pathParts) - 1) {
                                                                    echo '<li class="breadcrumb-item"><a href="?dir=' . urlencode($currentPath) . '">' . htmlspecialchars($part) . '</a></li>';
                                                                } else {
                                                                    echo '<li class="breadcrumb-item active">' . htmlspecialchars($part) . '</li>';
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </ol>
                                            </nav>
                                        </div>
                                        
                                        <?php foreach ($directories as $directory): ?>
                                            <div class="log-file-item">
                                                <a href="?dir=<?php echo urlencode($directory['path']); ?>" class="text-decoration-none text-dark d-block">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-folder me-2 text-warning"></i>
                                                        <div>
                                                            <div class="fw-medium">
                                                                <?php echo htmlspecialchars($directory['name']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php foreach ($logFiles as $logFile): ?>
                                            <div class="log-file-item <?php echo ($selectedLog === $logFile['name']) ? 'active' : ''; ?>">
                                                <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($logFile['name']); ?>" class="text-decoration-none text-dark d-block">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-text me-2"></i>
                                                        <div>
                                                            <div class="fw-medium"><?php echo htmlspecialchars($logFile['name']); ?></div>
                                                            <div class="log-file-meta">
                                                                <span><?php echo formatFileSize($logFile['size']); ?></span>
                                                                <span class="ms-2"><?php echo date('M d, Y - H:i', $logFile['modified']); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="p-4 text-center">
                                        <i class="bi bi-folder-x display-5 text-muted mb-3"></i>
                                        <h4>No Log Files Found</h4>
                                        <p class="text-muted">There are no log files in this directory.</p>
                                        <?php if (!empty($currentDir)): ?>
                                            <a href="?dir=" class="btn btn-outline-primary mt-2">
                                                <i class="bi bi-house me-1"></i> Go to Root Directory
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted small mt-3">
                                                Log files will appear here after system activity is logged.
                                                <br>You can create a test log file to see how this interface works.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Log Content -->
                    <div class="col-md-9">
                        <div class="card h-100">
                            <div class="card-header">
                                <div class="d-flex flex-column flex-md-row justify-content-between">
                                    <h5 class="card-title mb-2 mb-md-0">
                                        <?php echo !empty($selectedLog) ? htmlspecialchars($selectedLog) : 'Select a log file'; ?>
                                        <?php if (!empty($lineCount)): ?>
                                            <span class="badge bg-secondary ms-2"><?php echo $lineCount; ?> lines</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($filter)): ?>
                                            <span class="badge bg-primary ms-2">
                                                <i class="bi bi-search me-1"></i> <?php echo htmlspecialchars($filter); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($logLevel)): ?>
                                            <span class="badge <?php 
                                                if ($logLevel === 'security') echo 'bg-danger';
                                                elseif ($logLevel === 'database') echo 'bg-warning text-dark';
                                                elseif ($logLevel === 'info') echo 'bg-info text-dark';
                                                elseif ($logLevel === 'analytics') echo 'bg-secondary';
                                                elseif ($logLevel === 'meeting') echo 'bg-primary';
                                                elseif ($logLevel === 'mail') echo 'bg-success';
                                                elseif ($logLevel === 'webhooks') echo 'bg-purple';
                                                elseif ($logLevel === 'class') echo 'bg-dark';
                                                elseif ($logLevel === 'front') echo 'bg-light text-dark';
                                                else echo 'bg-secondary';
                                            ?> ms-2">
                                                <i class="bi bi-funnel me-1"></i> <?php echo htmlspecialchars($logLevel); ?>
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    <?php if (!empty($selectedLog)): ?>
                                        <div class="d-flex gap-2">
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>&download=true" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearLogModal">
                                                <i class="bi bi-trash me-1"></i> Clear
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($selectedLog)): ?>
                                    <div class="log-toolbar">
                                        <form action="" method="get" class="row gx-2">
                                            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($currentDir); ?>">
                                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($selectedLog); ?>">
                                            <div class="col-md-3 mb-2 mb-md-0">
                                                <div class="input-group">
                                                    <input type="text" name="filter" class="form-control" placeholder="Filter logs..." value="<?php echo htmlspecialchars($filter); ?>">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2 mb-md-0">
                                                <select name="level" class="form-select" onchange="this.form.submit()">
                                                    <option value="" <?php echo !isset($_GET['level']) || $_GET['level'] === '' ? 'selected' : ''; ?>>All Types</option>
                                                    <option value="general" <?php echo isset($_GET['level']) && $_GET['level'] === 'general' ? 'selected' : ''; ?>>General</option>
                                                    <option value="database" <?php echo isset($_GET['level']) && $_GET['level'] === 'database' ? 'selected' : ''; ?>>Database</option>
                                                    <option value="mail" <?php echo isset($_GET['level']) && $_GET['level'] === 'mail' ? 'selected' : ''; ?>>Mail</option>
                                                    <option value="security" <?php echo isset($_GET['level']) && $_GET['level'] === 'security' ? 'selected' : ''; ?>>Security</option>
                                                    <option value="analytics" <?php echo isset($_GET['level']) && $_GET['level'] === 'analytics' ? 'selected' : ''; ?>>Analytics</option>
                                                    <option value="front" <?php echo isset($_GET['level']) && $_GET['level'] === 'front' ? 'selected' : ''; ?>>Frontend</option>
                                                    <option value="meeting" <?php echo isset($_GET['level']) && $_GET['level'] === 'meeting' ? 'selected' : ''; ?>>Meeting</option>
                                                    <option value="info" <?php echo isset($_GET['level']) && $_GET['level'] === 'info' ? 'selected' : ''; ?>>Info</option>
                                                    <option value="class" <?php echo isset($_GET['level']) && $_GET['level'] === 'class' ? 'selected' : ''; ?>>Class</option>
                                                    <option value="webhooks" <?php echo isset($_GET['level']) && $_GET['level'] === 'webhooks' ? 'selected' : ''; ?>>Webhooks</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-2 mb-md-0">
                                                <select name="lines" class="form-select" onchange="this.form.submit()">
                                                    <option value="100" <?php echo isset($_GET['lines']) && $_GET['lines'] == '100' ? 'selected' : ''; ?>>Last 100 lines</option>
                                                    <option value="500" <?php echo isset($_GET['lines']) && $_GET['lines'] == '500' ? 'selected' : ''; ?>>Last 500 lines</option>
                                                    <option value="1000" <?php echo isset($_GET['lines']) && $_GET['lines'] == '1000' ? 'selected' : ''; ?>>Last 1000 lines</option>
                                                    <option value="5000" <?php echo isset($_GET['lines']) && $_GET['lines'] == '5000' ? 'selected' : ''; ?>>Last 5000 lines</option>
                                                    <option value="10000" <?php echo isset($_GET['lines']) && $_GET['lines'] == '10000' ? 'selected' : ''; ?>>Last 10000 lines</option>
                                                    <option value="0" <?php echo isset($_GET['lines']) && $_GET['lines'] == '0' ? 'selected' : ''; ?>>All lines</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-2 mb-md-0">
                                                <?php if (!empty($filter) || isset($_GET['level'])): ?>
                                                    <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>" class="btn btn-outline-secondary w-100">Clear Filters</a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-2">
                                                <a href="?dir=<?php echo urlencode($currentDir); ?>" class="btn btn-outline-secondary w-100">
                                                    <i class="bi bi-arrow-left me-1"></i> Back
                                                </a>
                                            </div>
                                        </form>
                                        
                                        <!-- Quick Level Filters -->
                                        <div class="mt-3 d-flex gap-2 flex-wrap">
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?><?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?><?php echo isset($_GET['lines']) ? '&lines=' . intval($_GET['lines']) : ''; ?>" 
                                               class="btn btn-sm <?php echo empty($logLevel) ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                                                All Types
                                            </a>
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>&level=security<?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?><?php echo isset($_GET['lines']) ? '&lines=' . intval($_GET['lines']) : ''; ?>" 
                                               class="btn btn-sm <?php echo $logLevel === 'security' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                                <i class="bi bi-shield-exclamation me-1"></i> Security
                                            </a>
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>&level=database<?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?><?php echo isset($_GET['lines']) ? '&lines=' . intval($_GET['lines']) : ''; ?>" 
                                               class="btn btn-sm <?php echo $logLevel === 'database' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                                <i class="bi bi-database me-1"></i> Database
                                            </a>
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>&level=info<?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?><?php echo isset($_GET['lines']) ? '&lines=' . intval($_GET['lines']) : ''; ?>" 
                                               class="btn btn-sm <?php echo $logLevel === 'info' ? 'btn-info' : 'btn-outline-info'; ?>">
                                                <i class="bi bi-info-circle-fill me-1"></i> Info
                                            </a>
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>&level=meeting<?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?><?php echo isset($_GET['lines']) ? '&lines=' . intval($_GET['lines']) : ''; ?>" 
                                               class="btn btn-sm <?php echo $logLevel === 'meeting' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                                <i class="bi bi-camera-video-fill me-1"></i> Meeting
                                            </a>
                                            <a href="?dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>&level=general<?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?><?php echo isset($_GET['lines']) ? '&lines=' . intval($_GET['lines']) : ''; ?>" 
                                               class="btn btn-sm <?php echo $logLevel === 'general' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                                                <i class="bi bi-gear-fill me-1"></i> General
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="log-content" id="logContent"><?php 
                                            if (empty($logContent)): 
                                                echo '<div class="log-empty">Log file is empty</div>';
                                            else:
                                                // Process log content
                                                $lines = explode("\n", $logContent);
                                                $filteredLines = [];
                                                
                                                // Get filter and log level preferences
                                                $filterText = !empty($filter) ? strtolower($filter) : '';
                                                $logLevel = isset($_GET['level']) && !empty($_GET['level']) ? $_GET['level'] : '';
                                                
                                                // Apply filters if needed
                                                foreach ($lines as $line) {
                                                    // Skip empty lines
                                                    if (empty(trim($line))) continue;
                                                    
                                                    // Check for text filter matches
                                                    $matchesFilter = empty($filterText) || stripos(strtolower($line), $filterText) !== false;
                                                    
                                                    // Check for log level matches
                                                    $matchesLevel = true;
                                                    if (!empty($logLevel)) {
                                                        $matchesLevel = stripos($line, "TYPE={$logLevel}") !== false;
                                                    }
                                                    
                                                    // Add line if it passes all filters
                                                    if ($matchesFilter && $matchesLevel) {
                                                        $filteredLines[] = $line;
                                                    }
                                                }
                                                
                                                // If no content after filtering, show a message
                                                if (empty($filteredLines)) {
                                                    echo '<div class="log-empty">No matching log entries found</div>';
                                                } else {
                                                    // Join the filtered lines back together
                                                    $content = implode("\n", $filteredLines);
                                                    
                                                    // Highlight log levels
                                                    $content = htmlspecialchars($content);
                                                    
                                                    // Highlight different log types
                                                    $content = preg_replace('/TYPE=security/i', '<span class="log-error">$0</span>', $content);
                                                    $content = preg_replace('/TYPE=database/i', '<span class="log-warning">$0</span>', $content);
                                                    $content = preg_replace('/TYPE=info/i', '<span class="log-info">$0</span>', $content);
                                                    $content = preg_replace('/TYPE=analytics/i', '<span class="log-debug">$0</span>', $content);
                                                    $content = preg_replace('/TYPE=meeting/i', '<span class="text-primary">$0</span>', $content);
                                                    $content = preg_replace('/TYPE=mail/i', '<span class="text-success">$0</span>', $content);
                                                    $content = preg_replace('/TYPE=webhooks/i', '<span class="text-purple">$0</span>', $content);
                                                    
                                                    // Highlight entire lines based on type
                                                    $content = preg_replace('/^.*TYPE=security.*$/m', '<span class="log-error">$0</span>', $content);
                                                    $content = preg_replace('/^.*TYPE=database.*$/m', '<span class="log-warning">$0</span>', $content);
                                                    $content = preg_replace('/^.*TYPE=info.*$/m', '<span class="log-info">$0</span>', $content);
                                                    $content = preg_replace('/^.*TYPE=analytics.*$/m', '<span class="log-debug">$0</span>', $content);
                                                    
                                                    // Highlight timestamps
                                                    $content = preg_replace('/\[\(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\)::[^\]]+\]/', '<strong>$0</strong>', $content);
                                                    
                                                    echo $content;
                                                }
                                            endif;
                                        ?>
                                    </div>
                                    
                                    <?php if (!empty($selectedLog) && !empty($logContent)): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoScrollToggle" checked>
                                            <label class="form-check-label" for="autoScrollToggle">Auto-scroll to bottom</label>
                                        </div>
                                        <small class="text-muted">Showing <?php echo count($filteredLines ?? []); ?> lines</small>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center p-5">
                                        <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                        <h4 class="mt-3">No Log File Selected</h4>
                                        <p class="text-muted">Please select a log file from the sidebar to view its contents.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Clear Log Modal -->
        <div class="modal fade" id="clearLogModal" tabindex="-1" aria-labelledby="clearLogModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clearLogModalLabel">Confirm Clear Log</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to clear the log file <strong><?php echo htmlspecialchars($selectedLog); ?></strong>?</p>
                        <?php if (!empty($currentDir)): ?>
                        <p class="small text-muted">Directory: <?php echo htmlspecialchars($currentDir); ?></p>
                        <?php endif; ?>
                        <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="<?php echo BASE; ?>admin/logs?action=clear&dir=<?php echo urlencode($currentDir); ?>&file=<?php echo urlencode($selectedLog); ?>" class="btn btn-danger">Clear Log</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include ROOT_PATH . '/components/footer.php'; ?>
        
        <script>
            // Auto-refresh log content every 30 seconds
            let refreshTimeout;
            let autoScroll = true;
            
            function setupRefresh() {
                clearTimeout(refreshTimeout);
                refreshTimeout = setTimeout(() => {
                    // Store the current URL parameters
                    const currentUrl = new URL(window.location.href);
                    const params = new URLSearchParams(currentUrl.search);
                    
                    // Add a parameter to prevent browser caching
                    params.set('_refresh', Date.now());
                    
                    // Construct the refresh URL
                    const refreshUrl = `${currentUrl.pathname}?${params.toString()}`;
                    
                    // Load the new content
                    fetch(refreshUrl)
                        .then(response => response.text())
                        .then(html => {
                            // Create a temporary DOM element to parse the HTML
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // Extract the log content
                            const newLogContent = doc.querySelector('.log-content').innerHTML;
                            const logContentElement = document.querySelector('.log-content');
                            
                            // Save the current scroll position
                            const wasAtBottom = isScrolledToBottom(logContentElement);
                            
                            // Update the content
                            logContentElement.innerHTML = newLogContent;
                            
                            // Restore scroll position if needed
                            if (wasAtBottom && autoScroll) {
                                logContentElement.scrollTop = logContentElement.scrollHeight;
                            }
                            
                            // Update the line count if it exists
                            const lineCountElement = document.querySelector('.text-muted');
                            const newLineCount = doc.querySelector('.text-muted');
                            if (lineCountElement && newLineCount) {
                                lineCountElement.textContent = newLineCount.textContent;
                            }
                        })
                        .catch(() => {
                            // If fetch fails, fall back to full page reload
                            location.reload();
                        });
                            
                    // Set up the next refresh
                    setupRefresh();
                }, 30000);
            }
            
            function isScrolledToBottom(element) {
                return Math.abs(element.scrollHeight - element.clientHeight - element.scrollTop) < 10;
            }
            
            // Handle keyboard shortcuts
            function setupKeyboardShortcuts() {
                document.addEventListener('keydown', function(event) {
                    // Only handle keyboard shortcuts when not typing in an input
                    if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || event.target.tagName === 'SELECT') {
                        return;
                    }
                    
                    // Get all log files and current index
                    const logItems = document.querySelectorAll('.log-file-item');
                    let currentIndex = -1;
                    logItems.forEach((item, index) => {
                        if (item.classList.contains('active')) {
                            currentIndex = index;
                        }
                    });
                    
                    switch (event.key) {
                        case '?': // Show help
                            showKeyboardHelp();
                            break;
                        case 'j': // Next log file
                        case 'ArrowDown':
                            if (currentIndex < logItems.length - 1) {
                                const nextItem = logItems[currentIndex + 1].querySelector('a');
                                if (nextItem) nextItem.click();
                            }
                            break;
                        case 'k': // Previous log file
                        case 'ArrowUp':
                            if (currentIndex > 0) {
                                const prevItem = logItems[currentIndex - 1].querySelector('a');
                                if (prevItem) prevItem.click();
                            }
                            break;
                        case 'g': // Go to top of log content
                            const logContent = document.querySelector('.log-content');
                            if (logContent) {
                                logContent.scrollTop = 0;
                            }
                            break;
                        case 'G': // Go to bottom of log content
                            const logContentBottom = document.querySelector('.log-content');
                            if (logContentBottom) {
                                logContentBottom.scrollTop = logContentBottom.scrollHeight;
                            }
                            break;
                        case 'f': // Focus on filter input
                            const filterInput = document.querySelector('input[name="filter"]');
                            if (filterInput) {
                                filterInput.focus();
                                event.preventDefault();
                            }
                            break;
                        case 'r': // Reload/refresh
                            location.reload();
                            break;
                        case 's': // Filter for security logs
                            applyLevelFilter('security');
                            break;
                        case 'd': // Filter for database logs
                            applyLevelFilter('database');
                            break;
                        case 'i': // Filter for info logs
                            applyLevelFilter('info');
                            break;
                        case 'm': // Filter for meeting logs
                            applyLevelFilter('meeting');
                            break;
                        case 'g': // Filter for general logs (if not already scrolling to top)
                            if (event.shiftKey) {
                                applyLevelFilter('general');
                            } else {
                                const logContent = document.querySelector('.log-content');
                                if (logContent) {
                                    logContent.scrollTop = 0;
                                }
                            }
                            break;
                        case 'a': // Show all levels
                            clearLevelFilter();
                            break;
                    }
                });
            }
            
            function applyLevelFilter(level) {
                const params = new URLSearchParams(window.location.search);
                params.set('level', level);
                window.location.search = params.toString();
            }
            
            function clearLevelFilter() {
                const params = new URLSearchParams(window.location.search);
                params.delete('level');
                window.location.search = params.toString();
            }
            
            function showKeyboardHelp() {
                // Create modal for keyboard shortcuts if it doesn't exist
                if (!document.getElementById('keyboardHelpModal')) {
                    const modalHTML = `
                        <div class="modal fade" id="keyboardHelpModal" tabindex="-1" aria-labelledby="keyboardHelpModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="keyboardHelpModalLabel">Keyboard Shortcuts</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Key</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td><kbd>?</kbd></td><td>Show this help</td></tr>
                                                <tr><td><kbd>j</kbd> or <kbd>↓</kbd></td><td>Next log file</td></tr>
                                                <tr><td><kbd>k</kbd> or <kbd>↑</kbd></td><td>Previous log file</td></tr>
                                                <tr><td><kbd>g</kbd></td><td>Scroll to top</td></tr>
                                                <tr><td><kbd>Shift</kbd>+<kbd>g</kbd></td><td>Filter for general logs</td></tr>
                                                <tr><td><kbd>G</kbd> (capital G)</td><td>Scroll to bottom</td></tr>
                                                <tr><td><kbd>f</kbd></td><td>Focus on filter input</td></tr>
                                                <tr><td><kbd>r</kbd></td><td>Reload page</td></tr>
                                                <tr><td><kbd>s</kbd></td><td>Filter for security logs</td></tr>
                                                <tr><td><kbd>d</kbd></td><td>Filter for database logs</td></tr>
                                                <tr><td><kbd>i</kbd></td><td>Filter for info logs</td></tr>
                                                <tr><td><kbd>m</kbd></td><td>Filter for meeting logs</td></tr>
                                                <tr><td><kbd>a</kbd></td><td>Show all log types</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Append modal to body
                    const modalContainer = document.createElement('div');
                    modalContainer.innerHTML = modalHTML;
                    document.body.appendChild(modalContainer);
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('keyboardHelpModal'));
                modal.show();
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                setupRefresh();
                setupKeyboardShortcuts();
                
                const logContent = document.getElementById('logContent');
                const autoScrollToggle = document.getElementById('autoScrollToggle');
                
                // Get auto scroll preference from localStorage or default to true
                autoScroll = localStorage.getItem('logAutoScroll') !== 'false';
                
                if (autoScrollToggle) {
                    // Update checkbox to match stored preference
                    autoScrollToggle.checked = autoScroll;
                    
                    // Toggle auto-scroll when checkbox is clicked
                    autoScrollToggle.addEventListener('change', function() {
                        autoScroll = this.checked;
                        localStorage.setItem('logAutoScroll', autoScroll);
                        if (autoScroll && logContent) {
                            logContent.scrollTop = logContent.scrollHeight;
                        }
                    });
                }
                
                // Scroll to bottom of log content by default if auto-scroll is enabled
                if (logContent && autoScroll) {
                    logContent.scrollTop = logContent.scrollHeight;
                }
                
                // Prevent auto-refresh when interacting with the page
                document.addEventListener('click', function() {
                    clearTimeout(refreshTimeout);
                    setupRefresh();
                });
                
                // Add keyboard shortcut help button
                const mainContent = document.querySelector('.dashboard-content');
                if (mainContent) {
                    const helpButton = document.createElement('button');
                    helpButton.className = 'btn btn-sm btn-outline-secondary position-fixed';
                    helpButton.style.bottom = '20px';
                    helpButton.style.right = '20px';
                    helpButton.style.zIndex = '1000';
                    helpButton.innerHTML = '<i class="bi bi-keyboard me-1"></i> ?';
                    helpButton.addEventListener('click', showKeyboardHelp);
                    mainContent.appendChild(helpButton);
                }
            });
        </script>
    </body>
</html>

<?php
/**
 * Format file size in a human-readable way
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>