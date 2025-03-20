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
                    <div class="dropdown-menu notification-dropdown">
                        <div class="dropdown-header">Recent Notifications</div>
                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="no-notifications">No notifications yet</div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo $notification['link']; ?>" class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <div class="notification-icon">
                                            <i class="bi <?php echo $notification['icon']; ?> <?php echo $notification['icon_color']; ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                            <div class="notification-time"><?php echo getTimeAgoNotif($notification['created_at']); ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE.$role; ?>notifications" class="view-all">View All Notifications</a>
                    </div>
                </div>
                <div class="dropdown">
                    <div class="profile-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo $_SESSION['profile']; ?>" alt="Profile" class="profile-img">
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>profile">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE.$role; ?>settings">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE; ?>user-logout">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>