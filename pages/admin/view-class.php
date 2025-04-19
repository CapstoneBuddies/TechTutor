<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'class_management.php'; 
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
        header("Location: " . BASE . "dashboard");
        exit();
    }

    // Get classes data with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 10;

    $classData = getClassesWithPagination($page,$items_per_page);
    $classes = $classData['classes'];
    $total_pages = $classData['total_pages'];

    $title = 'Class Management';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-1">
                                    <li class="breadcrumb-item"><a href="./">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Classes</li>
                                </ol>
                            </nav>
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
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo CLASS_IMG . (!empty($class['thumbnail']) ? $class['thumbnail'] : 'default.jpg'); ?>" 
                                                         alt="Class thumbnail" 
                                                         class="class-thumbnail me-2"
                                                         onerror="this.src='<?php echo CLASS_IMG; ?>default.jpg'; this.classList.add('img-error');">
                                                    <div>
                                                        <div class="class-name"><?php echo htmlspecialchars($class['class_name']); ?></div>
                                                        <small class="text-muted"><?php echo !empty($class['start_date']) ? date('M d, Y', strtotime($class['start_date'])) : 'N/A'; ?> - <?php echo !empty($class['end_date']) ? date('M d, Y', strtotime($class['end_date'])) : 'N/A'; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo USER_IMG . (!empty($class['techguru_profile']) ? $class['techguru_profile'] : 'default.jpg'); ?>" 
                                                         alt="TechGuru" 
                                                         class="tutor-avatar me-2"
                                                         onerror="this.src='<?php echo CLASS_IMG; ?>default.jpg'; this.classList.add('img-error');">
                                                    <?php echo htmlspecialchars($class['techguru_name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                        $total_students = isset($class['enrolled_students']) ? (int)$class['enrolled_students'] : 0;
                                                        $class_size = isset($class['class_size']) ? (int)$class['class_size'] : 1; // Use 1 as minimum to avoid division by zero
                                                        if ($class_size == 0) $class_size = 1; // Extra safety check
                                                        $percentage = ($total_students / $class_size) * 100;
                                                    ?>
                                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%" 
                                                             aria-valuenow="<?php echo $total_students; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="<?php echo $class_size; ?>">
                                                        </div>
                                                    </div>
                                                    <span class="text-muted small"><?php echo $total_students; ?>/<?php echo $class_size; ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $class['status'] === 'active' ? 'success' : 
                                                        ($class['status'] === 'completed' ? 'primary' : 
                                                        ($class['status'] === 'cancelled' ? 'danger' : 'warning')); 
                                                ?>">
                                                    <?php echo ucfirst($class['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="classes/details?id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                        <span class="d-none d-md-inline"> View</span>
                                                    </a>
                                                    <a href="classes/details/edit?id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                        <span class="d-none d-md-inline"> Edit</span>
                                                    </a>
                                                </div>
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

    <?php include ROOT_PATH . '/components/footer.php'; ?>
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

            // Handle image errors
            document.querySelectorAll('.class-thumbnail, .tutor-avatar').forEach(img => {
                img.addEventListener('error', function() {
                    // Set a default image or just a background
                    this.src = '<?php echo CLASS_IMG; ?>default.jpg';
                    // Add a class for styling
                    this.classList.add('img-error');
                });
            });

            // Toggle class status
            document.querySelectorAll('.toggle-status').forEach(button => {
                button.addEventListener('click', async function () {
                    const classId = this.dataset.classId;
                    const currentStatus = this.dataset.currentStatus;
                    const newStatus = currentStatus === 'active' ? 'restricted' : 'active';

                    if (!confirm(`Are you sure you want to ${currentStatus === 'active' ? 'restrict' : 'activate'} this class?`)) {
                        return;
                    }

                    try {
                        showLoading(true);
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
                        showLoading(false);
                        if (data.success) {
                            // Update UI
                            const statusBadge = this.closest('tr').querySelector('.status-badge');
                            statusBadge.className = `status-badge status-${newStatus}`;
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

                            // Update button text and data
                            this.innerHTML = `<i class="bi bi-shield"></i> ${newStatus === 'active' ? 'Restrict' : 'Activate'}`;
                            this.dataset.currentStatus = newStatus;

                            // Show success toast
                            showToast('success', `Class successfully ${newStatus === 'active' ? 'activated' : 'restricted'}.`);
                        } else {
                            throw new Error(data.error || 'Failed to update class status');
                        }
                    } catch (error) {
                        showLoading(false);
                        console.error('Error:', error);
                        
                        // Show error toast
                        showToast('error', 'An error occurred while updating the class status. Please try again.');

                        log_error("Class status update failed: " + error.message);
                    }
                })
            });
        });
    </script>

    <style>
        /* Class listing styles */
        .class-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #f0f0f0; /* Fallback background color */
            border: 1px solid #e0e0e0;
        }
        
        .tutor-avatar {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            background-color: #f0f0f0; /* Fallback background color */
            border: 1px solid #e0e0e0;
        }
        
        /* Add error handling for broken images */
        .class-thumbnail:error, .tutor-avatar:error {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 10px;
            color: #999;
            background-color: #f8f9fa;
        }
        
        .img-error {
            opacity: 0.7;
            background-color: #f8f9fa !important;
            border: 1px dashed #ccc !important;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .class-table td {
            vertical-align: middle;
            padding: 0.75rem 0.5rem;
        }
        
        .class-name {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .search-container {
            max-width: 400px;
            margin-bottom: 1.5rem;
        }
        
        .search-container .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .search-container .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .search-container .form-control {
            border-left: none;
            padding-left: 0;
        }
        
        .search-container .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        
        .progress {
            height: 6px;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        .pagination {
            margin-top: 1.5rem;
            justify-content: center;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--primary-color);
            border-radius: 4px;
            margin: 0 2px;
        }
        
        .btn-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.375rem 0.75rem;
            transition: all 0.2s;
        }
        
        .btn-group .btn i {
            margin-right: 0.25rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1199px) {
            .class-table th:nth-child(2),
            .class-table td:nth-child(2) {
                display: table-cell;
            }
            
            .class-name {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 991px) {
            .class-table th:nth-child(2),
            .class-table td:nth-child(2),
            .class-table th:nth-child(4),
            .class-table td:nth-child(4) {
                display: none;
            }
            
            .class-thumbnail {
                width: 36px;
                height: 36px;
            }
            
            .tutor-avatar {
                width: 28px;
                height: 28px;
            }
        }
        
        @media (max-width: 767px) {
            .class-table th:nth-child(3),
            .class-table td:nth-child(3) {
                display: none;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .btn-group .btn span {
                display: none;
            }
            
            .btn-group .btn i {
                margin-right: 0;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .class-thumbnail {
                width: 32px;
                height: 32px;
            }
        }
        
        @media (max-width: 575px) {
            .class-table th:nth-child(5),
            .class-table td:nth-child(5) {
                display: none;
            }
            
            .class-table th,
            .class-table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
            
            .pagination .page-link {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
</body>
</html>