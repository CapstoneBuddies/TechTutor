<?php 
    require_once '../../backends/main.php';
    
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
    <title>TechTutor | Admin Dashboard</title>
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
    <style>
        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                transition: transform 0.3s ease-in-out;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .toggle-sidebar {
                display: block !important;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
        }

        .toggle-sidebar {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .view-all-btn {
            padding: 0.5rem 1rem;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .view-all-btn:hover {
            background-color: #45a049;
            color: white;
        }

        .dashboard-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #333;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .table-container {
            margin-top: 1rem;
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

        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>

<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <button class="toggle-sidebar">
        <i class="bi bi-list"></i>
    </button>

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
                            <a href="<?php echo BASE; ?>dashboard/TechGurus" class="view-all-btn">
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
                                        <th class="text-center">Subject</th>
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
                                        <td><?php echo $user['subject']; ?></td>
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
                            <a href="<?php echo BASE; ?>dashboard/TechKids" class="view-all-btn">
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
                                        <td class="text-center"><?php echo $user['enrolled-classes']; ?></td>
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
    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.dashboard-content');

            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = sidebar.contains(event.target) || toggleBtn.contains(event.target);
                if (!isClickInside && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>