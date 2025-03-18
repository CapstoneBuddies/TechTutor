<?php 
    require_once '../../backends/main.php';
    require_once ROOT_PATH.'/backends/student_management.php';
    
    if (!isset($_SESSION)) {
        session_start();
    }

    // Check if user is logged in and is a TECHKID
    if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit;
    }

    $classes = [];
    $active_class = null;
    $unread_notifications = [];

    try {
        // Get student's classes using centralized function
        $classes = getStudentClasses($_SESSION['user']);

        // Get unread notifications for the student
        $unread_notifications = getUserNotifications($_SESSION['user'], $_SESSION['role'], true);
    } catch (Exception $e) {
        log_error("Class page error: " . $e->getMessage(), "database");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-2">My Classes</h1>
                        <p class="text-muted mb-0">Track your learning progress and upcoming sessions</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="enrollments" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Enroll in New Class
                        </a>
                    </div>
                </div>

                <?php if ($active_class): ?>
                <!-- Active Class Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">
                                <i class="bi bi-camera-video-fill me-2"></i>Currently Active Class
                            </h2>
                            <span class="badge bg-success">Live Now</span>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <h3 class="h4"><?php echo htmlspecialchars($active_class['title']); ?></h3>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo !empty($active_class['tutor_avatar']) ? BASE . $active_class['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                         alt="Tutor" 
                                         class="tutor-avatar me-2">
                                    <span class="text-muted">with <?php echo htmlspecialchars($active_class['tutor_name']); ?></span>
                                </div>
                                <p class="text-muted"><?php echo htmlspecialchars($active_class['description']); ?></p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <a href="<?php echo htmlspecialchars($active_class['meeting_url']); ?>" class="btn btn-primary btn-lg">
                                    <i class="bi bi-camera-video-fill me-2"></i>Join Class Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Upcoming Classes -->
                    <div class="col-md-8">
                        <h2 class="h5 mb-3">
                            <i class="bi bi-calendar-check me-2"></i>Upcoming Classes
                        </h2>
                        
                        <?php if (empty($classes['upcoming'])): ?>
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-calendar text-muted" style="font-size: 48px;"></i>
                                <h3 class="h5 mt-3">No Upcoming Classes</h3>
                                <p class="text-muted mb-3">Ready to start learning?</p>
                                <a href="enrollments" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Browse Available Classes
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                            <?php foreach ($classes['upcoming'] as $class): ?>
                            <div class="card shadow-sm class-card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary">Upcoming</span>
                                        <span class="text-muted">
                                            <?php echo date('l, M d, Y | h:i A', strtotime($class['start_time'])); ?>
                                        </span>
                                    </div>
                                    <h3 class="h5 mb-2"><?php echo htmlspecialchars($class['title']); ?></h3>
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo !empty($class['tutor_avatar']) ? BASE . $class['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                             alt="Tutor" 
                                             class="tutor-avatar me-2">
                                        <span class="text-muted">with <?php echo htmlspecialchars($class['tutor_name']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            <i class="bi bi-book me-2"></i><?php echo htmlspecialchars($class['topic']); ?>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="showClassDetails('<?php echo $class['id']; ?>')">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Completed Classes -->
                    <div class="col-md-4">
                        <h2 class="h5 mb-3">
                            <i class="bi bi-check-circle me-2"></i>Completed Classes
                        </h2>
                        
                        <?php if (empty($classes['completed'])): ?>
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-4">
                                <p class="text-muted mb-0">No completed classes yet</p>
                            </div>
                        </div>
                        <?php else: ?>
                            <?php foreach ($classes['completed'] as $class): ?>
                            <div class="card shadow-sm class-card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-success">Completed</span>
                                        <span class="text-muted">
                                            <?php echo date('M d, Y', strtotime($class['completion_date'])); ?>
                                        </span>
                                    </div>
                                    <h3 class="h5 mb-2"><?php echo htmlspecialchars($class['title']); ?></h3>
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo !empty($class['tutor_avatar']) ? BASE . $class['tutor_avatar'] : BASE . 'assets/images/default-avatar.jpg'; ?>" 
                                             alt="Tutor" 
                                             class="tutor-avatar me-2">
                                        <span class="text-muted"><?php echo htmlspecialchars($class['tutor_name']); ?></span>
                                    </div>
                                    <?php if ($class['recording_url']): ?>
                                    <a href="<?php echo htmlspecialchars($class['recording_url']); ?>" 
                                       class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-play-circle me-2"></i>Watch Recording
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
    </div>

    <!-- Class Details Modal -->
    <div class="modal fade" id="classDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Class Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="classDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        const classDetailsModal = new bootstrap.Modal(document.getElementById('classDetailsModal'));
        const notificationsModal = new bootstrap.Modal(document.getElementById('notificationsModal'));
        
        function showClassDetails(classId) {
            fetch(`<?php echo BASE; ?>backends/api/get-class-details.php?id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('classDetailsContent').innerHTML = `
                            <div class="text-center mb-4">
                                <img src="${data.class.thumbnail || '<?php echo BASE; ?>assets/images/default-class.jpg'}" 
                                     class="img-fluid rounded mb-3" 
                                     style="max-height: 200px; object-fit: cover;" 
                                     alt="${data.class.title}">
                                <h4>${data.class.title}</h4>
                            </div>
                            <div class="mb-4">
                                <h6>Class Description</h6>
                                <p>${data.class.description}</p>
                            </div>
                            <div class="mb-4">
                                <h6>Schedule</h6>
                                <p class="mb-1">
                                    <i class="bi bi-calendar me-2"></i>${data.class.date}
                                </p>
                                <p class="mb-0">
                                    <i class="bi bi-clock me-2"></i>${data.class.time}
                                </p>
                            </div>
                            <div>
                                <h6>Tutor</h6>
                                <div class="d-flex align-items-center">
                                    <img src="${data.class.tutor_avatar || '<?php echo BASE; ?>assets/images/default-avatar.jpg'}" 
                                         class="tutor-avatar me-2" 
                                         alt="Tutor">
                                    <div>
                                        <p class="mb-1">${data.class.tutor_name}</p>
                                        <p class="text-muted mb-0 small">${data.class.tutor_role}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        classDetailsModal.show();
                    } else {
                        showToast('error', data.message || 'Failed to load class details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Failed to load class details');
                });
        }

        function showNotifications() {
            notificationsModal.show();
        }

        // Mark individual notification as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.notificationId;
                fetch('<?php echo BASE; ?>backends/api/mark-notification-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notification_id: notificationId,
                        user_id: <?php echo $_SESSION['user']; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.add('opacity-50');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Failed to mark notification as read');
                });
            });
        });

        // Mark all notifications as read
        function markAllNotificationsRead() {
            fetch('<?php echo BASE; ?>backends/api/mark-all-notifications-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?php echo $_SESSION['user']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item').forEach(item => {
                        item.classList.add('opacity-50');
                    });
                    document.querySelector('.notification-dot')?.remove();
                    setTimeout(() => {
                        notificationsModal.hide();
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to mark all notifications as read');
            });
        }
    </script>
</body>
</html>