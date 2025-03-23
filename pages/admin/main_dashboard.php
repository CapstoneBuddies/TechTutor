<?php  
    require_once 'main.php';
    require_once BACKEND.'user_management.php';
    
    // Get current page from URL parameter, default to 1
    $techkids_page = isset($_GET['tkpage']) ? (int)$_GET['tkpage'] : 1;
    $techgurus_page = isset($_GET['tgpage']) ? (int)$_GET['tgpage'] : 1;
    $items_per_page = 8;

    // Get paginated data
    $techkids = getUserByRole('TECHKID', $techkids_page, $items_per_page);
    $techgurus = getUserByRole('TECHGURU', $techgurus_page, $items_per_page);

    // Get total counts for pagination
    $techkidCount = getItemCountByTable('users','TECHKID');
    $techguruCount = getItemCountByTable('users','TECHGURU');
    $adminCount = getItemCountByTable('users','ADMIN');
    $courseCount = getItemCountByTable('course');

    // Calculate total pages
    $techkids_total_pages = ceil($techkidCount / $items_per_page);
    $techgurus_total_pages = ceil($techguruCount / $items_per_page);
    $title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <link rel="stylesheet" href="<?php echo CSS; ?>dashboard.css">
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>

    <div class="dashboard-content">
        <div class="container-fluid">
            <div class="row">
                <main class="col-12">
                    <!-- Welcome Section -->
                    <div class="welcome-section dashboard-section">
                        <h1 class="mb-2">Welcome, <?php echo explode(' ', $_SESSION['first_name'])[0]; ?>!</h1>
                        <p class="text-muted mb-0">Here's what's happening in your platform today.</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mt-3">
                        <div class="col-6 col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h3 class="mb-0 fs-4"><?php echo $techkidCount; ?></h3>
                                            <p class="mb-0 text-muted small">TechKids</p>
                                        </div>
                                        <div class="icon-box">
                                            <i class="bi bi-people fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h3 class="mb-0 fs-4"><?php echo $techguruCount; ?></h3>
                                            <p class="mb-0 text-muted small">TechTutors</p>
                                        </div>
                                        <div class="icon-box">
                                            <i class="bi bi-person-workspace fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h3 class="mb-0 fs-4"><?php echo $adminCount; ?></h3>
                                            <p class="mb-0 text-muted small">Admin</p>
                                        </div>
                                        <div class="icon-box">
                                            <i class="bi bi-person-gear fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h3 class="mb-0 fs-4"><?php echo $courseCount; ?></h3>
                                            <p class="mb-0 text-muted small">Courses</p>
                                        </div>
                                        <div class="icon-box">
                                            <i class="bi bi-book fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TechGurus Section -->
                    <div class="dashboard-section mt-4">
                        <div class="table-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                            <h2 class="section-title mb-2 mb-md-0">Recent TechGurus</h2>
                            <a href="<?php echo BASE.'dashboard/a/'; ?>users?role=TECHGURU" class="view-all-btn btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>
                                View All
                            </a>
                        </div>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Name</th>
                                            <th>Email</th>
                                            <th class="text-center">No of Classes</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Last Login</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($techgurus as $user): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo USER_IMG . $user['profile_picture']; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                    <div>
                                                        <div class="fw-medium"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td class="text-center"><?php echo $user['num_classes']; ?></td>
                                            <td class="text-center">
                                                <span class="status-badge <?php echo getStatusBadgeClass($user['status']); ?>">
                                                    <?php echo normalizeStatus($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?php echo $user['last_login']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TechKids Section -->
                    <div class="dashboard-section mt-4">
                        <div class="table-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                            <h2 class="section-title mb-2 mb-md-0">Recent TechKids</h2>
                            <a href="<?php echo BASE.'dashboard/a/'; ?>users?role=TECHKID" class="view-all-btn btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>
                                View All
                            </a>
                        </div>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Name</th>
                                            <th>Email</th>
                                            <th class="text-center">Enrolled Classes</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Last Login</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($techkids as $user): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo USER_IMG.$user['profile_picture']; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                    <div>
                                                        <div class="fw-medium"><?php echo $user['first_name']." ".$user['last_name']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td class="text-center"><?php echo $user['num_classes']; ?></td>
                                            <td class="text-center">
                                                <span class="status-badge <?php echo getStatusBadgeClass($user['status']); ?>">
                                                    <?php echo normalizeStatus($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?php echo $user['last_login']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    </main>
    </div>
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>