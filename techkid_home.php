<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/stand_alone_logo.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,800&display=swap" rel="stylesheet">

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

      <a href="index.php" class="logo d-flex align-items-center me-auto">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="assets/img/stand_alone_logo.png" alt="">
        <img src="assets/img/TechTutor_text.png" alt="">
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="avatar.jpg" alt="User Avatar" class="avatar-icon">
            </a>
            <ul class="dropdown-menu" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="#">John Centh</a></li>
              <li><a class="dropdown-item" href="techkid_home.php">Home</a></li>
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
    <!-- Banner Section -->
    <section id="banner" class="banner">
      <!-- Banner image will be added later -->
    </section>

    <!-- Hero Section -->
    <section id="hero" class="hero section">
      <div class="container">
        <div class="dashboard">
          <div class="main-content">
            <div class="left-panel">
              <div class="user-info rounded-box">
                <img src="avatar.jpg" alt="User Avatar" class="avatar">
                <div class="user-details">
                  <h2>Keep it going, John Centh!</h2><br>
                  <p>You have 2,525 points</p>
                  <p>Earn 2 more badges and 475 more points to reach Explorer rank.</p>
                </div>
              </div>
              <div class="favorites">
                <h3>Favorites</h3>
                <p>There's nothing here yet</p>
                <p>Add badges to your favorites by clicking the star icon.</p>
              </div>
            </div>
            <div class="right-panel">
              <div class="explore">
                <h2>Subjects</h2>
                <div class="module-grid">
                  <div class="module" onclick="location.href='joinclass.php';" style="cursor: pointer;">
                    <img src="module1.jpg" alt="Subject 1">
                    <h3>Computer Programming</h3>
                    <h2>Python</h2>
                    <p>Learn how AI agents use LLMs and context to assist customers and human...</p>
                    <div class="module-details">
                      <p></p>
                      <p>-5 mins</p>
                    </div>
                  </div>
                  <div class="module" onclick="location.href='#';" style="cursor: pointer;">
                    <img src="module2.jpg" alt="Subject 2">
                    <h3>Networking</h3>
                    <p>description here...</p>
                    <div class="module-details">
                      <p></p>
                      <p>-5 mins</p>
                    </div>
                  </div>
                  <div class="module" onclick="location.href='#';" style="cursor: pointer;">
                    <img src="module1.jpg" alt="Subject 3">
                    <h3>UI/UX Designing</h3>
                    <p>description here...</p>
                    <div class="module-details">
                      <p></p>
                      <p>-5 mins</p>
                    </div>
                  </div>
                  <!-- Add more modules as needed -->
                </div>
              </div>
              <div class="available-class rounded-box">
                <h2>Available Class</h2>
                <div class="module-grid">
                  <div class="module-container">
                    <div class="module-details">
                      <p>Python Programming</p>
                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lesson1Modal">View More</button>
                    </div>
                    <hr>
                    <div class="module-details">
                      <p>Java Programming</p>
                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lesson2Modal">View More</button>
                    </div>
                    <hr>
                    <div class="module-details">
                      <p>PHP Programming</p>
                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lesson3Modal">View More</button>
                    </div>
                    <!-- Add more lessons as needed -->
                  </div>
                </div>
              </div>
              <div class="badges">
                <h2>Your Badges</h2>
                <div class="badge-grid">
                  <div class="badge">
                    <img src="badge1.jpg" alt="Badge 1">
                    <p>Badge Name</p>
                  </div>
                  <!-- Add more badges as needed -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->
  </main>
  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Modals -->
  <div class="modal fade" id="lesson1Modal" tabindex="-1" aria-labelledby="lesson1ModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lesson1ModalLabel">Python Programming</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Modal content for Python Programming -->
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="lesson2Modal" tabindex="-1" aria-labelledby="lesson2ModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lesson2ModalLabel">Java Programming</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Modal content for Java Programming -->
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="lesson3Modal" tabindex="-1" aria-labelledby="lesson3ModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lesson3ModalLabel">PHP Programming</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Modal content for PHP Programming -->
        </div>
      </div>
    </div>
  </div>

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