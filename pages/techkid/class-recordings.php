<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    require_once BACKEND.'class_management.php';

    // Ensure user is logged in and is a TechKid
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit();
    }

    // Get class ID from URL parameter
    $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Get class details
    $classDetails = getClassDetails($class_id);
    if (!$classDetails) {
        header('Location: ./');
        exit();
    }

    // Get recordings with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $recordings = getClassRecordings($class_id)['recordings'];

    $total_recordings = getClassRecordingsCount($class_id); 
    $total_pages = ceil($total_recordings / $per_page);

    // GET the recording id from the url
    if(isset($_GET['recording']) && !empty($recordings)) {
        $record_id = $_GET['recording'];
        $recordingIds = array_column($recordings, 'recordID');
        
        // Find the index of the specified recording ID
        $index = array_search($record_id, $recordingIds);
        if($index !== false) {
            $url = $recordings[$index]['url'];
            header("Location: ".$url);
        }
    }

    $title = "Class Recordings - " . htmlspecialchars($classDetails['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="dashboard-content bg">
            <!-- Header Section -->
            <div class="content-section mb-4">
                <div class="content-card bg-snow">
                    <div class="card-body">
                        <nav aria-label="breadcrumb" class="breadcrumb-nav">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="<?php echo BASE; ?>dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="./">My Classes</a></li>
                                <li class="breadcrumb-item"><a href="details?id=<?php echo $class_id; ?>"><?php echo htmlspecialchars($classDetails['class_name']); ?></a></li>
                                <li class="breadcrumb-item active">Recordings</li>
                            </ol>
                        </nav>
                        <h1 class="page-title mb-0">Class Recordings</h1>
                    </div>
                </div>
            </div>

            <!-- Recordings Section -->
            <div class="content-section">
                <div class="content-card">
                    <div class="card-body">
                        <?php if (empty($recordings)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-camera-video" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">No recordings available yet</p>
                            </div>
                        <?php else: ?>
                            <div class="recordings-grid">
                                <?php foreach ($recordings as $recording): ?>
                                    <div class="recording-card">
                                        <div class="recording-thumbnail">
                                            <img src="<?php echo $recording['thumbnail_url'] ?? BASE . 'assets/img/video-placeholder.png'; ?>" 
                                                 alt="Recording thumbnail"
                                                 class="img-fluid">
                                            <span class="duration-badge">
                                                <?php echo formatDuration($recording['duration']); ?>
                                            </span>
                                        </div>
                                        <div class="recording-info p-3">
                                            <h5 class="recording-title mb-2">
                                                Session on <?php echo date('F d, Y', strtotime($recording['session_date'])); ?>
                                            </h5>
                                            <p class="text-muted small mb-3">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($recording['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($recording['end_time'])); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary">
                                                    <?php echo $recording['participants']; ?> Participants
                                                </span>
                                                <a href="#"
                                                    onclick="window.open('<?php echo htmlspecialchars($recording['url']); ?>', '_blank')"
                                                   class="btn btn-primary btn-sm">
                                                    <i class="bi bi-play-fill"></i> Watch Now
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Recordings pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $class_id; ?>&page=<?php echo $page-1; ?>">
                                            Previous
                                        </a>
                                    </li>
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $class_id; ?>&page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $class_id; ?>&page=<?php echo $page+1; ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <style>
            .dashboard-content {
                background-color: #F5F5F5;
                min-height: calc(100vh - 60px);
                padding: 1.5rem;
                border-radius: 12px;
            }
            .content-card {
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            .card-body {
                padding: 1.5rem;
            }
            .page-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0;
            }
            .recordings-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
            }
            .recording-card {
                border: 1px solid #e9ecef;
                border-radius: 10px;
                overflow: hidden;
                transition: all 0.2s ease;
            }
            .recording-card:hover {
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
            .recording-thumbnail {
                position: relative;
                padding-top: 56.25%; /* 16:9 aspect ratio */
                background: #f8f9fa;
            }
            .recording-thumbnail img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 1rem;
            }
            .duration-badge {
                position: absolute;
                bottom: 0.5rem;
                right: 0.5rem;
                background: rgba(0,0,0,0.75);
                color: white;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                font-size: 0.875rem;
            }
            .recording-title {
                font-size: 1.1rem;
                font-weight: 500;
                line-height: 1.4;
            }
            @media (max-width: 768px) {
                .dashboard-content {
                    padding: 1rem;
                }
                .card-body {
                    padding: 1rem;
                }
                .page-title {
                    font-size: 1.25rem;
                }
                .recordings-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <?php
        function formatDuration($minutes) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            if ($hours > 0) {
                return sprintf("%d:%02d:00", $hours, $mins);
            }
            return sprintf("%d:00", $mins);
        }
        ?>
    </body>
</html> 