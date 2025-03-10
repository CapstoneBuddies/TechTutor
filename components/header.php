<?php
    if (!isset($_SESSION)) {
        session_start();
    }
?>
<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-container">
            <a href="<?php echo BASE; ?>/home">
                <img src="<?php echo BASE; ?>/assets/img/stand_alone_logo.png" alt="Logo" class="logo">
            </a>
        </div>
        
        <div class="user-info">
            <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="user-avatar">
            <div class="user-details">
                <p class="user-name"><?php echo $_SESSION['name']; ?> | <?php echo ucfirst(strtolower($_SESSION['role'])); ?></p>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?php echo BASE; ?>dashboard" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'main_dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
            <?php if ($_SESSION['role'] == 'TECHGURU'): ?>
            <a href="<?php echo BASE; ?>courses" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
                <i class="bi bi-book"></i>
                <span>Courses</span>
            </a>
            <a href="<?php echo BASE; ?>class" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'class.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Class</span>
            </a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] == 'ADMIN'): ?>
            <a href="<?php echo BASE; ?>dashboard/TechGurus" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'main_view-techguru.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>TechTutors</span>
            </a>
            <a href="<?php echo BASE; ?>dashboard/TechKids" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'main_view-techkids.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>TechKids</span>
            </a>
            <a href="<?php echo BASE; ?>courses" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
                <i class="bi bi-book"></i>
                <span>Courses</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE; ?>notifications" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i class="bi bi-bell"></i>
                <span>Notification</span>
            </a>
            <a href="<?php echo BASE; ?>transactions" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash"></i>
                <span>Transactions</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="search-bar">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search">
            </div>
            <div class="top-bar-right">
                <a href="<?php echo BASE; ?>notifications" class="notification-icon">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge">0</span>
                </a>
                <div class="dropdown">
                    <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile" class="profile-img" data-bs-toggle="dropdown">
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>dashboard/profile">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>dashboard/settings">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>user-logout">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
