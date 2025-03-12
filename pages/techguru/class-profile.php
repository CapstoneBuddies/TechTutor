<?php 
require_once '../../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';
require_once ROOT_PATH . '/backends/class_management.php';

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    header('Location: ' . BASE . 'login.php');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$class_id) {
    header('Location: ' . BASE . 'pages/techguru/techguru_classes.php');
    exit();
}

// Get class details
$class = getClassDetails($class_id, $_SESSION['user_id']);
if (!$class) {
    $_SESSION['error'] = "Class not found or access denied";
    header('Location: ' . BASE . 'pages/techguru/techguru_classes.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Class Profile - <?php echo htmlspecialchars($class['class_name']); ?></title>
    
    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    
    <!-- Vendor CSS -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>techguru-common.css" rel="stylesheet">
</head>

<body>
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
                                    <li class="breadcrumb-item"><a href="techguru_classes.php">My Classes</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($class['class_name']); ?></li>
                                </ol>
                            </nav>
                            <h2 class="page-header"><?php echo htmlspecialchars($class['class_name']); ?></h2>
                            <p class="subtitle"><?php echo htmlspecialchars($class['subject_name']); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                                <i class="bi bi-plus-lg"></i>
                                Add Topic
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row mt-4">
            <!-- Topics Section -->
            <div class="col-md-8">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title mb-0">Topics</h3>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" onclick="toggleDeleteMode()">
                                <i class="bi bi-trash"></i>
                                Manage Topics
                            </button>
                        </div>
                    </div>

                    <div class="list-group topics-list">
                        <?php 
                        $topics = [
                            'Introduction to Java',
                            'Java OOP Concepts',
                            'Control Flow Statements',
                            'Exception Handling',
                            'Java Collections Framework',
                            'Generics',
                            'Input/Output (I/O) in Java',
                            'Multithreading and Concurrency',
                            'Lambda Expressions',
                            'GUI Programming',
                            'Best Practices'
                        ];
                        
                        if (empty($topics)): ?>
                            <div class="text-center py-5">
                                <img src="<?php echo IMG; ?>illustrations/no-data.svg" alt="No Topics" class="mb-4" style="width: 200px;">
                                <h3>No Topics Yet</h3>
                                <p class="text-muted">Add your first topic using the button above.</p>
                            </div>
                        <?php else: 
                            foreach ($topics as $topic): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="checkbox" class="delete-checkbox form-check-input" style="display: none;">
                                        <a href="#" class="text-decoration-none text-dark">
                                            <i class="bi bi-file-text me-2"></i>
                                            <?php echo htmlspecialchars($topic); ?>
                                        </a>
                                    </div>
                                    <div class="topic-actions">
                                        <button class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>

                    <div id="deleteActions" class="mt-3" style="display: none;">
                        <button class="btn btn-danger" onclick="deleteSelectedTopics()">
                            <i class="bi bi-trash"></i>
                            Delete Selected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Class Stats -->
            <div class="col-md-4">
                <!-- Enrollment Stats -->
                <div class="dashboard-card mb-4">
                    <h3 class="card-title">Enrollment Stats</h3>
                    <div class="d-flex align-items-center mt-3">
                        <div class="display-4 me-3">75</div>
                        <div class="text-muted">Total Enrolled<br>TechKids</div>
                    </div>
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-end text-muted mt-2">75/100 slots filled</div>
                </div>

                <!-- Class Schedule -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title mb-0">Class Schedule</h3>
                        <button class="btn btn-sm btn-outline-primary">View All</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Schedule</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Morning Session</div>
                                        <small class="text-muted">Mon, Wed, Fri - 10:00 AM</small>
                                    </td>
                                    <td>25</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Afternoon Session</div>
                                        <small class="text-muted">Tue, Thu - 2:00 PM</small>
                                    </td>
                                    <td>30</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">Weekend Session</div>
                                        <small class="text-muted">Sat - 11:00 AM</small>
                                    </td>
                                    <td>20</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Topic Modal -->
    <div class="modal fade" id="addTopicModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="topicForm">
                        <div class="mb-3">
                            <label class="form-label">Topic Title</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Materials</label>
                            <input type="file" class="form-control" multiple>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="topicForm" class="btn btn-primary">Add Topic</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDeleteMode() {
            const checkboxes = document.querySelectorAll('.delete-checkbox');
            const deleteActions = document.getElementById('deleteActions');
            const topicActions = document.querySelectorAll('.topic-actions');
            
            checkboxes.forEach(checkbox => {
                checkbox.style.display = checkbox.style.display === 'none' ? 'block' : 'none';
            });
            
            topicActions.forEach(actions => {
                actions.style.display = actions.style.display === 'none' ? 'block' : 'none';
            });
            
            deleteActions.style.display = deleteActions.style.display === 'none' ? 'block' : 'none';
        }

        function deleteSelectedTopics() {
            const checkboxes = document.querySelectorAll('.delete-checkbox:checked');
            checkboxes.forEach(checkbox => {
                checkbox.closest('.list-group-item').remove();
            });
            toggleDeleteMode();
        }
    </script>
</body>
</html>