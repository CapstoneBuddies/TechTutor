<?php 
    require_once '../../backends/main.php';
    require_once ROOT_PATH.'/backends/student_management.php';
    
    $files = [];
    try {
        // Get student's files using centralized function
        $files = getStudentFiles($_SESSION['user']);
    } catch (Exception $e) {
        log_error("Files page error: " . $e->getMessage(), "database");
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>">
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="page-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>My Learning Materials</h1>
            <p>Access your course materials, assignments, and resources</p>
        </div>

        <!-- Search and Filter -->
        <div class="content-card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchFiles" 
                                placeholder="Search files by name or type...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="filterType">
                            <option value="">All File Types</option>
                            <option value="document">Documents</option>
                            <option value="video">Videos</option>
                            <option value="assignment">Assignments</option>
                            <option value="resource">Resources</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="sortBy">
                            <option value="date_desc">Newest First</option>
                            <option value="date_asc">Oldest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Files Section -->
        <div class="row">
            <!-- Recent Files -->
            <div class="col-md-8">
                <h2 class="section-title">
                    <i class="bi bi-file-earmark-text"></i>
                    Recent Files
                </h2>

                <?php if (empty($files['recent'])): ?>
                <div class="content-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-folder2-open text-muted" style="font-size: 48px;"></i>
                        <h3 class="mt-3">No Files Yet</h3>
                        <p class="text-muted">Your learning materials will appear here</p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($files['recent'] as $file): ?>
                    <div class="content-card file-card mb-2">
                        <div class="file-icon">
                            <?php 
                                $icon = 'file-earmark-text';
                                switch($file['type']) {
                                    case 'video': $icon = 'file-earmark-play'; break;
                                    case 'pdf': $icon = 'file-earmark-pdf'; break;
                                    case 'image': $icon = 'file-earmark-image'; break;
                                }
                            ?>
                            <i class="bi bi-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="file-info">
                            <div class="file-name"><?php echo $file['name']; ?></div>
                            <div class="file-meta">
                                <?php echo $file['size']; ?> • 
                                Added <?php echo date('M d, Y', strtotime($file['date_added'])); ?>
                            </div>
                        </div>
                        <div class="file-actions">
                            <button class="btn btn-sm btn-outline me-2" onclick="previewFile('<?php echo $file['id']; ?>')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="<?php echo $file['url']; ?>" class="btn btn-sm btn-primary" download>
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- File Categories -->
            <div class="col-md-4">
                <h2 class="section-title">
                    <i class="bi bi-folder2"></i>
                    Categories
                </h2>

                <div class="content-card mb-4">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                Documents
                            </div>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo count($files['documents'] ?? []); ?>
                            </span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-play text-success me-2"></i>
                                Videos
                            </div>
                            <span class="badge bg-success rounded-pill">
                                <?php echo count($files['videos'] ?? []); ?>
                            </span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-check text-warning me-2"></i>
                                Assignments
                            </div>
                            <span class="badge bg-warning rounded-pill">
                                <?php echo count($files['assignments'] ?? []); ?>
                            </span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-code text-info me-2"></i>
                                Resources
                            </div>
                            <span class="badge bg-info rounded-pill">
                                <?php echo count($files['resources'] ?? []); ?>
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Storage Usage -->
                <h2 class="section-title">
                    <i class="bi bi-hdd"></i>
                    Storage Usage
                </h2>
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Used Space</span>
                            <span><?php echo $files['storage']['used']; ?> / <?php echo $files['storage']['total']; ?></span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" 
                                style="width: <?php echo $files['storage']['percentage']; ?>%"
                                aria-valuenow="<?php echo $files['storage']['percentage']; ?>" 
                                aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $files['storage']['free']; ?> free space remaining
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

    <script>
        // File search functionality
        document.getElementById('searchFiles').addEventListener('input', function(e) {
            // Implement file search
            console.log('Searching:', e.target.value);
        });

        // File type filter
        document.getElementById('filterType').addEventListener('change', function(e) {
            // Implement file type filtering
            console.log('Filtering by:', e.target.value);
        });

        // Sort files
        document.getElementById('sortBy').addEventListener('change', function(e) {
            // Implement file sorting
            console.log('Sorting by:', e.target.value);
        });

        function previewFile(fileId) {
            // Implement file preview
            console.log('Previewing file:', fileId);
        }
    </script>
</body>
</html>