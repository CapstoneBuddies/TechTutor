<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor Admin Dashboard - Class Management</title>
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
              <li><a class="dropdown-item" href="#">Admin Name</a></li>
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
                <img src="avatar.jpg" alt="Admin Avatar" class="avatar">
                <div class="user-details">
                  <h2>Welcome, Admin!</h2>
                  <p>Manage your class sessions here.</p>
                  <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createClassModal">Create Class Session</button>
                </div>
              </div>
              <div class="favorites">
                <h3>Quick Actions</h3>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#createClassModal">Create Class Session</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#updateClassModal">Update Class Session</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#createFeedbackModal">Create Class Feedback</a></p>
              </div>
            </div>
            <div class="right-panel">
              <div class="explore">
                <h2>Class Sessions</h2>
                <div class="module-grid">
                  <div class="module" onclick="showClassDetails('Python Programming', 'Learn the basics of Python programming.', '2025-03-10', '10:00 AM', '12:00 PM');" style="cursor: pointer;">
                    <img src="module1.jpg" alt="Class 1">
                    <h3>Python Programming</h3>
                    <p>Manage class session details.</p>
                    <div class="module-details">
                      <p>Date: 2025-03-10</p>
                      <p>Time: 10:00 AM - 12:00 PM</p>
                      <button class="btn btn-danger mt-2" data-bs-toggle="modal" data-bs-target="#deleteClassModal">Delete</button>
                      <button class="btn btn-secondary mt-2" data-bs-toggle="modal" data-bs-target="#viewRecordingModal">View Recording</button>
                      <button class="btn btn-info mt-2" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal">View Feedback</button>
                    </div>
                  </div>
                  <!-- Add more class sessions as needed -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->
  </main>

  <!-- Create Class Modal -->
  <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createClassModalLabel">Create Class Session</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="className" class="form-label">Class Name</label>
              <input type="text" class="form-control" id="className" required>
            </div>
            <div class="mb-3">
              <label for="classDescription" class="form-label">Class Description</label>
              <textarea class="form-control" id="classDescription" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label for="classDate" class="form-label">Date</label>
              <input type="date" class="form-control" id="classDate" required>
            </div>
            <div class="mb-3">
              <label for="classStartTime" class="form-label">Start Time</label>
              <input type="time" class="form-control" id="classStartTime" required>
            </div>
            <div class="mb-3">
              <label for="classEndTime" class="form-label">End Time</label>
              <input type="time" class="form-control" id="classEndTime" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Create Class</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Class Modal -->
  <div class="modal fade" id="updateClassModal" tabindex="-1" aria-labelledby="updateClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateClassModalLabel">Update Class Session</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="updateClassName" class="form-label">Class Name</label>
              <input type="text" class="form-control" id="updateClassName" required>
            </div>
            <div class="mb-3">
              <label for="updateClassDescription" class="form-label">Class Description</label>
              <textarea class="form-control" id="updateClassDescription" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label for="updateClassDate" class="form-label">Date</label>
              <input type="date" class="form-control" id="updateClassDate" required>
            </div>
            <div class="mb-3">
              <label for="updateClassStartTime" class="form-label">Start Time</label>
              <input type="time" class="form-control" id="updateClassStartTime" required>
            </div>
            <div class="mb-3">
              <label for="updateClassEndTime" class="form-label">End Time</label>
              <input type="time" class="form-control" id="updateClassEndTime" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Update Class</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Class Modal -->
  <div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteClassModalLabel">Delete Class Session</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this class session?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Recording Modal -->
  <div class="modal fade" id="viewRecordingModal" tabindex="-1" aria-labelledby="viewRecordingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewRecordingModalLabel">View Session Recording</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <video controls width="100%">
            <source src="recording.mp4" type="video/mp4">
            Your browser does not support the video tag.
          </video>
          <div class="mt-3">
            <button class="btn btn-secondary" onclick="archiveRecording()">Archive</button>
            <button class="btn btn-danger" onclick="deleteRecording()">Delete</button>
            <button class="btn btn-primary" onclick="downloadRecording()">Download</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Feedback Modal -->
  <div class="modal fade" id="createFeedbackModal" tabindex="-1" aria-labelledby="createFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createFeedbackModalLabel">Create Class Feedback</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="feedbackClassName" class="form-label">Class Name</label>
              <input type="text" class="form-control" id="feedbackClassName" required>
            </div>
            <div class="mb-3">
              <label for="feedbackContent" class="form-label">Feedback</label>
              <textarea class="form-control" id="feedbackContent" rows="3" required></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Submit Feedback</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Feedback Modal -->
  <div class="modal fade" id="viewFeedbackModal" tabindex="-1" aria-labelledby="viewFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewFeedbackModalLabel">View Class Feedback</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Feedback content goes here...</p>
          <div class="mt-3">
            <button class="btn btn-secondary" onclick="archiveFeedback()">Archive</button>
            <button class="btn btn-danger" onclick="deleteFeedback()">Delete</button>
          </div>
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

  <script>
    function archiveRecording() {
      alert('Recording archived.');
    }

    function deleteRecording() {
      alert('Recording deleted.');
    }

    function downloadRecording() {
      alert('Recording downloaded.');
    }

    function archiveFeedback() {
      alert('Feedback archived.');
    }

    function deleteFeedback() {
      alert('Feedback deleted.');
    }
  </script>
</body>
</html>