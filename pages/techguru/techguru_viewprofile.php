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
                    <a class="nav-link btn" href="#" style="font-size: 20px; background-color: #0F52BA; color: white;">
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
                                            <label for="modal-password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="modal-password">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="modal-address" value="<?php echo $_SESSION['address']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control" id="modal-phone" value="<?php echo $_SESSION['phone']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="modal-position" class="form-label">Position</label>
                                            <input type="text" class="form-control" id="modal-position" value="<?php echo $_SESSION['position']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveProfileChanges">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Notification Modal -->
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="successModalLabel">Success</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Your personal information has been successfully updated!
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.getElementById('saveProfileChanges').addEventListener('click', function() {
                    // Perform AJAX request to update profile information
                    // Assuming the AJAX request is successful, show the success modal
                    $('#updateProfileModal').modal('hide');
                    $('#successModal').modal('show');
                });
            </script>

        
            <div class="container-fluid mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>TechGuru Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img src="<?php echo $_SESSION['profile']; ?>" alt="TechGuru Avatar" class="rounded-circle" width="150">
                                        <h3><?php echo $_SESSION['name']; ?></h3>
                                        <span style="color: #F57513;">Techguru</span>
                                    </div>
                                    <div class="col-md-8">
                                        <form style="margin: 20px 0; padding-top: 300px;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="full-name" class="form-label">Full Name</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email Address</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="password" class="form-label">Password</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="address" class="form-label">Address</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="phone" class="form-label">Phone</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="position" class="form-label">Position</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="full-name" class="form-label">Denis Durano</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">bantayka123@gmail.com</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="password" class="form-label">************</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="address" class="form-label">Bantay bata 143</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="phone" class="form-label">123912912391239123</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="position" class="form-label">TechkidsName</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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