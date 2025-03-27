<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = '';
if(isset($_GET['id'])) {
    $class_id = intval($_GET['id']);
}
else {
    header('Location: ./');
    exit();
}

$classSchedule = getClassSchedules($class_id);
$title = "Manage " . htmlspecialchars($classSchedule[0]['class_name']) . " Schedule";

if (isset($_POST['schedules']) && is_array($_POST['schedules'])) {
    // Process the submitted schedules
    $data = $_POST['schedules'];
    $schedules = [];

    for ($i = 0; $i < count($data); $i += 3) {
        $date = $data[$i]['date'];
        $start = $data[$i + 1]['start'];
        $end = $data[$i + 2]['end'];

        // Group them into the desired format
        $schedules[] = ['session_date' => $date, 'start_time' => $start, 'end_time' => $end];
    }
    if (!empty($schedules)) {
        $result = updateClassSchedules($class_id, $schedules, $_SESSION['user']);
        if (!$result['success']) {
            $_SESSION['error'] = "Failed to update schedules.";
        } else {
            $_SESSION['success'] = "Schedules updated successfully!";
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .clockpicker-active {
            background-color: #0d6efd !important;
            font-weight: bold;
            color: #fafafa !important;
        }
        .schedule-card {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .schedule-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        .schedule-entry {
            background: var(--bs-gray-100);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative;
            border: 1px solid var(--bs-gray-200);
            transition: all 0.2s ease;
        }
        .schedule-entry:hover {
            background: var(--bs-gray-50);
            border-color: var(--bs-primary);
        }
        .schedule-entry .remove-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .schedule-entry:hover .remove-btn {
            opacity: 1;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background: var(--bs-primary);
            color: #fff;
            font-weight: 500;
            border-bottom: none;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }
        .schedule-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.75rem;
            position: relative;
        }
        .schedule-status::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            border: 2px solid currentColor;
            opacity: 0.2;
        }
        .status-upcoming { 
            background: var(--bs-primary);
            color: var(--bs-primary);
        }
        .status-completed { 
            background: var(--bs-success);
            color: var(--bs-success);
        }
        .status-cancelled { 
            background: var(--bs-danger);
            color: var(--bs-danger);
        }
        .table-responsive {
            border-radius: 0.5rem;
            border: 1px solid var(--bs-gray-200);
            max-height: 600px;
        }
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: var(--bs-gray-100);
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--bs-gray-400);
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--bs-gray-500);
        }
        .delete-mode .delete-column {
            width: 50px;
            text-align: center;
            background: var(--bs-danger-bg-subtle);
        }
        .select-all-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: var(--bs-danger-bg-subtle);
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .form-check-input:checked {
            background-color: var(--bs-danger);
            border-color: var(--bs-danger);
        }
        .tooltip {
            z-index: 1070;
        }
        @media (max-width: 768px) {
            .schedule-entry {
                padding: 1rem;
            }
            .schedule-entry .remove-btn {
                opacity: 1;
                top: 0.5rem;
                right: 0.5rem;
            }
            .table td {
                white-space: nowrap;
            }
            .btn-group {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
        /* Clockpicker Custom Styles */
        .clockpicker-popover {
            z-index: 1060 !important;
        }
        .clockpicker-plate {
            top: 50%;
            left: 50%;
            transform: translate(-55%, -0%);
        }
        .clockpicker-button {
            padding: 6px 12px !important;
            border-radius: 4px !important;
            border: 1px solid var(--bs-gray-300) !important;
            transition: all 0.2s ease !important;
        }
        .clockpicker-canvas line {
            stroke: var(--bs-primary) !important;
        }
        .clockpicker-canvas-bg,
        .clockpicker-canvas-bearing {
            fill: var(--bs-primary) !important;
        }
        .clockpicker-tick:hover {
            background-color: var(--bs-primary) !important;
            color: white !important;
        }
    </style>
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
                                        <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard/t/class">My Classes</a></li>
                                        <li class="breadcrumb-item active"><?php echo $title; ?></li>
                                    </ol>
                                </nav>
                                <h2 class="page-header"><?php echo $title; ?></h2>
                                <p class="subtitle">Manage class schedules and sessions</p>
                            </div>
                            <div>
                                <a href="./?id=<?php echo $class_id;?>" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Classes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Add Schedule Form -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="schedule-card">
                        <h3 class="card-title mb-4">
                            <i class="bi bi-calendar-plus"></i> Add New Schedule
                        </h3>
                        <form id="scheduleForm" method="POST" action="">
                            <div id="scheduleContainer">
                                <div class="schedule-entry">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-btn d-none">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" name="schedules[0][date]" class="form-control schedule-date" required>
                                            <div class="form-text">Select a future date</div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group clockpicker">
                                                <input type="text" name="schedules[1][start]" class="form-control schedule-start" placeholder="--:--" required>
                                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group clockpicker">
                                                <input type="text" name="schedules[2][end]" class="form-control schedule-end" placeholder="--:--" required>
                                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="addScheduleEntry()">
                                    <i class="bi bi-plus-lg"></i> Add Another Schedule
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Schedules
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Schedules Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="schedule-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-calendar-week"></i> Current Schedules
                            </h3>
                            <div class="d-flex gap-2">
                            <button id="toggleDelete" class="btn btn-outline-danger" onclick="toggleDeleteMode()">
                                    <i class="bi bi-trash"></i> Delete Mode
                                </button>
                                <button id="deleteSelected" class="btn btn-danger d-none">
                                    <i class="bi bi-trash-fill"></i> Delete Selected
                            </button>
                            </div>
                        </div>
                        <?php if (empty($classSchedule)): ?>
                            <div class="text-center py-5">
                                <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Schedules" class="mb-4" style="width: 200px;">
                                <h3>No Schedules Added Yet</h3>
                                <p class="text-muted">Start by adding a new schedule using the form above.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="delete-column d-none">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classSchedule as $schedule): 
                                            $start = strtotime($schedule['start_time']);
                                            $end = strtotime($schedule['end_time']);
                                            $duration = round(($end - $start) / 3600, 1);
                                            $status = getScheduleStatus($schedule['session_date'], $schedule['start_time']);
                                        ?>
                                            <tr>
                                                <td class="delete-column d-none">
                                                    <div class="form-check">
                                                        <input class="form-check-input schedule-checkbox" 
                                                               type="checkbox" 
                                                               value="<?php echo $schedule['schedule_id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('D, M d, Y', strtotime($schedule['session_date'])); ?>
                                                </td>
                                                <td>
                                                    <?php echo date('h:i A', $start) . ' - ' . date('h:i A', $end); ?>
                                                </td>
                                                <td><?php echo $duration; ?> hours</td>
                                                <td>
                                                    <span class="schedule-status status-<?php echo $status; ?>"></span>
                                                    <?php echo ucfirst($status); ?>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-primary" onclick="editSchedule(<?php echo $schedule['schedule_id']; ?>)" data-bs-toggle="tooltip" title="Edit Schedule">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteSchedule(<?php echo $schedule['schedule_id']; ?>)" data-bs-toggle="tooltip" title="Delete Schedule">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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
        </main>

        <!-- Edit Schedule Modal -->
        <div class="modal fade" id="editScheduleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editScheduleForm">
                            <input type="hidden" id="editScheduleId">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" id="editDate" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="input-group clockpicker">
                                    <input type="text" id="editStartTime" class="form-control" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="input-group clockpicker">
                                    <input type="text" id="editEndTime" class="form-control" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveScheduleChanges()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                initializeClockpicker();
                setupFormValidation();
                setupDeleteMode();
                initializeTooltips();
                setupMobileView();
            });

            function initializeClockpicker() {
                $('.clockpicker').clockpicker({
                    autoclose: true,
                    twelvehour: true,
                    donetext: 'Done',
                    afterDone: function() {
                        validateTimeSlot(this.input);
                    }
                }).on('shown.clockpicker', function(e) {
                    const picker = $(this).data('clockpicker');
                    if (!picker) return;

                    // Update AM/PM button states
                    const ampmBlock = picker.popover.find('.clockpicker-am-pm-block');
                    const amButton = ampmBlock.find('button').first();
                    const pmButton = ampmBlock.find('button').last();

                    // Set initial state based on current value
                    const currentValue = picker.input.val();
                    if (currentValue) {
                        const isPM = currentValue.toLowerCase().includes('pm');
                        picker.amOrPm = isPM ? 'PM' : 'AM';
                        picker.spanAmPm.textContent = picker.amOrPm;
                        
                        amButton.toggleClass('active', !isPM);
                        pmButton.toggleClass('active', isPM);
                    } else {
                        // Default to AM
                        amButton.addClass('active');
                        pmButton.removeClass('active');
                        picker.amOrPm = 'AM';
                        picker.spanAmPm.textContent = 'AM';
                    }

                    // Handle AM/PM button clicks
                    ampmBlock.find('button').off('click').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const isAM = $(this).is(':first-child');
                        const ampm = isAM ? 'AM' : 'PM';
                        
                        // Update button states
                        amButton.toggleClass('active', isAM);
                        pmButton.toggleClass('active', !isAM);
                        
                        // Update picker state
                        picker.amOrPm = ampm;
                        picker.spanAmPm.textContent = ampm;
                        
                        // Update input value
                        const currentValue = picker.input.val();
                        if (currentValue) {
                            const timePart = currentValue.split(' ')[0];
                            picker.input.val(timePart + ' ' + ampm);
                        }
                    });
                });
            }

            function addScheduleEntry() {
                const container = document.getElementById('scheduleContainer');
                const entries = container.getElementsByClassName('schedule-entry');
                const newIndex = entries.length * 3;

                const template = `
                    <div class="schedule-entry">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-btn">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="schedules[${newIndex}][date]" class="form-control schedule-date" required>
                                <div class="form-text">Select a future date</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="schedules[${newIndex + 1}][start]" class="form-control schedule-start" placeholder="--:--" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Time</label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="schedules[${newIndex + 2}][end]" class="form-control schedule-end" placeholder="--:--" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', template);
                initializeClockpicker();
                
                // Show remove buttons if more than one entry
                if (entries.length > 0) {
                    document.querySelectorAll('.remove-btn').forEach(btn => btn.classList.remove('d-none'));
                }
            }

            function setupFormValidation() {
                const form = document.getElementById('scheduleForm');
                
                form.addEventListener('submit', function(e) {
                    if (!validateSchedules()) {
                        e.preventDefault();
                        showToast('Please fix the invalid schedule times.', 'error');
                    }
                });

                document.getElementById('scheduleContainer').addEventListener('click', function(e) {
                    if (e.target.closest('.remove-btn')) {
                        const entry = e.target.closest('.schedule-entry');
                        entry.classList.add('fade-out');
                        setTimeout(() => {
                            entry.remove();
                            updateRemoveButtons();
                        }, 200);
                    }
                });
            }

            function validateSchedules() {
                let isValid = true;
                const entries = document.getElementsByClassName('schedule-entry');
                
                Array.from(entries).forEach(entry => {
                    const date = entry.querySelector('.schedule-date');
                    const start = entry.querySelector('.schedule-start');
                    const end = entry.querySelector('.schedule-end');
                    
                    // Reset validation state
                    [date, start, end].forEach(input => input.classList.remove('is-invalid'));
                    
                    // Validate date is in future
                    const selectedDate = new Date(date.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (selectedDate < today) {
                        isValid = false;
                        date.classList.add('is-invalid');
                        showTooltip(date, 'Date must be in the future');
                    }
                    
                    // Validate end time is after start time
                    if (start.value && end.value) {
                        const startTime = new Date(`1970-01-01T${start.value}`);
                        const endTime = new Date(`1970-01-01T${end.value}`);
                        
                        if (startTime >= endTime) {
                            isValid = false;
                            end.classList.add('is-invalid');
                            showTooltip(end, 'End time must be after start time');
                        }
                    }
                });
                
                return isValid;
            }

            function validateTimeSlot(input) {
                const $input = $(input);
                const $entry = $input.closest('.schedule-entry');
                const $start = $entry.find('.schedule-start');
                const $end = $entry.find('.schedule-end');
                
                if ($start.val() && $end.val()) {
                    const startParts = $start.val().match(/(\d+):(\d+)\s*(AM|PM)/i);
                    const endParts = $end.val().match(/(\d+):(\d+)\s*(AM|PM)/i);
                    
                    if (startParts && endParts) {
                        let startHour = parseInt(startParts[1]);
                        let endHour = parseInt(endParts[1]);
                        const startMin = parseInt(startParts[2]);
                        const endMin = parseInt(endParts[2]);
                        const startPeriod = startParts[3].toUpperCase();
                        const endPeriod = endParts[3].toUpperCase();

                        // Convert to 24-hour format
                        if (startPeriod === 'PM' && startHour !== 12) startHour += 12;
                        if (startPeriod === 'AM' && startHour === 12) startHour = 0;
                        if (endPeriod === 'PM' && endHour !== 12) endHour += 12;
                        if (endPeriod === 'AM' && endHour === 12) endHour = 0;

                        // Compare times
                        if (startHour > endHour || (startHour === endHour && startMin >= endMin)) {
                            $end.addClass('is-invalid');
                            showTooltip($end[0], 'End time must be after start time');
                            return false;
                        } else {
                            $end.removeClass('is-invalid');
                            hideTooltip($end[0]);
                            return true;
                        }
                    }
                }
                return true;
            }

            function showTooltip(element, message) {
                const tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip) {
                    tooltip.dispose();
                }
                new bootstrap.Tooltip(element, {
                    title: message,
                    placement: 'top',
                    trigger: 'manual'
                }).show();
            }

            function hideTooltip(element) {
                const tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip) {
                    tooltip.dispose();
                }
            }

            function showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0`;
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                new bootstrap.Toast(toast).show();
                toast.addEventListener('hidden.bs.toast', () => toast.remove());
            }

            function updateRemoveButtons() {
                const entries = document.getElementsByClassName('schedule-entry');
                const removeButtons = document.querySelectorAll('.remove-btn');
                
                removeButtons.forEach(btn => {
                    btn.classList.toggle('d-none', entries.length === 1);
                });
            }

            function setupMobileView() {
                if (window.innerWidth <= 768) {
                    document.querySelectorAll('.table td').forEach(td => {
                        if (td.textContent.length > 20) {
                            td.setAttribute('data-bs-toggle', 'tooltip');
                            td.setAttribute('title', td.textContent);
                        }
                    });
                }
            }

            function initializeTooltips() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            function setupDeleteMode() {
                const toggleBtn = document.getElementById('toggleDelete');
                const deleteBtn = document.getElementById('deleteSelected');
                const deleteColumns = document.querySelectorAll('.delete-column');
                const selectAll = document.getElementById('selectAll');
                
                toggleBtn.addEventListener('click', function() {
                    const isDeleteMode = toggleBtn.classList.toggle('active');
                    deleteColumns.forEach(col => col.classList.toggle('d-none'));
                    deleteBtn.classList.toggle('d-none');
                    
                    if (!isDeleteMode) {
                        selectAll.checked = false;
                        document.querySelectorAll('.schedule-checkbox').forEach(cb => cb.checked = false);
                    }
                });
                
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.schedule-checkbox').forEach(cb => cb.checked = this.checked);
                });
                
                deleteBtn.addEventListener('click', function() {
                    const selected = Array.from(document.querySelectorAll('.schedule-checkbox:checked'))
                        .map(cb => cb.value);
                        
                    if (selected.length === 0) {
                        alert('Please select schedules to delete');
                        return;
                    }
                    
                    if (confirm(`Are you sure you want to delete ${selected.length} schedule(s)?`)) {
                        deleteSchedules(selected);
                    }
                });
            }

            function editSchedule(id) {
                // Fetch schedule details and populate modal
                fetch(BASE+`api/schedule/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('editScheduleId').value = id;
                        document.getElementById('editDate').value = data.date;
                        document.getElementById('editStartTime').value = data.start_time;
                        document.getElementById('editEndTime').value = data.end_time;
                        
                        const modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                        modal.show();
                        
                        // Reinitialize clockpicker for edit modal
                        $('#editScheduleModal .clockpicker').clockpicker({
                            autoclose: true,
                            twelvehour: true,
                            donetext: 'Done',
                            afterDone: function() {
                                validateTimeSlot(this.input);
                            }
                        });

                        // Prevent modal from closing when clicking clockpicker elements
                        const clockpickerPopover = document.querySelector('.clockpicker-popover');
                        if (clockpickerPopover) {
                            clockpickerPopover.addEventListener('click', function(e) {
                                e.stopPropagation();
                            });
                        }
                    });
            }

            function saveScheduleChanges() {
                const id = document.getElementById('editScheduleId').value;
                const date = document.getElementById('editDate').value;
                const start = document.getElementById('editStartTime').value;
                const end = document.getElementById('editEndTime').value;
                
                fetch(BASE+`api/schedule/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date, start_time: start, end_time: end })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                        location.reload();
                        } else {
                        alert(data.error);
                    }
                });
            }

            function deleteSchedules(ids) {
                fetch(BASE+'api/schedules', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error);
                    }
                });
            }

            // Update the AM/PM button click handlers
            $(document).on('click', '.clockpicker-button.am-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('clockpicker-active');
                $('.clockpicker-button.pm-button').removeClass('clockpicker-active');
            });

            $(document).on('click', '.clockpicker-button.pm-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('clockpicker-active');
                $('.clockpicker-button.am-button').removeClass('clockpicker-active');
            });

            // Add modal specific event prevention
            $('#editScheduleModal').on('shown.bs.modal', function() {
                $('.clockpicker-popover').on('click', function(e) {
                    e.stopPropagation();
                });
            });
        </script>
    </body>
</html>