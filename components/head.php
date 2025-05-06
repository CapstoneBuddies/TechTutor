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
    if(isset($_SESSION['user']) && isset($_SESSION['role'])) {
        $msg = "USER: ".$_SESSION['user']." Page visited: {$current_page} Level: ".($_SESSION['role'] != '' ? $_SESSION['role'] : 'INVALID');
    }
    else {
        $msg = "Page visited: {$current_page} ACCESSED LEVEL: INVALID/UNAUTHORIZED";
    }
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
    <link href="<?php echo IMG; ?>stand_alone_logo.png" sizes="180x180" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/clockpicker/dist/bootstrap-clockpicker.min.css">
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.1/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">

    <!-- Base Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS; ?>users.css">
    <link rel="stylesheet" href="<?php echo CSS; ?>header.css">
    <link rel="stylesheet" href="<?php echo CSS; ?>footer.css">

    <!-- Loading Screen CSS -->
    <style>
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
        }
        
        #page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        #page-loader .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--bs-primary);
        }
        
        #page-loader .loading-text {
            margin-top: 1rem;
            color: var(--bs-primary);
            font-weight: 500;
        }

        /* Navigation Group Styles */
        .nav-group {
            margin-bottom: 0.5rem;
        }

        .nav-subgroup {
            margin-left: 1.5rem;
            border-left: 2px solid rgba(255, 255, 255, 0.1);
            padding-left: 0.5rem;
            margin-top: 0.5rem;
        }

        .nav-subitem {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .nav-subitem:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .nav-subitem.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        .nav-subitem i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .nav-subgroup {
                margin-left: 1rem;
            }

            .nav-subitem {
                padding: 0.4rem 0.75rem;
            }
        }

        /* Container styles for consistent layout */
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }

        .table-responsive {
            margin: 0;
        }

        /* Button styles */
        .btn-group {
            gap: 0.25rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Status badges */
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Feedback styles */
        .feedback-item {
            transition: background-color 0.2s;
        }

        .feedback-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .rating-display {
            font-size: 1.1rem;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }

            .d-flex.gap-2 {
                flex-wrap: wrap;
            }

            .btn-group {
                flex-wrap: wrap;
            }

            .table-responsive {
                margin: 0 -1rem;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>

    <?php
    // Role-specific CSS
    if (isset($_SESSION['role'])) {
        $role = strtolower($_SESSION['role']);
        $role_css_path = $_SERVER['DOCUMENT_ROOT']. BASE . "/assets/css/{$role}-common.css";
        if (file_exists($role_css_path)) {
            echo "<link rel='stylesheet' href='" . CSS . "{$role}-common.css'>";
        }
    }

    // Common CSS per role
    if (strpos($current_page, 'dashboard') !== false) {
        echo "<link rel='stylesheet' href='" . CSS . "dashboard.css'>";
    }

    // Page-specific CSS
    $page_css_path = $_SERVER['DOCUMENT_ROOT'] .BASE. "assets/css/{$current_page}.css";
    if (file_exists($page_css_path)) {
        echo "<link rel='stylesheet' href='" . CSS . "{$current_page}.css'>";
    }
    ?>

    <!-- Common JavaScript -->
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script> 
        // Page loading functionality
        document.addEventListener("DOMContentLoaded", function() {
            // Hide page loader when content is fully loaded
            hidePageLoader();
        });
        
        // Show page loader (globally accessible)
        function showPageLoader() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.classList.remove('hidden');
            }
        }
        
        // Hide page loader (globally accessible)
        function hidePageLoader() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                setTimeout(() => {
                    loader.classList.add('hidden');
                    // Remove loader from DOM after transition completes
                    setTimeout(() => {
                        if (loader.parentNode) {
                            loader.parentNode.removeChild(loader);
                        }
                    }, 500); // Match this with the CSS transition time
                }, 500); // Delay to ensure content is rendered
            }
        }
        
        // Helper function to show/hide loading indicator
        function showLoading(show) {
            // Option 1: Use page loader for all loading operations
            if (show) {
                showPageLoader();
            } else {
                hidePageLoader();
            }
            
            // Option 2: Use separate loading indicator if present
            const loadingIndicator = document.getElementById("loadingIndicator");
            if (loadingIndicator) {
                if (show) {
                    loadingIndicator.classList.remove("d-none");
                } else {
                    loadingIndicator.classList.add("d-none");
                }
            }
        }
        
        // Helper to show the Toast
        function showToast(type, message) {
            const toastContainer = document.createElement('div');
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}-fill me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            toastContainer.appendChild(toast);
            document.body.appendChild(toastContainer);

            const bsToast = new bootstrap.Toast(toast, {
                animation: true,
                autohide: true,
                delay: 1000
            });

            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toastContainer);
            });
        }
        function logError(errorMessage, component, action) {
            const logData = {
                error: errorMessage,
                component: component,
                action: action
            };

            fetch(BASE + 'log', {
                method: 'POST', 
                headers: {
                    'Content-Type': 'application/json' 
                },
                body: JSON.stringify(logData) 
            })
            .then(response => response.json())
            .then(data => {
                console.log('Error logged:', data); 
            })
            .catch(error => {
                console.error('Error sending log:', error); 
            });
        }
        window.onerror = function(message, source, lineno, colno, error) {
            // Send the error to the server
            logError(error.message || message, source, `Line: ${lineno}, Col: ${colno}`);

            console.log(error);

            // Return true to prevent the default browser behavior (console logging the error)
            return true;
        };

    </script>
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
