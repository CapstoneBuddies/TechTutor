<?php 
   require_once($_SERVER['DOCUMENT_ROOT'] . '/TechTutor-1/backends/config.php');
    session_start();
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
  <link href="../../assets/img/stand_alone_logo.png" rel="icon">
  <link href="../../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

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

<header class="d-flex justify-content-between align-items-center p-3 mb-3 border-bottom bg-light">
    <img src="../../assets/img/stand_alone_logo.png" alt="Logo" width="50">
    <span class="ms-auto" style="font-size: 20px;">Sample name | Techguru<?php echo $_SESSION['name']; ?></span>
</header>

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
                            <a class="nav-link btn" href="#">
                                <i class="bi bi-person"></i>
                                Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"  style="font-size: 20px; background-color: #0F52BA; color: white;">
                                <i class="bi bi-gear"></i>
                                Settings
                            </a>
                        </li>
                    </center>
                    <hr>
                    <li class="nav-item mt-auto">
                        <a class="nav-link" href="user-logout" style="font-size: 20px;">
                            <i class="bi bi-box-arrow-right"></i>
                            Log out
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Account Settings</h1>
            </div>

            <div class="container-fluid mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Personal Details</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img src="<?php echo $_SESSION['profile']; ?>" alt="TechGuru Avatar" class="rounded-circle" width="150">
                                        <h3><?php echo $_SESSION['name']; ?></h3>
                                        <span style="color: #F57513;">Techguru</span>
                                    </div>
                                    <div class="col-md-8">
                                        <form style="margin: 20px 0;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="full-name" class="form-label">Full Name</label>
                                                        <input type="text" class="form-control" id="full-name" value="<?php echo $_SESSION['name']; ?>" disabled>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email Address</label>
                                                        <input type="email" class="form-control" id="email" value="<?php echo $_SESSION['email']; ?>" disabled>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="password" class="form-label">Password</label>
                                                        <input type="password" class="form-control" id="password" value="************" disabled>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="address" class="form-label">Address</label>
                                                        <input type="text" class="form-control" id="address" value="<?php echo $_SESSION['address']; ?>" disabled>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="phone" class="form-label">Phone</label>
                                                        <input type="text" class="form-control" id="phone" value="<?php echo $_SESSION['phone']; ?>" disabled>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="position" class="form-label">Position</label>
                                                        <input type="text" class="form-control" id="position" value="<?php echo $_SESSION['position']; ?>" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">Delete Account</button>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                                        </div>

                                        <!-- Delete Account Modal -->
                                        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure, You want to Delete this Account?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="button" class="btn btn-danger">Delete</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Change Password Modal -->
                                        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form>
                                                            <div class="alert alert-info" role="alert" style="font-size: 12px;">
                                                                    <strong>Password Requirements:</strong>
                                                                    <ul>
                                                                        <li>Must be at least 8 characters long.</li>
                                                                        <li>Must include at least 1 uppercase letter (A-Z).</li>
                                                                        <li>Must include at least 1 special character (e.g., *,_,-,!).</li>
                                                                        <li>Must contain at least 1 number (0-9).</li>
                                                                        <li>Do not use your birthdate, name, or any easily guessable information.</li>
                                                                        <li>Choose a unique and strong password to enhance security.</li>
                                                                    </ul>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="old-password" class="form-label">Old Password</label>
                                                                <input type="password" class="form-control" id="old-password">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="new-password" class="form-label">New Password</label>
                                                                <input type="password" class="form-control" id="new-password">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="confirm-new-password" class="form-label">Confirm New Password</label>
                                                                <input type="password" class="form-control" id="confirm-new-password">
                                                            </div>
                                                            
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="button" class="btn btn-primary">Change Password</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Vendor JS Files -->
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/php-email-form/validate.js"></script>
<script src="../../assets/vendor/aos/aos.js"></script>
<script src="../../assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="../../assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="../../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="../../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="../../assets/vendor/swiper/swiper-bundle.min.js"></script>

<!-- Main JS File -->
<script src="../../assets/js/main.js"></script>
</body>
</html>