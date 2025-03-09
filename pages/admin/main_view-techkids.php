<?php 
    require_once '../../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';

    // Get current page from URL parameter, default to 1
    $techkids_page = isset($_GET['tkpage']) ? (int)$_GET['tkpage'] : 1;
    $items_per_page = 50;

    // Get paginated data
    $techkids = getUserByRole('TECHKID', $techkids_page, $items_per_page);

    // Calculate total pages
    $techkids_total_pages = ceil($techkidsCount / $items_per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor | View All Tutors</title>
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
  <link href="<?php echo CSS; ?>main.css" rel="stylesheet">
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
        <li><a class="dropdown-item" href="profile">Profile</a></li>
        <li><a class="dropdown-item" href="settings">Settings</a></li>
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
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="<?php echo BASE; ?>dashboard">
                            <i class="bi bi-house-door"></i>
                            <span class="ms-2">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="TechGurus">
                            <i class="bi bi-people"></i>
                            <span class="ms-2">TechGurus</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded active" href="TechKids">
                            <i class="bi bi-person"></i>
                            <span class="ms-2">TechKids</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="course">
                            <i class="bi bi-book"></i>
                            <span class="ms-2">Courses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="notifications">
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
        <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            
        <div class="row" style="height: 100vh;">
            <div class="col-12" style="background-color: #f8f9fa; height: 100%;">
            <h2 style="margin:10px 20px 0px 20px;">TechGurus</h2>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div></div>
                <div class="d-flex">
                    <select class="form-select me-2" id="searchColumn" style="width: auto;">
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                        <option value="course">Course</option>
                        <option value="schedule">Schedule</option>
                        <option value="status">Status</option>
                    </select>
                    <input type="text" class="form-control me-2" id="searchInput" placeholder="Search" style="width: 250px;">
                </div>
            </div>
            <div class="table-responsive" style="max-height: calc(100% - 100px); overflow-y: auto;">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="id">ID NO. <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="name">Name <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="email">Email <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="course">Course <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="schedule">Schedule <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="last_login">Last login <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="status">Status <i class="bi bi-arrow-down-up"></i></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="techGuruTable">
                        <?php
                            if (!empty($techkids)) {
                                foreach ($techkids as $kids) {
                                    if ((bool)$kids['status']) {
                                        $statusClass = getStatusBadgeClass($kids['status']);
                                        $statusText = ucfirst(normalizeStatus($kids['status']));
                                        
                                        echo "<tr>
                                            <td class='text-center'>{$kids['uid']}</td>
                                            <td>{$kids['first_name']} {$kids['last_name']}</td>
                                            <td>{$kids['email']}</td>
                                            <td>{$kids['course']}</td>
                                            <td>{$kids['schedule']}</td>
                                            <td>{$kids['last_login']}</td>
                                            <td><span class='badge {$statusClass}'>{$statusText}</span></td>
                                            <td>
                                                <div class='d-flex'>
                                                    <button class='btn btn-sm btn-danger rounded-circle me-1'><i class='bi bi-trash'></i></button>
                                                    <button class='btn btn-sm btn-primary rounded-circle me-1'><i class='bi bi-pencil'></i></button>
                                                    <button class='btn btn-sm btn-warning rounded-circle'><i class='bi bi-eye'></i></button>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='8'>No active TechKids found.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        </main>
    </div>
</div>

<!-- Vendor JS Files -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/php-email-form/validate.js"></script>
<script src="../assets/vendor/aos/aos.js"></script>
<script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="../assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>

<!-- Main JS File -->
<script src="../assets/js/main.js"></script>

<!-- Custom JavaScript for search and sorting -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchColumn = document.getElementById('searchColumn');
    const table = document.getElementById('techGuruTable');
    const rows = table.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchText = searchInput.value.toLowerCase();
        const columnIndex = getColumnIndex(searchColumn.value);
        
        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            if (cells.length > 0) {
                const cellText = cells[columnIndex].textContent.toLowerCase();
                if (cellText.indexOf(searchText) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    });

    // Get column index based on selected value
    function getColumnIndex(columnName) {
        switch(columnName) {
            case 'name': return 1;
            case 'email': return 2;
            case 'course': return 3;
            case 'schedule': return 4;
            case 'tutor': return 5;
            case 'status': return 6;
            default: return 1;
        }
    }

    // Sorting functionality
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            const columnIndex = getSortColumnIndex(column);
            const currentDirection = this.getAttribute('data-direction') || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            
            // Reset all headers
            sortableHeaders.forEach(h => {
                h.setAttribute('data-direction', '');
                h.querySelector('i').className = 'bi bi-arrow-down-up';
            });
            
            // Set new direction and icon
            this.setAttribute('data-direction', newDirection);
            this.querySelector('i').className = newDirection === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
            
            // Sort the table
            sortTable(columnIndex, newDirection);
        });
    });

    // Get column index for sorting
    function getSortColumnIndex(columnName) {
        switch(columnName) {
            case 'id': return 0;
            case 'name': return 1;
            case 'email': return 2;
            case 'course': return 3;
            case 'schedule': return 4;
            case 'tutor': return 5;
            case 'status': return 6;
            default: return 0;
        }
    }

    // Sort table function
    function sortTable(columnIndex, direction) {
        const rowsArray = Array.from(rows);
        const tbody = table.querySelector('tbody');
        
        // Skip any rows without cells (like "No data" messages)
        const sortableRows = rowsArray.filter(row => row.cells.length > 0);
        
        sortableRows.sort((a, b) => {
            let aValue = a.cells[columnIndex].textContent.trim();
            let bValue = b.cells[columnIndex].textContent.trim();
            
            // Handle numeric sorting for ID column
            if (columnIndex === 0) {
                return direction === 'asc' 
                    ? parseInt(aValue) - parseInt(bValue)
                    : parseInt(bValue) - parseInt(aValue);
            }
            
            // Handle text sorting
            return direction === 'asc'
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });
        
        // Remove all existing rows
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
        
        // Add sorted rows
        sortableRows.forEach(row => {
            tbody.appendChild(row);
        });
    }

    // Add CSS for sortable headers
    const style = document.createElement('style');
    style.textContent = `
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            background-color: #f0f0f0;
        }
    `;
    document.head.appendChild(style);
});
</script>
</body>
</html>