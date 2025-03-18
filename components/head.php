<?php
/**
 * Centralized head component for TechTutor
 * Includes all common meta tags, CSS, and JavaScript dependencies
 */

// Get the current page name for dynamic title
$current_page = basename($_SERVER['PHP_SELF'], '.php');

if (!isset($title) || empty($title)) {
    $page_title = ucwords(str_replace(['_', '-'], ' ', $current_page));
} else {
    $page_title = ucwords(str_replace(['_', '-'], ' ', $title));
}

// Default title fallback
if ($current_page === 'index' || $current_page === 'default') {
    $page_title = 'Home';
}

// Log page visit if function exists
if (function_exists('log_error')) {
    $msg = "USER: ".$_SESSION['user']." Page visited: {$current_page} Level: ".($_SESSION['role'] != '' ? $_SESSION['role'] : 'INVALID');
    log_error($msg, 5);
} else {
    error_log("Log function missing for page: {$current_page}");
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | <?php echo $page_title; ?></title>

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">

    <!-- Base Custom CSS -->
    <link href="<?php echo CSS; ?>users.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS; ?>header.css">
    <link rel="stylesheet" href="<?php echo CSS; ?>responsive.css">

    <?php
    // Role-specific CSS
    if (isset($_SESSION['role'])) {
        $role = strtolower($_SESSION['role']);
        $role_css_path = $_SERVER['DOCUMENT_ROOT']. BASE . "/assets/css/{$role}-common.css";
        if (file_exists($role_css_path)) {
            echo "<link rel='stylesheet' href='" . CSS . "{$role}-common.css'>";
        }
        log_error($role_css_path);
    }

    // Common CSS per role
    if (strpos($current_page, 'dashboard') !== false) {
        echo "<link href='" . CSS . "dashboard.css' rel='stylesheet'>";
    }

    // Page-specific CSS
    $page_css_path = $_SERVER['DOCUMENT_ROOT'] .BASE. "assets/css/{$current_page}.css";
    if (file_exists($page_css_path)) {
        echo "<link href='" . CSS . "{$current_page}.css' rel='stylesheet'>";
    }
    ?>

    <!-- Common JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>

    <?php
    // Role-specific JavaScript
    if (isset($_SESSION['role'])) {
        $role_js_path = $_SERVER['DOCUMENT_ROOT'] . "/assets/js/{$role}.js";
        if (file_exists($role_js_path)) {
            echo "<script src='" . BASE . "assets/js/{$role}.js' defer></script>";
        }
    }

    // Page-specific JavaScript
    $page_js_path = $_SERVER['DOCUMENT_ROOT'] . "/assets/js/{$current_page}.js";
    if (file_exists($page_js_path)) {
        echo "<script src='" . BASE . "assets/js/{$current_page}.js' defer></script>";
    }
    ?>
</head>
