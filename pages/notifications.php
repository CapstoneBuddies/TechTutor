<?php
    require_once '../backends/main.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
        header('Location: ' . BASE . 'login.php');
        exit;
    }

    // Fetch notifications using the new function
    $notifications = fetchUserNotifications($_SESSION['user'], $_SESSION['role']); 
    $notif_id = [];
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body>
        <!-- Header -->
        <?php include ROOT_PATH . '/components/header.php'; ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <?php if ($_SESSION['role'] == 'ADMIN'): ?>
                                        All System Notifications
                                    <?php else: ?>
                                        My Notifications
                                    <?php endif; ?>
                                </h5>
                                <?php if (!empty($notifications)): ?>
                                    <button class="btn btn-primary btn-sm" onclick="markAllAsRead()">
                                        Mark All as Read
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-bell text-muted" style="font-size: 48px;"></i>
                                <p class="mt-3 text-muted">No notifications yet</p>
                            </div>
                            <?php else: ?>
                        </div>
                        <div class="notification-list notification-holder shadow-lg">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                                     data-notification-id="<?php echo $notification['notification_id']; ?>">
                                    <div class="notification-icon">
                                        <i class="bi <?php echo $notification['icon']; ?> <?php echo $notification['icon_color']; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-header">
                                            <?php if ($_SESSION['role'] == 'ADMIN'): ?>
                                                <small class="text-muted">
                                                    <?php 
                                                        if (!empty($notification['recipient_id'])) {
                                                            echo 'To: ' . htmlspecialchars($notification['recipient_name'] ?? 'User');
                                                        } else {
                                                            echo 'To: ' . ($notification['recipient_role'] ?? 'All Users');
                                                        }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                            <small class="notification-time">
                                                <?php echo getTimeAgo($notification['created_at']); ?>
                                            </small>
                                        </div>
                                        <p class="notification-message">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <?php if ($notification['class_id']): ?>
                                        <small class="text-primary">
                                            <?php 
                                            try {
                                                // Get class name from class_id
                                                $class_query = "SELECT class_name FROM class WHERE class_id = ?";
                                                $class_stmt = $conn->prepare($class_query);
                                                $class_stmt->bind_param("i", $notification['class_id']);
                                                $class_stmt->execute();
                                                $class_result = $class_stmt->get_result();
                                                $class_name = $class_result->fetch_assoc()['class_name'] ?? 'Unknown Class';
                                                echo "Class: " . htmlspecialchars($class_name);
                                            } catch (Exception $e) {
                                                log_error("Error fetching class name: " . $e->getMessage());
                                                echo "Class: Unknown";
                                            }
                                            ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                    <div class="notification-status">
                                        <span class="badge bg-primary">New</span>
                                    </div>
                                    <?php endif; ?>
                                    <button class="btn btn-delete-notif" data-notification-id="<?php echo $notification['notification_id']; ?>">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </main>
        </div>
        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <!-- JavaScript Section -->
        <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
        <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
        <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
        <script src="<?php echo BASE; ?>assets/js/notifications.js"></script>
        
        <script>
            function getUnreadNotificationIds() {
                return Array.from(document.querySelectorAll('.notification-item.unread'))
                            .map(item => item.dataset.notificationId);
            }
            // Mark all notifications as read
            function markAllAsRead() {
                fetch("<?php echo BASE.'mark-all-notifications-read'; ?>", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        user_id: <?php echo $_SESSION['user']; ?>,
                        notifIDs: getUnreadNotificationIds()
                     })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            item.querySelector('.notification-status')?.remove();
                            setTimeout(() => location.reload(), 1000);
                        });
                        // Update the notification badge in the header
                        const badge = document.querySelector('.notification-badge');
                        if (badge) badge.remove();
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            document.addEventListener("DOMContentLoaded", function () {
                // Attach event listener to all delete buttons
                document.querySelectorAll(".btn-delete-notif").forEach(button => {
                    button.addEventListener("click", function () {
                        deleteNotif(this);
                    });
                });
            });

            function deleteNotif(button) {
                const notifItem = button.closest(".notification-item"); // Get the parent notification div
                const notifId = button.getAttribute("data-notification-id"); // Get the notification ID

                if (notifItem) {
                    // Apply transition effect
                    notifItem.classList.add("notif-hide");

                    // Send an AJAX request to delete notification from the database
                    fetch("<?php echo BASE.'delete-notification'; ?>", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `notification_id=${notifId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the notification from DOM after transition
                            setTimeout(() => {
                                notifItem.remove();
                            }, 400);
                        } else {
                            console.error("Failed to delete notification.");
                        }
                    })
                    .catch(error => console.error("Error:", error));
                }
            }

        </script>
    </body>
</html>





        