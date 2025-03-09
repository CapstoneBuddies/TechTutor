<?php 
    require_once '../../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';
    
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor | Dashboard</title>
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

  <!-- Main CSS File -->
  <link href="<?php echo BASE; ?>assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

<header id="header" class="header d-flex align-items-center fixed-top" style="padding: 0 20px;">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">

    <a href="home" class="logo d-flex align-items-center me-auto">
    <img src="<?php echo IMG; ?>stand_alone_logo.png" alt="">
    <img src="<?php echo IMG; ?>TechTutor_text.png" alt="">
    </a>

    <nav id="navmenu" class="navmenu">
    <ul class="d-flex align-items-center">
      <li class="nav-item">
      <a href="#" class="nav-link">
        <i class="bi bi-bell"></i>
      </a>
      </li>
      <li class="nav-item dropdown">
      <a href="#" class="nav-link dropdown-toggle main-avatar" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <img src=<?php echo $_SESSION['profile']; ?> alt="User Avatar" class="avatar-icon">
      </a>
      <ul class="dropdown-menu" aria-labelledby="userDropdown">
        <li><span class="dropdown-item user-item"><?php echo $_SESSION['name']; ?></span></li>
        <li><a class="dropdown-item" href="dashboard/profile">Profile</a></li>
        <li><a class="dropdown-item" href="dashboard/settings">Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="user-logout">Log Out</a></li>
      </ul>
      </li>
    </ul>
    <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>
<br><br><br><br><br>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <!-- User Profile Section -->
                <div class="text-center py-4">
                    <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="rounded-circle profile" width="80" height="80">
                    <h5 class="mt-2" style="font-size: 20px;"><?php echo $_SESSION['name']; ?></h5>
                    <span style="font-size: 16px;">Admin</span>
                </div>

                <!-- Sidebar Menu -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded active" href="dashboard">
                            <i class="bi bi-house-door"></i>
                            <span class="ms-2">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="dashboard/TechGurus">
                            <i class="bi bi-people"></i>
                            <span class="ms-2">TechGurus</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="dashboard/TechKids">
                            <i class="bi bi-person"></i>
                            <span class="ms-2">TechKids</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="dashboard/course">
                            <i class="bi bi-book"></i>
                            <span class="ms-2">Courses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="dashboard/notifications">
                            <i class="bi bi-bell"></i>
                            <span class="ms-2">Notification</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="dashboard/transactions">
                            <i class="bi bi-wallet2"></i>
                            <span class="ms-2">Transactions</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4">
            <!-- Tables Section -->
            <div class="row mt-4">
                <!-- TechKids Table -->
                <div class="col-md-6 mb-4">
                    <h5 class="mb-3 table-title">TechKids</h5>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Schedule</th>
                                        <th>Guru</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techkids as $user): ?>
                                    <tr>
                                        <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo $user['course']; ?></td>
                                        <td><?php echo $user['schedule']; ?></td>
                                        <td><?php echo $user['guru']; ?></td>
                                        <td><span class="<?php echo getStatusBadgeClass($user['status']); ?>"><?php echo ucfirst(normalizeStatus($user['status'])); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>Showing <?php echo count($techkids); ?> of <?php echo $techkidCount; ?></div>
                            <div class="pagination">
                                <?php if ($techkids_page > 1): ?>
                                    <a href="?tkpage=<?php echo $techkids_page - 1; ?>" class="text-decoration-none">
                                        <span>Previous</span>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $techkids_total_pages; $i++): ?>
                                    <a href="?tkpage=<?php echo $i; ?>" class="text-decoration-none">
                                        <span class="px-2 <?php echo $i === $techkids_page ? 'active' : ''; ?>"><?php echo $i; ?></span>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($techkids_page < $techkids_total_pages): ?>
                                    <a href="?tkpage=<?php echo $techkids_page + 1; ?>" class="text-decoration-none">
                                        <span>Next</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <a href="dashboard/TechKids" class="view-all-btn">View All TechKids</a>
                        </div>
                    </div>
                </div>

                <!-- TechGurus Table -->
                <div class="col-md-6 mb-4">
                    <h5 class="mb-3 table-title">TechGurus</h5>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techgurus as $guru): ?>
                                    <tr>
                                        <td><?php echo $guru['first_name'] . ' ' . $guru['last_name']; ?></td>
                                        <td><?php echo $guru['email']; ?></td>
                                        <td><?php echo $guru['course']; ?></td>
                                        <td><?php echo $guru['schedule']; ?></td>
                                        <td><span class="<?php echo getStatusBadgeClass($guru['status']); ?>"><?php echo ucfirst(normalizeStatus($guru['status'])); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>Showing <?php echo count($techgurus); ?> of <?php echo $techguruCount; ?></div>
                            <div class="pagination">
                                <?php if ($techgurus_page > 1): ?>
                                    <a href="?tgpage=<?php echo $techgurus_page - 1; ?>" class="text-decoration-none">
                                        <span>Previous</span>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $techgurus_total_pages; $i++): ?>
                                    <a href="?tgpage=<?php echo $i; ?>" class="text-decoration-none">
                                        <span class="px-2 <?php echo $i === $techgurus_page ? 'active' : ''; ?>"><?php echo $i; ?></span>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($techgurus_page < $techgurus_total_pages): ?>
                                    <a href="?tgpage=<?php echo $techgurus_page + 1; ?>" class="text-decoration-none">
                                        <span>Next</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <a href="dashboard/TechGurus" class="view-all-btn">View All TechGurus</a>
                        </div>
                    </div>
                </div>
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

            <!-- Transactions Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Transactions</h5>
                        <div class="search-box">
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>File Record</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    if (isset($_SESSION['transactions'])) {
                                    foreach ($_SESSION['transactions'] as $transaction): 
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                    <td><?php echo $transaction['name']; ?></td>
                                    <td><?php echo $transaction['email']; ?></td>
                                    <td>₱<?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo $transaction['file_record']; ?></td>
                                    <td><span class="<?php echo getStatusBadgeClass($transaction['status']); ?>"><?php echo ucfirst(normalizeStatus($transaction['status'])); ?></span></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-primary" title="View"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check"></i></button>
                                            <button class="btn btn-sm btn-danger" title="Reject"><i class="bi bi-x"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; }
                                    else {
                                        echo "<tr><td colspan='7' class='text-center'>No transactions available</td></tr>";
                                    } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>Showing 12 of 30</div>
                        <div class="pagination">
                            <span>Previous</span>
                            <span class="px-2">1</span>
                            <span>Next</span>
                        </div>
                        <button class="btn btn-primary">View All</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Vendor JS Files -->
<script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/php-email-form/validate.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>

<!-- Main JS File -->
<script src="<?php echo BASE; ?>assets/js/main.js"></script>
</body>
</html>