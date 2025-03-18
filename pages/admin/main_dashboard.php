<?php  
    require_once 'main.php';
    require_once 'user_management.php';
    
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
                        <h1>Welcome, <?php echo explode(' ', $_SESSION['first_name'])[0]; ?>!</h1>
                        <p>Here's what's happening in your platform today.</p>
                    </div>

                    <!-- Statistics Cards -->
            <div class="row mt-4">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?php echo $techkidCount; ?></h3>
                                    <p class="mb-0 text-muted">TechKids</p>
                                </div>
                                <div class="icon-box">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?php echo $techguruCount; ?></h3>
                                    <p class="mb-0 text-muted">TechTutors</p>
                                </div>
                                <div class="icon-box">
                                    <i class="bi bi-person-workspace fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?php echo $adminCount; ?></h3>
                                    <p class="mb-0 text-muted">Admin</p>
                                </div>
                                <div class="icon-box">
                                    <i class="bi bi-person-gear fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?php echo $courseCount; ?></h3>
                                    <p class="mb-0 text-muted">Courses</p>
                                </div>
                                <div class="icon-box">
                                    <i class="bi bi-book fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    <!-- TechGurus Section -->
                    <div class="dashboard-section">
                        <div class="table-header">
                            <h2 class="section-title">Recent TechGurus</h2>
                            <a href="<?php echo BASE.'dashboard/a/'; ?>users?role=TECHGURU" class="view-all-btn">
                                <i class="bi bi-eye"></i>
                                View All
                            </a>
                        </div>
                        <div class="table-container table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-center">Name</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">No of Classes</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Last Login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techgurus as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo USER_IMG . $user['profile_picture']; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo $user['email']; ?></td>
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

                    <!-- TechKids Section -->
                    <div class="dashboard-section">
                        <div class="table-header">
                            <h2 class="section-title">Recent TechKids</h2>
                            <a href="<?php echo BASE.'dashboard/a/'; ?>users?role=TECHKID" class="view-all-btn">
                                <i class="bi bi-eye"></i>
                                View All
                            </a>
                        </div>
                        <div class="table-container table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-center">Name</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">Enrolled Classes</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Last Login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techkids as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo USER_IMG.$user['profile_picture']; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                <span><?php echo $user['first_name']." ".$user['last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo $user['email']; ?></td>
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
                </main>
            </div>
        </div>
    </div>

    </main>
    </div>
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>