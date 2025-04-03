<?php 
require_once '../../backends/main.php';
require_once BACKEND.'class_management.php';
require_once BACKEND.'meeting_management.php';

// Ensure user is logged in and is an ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id);
if (!$classDetails) {
    header('Location: ./');
    exit();
}

// Initialize meeting management
$meeting = new MeetingManagement();

// Get recordings
$result = $meeting->getClassRecordings($class_id);
$recordings = $result['recordings'] ?? [];

// Separate active and archived recordings
$activeRecordings = array_filter($recordings, function($rec) {
    return !($rec['is_archived'] ?? false);
});

$archivedRecordings = array_filter($recordings, function($rec) {
    return $rec['is_archived'] ?? false;
});

$title = "Class Recordings - " . htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
        
        <main class="container py-4">
            <!-- Header Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-1">
                                            <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="../">Classes</a></li>
                                            <li class="breadcrumb-item d-none d-md-inline"><a href="../.?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                            <li class="breadcrumb-item active">Recordings</li>
                                        </ol>
                                    </nav>
                                    <h2 class="page-header mb-0">Class Recordings</h2>
                                    <p class="text-muted">Manage recordings for <?php echo htmlspecialchars($classDetails['class_name']); ?></p>
                                </div>
                                <div class="mt-2 mt-md-0">
                                    <a href="class-details?id=<?php echo $class_id; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Class
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Active Recordings -->
            <div class="row mt-4" id="active-recordings-container">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-camera-video"></i> Active Recordings
                                <span class="badge bg-primary ms-2" id="active-count"><?php echo count($activeRecordings); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activeRecordings)): ?>
                                <div class="text-center text-muted py-4" id="no-active-recordings">
                                    <i class="bi bi-camera-video-off display-4"></i>
                                    <p class="mt-2">No active recordings available</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="active-recordings-table">
                                        <thead>
                                            <tr>
                                                <th>Session Date</th>
                                                <th>Duration</th>
                                                <th>Size</th>
                                                <th>Participants</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activeRecordings as $recording): ?>
                                                <tr data-recording-id="<?php echo $recording['recordID']; ?>">
                                                    <td>
                                                        <?php echo date('F d, Y', strtotime($recording['session_date'])); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('h:i A', strtotime($recording['start_time'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo $recording['duration']; ?> minutes</td>
                                                    <td><?php echo formatBytes($recording['size']); ?></td>
                                                    <td><?php echo $recording['participants']; ?></td>
                                                    <td class="text-end">
                                                        <div class="btn-group">
                                                            <a href="<?php echo $recording['url']; ?>" 
                                                               target="_blank"
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-play-circle"></i>
                                                                <span class="d-none d-md-inline"> Watch</span>
                                                            </a>
                                                            <a href="<?php echo $recording['download_url']; ?>" 
                                                               class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-download"></i>
                                                                <span class="d-none d-md-inline"> Download</span>
                                                            </a>
                                                            <button type="button" 
                                                                  class="btn btn-sm btn-outline-secondary archive-btn"
                                                                  data-recording-id="<?php echo $recording['recordID']; ?>">
                                                                <i class="bi bi-archive"></i>
                                                                <span class="d-none d-md-inline"> Archive</span>
                                                            </button>
                                                            <button type="button" 
                                                                  class="btn btn-sm btn-outline-danger delete-btn"
                                                                  data-recording-id="<?php echo $recording['recordID']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                                <span class="d-none d-md-inline"> Delete</span>
                                                            </button>
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

            <!-- Archived Recordings -->
            <div class="row mt-4" id="archived-recordings-container">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-archive"></i> Archived Recordings
                                <span class="badge bg-secondary ms-2" id="archived-count"><?php echo count($archivedRecordings); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($archivedRecordings)): ?>
                                <div class="text-center text-muted py-4" id="no-archived-recordings">
                                    <i class="bi bi-archive display-4"></i>
                                    <p class="mt-2">No archived recordings</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="archived-recordings-table">
                                        <thead>
                                            <tr>
                                                <th>Session Date</th>
                                                <th>Duration</th>
                                                <th>Size</th>
                                                <th>Participants</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archivedRecordings as $recording): ?>
                                                <tr data-recording-id="<?php echo $recording['recordID']; ?>">
                                                    <td>
                                                        <?php echo date('F d, Y', strtotime($recording['session_date'])); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('h:i A', strtotime($recording['start_time'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo $recording['duration']; ?> minutes</td>
                                                    <td><?php echo formatBytes($recording['size']); ?></td>
                                                    <td><?php echo $recording['participants']; ?></td>
                                                    <td class="text-end">
                                                        <div class="btn-group">
                                                            <a href="<?php echo $recording['url']; ?>" 
                                                               target="_blank"
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-play-circle"></i>
                                                                <span class="d-none d-md-inline"> Watch</span>
                                                            </a>
                                                            <a href="<?php echo $recording['download_url']; ?>" 
                                                               class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-download"></i>
                                                                <span class="d-none d-md-inline"> Download</span>
                                                            </a>
                                                            <button type="button" 
                                                                  class="btn btn-sm btn-outline-secondary unarchive-btn"
                                                                  data-recording-id="<?php echo $recording['recordID']; ?>">
                                                                <i class="bi bi-archive"></i>
                                                                <span class="d-none d-md-inline"> Unarchive</span>
                                                            </button>
                                                            <button type="button" 
                                                                  class="btn btn-sm btn-outline-danger delete-btn"
                                                                  data-recording-id="<?php echo $recording['recordID']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                                <span class="d-none d-md-inline"> Delete</span>
                                                            </button>
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
        </main>
        
        <?php include ROOT_PATH . '/components/footer.php'; ?>
        
        <script src="<?php echo BASE; ?>assets/js/admin-class-management.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize class ID
                const classId = <?php echo $class_id; ?>;
                
                // Handle Archive Recording
                document.querySelectorAll('.archive-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const recordingId = this.getAttribute('data-recording-id');
                        const row = this.closest('tr');
                        
                        if (confirm('Are you sure you want to archive this recording?')) {
                            ClassManager.archiveRecording(recordingId, true, classId, function(response) {
                                // Move the recording to archived section
                                const archivedTable = document.getElementById('archived-recordings-table');
                                const noArchivedRecordings = document.getElementById('no-archived-recordings');
                                
                                if (noArchivedRecordings) {
                                    noArchivedRecordings.remove();
                                    
                                    // Create table if it doesn't exist
                                    if (!archivedTable) {
                                        const tableHtml = `
                                            <div class="table-responsive">
                                                <table class="table table-hover" id="archived-recordings-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Session Date</th>
                                                            <th>Duration</th>
                                                            <th>Size</th>
                                                            <th>Participants</th>
                                                            <th class="text-end">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        `;
                                        
                                        document.querySelector('#archived-recordings-container .card-body').innerHTML = tableHtml;
                                    }
                                }
                                
                                // Update action buttons for archived state
                                const actionsTd = row.querySelector('td:last-child');
                                const archiveBtn = actionsTd.querySelector('.archive-btn');
                                archiveBtn.classList.remove('archive-btn');
                                archiveBtn.classList.add('unarchive-btn');
                                archiveBtn.querySelector('span').textContent = ' Unarchive';
                                
                                // Move row to archived table
                                document.querySelector('#archived-recordings-table tbody').appendChild(row);
                                
                                // Update counts
                                updateRecordingCounts();
                                
                                // Check if active table is now empty
                                const activeTable = document.getElementById('active-recordings-table');
                                if (activeTable && activeTable.querySelectorAll('tbody tr').length === 0) {
                                    const noActiveHtml = `
                                        <div class="text-center text-muted py-4" id="no-active-recordings">
                                            <i class="bi bi-camera-video-off display-4"></i>
                                            <p class="mt-2">No active recordings available</p>
                                        </div>
                                    `;
                                    
                                    document.querySelector('#active-recordings-container .card-body').innerHTML = noActiveHtml;
                                }
                            });
                        }
                    });
                });
                
                // Handle Unarchive Recording
                document.querySelectorAll('.unarchive-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const recordingId = this.getAttribute('data-recording-id');
                        const row = this.closest('tr');
                        
                        if (confirm('Are you sure you want to unarchive this recording?')) {
                            ClassManager.archiveRecording(recordingId, false, classId, function(response) {
                                // Move the recording to active section
                                const activeTable = document.getElementById('active-recordings-table');
                                const noActiveRecordings = document.getElementById('no-active-recordings');
                                
                                if (noActiveRecordings) {
                                    noActiveRecordings.remove();
                                    
                                    // Create table if it doesn't exist
                                    if (!activeTable) {
                                        const tableHtml = `
                                            <div class="table-responsive">
                                                <table class="table table-hover" id="active-recordings-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Session Date</th>
                                                            <th>Duration</th>
                                                            <th>Size</th>
                                                            <th>Participants</th>
                                                            <th class="text-end">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        `;
                                        
                                        document.querySelector('#active-recordings-container .card-body').innerHTML = tableHtml;
                                    }
                                }
                                
                                // Update action buttons for active state
                                const actionsTd = row.querySelector('td:last-child');
                                const unarchiveBtn = actionsTd.querySelector('.unarchive-btn');
                                unarchiveBtn.classList.remove('unarchive-btn');
                                unarchiveBtn.classList.add('archive-btn');
                                unarchiveBtn.querySelector('span').textContent = ' Archive';
                                
                                // Move row to active table
                                document.querySelector('#active-recordings-table tbody').appendChild(row);
                                
                                // Update counts
                                updateRecordingCounts();
                                
                                // Check if archived table is now empty
                                const archivedTable = document.getElementById('archived-recordings-table');
                                if (archivedTable && archivedTable.querySelectorAll('tbody tr').length === 0) {
                                    const noArchivedHtml = `
                                        <div class="text-center text-muted py-4" id="no-archived-recordings">
                                            <i class="bi bi-archive display-4"></i>
                                            <p class="mt-2">No archived recordings</p>
                                        </div>
                                    `;
                                    
                                    document.querySelector('#archived-recordings-container .card-body').innerHTML = noArchivedHtml;
                                }
                            });
                        }
                    });
                });
                
                // Handle Delete Recording
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const recordingId = this.getAttribute('data-recording-id');
                        const row = this.closest('tr');
                        
                        // Use ClassManager to handle deletion
                        ClassManager.deleteRecording(recordingId, classId, function(response) {
                            // Remove row from table
                            row.remove();
                            
                            // Update counts
                            updateRecordingCounts();
                            
                            // Check if tables are now empty
                            const activeTable = document.getElementById('active-recordings-table');
                            if (activeTable && activeTable.querySelectorAll('tbody tr').length === 0) {
                                const noActiveHtml = `
                                    <div class="text-center text-muted py-4" id="no-active-recordings">
                                        <i class="bi bi-camera-video-off display-4"></i>
                                        <p class="mt-2">No active recordings available</p>
                                    </div>
                                `;
                                
                                document.querySelector('#active-recordings-container .card-body').innerHTML = noActiveHtml;
                            }
                            
                            const archivedTable = document.getElementById('archived-recordings-table');
                            if (archivedTable && archivedTable.querySelectorAll('tbody tr').length === 0) {
                                const noArchivedHtml = `
                                    <div class="text-center text-muted py-4" id="no-archived-recordings">
                                        <i class="bi bi-archive display-4"></i>
                                        <p class="mt-2">No archived recordings</p>
                                    </div>
                                `;
                                
                                document.querySelector('#archived-recordings-container .card-body').innerHTML = noArchivedHtml;
                            }
                        });
                    });
                });
                
                // Helper function to update recording counts
                function updateRecordingCounts() {
                    const activeCount = document.getElementById('active-recordings-table') 
                        ? document.querySelectorAll('#active-recordings-table tbody tr').length
                        : 0;
                        
                    const archivedCount = document.getElementById('archived-recordings-table')
                        ? document.querySelectorAll('#archived-recordings-table tbody tr').length
                        : 0;
                        
                    document.getElementById('active-count').textContent = activeCount;
                    document.getElementById('archived-count').textContent = archivedCount;
                }
                
                // Helper function to format bytes
                function formatBytes(bytes, decimals = 2) {
                    if (bytes === 0) return '0 Bytes';
                    
                    const k = 1024;
                    const dm = decimals < 0 ? 0 : decimals;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
                }
            });
        </script>
        
        <style>
            /* Common Admin Class Pages Styling */
            .page-header {
                font-size: 1.75rem;
                font-weight: 600;
                color: var(--primary-color, #0052cc);
            }
            
            .breadcrumb {
                font-size: 0.875rem;
            }
            
            .breadcrumb-item.active {
                color: var(--primary-color, #0052cc);
                font-weight: 500;
            }
            
            .card {
                border-radius: 0.5rem;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                margin-bottom: 1.5rem;
                overflow: hidden;
            }
            
            .card-header {
                background-color: rgba(0, 0, 0, 0.02);
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 1rem;
            }
            
            .card-header .card-title {
                margin-bottom: 0;
                display: flex;
                align-items: center;
            }
            
            .card-header .card-title i {
                margin-right: 0.5rem;
                color: var(--primary-color, #0052cc);
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .btn {
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .btn i {
                font-size: 1.1em;
            }
            
            .img-error {
                opacity: 0.7;
                background-color: #f8f9fa !important;
                border: 1px dashed #ccc !important;
            }
            
            /* Mobile Responsiveness */
            @media (max-width: 991.98px) {
                .container {
                    max-width: 100%;
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
                
                .card-body {
                    padding: 1rem;
                }
                
                .row {
                    margin-left: -0.5rem;
                    margin-right: -0.5rem;
                }
                
                .btn-group {
                    flex-wrap: nowrap;
                }
                
                .btn-group .btn {
                    padding: 0.375rem 0.5rem;
                    font-size: 0.875rem;
                }
            }
            
            @media (max-width: 767.98px) {
                .page-header {
                    font-size: 1.5rem;
                }
                
                .d-flex {
                    flex-wrap: wrap;
                }
                
                .table-responsive {
                    margin: 0 -1rem;
                    padding: 0 1rem;
                    width: calc(100% + 2rem);
                }
                
                .btn-group {
                    gap: 0.25rem;
                }
            }
            
            @media (max-width: 575.98px) {
                .card-header .card-title {
                    font-size: 1.1rem;
                }
                
                .py-4 {
                    padding-top: 1rem !important;
                    padding-bottom: 1rem !important;
                }
                
                .mt-4 {
                    margin-top: 1rem !important;
                }
                
                .mb-4 {
                    margin-bottom: 1rem !important;
                }
                
                .table th, .table td {
                    padding: 0.5rem 0.25rem;
                    font-size: 0.875rem;
                }
            }
        </style>
    </body>
</html> 