<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
  <style>
    .toolbar {
      display: none;
      flex-direction: column;
      padding: 5px; /* Thinner padding */
      border-radius: 8px;
    }
    .toolbar-container {
      background-color: rgba(0, 0, 0, 0.5); /* Light black transparent background */
    }
    .toolbar-container:hover .toolbar {
      display: flex;
    }
    .toolbar button {
      margin-bottom: 5px; /* Thinner spacing between buttons */
    }
    .audio-controls {
      display: flex;
      gap: 10px;
    }
    .exit-classroom-btn {
      background-color: #dc3545; /* Red color */
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 20px;
      display: block;
      width: 100%;
      text-align: center;
    }
    .exit-classroom-btn:hover {
      background-color: #c82333;
    }
    .video-controls {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }
    .video-controls button {
      background-color: transparent;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 50%;
      cursor: pointer;
      font-size: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .video-controls button:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }
  </style>
</head>
<body>
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">

    <a href="home" class="logo d-flex align-items-center me-auto">
      <!-- Uncomment the line below if you also wish to use an image logo -->
      <img src="assets/img/stand_alone_logo.png" alt="">
      <img src="assets/img/TechTutor_text.png" alt="">
    </a>

    <nav id="navmenu" class="navmenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a href="#" class="nav-link dropdown-toggle" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="avatar.jpg" alt="User Avatar" class="avatar-icon rounded-circle">
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

<main class="classroom-main" style="margin-top: 100px;"> <!-- Adjust the margin-top value as needed -->
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-9 col-md-8 col-sm-12">
        <div class="video-call card shadow-sm">
          <div class="video-container card-body position-relative">
            <video id="teacher-video" class="w-100 rounded" autoplay></video>
            <div class="student-videos d-flex mt-3">
              <video id="student-video-1" class="rounded me-2" autoplay></video>
              <video id="student-video-2" class="rounded" autoplay></video>
              <!-- Add more student videos as needed -->
            </div>
            <div class="toolbar-container position-absolute top-50 start-0 translate-middle-y p-1 rounded">
              <div class="toolbar" id="toolbar">
                <button class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-secondary"><i class="bi bi-eraser"></i></button>
                <button class="btn btn-outline-secondary"><i class="bi bi-fonts"></i></button>
                <button class="btn btn-outline-secondary"><i class="bi bi-textarea-t"></i></button>
                <button class="btn btn-outline-secondary"><i class="bi bi-crop"></i></button>
                <button class="btn btn-outline-secondary"><i class="bi bi-gear"></i></button>
              </div>
              <button class="btn btn-outline-secondary mt-2" id="toggle-toolbar"><i class="bi bi-caret-right-fill"></i></button>
            </div>
          </div>
          <div class="video-controls">
            <button><i class="bi bi-camera-video"></i></button>
            <button><i class="bi bi-hand-thumbs-up"></i></button>
            <button><i class="bi bi-play-fill"></i></button>
            <button><i class="bi bi-pause-fill"></i></button>
            <button><i class="bi bi-mic-mute"></i></button>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-4 col-sm-12">
        <div class="user-panel card shadow-sm">
          <div class="card-body">
            <h3 class="card-title">Participants</h3>
            <ul class="user-list list-unstyled">
              <li class="user-item d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                  <img src="avatar.jpg" alt="User Avatar" class="avatar-icon rounded-circle me-2">
                  <span>John Centh</span>
                </div>
                <div class="audio-controls">
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-mic-mute"></i></button>
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-volume-up"></i></button>
                </div>
              </li>
              <li class="user-item d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                  <img src="avatar.jpg" alt="User Avatar" class="avatar-icon rounded-circle me-2">
                  <span>Jane Doe</span>
                </div>
                <div class="audio-controls">
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-mic-mute"></i></button>
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-volume-up"></i></button>
                </div>
              </li>
              <!-- Add more users as needed -->
            </ul>
          </div>
        </div>
        <button class="exit-classroom-btn mt-3" onclick="exitClassroom()">Exit Classroom</button>
      </div>
    </div>
  </div>
</main>

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
<script>
  function toggleToolbar() {
    const toolbar = document.getElementById('toolbar');
    const toggleButton = document.getElementById('toggle-toolbar');
    if (toolbar.style.display === 'none') {
      toolbar.style.display = 'flex';
      toggleButton.innerHTML = '<i class="bi bi-caret-right-fill"></i>';
    } else {
      toolbar.style.display = 'none';
      toggleButton.innerHTML = '<i class="bi bi-caret-left-fill"></i>';
    }
  }

  function exitClassroom() {
    window.location.href = 'joinclass.php';
  }
</script>
</body>
</html>
