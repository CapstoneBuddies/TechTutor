<?php 
  require_once '../backends/config.php';
  session_start();
?>

<!DOCTYPE html>
<html lang="en">



<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Update User Profile</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
  <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,800&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="<?php echo BASE.CSS ; ?>main.css" rel="stylesheet">
</head>

<body>

<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="home" class="logo d-flex align-items-center me-auto">
        <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="">
        <img src="<?php echo BASE; ?>assets/img/TechTutor_text.png" alt="">
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="avatar.jpg" alt="User Avatar" class="avatar-icon">
            </a>
            <ul class="dropdown-menu" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="#">John Centh</a></li>
              <li><a class="dropdown-item" href="techkid_home.php">home</a></li>
              <li><a class="dropdown-item" href="#">Profile</a></li>
              <li><a class="dropdown-item" href="#">Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#">Log Out</a></li>
            </ul>
          </li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>

  <main class="main">
    <section id="update-profile" class="update-profile section">
      <div class="container">
        <h2>Update Profile</h2>
        <form action="techkid_profile.php" method="POST">
          <h3>Personal Information</h3>
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" required>
          </div>
          <h3>Account Information</h3>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3">
            <label for="points" class="form-label">Points</label>
            <input type="number" class="form-control" id="points" name="points" required>
          </div>
          <button type="submit" class="btn btn-primary">Update</button>
        </form>
      </div>
    </section>
  </main>

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

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $points = $_POST['points'];

    // Here you would typically update the database with the new information
    // For demonstration purposes, we'll just echo the data
    echo "<div class='container mt-5'>";
    echo "<h3>Profile Updated</h3>";
    echo "<p>Name: $name</p>";
    echo "<p>Email: $email</p>";
    echo "<p>Phone: $phone</p>";
    echo "<p>Address: $address</p>";
    echo "<p>Username: $username</p>";
    echo "<p>Password: $password</p>";
    echo "<p>Points: $points</p>";
    echo "</div>";
}
?>