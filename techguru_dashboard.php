<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor Admin Dashboard</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/stand_alone_logo.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

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
<br><br>
<br> <br> <main class="main">
    <section id="dashboard" class="dashboard section">
      <div class="container">
        <div class="row">
          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h2>Total Active Students</h2>
              </div>
              <div class="card-body">
                <!-- Add content for Total Active Students here -->
              </div>
            </div>
            <div class="card mt-4">
              <div class="card-header">
                <h2>Students</h2>
              </div>
              <div class="card-body">
                <!-- Add content for Students here -->
                <button class="btn btn-danger">Delete</button>
                <button class="btn btn-warning">Restrict</button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#studentInfoModal">View Information</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-header">
                <h2>Top Performing TechGuru</h2>
              </div>
              <div class="card-body">
                <!-- Add content for Top Performing TechGuru here -->
                <div class="techguru-card text-center">
                  <img src="techguru.jpg" alt="TechGuru Image" class="rounded-circle mb-3" style="width: 100px; height: 100px;">
                  <h3>Jane Doe</h3>
                  <p>
                    <span class="bi bi-star-fill text-warning"></span>
                    <span class="bi bi-star-fill text-warning"></span>
                    <span class="bi bi-star-fill text-warning"></span>
                    <span class="bi bi-star-fill text-warning"></span>
                    <span class="bi bi-star-fill text-warning"></span>
                  </p>
                  <p>Specialization: Networking</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Student Information Modal -->
  <div class="modal fade" id="studentInfoModal" tabindex="-1" aria-labelledby="studentInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="studentInfoModalLabel">Student Information</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="studentName" class="form-label">Name</label>
              <input type="text" class="form-control" id="studentName" value="John Doe">
            </div>
            <div class="mb-3">
              <label for="studentEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="studentEmail" value="john.doe@example.com">
            </div>
            <div class="mb-3">
              <label for="studentCourse" class="form-label">Course</label>
              <input type="text" class="form-control" id="studentCourse" value="Networking">
            </div>
            <div class="mb-3">
              <label for="studentStatus" class="form-label">Status</label>
              <select class="form-select" id="studentStatus">
                <option selected>Active</option>
                <option>Inactive</option>
                <option>Restricted</option>
              </select>
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

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

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