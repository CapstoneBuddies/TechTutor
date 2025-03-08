-<?php 
    require_once '../backends/config.php';
    require_once '../backends/db.php';
    session_start();
    getUsers();
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
  <link href="../assets/img/stand_alone_logo.png" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../assets/css/main.css" rel="stylesheet">

  
</head>

<body class="index-page">

<header id="header" class="header d-flex align-items-center fixed-top" style="padding: 0 20px;">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">

    <a href="home" class="logo d-flex align-items-center me-auto">
    <img src="../assets/img/stand_alone_logo.png" alt="">
    <img src="../assets/img/TechTutor_text.png" alt="">
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
        <li><a class="dropdown-item" href="#">Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="user-logout">Log Out</a></li>
      </ul>
      </li>
    </ul>
    <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>
<br><br><br><br><br><br>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
       
            <div class="position-sticky">
            <div class="text-center py-4">
                <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="rounded-circle" width="100">
                <h5 class="mt-2" style="font-size: 30px;"><?php echo $_SESSION['name']; ?></h5>
                <span style="font-size: 20px;">John Doe | Admin</span>
            </div>
            <ul class="nav flex-column">
               <center>
                <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#">
                <button class="btn" style="background-color: #0F52BA; color: white; font-size: 20px;"> <i class="bi bi-house-door"> Dashboard </i></button>
                </a> 
                </li>
                <li class="nav-item dropdown">
                <a class="nav-link" href="#" style="font-size: 20px;">
                    <i class="bi bi-people"></i>
                    TechTutors
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#" style="font-size: 20px;">
                    <i class="bi bi-person"></i>
                    TechKids
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#" style="font-size: 20px;">
                    <i class="bi bi-book"></i>
                    Courses
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#" style="font-size: 20px;">
                    <i class="bi bi-bell"></i>
                    Notification
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#" style="font-size: 20px;">
                    <i class="bi bi-wallet2"></i>
                    Transactions
                </a>
                </li><center>
            </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            

        <div class="row" style="height: 100vh;">
            <div class="col-12" style="background-color: #f8f9fa; height: 100%;">
            <h2 style="margin:10px 20px 0px 20px;">TechKids</h2>
            <div class="d-flex justify-content-end mb-3">
                <input type="text" class="form-control" style="width: 35%;" placeholder="Search">
                <a href="view-all-techkids.php" class="btn btn-primary" ><img src="../assets/img/search-interface-symbol.png" alt="Default Image" width="20" height="20"></a>

            </div>
            <div class="table-responsive" style="max-height: calc(100% - 100px); overflow-y: auto;">
                <table class="table table-striped table-sm">
                <thead>
                    <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $activeUsersCount = 0;
                    if (!empty($_SESSION['users'])) {
                    foreach ($_SESSION['users'] as $user) {
                        if ((bool)$user['status']) {
                        $activeUsersCount++;
                        echo "<tr>
                            <td class='user-data'>{$user['first_name']} {$user['last_name']}</td>
                            <td class='user-data'>{$user['email']}</td>
                            <td class='user-data'>{$user['course']}</td>
                            <td class='user-data'>{$user['schedule']}</td>
                            <td class='user-data'>" . ($user['is_verified'] ? "Verified" : "Unverified") . "</td>
                            <td class='user-data'>{$user['last_login']}</td>
                        </tr>";
                        }
                    }
                    } else {
                    echo "<tr><td colspan='6'>No active users found.</td></tr>";
                    }
                    ?>
                </tbody>
                </table>
            </div>
            <p>Total Active Users: <?php echo $activeUsersCount; ?></p>
            
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
</body>
</html>