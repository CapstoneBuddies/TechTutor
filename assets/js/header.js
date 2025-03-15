/**
 * Header and Sidebar Navigation JavaScript
 * Handles notifications and mobile responsiveness
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Create overlay for mobile view
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Handle mobile sidebar
    function handleMobileSidebar() {
        if (window.innerWidth <= 991) {
            // Mobile behavior - show/hide sidebar
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    }
    
    // Close sidebar when clicking overlay (mobile only)
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            // Remove mobile classes when resizing to desktop
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    });
    
    // Load notifications
    function loadNotifications() {
        fetch(BASE + 'get-notifications.php')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notificationList');
                if (!notificationList) return;
                
                notificationList.innerHTML = '';
                
                if (data.length === 0) {
                    notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
                    return;
                }
                
                data.forEach(notification => {
                    const notificationItem = document.createElement('div');
                    notificationItem.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                    notificationItem.innerHTML = `
                        <div class="notification-icon">
                            <i class="${notification.icon} ${notification.icon_color}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${notification.created_at}</div>
                        </div>
                    `;
                    notificationList.appendChild(notificationItem);
                });
            })
            .catch(error => console.error('Error loading notifications:', error));
    }
    
    // Mark all notifications as read
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch(BASE + 'mark-all-notifications-read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.notification-badge');
                    if (badge) badge.remove();
                    
                    const unreadItems = document.querySelectorAll('.notification-item.unread');
                    unreadItems.forEach(item => item.classList.remove('unread'));
                }
            })
            .catch(error => console.error('Error marking notifications as read:', error));
        });
    }
    
    // Initial load of notifications
    loadNotifications();
    
    // Refresh notifications periodically
    setInterval(loadNotifications, 60000); // Refresh every minute
});
