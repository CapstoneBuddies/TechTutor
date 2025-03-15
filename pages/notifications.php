<?php 
    require_once '../backends/main.php';

    if (!isset($_SESSION)) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
        header('Location: ' . BASE . 'login.php');
        exit;
    }

    // Fetch notifications using the new function
    $notifications = getUserNotifications($_SESSION['user'], $_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Notifications</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS Files -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
</head>
<body>
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
                            <div class="notification-list">
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
                                                            echo $notification['recipient_id'] 
                                                                ? 'To: ' . htmlspecialchars($notification['recipient_name'])
                                                                : 'To: ' . $notification['recipient_role']; 
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                                <small class="notification-time">
                                                    <?php echo getTimeAgoNotif($notification['created_at']); ?>
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
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </main>
    </div>
    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/js/notifications.js"></script>
    
    <script>
        // Mark all notifications as read
        function markAllAsRead() {
            fetch('mark-all-notifications-read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        item.querySelector('.notification-status')?.remove();
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
    </script>
</body>
</html>