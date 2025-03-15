<?php 
    require_once '../../backends/main.php';
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }

    try {
        // Get classes data with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $items_per_page = 10;
        $offset = ($page - 1) * $items_per_page;

        // Query to get classes with related information based on existing schema
        $query = "SELECT 
                    c.class_id,
                    c.class_name,
                    s.subject_name,
                    CONCAT(u.first_name, ' ', u.last_name) as techguru_name,
                    (SELECT COUNT(*) FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.role = 'STUDENT') as enrolled_students,
                    c.status
                FROM class c
                LEFT JOIN subject s ON c.subject_id = s.subject_id
                LEFT JOIN users u ON c.tutor_id = u.uid
                ORDER BY c.class_id DESC
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $items_per_page, $offset);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM class";
        $total_result = $conn->query($count_query);
        if (!$total_result) {
            throw new Exception("Count query failed: " . $conn->error);
        }
        $total_classes = $total_result->fetch_assoc()['total'];
        $total_pages = ceil($total_classes / $items_per_page);

    } catch (Exception $e) {
        log_error("Error in view-class.php: " . $e->getMessage(), 'database');
        $_SESSION['msg'] = "An error occurred while fetching classes. Please try again later.";
        $classes = [];
        $total_pages = 0;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Class Management</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS Files -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
    <link href="<?php echo CSS; ?>header.css" rel="stylesheet">
    <style>
        .class-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-restricted {
            background-color: #ffebee;
            color: #c62828;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .search-container {
            margin-bottom: 1.5rem;
        }

        .pagination {
            margin-top: 1.5rem;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Class Management</h5>
                        </div>
                        <div class="card-body">
                            <!-- Search Box -->
                            <div class="search-container">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search classes...">
                                </div>
                            </div>

                            <!-- Classes Table -->
                            <div class="table-responsive">
                                <table class="table table-hover class-table">
                                    <thead>
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Subject</th>
                                            <th>TechGuru</th>
                                            <th>Students Enrolled</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['techguru_name']); ?></td>
                                                <td><?php echo $class['enrolled_students']; ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $class['status']; ?>">
                                                        <?php echo ucfirst($class['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="<?php echo BASE; ?>dashboard/classes/details?id=<?php echo $class['class_id']; ?>" class="btn btn-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <?php if ($class['status'] === 'active' || $class['status'] === 'restricted'): ?>
                                                    <button class="btn btn-warning toggle-status" data-class-id="<?php echo $class['class_id']; ?>" data-current-status="<?php echo $class['status']; ?>">
                                                        <i class="bi bi-shield"></i> <?php echo $class['status'] === 'active' ? 'Restrict' : 'Activate'; ?>
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo JS; ?>dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('.class-table tbody tr');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });

            // Toggle class status
            document.querySelectorAll('.toggle-status').forEach(button => {
                button.addEventListener('click', async function() {
                    const classId = this.dataset.classId;
                    const currentStatus = this.dataset.currentStatus;
                    const newStatus = currentStatus === 'active' ? 'restricted' : 'active';

                    if (!confirm(`Are you sure you want to ${currentStatus === 'active' ? 'restrict' : 'activate'} this class?`)) {
                        return;
                    }

                    try {
                        const response = await fetch('<?php echo BASE; ?>api/toggle-class-status', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                classId: classId,
                                status: newStatus
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // Update UI
                            const statusBadge = this.closest('tr').querySelector('.status-badge');
                            statusBadge.className = `status-badge status-${newStatus}`;
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            
                            // Update button text and data
                            this.innerHTML = `<i class="bi bi-shield"></i> ${newStatus === 'active' ? 'Restrict' : 'Activate'}`;
                            this.dataset.currentStatus = newStatus;
                        } else {
                            throw new Error(data.error || 'Failed to update class status');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while updating the class status. Please try again.');
                        log_error("Class status update failed: " + error.message);
                    }
                });
            });
        });
    </script>
</body>
</html>