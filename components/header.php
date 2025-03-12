<?php
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Since this is a shared component, we need to use ROOT_PATH for consistent access
    require_once ROOT_PATH . '/backends/config.php';
    require_once ROOT_PATH . '/backends/db.php';
    
    // Helper function for time ago
    function getTimeAgoNotifNotif($timestamp) {
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
        'techguru_classes.php',
        'techguru_subjects.php',
        'techguru_subject_details.php',
        'techguru_create_class.php',
        'class.php'
    ];
    $is_teaching_page = in_array($current_page, $teaching_pages);
?>
<div class="dashboard-container">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo-container">
            <a href="<?php echo BASE; ?>/home">
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
                <a href="<?php echo BASE; ?>dashboard/TechGurus" class="nav-item <?php echo $current_page == 'main_view-techguru.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-check"></i>
                    <span>TechGurus</span>
                </a>
                <a href="<?php echo BASE; ?>dashboard/TechKids" class="nav-item <?php echo $current_page == 'main_view-techkids.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                    <span>TechKids</span>
                </a>
                <a href="<?php echo BASE; ?>dashboard/users" class="nav-item <?php echo $current_page == 'main_view-users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>View All Users</span>
                </a>
                <a href="<?php echo BASE; ?>dashboard/courses" class="nav-item <?php echo $current_page == 'main_view-course.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book"></i>
                    <span>Courses</span>
                </a>
            <!-- END ADMIN DASHBOARD SELECTION -->
            <!-- TECHGURU DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHGURU'): ?>
            <a href="<?php echo BASE; ?>dashboard/class" class="nav-item <?php echo $is_teaching_page ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Classes</span>
            </a>
            <a href="<?php echo BASE; ?>class" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                <i class="bi bi-award"></i>
                <span>Certificates</span>
            </a>
            <!-- END TECHGURU DASHBOARD SELECTION -->
            <!-- TECHKID DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHKID'): ?>
                <!-- TechKid Links -->
                <a href="<?php echo BASE; ?>dashboard/courses" class="nav-item <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book"></i>
                    <span>My Courses</span>
                </a>
                <a href="<?php echo BASE; ?>dashboard/tutors" class="nav-item <?php echo $current_page == 'tutors.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-check"></i>
                    <span>My Tutors</span>
                </a>
            <?php endif; ?>
            <!-- Common Bottom Links -->
            <a href="<?php echo BASE; ?>dashboard/transactions" class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash"></i>
                <span>Transactions</span>
            </a>
            <a href="<?php echo BASE; ?>dashboard/notifications" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
            <!--  -->
            <?php else: ?>
            <a href="<?php echo BASE; ?>profile" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo BASE; ?>settings" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
            <?php endif; ?>
        </nav>
    </nav>

    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-right">
                <div class="dropdown">
                    <a href="#" class="notification-icon" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu notification-dropdown dropdown-menu-end">
                        <h6 class="dropdown-header">Recent Notifications</h6>
                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="dropdown-item notification-item text-center">
                                    <p class="text-muted mb-0">No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo $notification['link']; ?>" class="dropdown-item notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <i class="bi <?php echo $notification['icon']; ?> <?php echo $notification['icon_color']; ?>"></i>
                                        <div class="notification-content">
                                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small><?php echo getTimeAgoNotif($notification['created_at']); ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE; ?>dashboard/notifications" class="dropdown-item text-center view-all">
                            View All Notifications
                        </a>
                    </div>
                </div>
                <div class="dropdown">
                    <div class="profile-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile" class="profile-img">
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>dashboard/profile">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>dashboard/settings">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>user-logout">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>