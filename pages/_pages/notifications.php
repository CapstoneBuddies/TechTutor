<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';
    require_once '../backends/db.php';

    // Fetch notifications based on user role
    $query = "";
    $params = [];
    $types = "";

    if ($_SESSION['role'] == 'ADMIN') {
        // Admins can see all notifications
        $query = "SELECT n.*, u.name as recipient_name, c.class_name 
                 FROM notifications n 
                 LEFT JOIN users u ON n.recipient_id = u.uid 
                 LEFT JOIN classes c ON n.class_id = c.class_id 
                 ORDER BY n.created_at DESC";
    } elseif ($_SESSION['role'] == 'TECHGURU') {
        // TechGurus see their own and their class notifications
        $query = "SELECT n.*, u.name as recipient_name, c.class_name 
                 FROM notifications n 
                 LEFT JOIN users u ON n.recipient_id = u.uid 
                 LEFT JOIN classes c ON n.class_id = c.class_id 
                 WHERE n.recipient_id = ? 
                 OR n.class_id IN (SELECT class_id FROM classes WHERE techguru_id = ?) 
                 OR n.recipient_role = 'ALL' 
                 ORDER BY n.created_at DESC";
        $params = [$_SESSION['user'], $_SESSION['user']];
        $types = "ii";
    } else {
        // TechKids see their own and enrolled class notifications
        $query = "SELECT n.*, u.name as recipient_name, c.class_name 
                 FROM notifications n 
                 LEFT JOIN users u ON n.recipient_id = u.uid 
                 LEFT JOIN classes c ON n.class_id = c.class_id 
                 WHERE n.recipient_id = ? 
                 OR n.class_id IN (SELECT class_id FROM enrollments WHERE student_id = ?) 
                 OR n.recipient_role = 'ALL' 
                 ORDER BY n.created_at DESC";
        $params = [$_SESSION['user'], $_SESSION['user']];
        $types = "ii";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <?php include '../components/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Notifications</h5>
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
                                                    <?php echo getTimeAgo($notification['created_at']); ?>
                                                </small>
                                            </div>
                                            <p class="notification-message">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <?php if ($notification['class_id']): ?>
                                                <small class="text-primary">
                                                    Class: <?php echo htmlspecialchars($notification['class_name']); ?>
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
            fetch('<?php echo BASE; ?>backends/mark_all_notifications_read.php', {
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
