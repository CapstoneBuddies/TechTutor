<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTutor | Create An Account</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/stand_alone_logo.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="home" class="logo d-flex align-items-center me-auto">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="assets/img/stand_alone_logo.png" alt="">
        <img src="assets/img/TechTutor_text.png" alt="">
      </a>

      <nav id="navmenu" class="navmenu">
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a class="btn-getstarted flex-md-shrink-0" href="login">Sign In</a>
    </div>
  </header>

  <!-- Add space between header and body -->
  <div style="height: 60px;"></div>

  <main class="main">
    <!-- Hero Section -->
    <section id="hero" class="hero section">
    <div class="signup-container">
        <div class="signup-box" data-aos="fade-up">
            <div class="signup-left">
                <img src="assets/img/signup_image.png" alt="" data-aos="fade-up" data-aos-delay="200">
            </div>
            <div class="signup-right">
                <h2>Let’s Get Started</h2>
                <?php
                    if (isset($_SESSION["msg"])) {
                        echo '<div><p>' . $_SESSION["msg"] . '</p></div>';
                    }
                    unset($_SESSION["msg"]);
                ?>
                <form action="user-register" method="post">
                    <div class="input-group">
                        <label for="role">I am a...</label>
                        <select id="role" name="role" onchange="toggleFileUpload()">
                            <option value="TECHKIDS">TechKid</option>
                            <option value="TECHGURU">TechGuru</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="input-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" required>
                    </div>
                    <div class="input-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm-password" required>
                    </div>
                    <!-- File Upload Input -->
                    <div class="input-group" id="file-upload-group" data-aos="fade-up">
                        <label for="credentials">Upload a file for credentials (e.g., Certificate)</label>
                        <input type="file" id="credentials" name="credentials">
                    </div>
                    <button type="submit" class="signup-btn" name="register">Continue</button>
                    <p class="terms">
                        By proceeding, I agree to TechTutor’s <a href="#">Privacy Statement</a> and <a href="#">Terms of Service</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    </section><!-- /Hero Section -->
  </main>
  <!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script>
function toggleFileUpload() {
    var role = document.getElementById("role").value;
    var fileUploadGroup = document.getElementById("file-upload-group");
    
    if (role === "techguru") {
        fileUploadGroup.style.display = "block";
    } else {
        fileUploadGroup.style.display = "none";
    }
}

// Ensure it runs when the page loads
document.addEventListener("DOMContentLoaded", toggleFileUpload);
</script>

<!-- Vendor JS Files -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

<!-- Main JS File -->
<script src="assets/js/main.js"></script>
</body>
</html>