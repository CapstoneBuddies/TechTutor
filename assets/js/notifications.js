// Handle notification click events
document.addEventListener('DOMContentLoaded', function() {
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        if (item.classList.contains('text-center')) return; // Skip "No notifications" message
        
        item.addEventListener('click', async function(e) {
            const notificationId = this.dataset.notificationId;
            if (!notificationId) return;
            
            try {
                const response = await fetch(BASE+'mark-notification-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notification_id=${notificationId}`
                });
                
                if (response.ok) {
                    // Remove unread class and update badge count
                    this.classList.remove('unread');
                    const badge = document.querySelector('.notification-badge');
                    const badges = document.querySelector('.badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 1) {
                            badge.textContent = currentCount - 1;
                        } else {
                            badge.remove();
                        }
                    }
                    // setTimeout(() => location.reload(), 2000);
                    badges.remove();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        });
    });
    
    // Handle chevron animation
    const profileToggles = document.querySelectorAll('.profile-toggle');
    profileToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const chevron = this.querySelector('.chevron-icon');
            chevron.classList.toggle('rotated');
        });
    });
});
