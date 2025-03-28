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

// Check if class is active
if (!in_array($classDetails['status'], ['active','ongoing'])) {
    log_error($classDetails['status']);
    $_SESSION['error'] = 'This class is not currently active. Recordings are only available for active classes.';
    header('Location: ./?id=' . $class_id);
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
                                    <a href="class-details?id=<?php echo $class_id; ?>">
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
                    <?php foreach ($recordings as $recording): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="recording-card border position-relative">
                                <!-- Recording Status Badge -->
                                <div class="recording-status">
                                    <span class="badge <?php echo $recording['published'] ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $recording['published'] ? 'Published' : 'Unpublished'; ?>
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
                                        <?php if ($recording['download_url']): ?>
                                        <a href="<?php echo htmlspecialchars($recording['download_url']); ?>" 
                                           download
                                           class="btn btn-sm btn-info">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-sm <?php echo $recording['archived'] ? 'btn-secondary' : 'btn-dark'; ?>"
                                                onclick="toggleArchiveStatus('<?php echo $recording['recordID']; ?>', <?php echo $recording['archived'] ? 'false' : 'true'; ?>)">
                                            <i class="bi bi-archive<?php echo $recording['archived'] ? '-fill' : ''; ?>"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" 
                                                class="btn btn-sm <?php echo $recording['published'] ? 'btn-warning' : 'btn-success'; ?> flex-grow-1"
                                                onclick="toggleRecordingVisibility('<?php echo $recording['recordID']; ?>', <?php echo $recording['published'] ? 'false' : 'true'; ?>)">
                                            <i class="bi bi-<?php echo $recording['published'] ? 'eye-slash' : 'eye'; ?> me-1"></i>
                                            <?php echo $recording['published'] ? 'Unpublish' : 'Publish'; ?>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="deleteRecording('<?php echo $recording['recordID']; ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>
        <script>
            // Toggle recording visibility (publish/unpublish)
            function toggleRecordingVisibility(recordId, publish) {
                if (!confirm('Are you sure you want to ' + (publish ? 'publish' : 'unpublish') + ' this recording?')) {
                    return;
                }

                showLoading(true);
                fetch(`${BASE}api/meeting?action=toggle_recording`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'toggle_recording',
                        record_id: recordId,
                        publish: publish
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        location.reload();
                    } else {
                        showToast('error', 'Failed to update recording visibility: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while updating the recording visibility');
                });
            }

            // Delete recording
            function deleteRecording(recordId) {
                if (!confirm('Are you sure you want to delete this recording? This action cannot be undone.')) {
                    return;
                }

                showLoading(true);
                fetch(`${BASE}api/meeting?action=delete_recording`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_recording',
                        record_id: recordId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        location.reload();
                    } else {
                        showToast('error', 'Failed to delete recording: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    showLoading(false);
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while deleting the recording');
                });
            }

            // Toggle archive status
            function toggleArchiveStatus(recordId, archive) {
                if (!confirm('Are you sure you want to ' + (archive ? 'archive' : 'unarchive') + ' this recording?')) {
                    return;
                }

                showLoading(true);
                fetch(`${BASE}api/meeting?action=archive_recording`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'archive_recording',
                        record_id: recordId,
                        archive: archive
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        location.reload();
                    } else {
                        showToast('error', 'Failed to update archive status: ' + (data.error || 'Unknown error'));
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