<?php 
    require_once '../../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';
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
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../../assets/css/main.css" rel="stylesheet">

  
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
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar" style="padding: 15px;">
            <div class="position-sticky">
            <ul class="nav flex-column">
               <center>
                <li class="nav-item">
                    <a class="nav-link" href="#" style="font-size: 20px;">
                        <i class="bi bi-house"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" style="font-size: 20px;">
                    <i class="bi bi-person"></i>
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" style="font-size: 20px;">
                        <i class="bi bi-gear"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" style="font-size: 20px;">
                    <button class="btn" style="background-color: #0F52BA; color: white; font-size: 20px;"> <i class="bi bi-people" style="color: white;">
                        Users
                    </i></a></button> 
                </li>
            
            </li><center>
            </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Profile</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal">Update Profile</button>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateProfileModalLabel">Update Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="updateProfileForm">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="rounded-circle" width="150">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-secondary">Update Image</button>
                                            <button type="button" class="btn btn-danger">Remove Image</button>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="modal-first-name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="modal-first-name" value="<?php echo $_SESSION['first_name']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-last-name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="modal-last-name" value="<?php echo $_SESSION['last_name']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="modal-email" value="<?php echo $_SESSION['email']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="modal-address" value="<?php echo $_SESSION['address']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control" id="modal-phone" value="<?php echo $_SESSION['phone']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
            <div class="col-md-4 text-center">
                <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="rounded-circle" width="300">
                <h3 class="mt-3"><?php echo $_SESSION['name']; ?></h3>
                <p>Admin</p>
            </div>
            <div class="col-md-8">
                <form>
                <div class="mb-3">
                    <label for="first-name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first-name" value="<?php echo $_SESSION['first_name']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="last-name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last-name" value="<?php echo $_SESSION['last_name']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" value="<?php echo $_SESSION['email']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" value="********" readonly>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" value="<?php echo $_SESSION['address']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" value="<?php echo $_SESSION['phone']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="position" class="form-label">Position</label>
                    <input type="text" class="form-control" id="position" value="<?php echo $_SESSION['position']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="last-login" class="form-label">Last Login</label>
                    <input type="text" class="form-control" id="last-login" value="<?php echo $_SESSION['last_login']; ?>" readonly>
                </div>
                </form>
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