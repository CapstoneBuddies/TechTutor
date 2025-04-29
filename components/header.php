<?php
    if (!isset($_SESSION)) {
        session_start();
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
        'manage-schedule.php',
        'analytics.php',
        'recordings.php'
    ];
    $is_teaching_page = in_array($current_page, $teaching_pages);

    $role = '';
    switch ($_SESSION['role']) {
        case 'TECHKID':
            $role = 'dashboard/s/';
            break;
        case 'TECHGURU':
            $role = 'dashboard/t/';
            break;
        case 'ADMIN':
            $role = 'dashboard/a/';
            break;
        default:
            header("location: user-logout");
            break;
    }
?>
<div class="dashboard-container">
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>
    
    <!-- Mobile Toggle Button -->
    <a class="mobile-toggle d-md-none" style="cursor: pointer;">
        <img src="<?php echo IMG; ?>circle-logo.png" alt="Logo" class="logo">
    </a>
    
    <!-- Sidebar -->
    <nav class="sidebar collapsed">
        <div class="logo-container">
            <img src="<?php echo IMG; ?>circle-logo.png" alt="Logo" class="logo">
        </div>
        <div class="user-info">
            <a href="<?php echo BASE.$role; ?>profile">
            <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="user-avatar">
            <div class="user-details">
                <p class="user-name"><?php echo $_SESSION['name']; ?> | <?php echo ucfirst(strtolower($_SESSION['role'])); ?></p>
            </div>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?php echo BASE; ?>dashboard" class="nav-item <?php echo $current_page == 'role_redirect.php' ? 'active' : ''; ?>">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
            <?php if( $current_page != 'profile.php' && $current_page != 'settings.php' ) : ?>
            <?php if ($_SESSION['role'] == 'ADMIN'): ?>
            <!-- ADMIN DASHBOARD SELECTION -->
                <a href="<?php echo BASE.$role; ?>users" class="nav-item <?php echo $current_page == 'view-users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>View All Users</span>
                </a>
                <a href="<?php echo BASE.$role; ?>classes" class="nav-item <?php echo in_array($current_page, ['view-class.php', 'class-details.php', 'class-sessions.php', 'class-recordings.php', 'class-feedback.php', 'class-enroll.php']) ? 'active' : ''; ?>">
                        <i class="bi bi-book-fill"></i>
                        <span>Classes</span>
                    </a>
                <a href="<?php echo BASE.$role; ?>courses" class="nav-item <?php echo $current_page == 'view-course.php' ? 'active' : ''; ?>">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Courses</span>
                </a>
                <a href="<?php echo BASE.$role; ?>reports" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
                <a href="<?php echo BASE.$role; ?>logs" class="nav-item <?php echo $current_page == 'view-logs.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Logs</span>
                </a>
                <a href="<?php echo BASE.$role; ?>certificates" class="nav-item <?php echo in_array($current_page, ['certificates.php', 'verify-certificate.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-award"></i>
                    <span>Certificate Management</span>
                </a>
            <!-- END ADMIN DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHGURU'): ?>
            <!-- TECHGURU DASHBOARD SELECTION -->
            <a href="<?php echo BASE.$role; ?>class" class="nav-item <?php echo $is_teaching_page ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Classes</span>
            </a>
            <a href="<?php echo BASE.$role; ?>reports" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i>
                <span>Reports</span>
            </a>
            <a href="<?php echo BASE.$role; ?>certificates" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                <i class="bi bi-award"></i>
                <span>Certificates</span>
            </a>
            <!-- END TECHGURU DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHKID'): ?>
            <!-- TECHKID DASHBOARD SELECTION -->
                <!-- TechKid Links -->
                <a href="<?php echo BASE.$role; ?>class" class="nav-item <?php echo $current_page == 'class.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>My Classes</span>
                </a>
                <a href="<?php echo BASE.$role; ?>schedule" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Schedule</span>
                </a>
                <a href="<?php echo BASE.$role; ?>files" class="nav-item <?php echo $current_page == 'files.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>My Files</span>
                </a>
                <a href="<?php echo BASE.$role; ?>certificates" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i>
                    <span>My Certificates</span>
                </a>
                <a href="<?php echo BASE.$role; ?>reports" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            <?php endif; ?>
            <!-- Common Bottom Links -->
            <a href="<?php echo BASE.$role; ?>transactions" class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash"></i>
                <span>Transactions</span>
            </a>
            <a href="<?php echo BASE.$role; ?>notifications" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
            <!--  -->
            <?php else: ?>
            <a href="<?php echo BASE.$role; ?>profile" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo BASE.$role; ?>settings" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
            <?php endif; ?>
            <div>
                <a href="<?php echo BASE; ?>user-logout" class="nav-item logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
            
        </nav>
    </nav>

    <main class="main-content expanded ">
        <!-- Topbar with notifications and tokens -->
        <div class="topbar">
            <div class="container-fluid">
                <div class="d-flex justify-content-end align-items-center py-2">
                    <!-- Token Balance -->
                    <?php if (isset($_SESSION['user'])): 
                        // Get user token balance
                        $token_balance = 0;
                        try {
                            $query = "SELECT token_balance FROM users WHERE uid = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param('i', $_SESSION['user']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $token_balance = $row['token_balance'] ?? 0;
                            }
                        } catch (Exception $e) {
                            log_error("Error fetching token balance: " . $e->getMessage(), 'database');
                        }
                    ?>
                    <div class="me-4 d-flex align-items-center gap-3">
                        <a href="<?php echo BASE; ?>game" class="game-btn">
                            <span class="game-icon-wrapper">
                                <i class="bi bi-controller"></i>
                            </span>
                            <span class="game-text">Play & Learn</span>
                        </a>
                        <a href="<?php echo BASE; ?>payment" class="token-balance-link">
                            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                <i class="bi bi-coin me-1"></i> <?php echo number_format($token_balance, 2); ?> Tokens
                            </span>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <button class="btn btn-link text-dark position-relative p-0" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            <?php
                            // Get notification count
                            $notification_count = 0;
                            if (isset($_SESSION['user'])) {
                                try {
                                    $query = "SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND is_read = 0";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param('i', $_SESSION['user']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($row = $result->fetch_assoc()) {
                                        $notification_count = $row['count'];
                                    }
                                } catch (Exception $e) {
                                    log_error("Error fetching notification count: " . $e->getMessage(), 'database');
                                }
                            }
                            
                            if ($notification_count > 0): 
                            ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count">
                                <?php echo $notification_count; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 overflow-hidden" aria-labelledby="notificationsDropdown" style="width: 350px; max-height: 450px; overflow-y: auto;">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Notifications</h6>
                                <div>
                                    <?php
                                    // Get recent notifications
                                    $notifications = [];
                                    if (isset($_SESSION['user'])) {
                                        try {
                                            $query = "SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC LIMIT 5";
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param('i', $_SESSION['user']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()) {
                                                $notifications[] = $row;
                                            }
                                        } catch (Exception $e) {
                                            log_error("Error fetching notifications: " . $e->getMessage(), 'database');
                                        }
                                    }
                                    
                                    if (count($notifications) > 0): ?>
                                    <button class="btn btn-sm btn-link text-primary p-0 me-2" onclick="markAllAsRead()" type="button">
                                        Mark all as read
                                    </button>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE.$role; ?>notifications" class="text-decoration-none small">View All</a>
                                </div>
                            </div>
                            <?php
                            if (count($notifications) > 0):
                                foreach ($notifications as $notification):
                            ?>
                            <a href="<?php echo $notification['link']; ?>" class="dropdown-item p-3 border-bottom <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" data-notification-id="<?php echo $notification['notification_id']; ?>">
                                <div class="d-flex">
                                    <div class="notification-icon me-3 flex-shrink-0">
                                        <i class="<?php echo $notification['icon'] ?? 'bi bi-bell'; ?> <?php echo $notification['icon_color'] ?? 'text-primary'; ?>"></i>
                                    </div>
                                    <div class="notification-content overflow-hidden">
                                        <p class="mb-1 fw-semibold text-wrap"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <p class="text-muted small mb-0"><?php echo date('M d, g:i a', strtotime($notification['created_at'])); ?></p>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                    <div class="ms-2 flex-shrink-0">
                                        <span class="badge bg-primary">New</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <div class="p-3 text-center text-muted">
                                <p class="mb-0">No notifications</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User Profile -->
                    <div class="dropdown">
                        <button class="btn btn-link p-0 border-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="topbar-user-avatar">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li class="dropdown-header">
                                <h6 class="mb-0"><?php echo $_SESSION['name']; ?></h6>
                                <span class="text-muted small"><?php echo $_SESSION['email']; ?></span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>profile"><i class="bi bi-person me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>settings"><i class="bi bi-gear me-2"></i> Settings</a></li>
                            <?php if($_SESSION['email'] === 'admin@test.com'): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>webhooks"><i class="bi bi-rss me-2"></i> Webhooks</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>status"><i class="bi bi-check-circle-fill me-2"></i> Transaction Status</a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE; ?>user-logout"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

<style>
    .topbar {
        border-bottom: 1px solid rgba(0,0,0,0.08);
        padding: 0.5rem 0;
        background-color: #fff;
    }
    .topbar-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }
    .notification-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: rgba(13, 110, 253, 0.1);
    }
    .token-balance-link {
        text-decoration: none;
        display: inline-block;
    }
    .token-balance-link:hover .badge {
        background-color: rgba(13, 110, 253, 0.2) !important;
    }
    .notification-content {
        max-width: 100%;
        word-wrap: break-word;
    }
    .dropdown-item:hover .notification-icon {
        background-color: rgba(13, 110, 253, 0.2);
    }
</style>

<script>
    const BASE = '<?php echo BASE; ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Handle sidebar toggle
            const mobileToggle = document.querySelector('.mobile-toggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const logoContainer = document.querySelector('.logo-container');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            let isLogoHidden = false;
            let lastNotificationId = 0; // Track last notification ID

            // Function to mark all notifications as read
            window.markAllAsRead = function() {
                const unreadNotificationIds = Array.from(document.querySelectorAll('.dropdown-item[data-notification-id]'))
                    .filter(item => item.classList.contains('bg-light'))
                    .map(item => item.dataset.notificationId);
                
                if (unreadNotificationIds.length === 0) return;
                
                fetch(BASE + 'mark-all-notifications-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        user_id: <?php echo isset($_SESSION['user']) ? $_SESSION['user'] : 0; ?>,
                        notifIDs: unreadNotificationIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI to show all notifications as read
                        document.querySelectorAll('.dropdown-item.bg-light').forEach(item => {
                            item.classList.remove('bg-light');
                            const badge = item.querySelector('.badge');
                            if (badge) badge.remove();
                        });
                        
                        // Hide notification count badge
                        const countBadge = document.querySelector('.notification-count');
                        if (countBadge) countBadge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error marking notifications as read:', error));
            };

            // Function to check for new notifications
            async function checkNewNotifications() {
                try {
                    const response = await fetch(BASE + 'check-notifications', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `last_id=${lastNotificationId}`
                    });
                    
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const data = await response.json();
                    if (data.notifications && data.notifications.length > 0) {
                        // Show a simple notification message
                        showToast('success', 'You have new notifications! Visit the notifications page to view them.');
                        
                        // Update last notification ID
                        data.notifications.forEach(notification => {
                            lastNotificationId = Math.max(lastNotificationId, notification.id);
                        });
                        
                        // Update notification count if element exists
                        const notifCount = document.querySelector('.notification-count');
                        if (notifCount) {
                            notifCount.textContent = data.unread_count || '0';
                        }
                    }
                } catch (error) {
                    console.error('Error checking notifications:', error);
                    logAction(error.message, 'notification_check_error', true);
                }
            }

            // Initial check for notifications
            checkNewNotifications();

            // Set up periodic checking (every 30 seconds)
            setInterval(checkNewNotifications, 30000);

            // Function to handle sidebar toggle for mobile only
            function toggleSidebar() {
                if (window.innerWidth <= 770) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            }

            // Function to close mobile sidebar
            function closeMobileSidebar() {
                if (window.innerWidth <= 770) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            }

            // Function to log using the project's standard log_error
            function logAction(message, action, isError = false) {
                const data = new FormData();
                data.append('error', isError ? message : '');
                data.append('message', !isError ? message : '');
                data.append('component', 'header');
                data.append('action', action);

                fetch(BASE + 'logs', {
                    method: 'POST',
                    body: data
                }).catch(err => console.error('Error logging to server:', err));
            }

            // Mobile sidebar toggle
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    toggleSidebar();
                    // Toggle logo container visibility
                    logoContainer.classList.toggle('hidden');
                    isLogoHidden = !isLogoHidden;
                });
            }

            // Close sidebar when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeMobileSidebar);
            }

            // Close sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 770 && 
                    !sidebar.contains(e.target) && 
                    !mobileToggle.contains(e.target) &&
                    sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 770) {
                    closeMobileSidebar();
                }
                if (window.innerWidth <= 991) {
                    sidebar.classList.add("collapsed");
                    mainContent.classList.add("expanded");
                }
            });

            // Remove hover effects on mobile
            if (window.innerWidth <= 770) {
                sidebar.removeEventListener("mouseenter", null);
                sidebar.removeEventListener("mouseleave", null);
            } else {
                sidebar.addEventListener("mouseenter", function() {
                    sidebar.classList.remove("collapsed");
                    mainContent.classList.remove("expanded");
                });
                sidebar.addEventListener("mouseleave", function() {
                    sidebar.classList.add("collapsed");
                    mainContent.classList.add("expanded");
                });
            }
        } catch (error) {
            console.error('Error initializing header functionality:', error);
            logAction(error.message, 'initialization_error', true);
        }
    });
</script>