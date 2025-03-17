<?php
/**
 * Centralized head component for TechTutor
 * Includes all common meta tags, CSS, and JavaScript dependencies
 */

// Include required files
require_once ROOT_PATH . '/backends/db.php';

// Get the current page name for dynamic title
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title = ucwords(str_replace('_', ' ', $current_page));
$page_title = ucwords(str_replace('-', ' ', $page_title));

// Default title fallback
if ($current_page === 'index' || $current_page === 'default') {
    $page_title = 'Home';
}

// Log page visit for analytics
log_error("Page visited: {$current_page} access", 4);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | <?php echo $page_title; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
 
    <!-- Favicons -->
    <link href="assets/img/stand_alone_logo.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- BOOTSTRAP Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/fontawesome/css/all.min.css">

    <!-- Base Custom CSS -->
    <link href="<?php echo BASE; ?>assets/css/style.css" rel="stylesheet">
    
    <?php
    // Role-specific base CSS
    if (isset($_SESSION['role'])) {
        $role = strtolower($_SESSION['role']);
        
        // Load role-specific common CSS first
        $role_common_css = ROOT_PATH . "/assets/css/{$role}-common.css";
        if (file_exists($role_common_css)) {
            echo "<link href='" . BASE . "assets/css/{$role}-common.css' rel='stylesheet'>";
        }
        
        // Then load role-specific CSS
        $role_css = ROOT_PATH . "/assets/css/{$role}.css";
        if (file_exists($role_css)) {
            echo "<link href='" . BASE . "assets/css/{$role}.css' rel='stylesheet'>";
        }
    }

    // Load dashboard CSS for dashboard pages
    if (strpos($current_page, 'dashboard') !== false) {
        // First load base dashboard styles
        echo "<link href='" . BASE . "assets/css/dashboard.css' rel='stylesheet'>";
        
        // Then load role-specific dashboard styles if they exist
        if (isset($_SESSION['role'])) {
            $role = strtolower($_SESSION['role']);
            $role_dashboard_css = ROOT_PATH . "/assets/css/{$role}-dashboard.css";
            if (file_exists($role_dashboard_css)) {
                echo "<link href='" . BASE . "assets/css/{$role}-dashboard.css' rel='stylesheet'>";
            }
        }
    }

    // Finally load page-specific CSS (highest priority)
    $page_css = ROOT_PATH . "/assets/css/{$current_page}.css";
    if (file_exists($page_css)) {
        echo "<link href='" . BASE . "assets/css/{$current_page}.css' rel='stylesheet'>";
    }
    ?>
    
    <!-- Common JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="<?php echo BASE; ?>assets/js/common.js" defer></script>
    
    <?php
    // Role-specific JavaScript
    if (isset($_SESSION['role'])) {
        $role = strtolower($_SESSION['role']);
        
        // Load role-specific common JS first
        $role_common_js = ROOT_PATH . "/assets/js/{$role}-common.js";
        if (file_exists($role_common_js)) {
            echo "<script src='" . BASE . "assets/js/{$role}-common.js' defer></script>";
        }
        
        // Then load role-specific JS
        $role_js = ROOT_PATH . "/assets/js/{$role}.js";
        if (file_exists($role_js)) {
            echo "<script src='" . BASE . "assets/js/{$role}.js' defer></script>";
        }
        
        // Finally load role-specific dashboard JS if on dashboard
        if (strpos($current_page, 'dashboard') !== false) {
            $role_dashboard_js = ROOT_PATH . "/assets/js/{$role}-dashboard.js";
            if (file_exists($role_dashboard_js)) {
                echo "<script src='" . BASE . "assets/js/{$role}-dashboard.js' defer></script>";
            }
        }
    }
    
    // Page-specific JavaScript (highest priority)
    $page_js = ROOT_PATH . "/assets/js/{$current_page}.js";
    if (file_exists($page_js)) {
        echo "<script src='" . BASE . "assets/js/{$current_page}.js' defer></script>";
    }
    ?>
</head>