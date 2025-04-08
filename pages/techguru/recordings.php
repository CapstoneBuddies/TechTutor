<?php 
require_once '../../backends/main.php';
require_once BACKEND.'meeting_management.php';
require_once BACKEND.'class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login');
    exit();
}

// Get class ID from URL parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get class details or redirect if invalid
$classDetails = getClassDetails($class_id, $_SESSION['user']);
if (!$classDetails) {
    header('Location: ./');
    exit();
}


// Initialize meeting management and get recordings
$meeting = new MeetingManagement();
$result = $meeting->getClassRecordings($class_id);

if (!$result['success']) {
    log_error("Failed to retrieve recordings for class {$class_id}: {$result['error']}", "meeting");
    $_SESSION['error'] = 'Unable to retrieve recordings at this time. Please try again later.';
}

$recordings = $result['recordings'];
$title = 'Class Recordings - ' . htmlspecialchars($classDetails['class_name']);

$activeCount = 0;
$archivedCount = 0;
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <style>
        .recording-card {
            transition: transform 0.2s;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .recording-card:hover {
            transform: translateY(-2px);
        }
        .recording-thumbnail {
            height: 160px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .recording-thumbnail i {
            font-size: 3rem;
        }
        .recording-info {
            padding: 1rem;
        }
        .recording-actions {
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        .recording-status {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 1;
        }
        @media (max-width: 768px) {
            .recording-thumbnail {
                height: 120px;
            }
        }
    </style>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <main class="container py-4">
            <!-- Header Section -->
            <div class="dashboard-card mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb" class="breadcrumb-nav">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                <li class="breadcrumb-item">
                                    <a href="./?id=<?php echo $class_id; ?>">
                                        <?php echo htmlspecialchars($classDetails['class_name']); ?>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active">Recordings</li>
                            </ol>
                        </nav>
                        <h2 class="page-header mb-0">Class Recordings</h2>
                        <p class="text-muted">Manage and share your class recordings</p>
                    </div>
                    <div>
                        <a href="./?id=<?php echo $class_id;?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Back to Class
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error'];
                        log_error($_SESSION['error']);
                        unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="recordingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-recordings" type="button" role="tab" aria-controls="active-recordings" aria-selected="true">
                        <i class="bi bi-camera-video-fill me-1"></i> Active Recordings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-recordings" type="button" role="tab" aria-controls="archived-recordings" aria-selected="false">
                        <i class="bi bi-archive-fill me-1"></i> Archived Recordings
                        <span class="badge bg-secondary ms-1" id="archived-count">0</span>
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="recordingTabsContent">
                <!-- Active Recordings Tab -->
                <div class="tab-pane fade show active" id="active-recordings" role="tabpanel" aria-labelledby="active-tab">
                    <!-- Recordings Grid -->
                    <div class="row g-4">
                        <?php if (empty($recordings)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-camera-reels display-1 text-muted"></i>
                                    <h4 class="mt-3">No Recordings Available</h4>
                                    <p class="text-muted">Recordings will appear here after your class sessions</p>
                                    <a href="./?id=<?php echo $class_id; ?>" class="btn btn-primary mt-3">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Class Details
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php 
                            foreach ($recordings as $recording): 
                                if (!$recording['archived']):
                                    $activeCount++;
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="recording-card border position-relative">
                                        <!-- Recording Status Badge -->
                                        <div class="recording-status">
                                            <?php 
                                            // Get visibility status from database
                                            $visibilitySettings = getRecordingVisibilitySettings($class_id);
                                            $isVisible = isset($visibilitySettings[$recording['recordID']]) ? 
                                                $visibilitySettings[$recording['recordID']]['is_visible'] : false;
                                            ?>
                                            <span class="badge <?php echo $isVisible ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $isVisible ? 'Visible to Students' : 'Hidden from Students'; ?>
                                            </span>
                                        </div>

                                        <!-- Recording Thumbnail -->
                                        <div class="recording-thumbnail">
                                            <i class="bi bi-play-circle"></i>
                                        </div>

                                        <!-- Recording Info -->
                                        <div class="recording-info">
                                            <h5 class="mb-2"><?php echo htmlspecialchars($recording['name']); ?></h5>
                                            <div class="text-muted small mb-2">
                                                <div><i class="bi bi-calendar me-2"></i><?php echo date('F j, Y', strtotime($recording['session_date'])); ?></div>
                                                <div><i class="bi bi-clock me-2"></i><?php echo date('g:i A', strtotime($recording['start_time'])); ?> - <?php echo date('g:i A', strtotime($recording['end_time'])); ?></div>
                                                <div><i class="bi bi-stopwatch me-2"></i><?php echo round($recording['duration'] / 60); ?> minutes</div>
                                            </div>
                                        </div>

                                        <!-- Recording Actions -->
                                        <div class="recording-actions">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        onclick="window.open('<?php echo htmlspecialchars($recording['url']); ?>', '_blank')"
                                                        class="btn btn-sm btn-primary flex-grow-1">
                                                    <i class="bi bi-play-fill me-1"></i> Play
                                                </button>
                                                
                                                <a href="<?php echo BASE; ?>download-video?id=<?php echo $recording['recordID']; ?>&name=<?php echo urlencode($recording['name']); ?>.mp4"
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Download Recording">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                
                                                <button type="button" 
                                                        class="btn btn-sm btn-dark"
                                                        onclick="toggleArchiveStatus('<?php echo $recording['recordID']; ?>', true)" 
                                                        data-bs-toggle="tooltip" title="Archive Recording">
                                                    <i class="bi bi-archive"></i>
                                                </button>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" 
                                                        class="btn btn-sm <?php echo $isVisible ? 'btn-warning' : 'btn-success'; ?> flex-grow-1"
                                                        onclick="toggleStudentVisibility('<?php echo $recording['recordID']; ?>', <?php echo $isVisible ? 'false' : 'true'; ?>, <?php echo $class_id; ?>, '<?php echo $recording['meetingID']; ?>')">
                                                    <i class="bi bi-<?php echo $isVisible ? 'eye-slash' : 'eye'; ?> me-1"></i>
                                                    <?php echo $isVisible ? 'Hide from Students' : 'Show to Students'; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                else:
                                    $archivedCount++;
                                endif;
                            endforeach; 
                            
                            if ($activeCount === 0): 
                            ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="bi bi-camera-reels display-1 text-muted"></i>
                                        <h4 class="mt-3">No Active Recordings</h4>
                                        <p class="text-muted">All your recordings are currently archived.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Archived Recordings Tab -->
                <div class="tab-pane fade" id="archived-recordings" role="tabpanel" aria-labelledby="archived-tab">
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Note:</strong> Archived recordings will be automatically deleted after 7 days to free up storage space.
                    </div>
                    
                    <div class="row g-4">
                        <?php 
                        if (empty($recordings) || $archivedCount === 0): 
                        ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-archive display-1 text-muted"></i>
                                    <h4 class="mt-3">No Archived Recordings</h4>
                                    <p class="text-muted">Archived recordings will appear here.</p>
                                </div>
                            </div>
                        <?php 
                        else:
                            foreach ($recordings as $recording): 
                                if ($recording['archived']):
                        ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="recording-card border position-relative">
                                        <!-- Recording Thumbnail -->
                                        <div class="recording-thumbnail">
                                            <i class="bi bi-play-circle"></i>
                                        </div>

                                        <!-- Recording Info -->
                                        <div class="recording-info">
                                            <h5 class="mb-2"><?php echo htmlspecialchars($recording['name']); ?></h5>
                                            <div class="text-muted small mb-2">
                                                <div><i class="bi bi-calendar me-2"></i><?php echo date('F j, Y', strtotime($recording['session_date'])); ?></div>
                                                <div><i class="bi bi-clock me-2"></i><?php echo date('g:i A', strtotime($recording['start_time'])); ?> - <?php echo date('g:i A', strtotime($recording['end_time'])); ?></div>
                                                <div><i class="bi bi-stopwatch me-2"></i><?php echo round($recording['duration'] / 60); ?> minutes</div>
                                                <?php 
                                                // Calculate days until deletion (7 days from archive date)
                                                $archiveDate = isset($recording['meta']['archive_date']) ? 
                                                    strtotime($recording['meta']['archive_date']) : 
                                                    time();
                                                $deletionDate = $archiveDate + (7 * 24 * 60 * 60);
                                                $daysLeft = ceil(($deletionDate - time()) / (24 * 60 * 60));
                                                ?>
                                                <div class="mt-2 text-danger">
                                                    <i class="bi bi-trash me-2"></i>
                                                    <strong>Auto-delete in <?php echo $daysLeft; ?> day<?php echo $daysLeft !== 1 ? 's' : ''; ?></strong>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recording Actions -->
                                        <div class="recording-actions">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        onclick="window.open('<?php echo htmlspecialchars($recording['url']); ?>', '_blank')"
                                                        class="btn btn-sm btn-primary flex-grow-1">
                                                    <i class="bi bi-play-fill me-1"></i> Play
                                                </button>
                                                
                                                <a href="<?php echo BASE; ?>backends/handler/download_recording.php?id=<?php echo $recording['recordID']; ?>&name=<?php echo urlencode($recording['name']); ?>.mp4"
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Download Recording">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-secondary flex-grow-1"
                                                        onclick="toggleArchiveStatus('<?php echo $recording['recordID']; ?>', false)">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                    Restore Recording
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php 
                                endif;
                            endforeach; 
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Update the archived count badge
                document.getElementById('archived-count').textContent = <?php echo $archivedCount; ?>;
            });
            
            // Toggle student visibility (show/hide to students)
            function toggleStudentVisibility(recordId, visible, classId, meetingId) {
                if (!confirm('Are you sure you want to ' + (visible ? 'show' : 'hide') + ' this recording ' + (visible ? 'to' : 'from') + ' students?')) {
                    return;
                }
                
                showLoading(true);
                fetch(`${BASE}api/meeting?action=toggle-visibility`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        record_id: recordId,
                        class_id: classId,
                        visible: visible,
                        meeting_id: meetingId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showLoading(false);
                        showToast('success', data.message || 'Recording visibility was successfully updated');
                        setTimeout(() => { location.reload(); }, 1500);
                    } else {
                        showLoading(false);
                        showToast('error', 'Failed to update recording visibility');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while updating the recording visibility');
                });
            }

            // Toggle archive status
            function toggleArchiveStatus(recordId, archive) {
                if (!confirm('Are you sure you want to ' + (archive ? 'archive' : 'unarchive') + ' this recording?')) {
                    return;
                }

                showLoading(true);
                fetch(`${BASE}api/meeting?action=archive-recording&recording_id=${recordId}&archive=${archive}`)
                    .then(response => response.json())
                    .then(data => {
                        showLoading(false);
                        if (data.success) {
                            location.reload();
                        } else {
                            showToast('error', 'Failed to update archive status: ' + (data.error || 'Unknown error'));
                            console.error(data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        showLoading(false);
                        console.error('Error:', error);
                        showToast('error', 'An error occurred while updating the archive status');
                    });
            }
        </script>
    </body>
</html> 