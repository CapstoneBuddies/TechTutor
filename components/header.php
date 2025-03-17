<?php
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Since this is a shared component, we need to use ROOT_PATH for consistent access
    require_once ROOT_PATH . '/backends/db.php';
    
    // Helper function for time ago
    function getTimeAgoNotif($timestamp) {
        $datetime = new DateTime($timestamp);
        $now = new DateTime();
        $interval = $now->diff($datetime);
        
        if ($interval->y > 0) return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
        if ($interval->m > 0) return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
        if ($interval->d > 0) return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
        if ($interval->h > 0) return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        if ($interval->i > 0) return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    }
    
    // Fetch role-specific notifications
    $notification_query = "SELECT * FROM notifications WHERE recipient_id = ? OR recipient_role = ? OR recipient_role = 'ALL' ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("is", $_SESSION['user'], $_SESSION['role']);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Count unread notifications
    $unread_count = 0;
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $unread_count++;
        }
    }

    // Check if current page is teaching-related
    $current_page = basename($_SERVER['PHP_SELF']);
    $teaching_pages = [
        'class-details.php',
        'class-edit.php',
        'classes.php',
        'create-class.php',
        'subjects.php',
        'subject-details.php',
        'subjects.php'
    ];
    $is_teaching_page = in_array($current_page, $teaching_pages);

    $rolePrefix = '';
    switch ($_SESSION['role']) {
        case 'TECHKID':
            $rolePrefix = 's';
            break;
        case 'TECHGURU':
            $rolePrefix = 't';
            break;
        case 'ADMIN':
            $rolePrefix = 'a';
            break;
        default:
            $rolePrefix = '';
            break;
    }
?>
<!-- Include header.css for styling -->
<link rel="stylesheet" href="<?php echo CSS; ?>header.css">
<!-- Include responsive.css for mobile responsiveness -->
<link rel="stylesheet" href="<?php echo CSS; ?>responsive.css">

<div class="dashboard-container">
    <!-- Sidebar -->
    <nav class="sidebar collapsed">
        <div class="logo-container" id="sidebarToggle">
            <a style="cursor: pointer;">
                <img src="<?php echo IMG; ?>circle-logo.png" alt="Logo" class="logo">
            </a>
        </div>
        <div class="user-info">
            <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="user-avatar">
            <div class="user-details">
                <p class="user-name"><?php echo $_SESSION['name']; ?> | <?php echo ucfirst(strtolower($_SESSION['role'])); ?></p>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?php echo BASE; ?>dashboard" class="nav-item <?php echo $current_page == 'main_dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
            <?php if( $current_page != 'profile.php' && $current_page != 'settings.php' ) : ?>
            <!-- ADMIN DASHBOARD SELECTION -->
            <?php if ($_SESSION['role'] == 'ADMIN'): ?>
                <!-- Admin Links -->
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/users" class="nav-item <?php echo $current_page == 'view-users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>View All Users</span>
                </a>
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/classes" class="nav-item <?php echo $current_page == 'view-class.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book-fill"></i>
                    <span>View All Classes</span>
                </a>
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/courses" class="nav-item <?php echo $current_page == 'view-course.php' ? 'active' : ''; ?>">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Courses</span>
                </a>
            <!-- END ADMIN DASHBOARD SELECTION -->
            <!-- TECHGURU DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHGURU'): ?>
            <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/class" class="nav-item <?php echo $is_teaching_page ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Classes</span>
            </a>
            <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/certificates" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                <i class="bi bi-award"></i>
                <span>Certificates</span>
            </a>
            <!-- END TECHGURU DASHBOARD SELECTION -->
            <!-- TECHKID DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHKID'): ?>
                <!-- TechKid Links -->
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/class" class="nav-item <?php echo $current_page == 'class.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>My Class</span>
                </a>
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/schedule" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Schedule</span>
                </a>
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/files" class="nav-item <?php echo $current_page == 'files.php' ? 'active' : ''; ?>">
                    <i class="fas fa-folder"></i>
                    <span>Files</span>
                </a>
                <a href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/certificates" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-award"></i>
                    <span>Certificates</span>
                </a>
            <?php endif; ?>
            <!-- Common Bottom Links -->
            <a href="<?php echo BASE.'dashboard/'.$rolePrefix.'/transactions'; ?>" class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash"></i>
                <span>Transactions</span>
            </a>
            <a href="<?php echo BASE.'dashboard/'.$rolePrefix.'/notifications'; ?>" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
            <!--  -->
            <?php else: ?>
            <a href="<?php echo BASE.'dashboard/'.$rolePrefix.'/profile'; ?>" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo BASE.'dashboard/'.$rolePrefix.'/settings'; ?>" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
            <?php endif; ?>
        </nav>
    </nav>

    <main class="main-content expanded">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <!-- Menu toggle button will be inserted by JavaScript -->
            </div>
            <div class="top-bar-right">
                <div class="dropdown">
                    <a href="#" class="notification-icon" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">Recent Notifications</div>
                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <li><div class="no-notifications">No notifications yet</div></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li><a class="dropdown-item" href="<?php echo $notification['link']; ?>" class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <div class="notification-icon">
                                            <i class="bi <?php echo $notification['icon']; ?> <?php echo $notification['icon_color']; ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                            <div class="notification-time"><?php echo getTimeAgoNotif($notification['created_at']); ?></div>
                                        </div>
                                    </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-divider"></div>
                        <li><a class="dropdown-item" href="<?php echo BASE.'dashboard/'.$rolePrefix; ?>/notifications" class="view-all">View All Notifications</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <div class="profile-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile" class="profile-img">
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE.'dashboard/'.$rolePrefix.'/profile'; ?>">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE.'dashboard/'.$rolePrefix.'/settings'; ?>">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>user-logout">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

<!-- Include header.js for sidebar functionality -->
<script>
    const BASE = '<?php echo BASE; ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Handle sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (!sidebarToggle || !sidebar || !mainContent) {
                throw new Error('Required DOM elements not found for sidebar functionality');
            }
            
            // Create overlay for mobile
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function(e) {
                try {
                    e.preventDefault();
                    if (window.innerWidth <= 991) {
                        // Mobile behavior
                        sidebar.classList.toggle('active');
                        overlay.classList.toggle('active');
                    } else {
                        // Desktop behavior
                        sidebar.classList.toggle('collapsed');
                        mainContent.classList.toggle('expanded');
                    }
                } catch (error) {
                    console.error('Error toggling sidebar:', error);
                    fetch(BASE + 'log-error.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            error: error.message,
                            component: 'header',
                            action: 'toggle_sidebar'
                        })
                    }).catch(err => console.error('Error logging to server:', err));
                }
            });
            
            // Close sidebar when clicking overlay (mobile only)
            overlay.addEventListener('click', function() {
                try {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                } catch (error) {
                    console.error('Error closing sidebar:', error);
                    fetch(BASE + 'log-error.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            error: error.message,
                            component: 'header',
                            action: 'close_sidebar'
                        })
                    }).catch(err => console.error('Error logging to server:', err));
                }
            });
            
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                try {
                    // Debounce resize event
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        if (window.innerWidth > 991) {
                            sidebar.classList.remove('active');
                            overlay.classList.remove('active');
                        }
                    }, 250);
                } catch (error) {
                    console.error('Error handling resize:', error);
                    fetch(BASE + 'log-error.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            error: error.message,
                            component: 'header',
                            action: 'window_resize'
                        })
                    }).catch(err => console.error('Error logging to server:', err));
                }
            });
            
            // Handle notifications dropdown
            const notificationIcon = document.querySelector('.notification-icon');
            const notificationDropdown = document.querySelector('.notification-dropdown');
            
            if (notificationIcon && notificationDropdown) {
                notificationIcon.addEventListener('click', function(e) {
                    try {
                        e.preventDefault();
                        e.stopPropagation(); // Prevent event from bubbling up
                        notificationDropdown.classList.toggle('show');
                    } catch (error) {
                        console.error('Error toggling notifications:', error);
                        fetch(BASE + 'log-error.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                error: error.message,
                                component: 'header',
                                action: 'toggle_notifications'
                            })
                        }).catch(err => console.error('Error logging to server:', err));
                    }
                });
            }
            
            // Close notifications dropdown when clicking outside
            document.addEventListener('click', function(e) {
                try {
                    const target = e.target;
                    if (!target.closest('.notification-icon') && !target.closest('.notification-dropdown')) {
                        const dropdowns = document.querySelectorAll('.notification-dropdown');
                        dropdowns.forEach(dropdown => {
                            if (dropdown.classList.contains('show')) {
                                dropdown.classList.remove('show');
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error closing notifications:', error);
                    fetch(BASE + 'log-error.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            error: error.message,
                            component: 'header',
                            action: 'close_notifications'
                        })
                    }).catch(err => console.error('Error logging to server:', err));
                }
            });
            
            // Prevent notification dropdown from closing when clicking inside it
            if (notificationDropdown) {
                notificationDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        } catch (error) {
            console.error('Error initializing header functionality:', error);
            fetch(BASE + 'log-error.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    error: error.message,
                    component: 'header',
                    action: 'initialization'
                })
            }).catch(err => console.error('Error logging to server:', err));
        }
    });
</script>