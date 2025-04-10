<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php';

    // Check if user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    $tutor_id = $_SESSION['user'];
    $classes = getTechGuruClasses($tutor_id);
    $title = 'My Schedules';

    // Process each class to gather all schedules
    $allSchedules = [];
    $todayDate = date('Y-m-d');
    
    foreach ($classes as $class) {
        $classSchedules = getClassSchedules($class['class_id']);
        if (!empty($classSchedules)) {
            foreach ($classSchedules as $schedule) {
                $schedule['class_name'] = $class['class_name'];
                $schedule['class_id'] = $class['class_id'];
                $schedule['subject_name'] = $class['subject_name'];
                $schedule['thumbnail'] = $class['thumbnail'];
                $allSchedules[] = $schedule;
            }
        }
    }

    // Sort schedules by date and time
    usort($allSchedules, function($a, $b) {
        $dateCompare = strtotime($a['session_date']) - strtotime($b['session_date']);
        if ($dateCompare === 0) {
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        }
        return $dateCompare;
    });

    // Group schedules by date
    $schedulesByDate = [];
    foreach ($allSchedules as $schedule) {
        $scheduleDate = $schedule['session_date'];
        if (!isset($schedulesByDate[$scheduleDate])) {
            $schedulesByDate[$scheduleDate] = [];
        }
        $schedulesByDate[$scheduleDate][] = $schedule;
    }

    // Get upcoming and past schedules
    $upcomingSchedules = [];
    $pastSchedules = [];
    
    foreach ($allSchedules as $schedule) {
        if ($schedule['status'] !== 'completed' && $schedule['status'] !== 'canceled' && $schedule['session_date'] >= $todayDate) {
            $upcomingSchedules[] = $schedule;
        } else {
            $pastSchedules[] = $schedule;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active">My Schedules</li>
                                </ol>
                            </nav>
                            <h2 class="page-header">Class Schedules</h2>
                            <p class="subtitle">View and manage all upcoming and past class sessions</p>
                        </div>
                        <div>
                            <a href="./" class="btn btn-primary btn-action">
                                <i class="bi bi-arrow-left"></i>
                                Go Back to Your Class
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Views -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <ul class="nav nav-tabs mb-4" id="scheduleTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" type="button" role="tab" aria-controls="calendar-view" aria-selected="true">
                                <i class="bi bi-calendar3"></i> Calendar View
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming-view" type="button" role="tab" aria-controls="upcoming-view" aria-selected="false">
                                <i class="bi bi-clock"></i> Upcoming Sessions (<?php echo count($upcomingSchedules); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past-view" type="button" role="tab" aria-controls="past-view" aria-selected="false">
                                <i class="bi bi-clock-history"></i> Past Sessions (<?php echo count($pastSchedules); ?>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="scheduleTabContent">
                        <!-- Calendar View Tab -->
                        <div class="tab-pane fade show active" id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
                            <?php if (empty($allSchedules)): ?>
                                <div class="text-center py-5">
                                    <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Schedules" class="mb-4" style="width: 200px;">
                                    <h3>No Schedules Found</h3>
                                    <p class="text-muted">You don't have any class schedules yet. Create a class and add schedules to get started.</p>
                                </div>
                            <?php else: ?>
                                <div id="calendar"></div>
                            <?php endif; ?>
                        </div>

                        <!-- Upcoming Sessions Tab -->
                        <div class="tab-pane fade" id="upcoming-view" role="tabpanel" aria-labelledby="upcoming-tab">
                            <?php if (empty($upcomingSchedules)): ?>
                                <div class="text-center py-5">
                                    <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Upcoming Schedules" class="mb-4" style="width: 200px;">
                                    <h3>No Upcoming Sessions</h3>
                                    <p class="text-muted">You don't have any upcoming class sessions scheduled.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive table-scroll">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingSchedules as $schedule): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img class="thumbnail rounded me-2" src="<?php echo htmlspecialchars(CLASS_IMG.$schedule['thumbnail']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($schedule['class_name']); ?>" 
                                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                                            <div>
                                                                <div class="fw-medium"><?php echo htmlspecialchars($schedule['class_name']); ?></div>
                                                                <div class="text-muted small"><?php echo htmlspecialchars($schedule['subject_name']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $sessionDate = new DateTime($schedule['session_date']);
                                                            echo $sessionDate->format('M d, Y');
                                                            
                                                            // Check if today
                                                            if ($sessionDate->format('Y-m-d') === date('Y-m-d')) {
                                                                echo ' <span class="badge bg-primary">Today</span>';
                                                            }
                                                            
                                                            // Check if tomorrow
                                                            $tomorrow = new DateTime('tomorrow');
                                                            if ($sessionDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                                                                echo ' <span class="badge bg-info">Tomorrow</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $startTime = new DateTime($schedule['start_time']);
                                                            $endTime = new DateTime($schedule['end_time']);
                                                            echo $startTime->format('h:i A') . ' - ' . $endTime->format('h:i A');
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $duration = $startTime->diff($endTime);
                                                            $hours = $duration->h;
                                                            $minutes = $duration->i;
                                                            
                                                            if ($hours > 0) {
                                                                echo $hours . 'h ';
                                                            }
                                                            if ($minutes > 0) {
                                                                echo $minutes . 'm';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $statusClass = '';
                                                            $statusText = ucfirst($schedule['status']);
                                                            
                                                            switch($schedule['status']) {
                                                                case 'confirmed':
                                                                    $statusClass = 'bg-success';
                                                                    break;
                                                                case 'pending':
                                                                    $statusClass = 'bg-warning';
                                                                    break;
                                                                case 'completed':
                                                                    $statusClass = 'bg-info';
                                                                    break;
                                                                case 'canceled':
                                                                    $statusClass = 'bg-danger';
                                                                    break;
                                                                default:
                                                                    $statusClass = 'bg-secondary';
                                                            }
                                                        ?>
                                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo BASE; ?>dashboard/t/manage-schedule?class_id=<?php echo $schedule['class_id']; ?>"
                                                               class="btn btn-sm btn-outline-primary"
                                                               data-bs-toggle="tooltip"
                                                               title="Manage Schedule">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <?php if ($schedule['status'] !== 'completed' && $schedule['status'] !== 'canceled'): ?>
                                                                <?php if ($schedule['session_date'] === date('Y-m-d')): ?>
                                                                    <a href="<?php echo BASE; ?>dashboard/meeting?id=<?php echo $schedule['schedule_id']; ?>"
                                                                       class="btn btn-sm btn-outline-success"
                                                                       data-bs-toggle="tooltip"
                                                                       title="Start Session">
                                                                        <i class="bi bi-camera-video"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <a href="<?php echo BASE; ?>dashboard/t/class/details?id=<?php echo $schedule['class_id']; ?>"
                                                               class="btn btn-sm btn-outline-secondary"
                                                               data-bs-toggle="tooltip"
                                                               title="View Class Details">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Past Sessions Tab -->
                        <div class="tab-pane fade" id="past-view" role="tabpanel" aria-labelledby="past-tab">
                            <?php if (empty($pastSchedules)): ?>
                                <div class="text-center py-5">
                                    <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Past Schedules" class="mb-4" style="width: 200px;">
                                    <h3>No Past Sessions</h3>
                                    <p class="text-muted">You don't have any past class sessions.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive table-scroll">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pastSchedules as $schedule): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img class="thumbnail rounded me-2" src="<?php echo htmlspecialchars(CLASS_IMG.$schedule['thumbnail']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($schedule['class_name']); ?>" 
                                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                                            <div>
                                                                <div class="fw-medium"><?php echo htmlspecialchars($schedule['class_name']); ?></div>
                                                                <div class="text-muted small"><?php echo htmlspecialchars($schedule['subject_name']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $sessionDate = new DateTime($schedule['session_date']);
                                                            echo $sessionDate->format('M d, Y');
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $startTime = new DateTime($schedule['start_time']);
                                                            $endTime = new DateTime($schedule['end_time']);
                                                            echo $startTime->format('h:i A') . ' - ' . $endTime->format('h:i A');
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $duration = $startTime->diff($endTime);
                                                            $hours = $duration->h;
                                                            $minutes = $duration->i;
                                                            
                                                            if ($hours > 0) {
                                                                echo $hours . 'h ';
                                                            }
                                                            if ($minutes > 0) {
                                                                echo $minutes . 'm';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $statusClass = '';
                                                            $statusText = ucfirst($schedule['status']);
                                                            
                                                            switch($schedule['status']) {
                                                                case 'confirmed':
                                                                    $statusClass = 'bg-success';
                                                                    break;
                                                                case 'pending':
                                                                    $statusClass = 'bg-warning';
                                                                    break;
                                                                case 'completed':
                                                                    $statusClass = 'bg-info';
                                                                    break;
                                                                case 'canceled':
                                                                    $statusClass = 'bg-danger';
                                                                    break;
                                                                default:
                                                                    $statusClass = 'bg-secondary';
                                                            }
                                                        ?>
                                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo BASE; ?>dashboard/t/recordings?class_id=<?php echo $schedule['class_id']; ?>&schedule_id=<?php echo $schedule['schedule_id']; ?>"
                                                               class="btn btn-sm btn-outline-primary"
                                                               data-bs-toggle="tooltip"
                                                               title="View Recordings">
                                                                <i class="bi bi-play-circle"></i>
                                                            </a>
                                                            <a href="<?php echo BASE; ?>dashboard/t/class/details?id=<?php echo $schedule['class_id']; ?>"
                                                               class="btn btn-sm btn-outline-secondary"
                                                               data-bs-toggle="tooltip"
                                                               title="View Class Details">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <style>
        .dashboard-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .page-header {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .subtitle {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .thumbnail {
            object-fit: cover;
        }
        
        .table-scroll {
            max-height: 600px;
            overflow-y: auto;
            border-radius: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }
        
        .table-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .table-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .table-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .table-scroll thead th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
            border-top: none;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1rem;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--bs-primary);
            border-bottom: 2px solid var(--bs-primary);
            background-color: transparent;
        }
        
        .fc-event {
            cursor: pointer;
        }
        
        .fc-day-today {
            background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
        }
        
        .fc-event-title {
            font-weight: 500;
        }
        
        #calendar {
            height: 650px;
        }
        
        @media (max-width: 768px) {
            #calendar {
                height: 500px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize calendar
            <?php if (!empty($allSchedules)): ?>
                const calendarEl = document.getElementById('calendar');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: [
                        <?php foreach ($allSchedules as $schedule): ?>
                            {
                                title: '<?php echo addslashes($schedule['class_name']); ?>',
                                start: '<?php echo $schedule['session_date'] . 'T' . $schedule['start_time']; ?>',
                                end: '<?php echo $schedule['session_date'] . 'T' . $schedule['end_time']; ?>',
                                url: '<?php echo BASE; ?>dashboard/t/class/details?id=<?php echo $schedule['class_id']; ?>',
                                backgroundColor: '<?php echo getStatusColor($schedule['status']); ?>',
                                borderColor: '<?php echo getStatusColor($schedule['status']); ?>',
                                textColor: '#fff',
                                extendedProps: {
                                    schedule_id: <?php echo $schedule['schedule_id']; ?>,
                                    status: '<?php echo $schedule['status']; ?>',
                                    className: '<?php echo addslashes($schedule['class_name']); ?>'
                                }
                            },
                        <?php endforeach; ?>
                    ],
                    eventClick: function(info) {
                        // Prevent default link behavior
                        info.jsEvent.preventDefault();
                        
                        const scheduleId = info.event.extendedProps.schedule_id;
                        const classId = info.event.url.split('=')[1];
                        const status = info.event.extendedProps.status;
                        const className = info.event.extendedProps.className;
                        
                        // Create modal content for the event
                        let modalContent = `
                            <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">${className}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Date:</strong> ${info.event.start.toLocaleDateString()}</p>
                                            <p><strong>Time:</strong> ${info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${info.event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                            <p><strong>Status:</strong> <span class="badge bg-${getStatusBadgeClass(status)}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        `;
                        
                        // Add appropriate action buttons based on schedule status
                        if (status === 'confirmed' || status === 'pending') {
                            if (info.event.start.toDateString() === new Date().toDateString()) {
                                modalContent += `<a href="<?php echo BASE; ?>dashboard/meeting?id=${scheduleId}" class="btn btn-success">Start Session</a>`;
                            }
                            modalContent += `<a href="<?php echo BASE; ?>dashboard/t/manage-schedule?class_id=${classId}" class="btn btn-primary">Manage Schedule</a>`;
                        } else if (status === 'completed') {
                            modalContent += `<a href="<?php echo BASE; ?>dashboard/t/recordings?class_id=${classId}&schedule_id=${scheduleId}" class="btn btn-primary">View Recordings</a>`;
                        }
                        
                        modalContent += `<a href="<?php echo BASE; ?>dashboard/t/class/details?id=${classId}" class="btn btn-info">Class Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Remove existing modal if any
                        const existingModal = document.getElementById('eventModal');
                        if (existingModal) {
                            existingModal.remove();
                        }
                        
                        // Append new modal to the body
                        document.body.insertAdjacentHTML('beforeend', modalContent);
                        
                        // Show the modal
                        const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
                        eventModal.show();
                    }
                });
                
                calendar.render();
            <?php endif; ?>
        });
        
        // Helper function to get color based on status
        function getStatusColor(status) {
            switch(status) {
                case 'confirmed':
                    return '#198754'; // success
                case 'pending':
                    return '#ffc107'; // warning
                case 'completed':
                    return '#0dcaf0'; // info
                case 'canceled':
                    return '#dc3545'; // danger
                default:
                    return '#6c757d'; // secondary
            }
        }
        
        // Helper function to get Bootstrap badge class based on status
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'confirmed':
                    return 'success';
                case 'pending':
                    return 'warning';
                case 'completed':
                    return 'info';
                case 'canceled':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }
    </script>
    <?php
    // Helper function to get color based on status
    function getStatusColor($status) {
        switch($status) {
            case 'confirmed':
                return '#198754'; // success
            case 'pending':
                return '#ffc107'; // warning
            case 'completed':
                return '#0dcaf0'; // info
            case 'canceled':
                return '#dc3545'; // danger
            default:
                return '#6c757d'; // secondary
        }
    }
    ?>
</body>
</html>