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
                                <a href="./" class="btn btn-outline-primary">
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
                    <div class="dashboard-card">
                        <h3 class="card-title mb-4">Add New Schedule</h3>
                        <form method="POST" action="">
                            <div id="scheduleContainer">
                                <div class="schedule-entry row mb-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Date</label>
                                            <input type="date" name="schedules[0][date]" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Start Time</label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                                <input type="text" name="schedules[1][start]" class="form-control" placeholder="--:--" required>
                                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">End Time</label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                                <input type="text" name="schedules[2][end]" class="form-control" placeholder="--:--" required>
                                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-secondary" onclick="addScheduleEntry()">
                                    <i class="bi bi-plus-lg"></i> Add Another Schedule
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Schedules Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="card-title mb-0">Current Schedules</h3>
                            <button id="toggleDelete" class="btn btn-outline-danger" onclick="toggleDeleteMode()">
                                <i class="bi bi-trash"></i> Delete Schedules
                            </button>
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
                                            <th class="delete-column d-none" width="100">
                                                <div class="form-check">
                                                    <div class="select-all-wrapper">
                                                        <input class="form-check-input" type="checkbox" id="selectAll" name="selectAll">
                                                        <label class="form-check-label" for="selectAll">Select All</label>
                                                    </div>
                                                </div>
                                            </th>
                                            <th>Date</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th width="100" class="actions-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classSchedule as $schedule): ?>
                                            <tr>
                                                <td class="delete-column d-none">
                                                    <div class="form-check">
                                                        <input class="form-check-input schedule-checkbox" 
                                                               type="checkbox" 
                                                               value="<?php echo $schedule['schedule_id']; ?>">
                                                    </div>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($schedule['session_date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                                <td class="actions-column">
                                                    <button class="btn btn-sm btn-primary" onclick="editSchedule(<?php echo $schedule['schedule_id']; ?>)">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div id="deleteControls" class="mt-3 d-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="text-muted small schedule-counter"></div>
                                        <button id="deleteSelected" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Delete Selected
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="toggleDeleteMode()">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            // Initialize clockpicker
            $('.clockpicker').clockpicker({
                placement: 'bottom',
                align: 'left',
                autoclose: true,
                'default': 'now'
            });

            function addScheduleEntry() {
                const container = document.getElementById('scheduleContainer');
                const index = container.children.length * 3;
                const entry = `
                    <div class='schedule-entry row mb-3'>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type='date' name='schedules[${index}][date]' class='form-control' required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Start Time</label>
                                <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                    <input type='text' name='schedules[${index + 1}][start]' class='form-control' placeholder="--:--" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">End Time</label>
                                <div class="input-group clockpicker" data-placement="bottom" data-align="left" data-autoclose="true">
                                    <input type='text' name='schedules[${index + 2}][end]' class='form-control' placeholder="--:--" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="delete-schedule-btn" onclick="deleteScheduleEntry(this)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>`;
                container.insertAdjacentHTML('beforeend', entry);
                
                // Add first-schedule class to the first entry
                if (container.children.length === 1) {
                    container.firstElementChild.classList.add('first-schedule');
                }
                
                // Initialize clockpicker for new elements
                const newRow = container.lastElementChild;
                $(newRow).find('.clockpicker').clockpicker({
                    placement: 'bottom',
                    align: 'left',
                    autoclose: true,
                    'default': 'now'
                });
            }

            function deleteScheduleEntry(button) {
                const entry = button.closest('.schedule-entry');
                entry.remove();
                
                // Update the first-schedule class
                const container = document.getElementById('scheduleContainer');
                if (container.children.length === 1) {
                    container.firstElementChild.classList.add('first-schedule');
                }
                
                // Reindex the remaining entries
                reindexScheduleEntries();
            }

            function reindexScheduleEntries() {
                const container = document.getElementById('scheduleContainer');
                const entries = container.getElementsByClassName('schedule-entry');
                
                Array.from(entries).forEach((entry, i) => {
                    const index = i * 3;
                    entry.querySelector('input[type="date"]').name = `schedules[${index}][date]`;
                    entry.querySelector('input[type="text"][name*="[start]"]').name = `schedules[${index + 1}][start]`;
                    entry.querySelector('input[type="text"][name*="[end]"]').name = `schedules[${index + 2}][end]`;
                });
            }

            // Add first-schedule class to the initial entry
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('scheduleContainer');
                if (container.children.length === 1) {
                    container.firstElementChild.classList.add('first-schedule');
                }
            });

            function toggleDeleteMode() {
                const table = document.querySelector('table');
                const deleteControls = document.getElementById('deleteControls');
                const toggleBtn = document.getElementById('toggleDelete');
                
                table.classList.toggle('delete-mode');
                if (table.classList.contains('delete-mode')) {
                    deleteControls.classList.remove('d-none');
                    toggleBtn.classList.replace('btn-outline-danger', 'btn-danger');
                } else {
                    deleteControls.classList.add('d-none');
                    toggleBtn.classList.replace('btn-danger', 'btn-outline-danger');
                    // Uncheck all checkboxes
                    document.querySelectorAll('.schedule-checkbox, #selectAll').forEach(cb => cb.checked = false);
                    updateDeleteButton();
                }
            }

            // Handle select all checkbox
            document.getElementById('selectAll')?.addEventListener('change', function() {
                document.querySelectorAll('.schedule-checkbox').forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButton();
            });

            // Handle individual checkboxes
            document.querySelectorAll('.schedule-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateDeleteButton);
            });

            function updateDeleteButton() {
                const selectedCount = document.querySelectorAll('.schedule-checkbox:checked').length;
                const counterDiv = document.querySelector('.schedule-counter');
                
                if (selectedCount > 0) {
                    counterDiv.textContent = `${selectedCount} schedule(s) selected`;
                } else {
                    counterDiv.textContent = '';
                }
            }

            function editSchedule(scheduleId) {
                // Implement edit functionality
                console.log('Edit schedule:', scheduleId);
            }

            document.getElementById('deleteSelected')?.addEventListener('click', function() {
                const selectedIds = Array.from(document.querySelectorAll('.schedule-checkbox:checked'))
                    .map(checkbox => checkbox.value);

                if (selectedIds.length === 0) return;

                if (confirm(`Are you sure you want to delete ${selectedIds.length} schedule(s)?`)) {
                    showLoading();
                    const formData = new FormData();
                    formData.append('classId', <?php echo $class_id; ?>);
                    selectedIds.forEach(id => formData.append('scheduleIds[]', id));

                    fetch(`${document.body.dataset.base}backends/api/schedule_delete.php`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', data.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showToast('error', data.message || 'Failed to delete schedules');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'An error occurred while deleting schedules');
                    })
                    .finally(() => {
                        hideLoading();
                    });
                }
            });
        </script>
    </body>
</html>