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
                                                    <a href="classes/details?id=<?php echo $class['class_id']; ?>" class="btn btn-primary">
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
    </main> <!-- Ending All Main Content -->
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
</body>
</html>