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
        'subjects.php'
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
    <!-- Mobile Toggle Button -->
     <a class="mobile-toggle d-md-none" style="cursor: pointer;">
        <img src="<?php echo IMG; ?>circle-logo.png" alt="Logo" class="logo">
    </a>
    
    <!-- Sidebar -->
    <nav class="sidebar collapsed">
        <div class="logo-container" id="sidebarToggle">
            <a style="cursor: pointer;">
                <img src="<?php echo IMG; ?>circle-logo.png" alt="Logo" class="logo">
            </a>
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
            <a href="<?php echo BASE; ?>dashboard" class="nav-item <?php echo $current_page == 'main_dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
            <?php if( $current_page != 'profile.php' && $current_page != 'settings.php' ) : ?>
            <!-- ADMIN DASHBOARD SELECTION -->
            <?php if ($_SESSION['role'] == 'ADMIN'): ?>
                <!-- Admin Links -->
                <a href="<?php echo BASE.$role; ?>users" class="nav-item <?php echo $current_page == 'view-users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>View All Users</span>
                </a>
                <a href="<?php echo BASE.$role; ?>classes" class="nav-item <?php echo $current_page == 'view-class.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book-fill"></i>
                    <span>View All Classes</span>
                </a>
                <a href="<?php echo BASE.$role; ?>courses" class="nav-item <?php echo $current_page == 'view-course.php' ? 'active' : ''; ?>">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Courses</span>
                </a>
            <!-- END ADMIN DASHBOARD SELECTION -->
            <!-- TECHGURU DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHGURU'): ?>
            <a href="<?php echo BASE.$role; ?>class" class="nav-item <?php echo $is_teaching_page ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Classes</span>
            </a>
            <a href="<?php echo BASE.$role; ?>certificates" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                <i class="bi bi-award"></i>
                <span>Certificates</span>
            </a>
            <!-- END TECHGURU DASHBOARD SELECTION -->
            <!-- TECHKID DASHBOARD SELECTION -->
            <?php elseif ($_SESSION['role'] == 'TECHKID'): ?>
                <!-- TechKid Links -->
                <a href="<?php echo BASE.$role; ?>class" class="nav-item <?php echo $current_page == 'class.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-check"></i>
                    <span>My Classes</span>
                </a>
                <a href="<?php echo BASE.$role; ?>schedule" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar"></i>
                    <span>My Schedule</span>
                </a>
                <a href="<?php echo BASE.$role; ?>files" class="nav-item <?php echo $current_page == 'files.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-check"></i>
                    <span>My Files</span>
                </a>
                <a href="<?php echo BASE.$role; ?>certificates" class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-check"></i>
                    <span>My Certificates</span>
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

    <main class="main-content expanded">
        

<script>
    const BASE = '<?php echo BASE; ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Handle sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileToggle = document.querySelector('.mobile-toggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const logoContainer = document.querySelector('.logo-container');
            let isLogoHidden = false;

            // Function to handle sidebar toggle
            function toggleSidebar() {
                if (window.innerWidth <= 770) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
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

            // Desktop sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    try {
                        if (window.innerWidth > 775) {
                            toggleSidebar();
                        }
                    } catch (error) {
                        console.error('Error toggling logo:', error);
                        logAction(error.message, 'toggle_logo_error', true);
                    }
                });
            }

            // Mobile sidebar toggle
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    toggleSidebar();
                // Toggle logo container visibility
                logoContainer.classList.toggle('hidden');
                isLogoHidden = !isLogoHidden;
                console.log(logoContainer.classList);
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 770 && 
                    !sidebar.contains(e.target) && 
                    !mobileToggle.contains(e.target) &&
                    sidebar.classList.contains('show')) {
                    toggleSidebar();
                }
            });
            sidebar.addEventListener("mouseenter", function() {
                sidebar.classList.remove("collapsed");
                mainContent.classList.remove("expanded");
            });
            sidebar.addEventListener("mouseleave", function() {
                sidebar.classList.add("collapsed");
                mainContent.classList.add("expanded");
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 770) {
                    sidebar.classList.remove('show');
                }
                if (window.innerWidth <= 991) {
                    sidebar.classList.add("collapsed");
                    mainContent.classList.add("expanded");
                }
                else {
                    sidebar.classList.remove("collapsed");
                    mainContent.classList.remove("expanded");
                }
            });
        } catch (error) {
            console.error('Error initializing header functionality:', error);
            logAction(error.message, 'initialization_error', true);
        }
    });
</script>