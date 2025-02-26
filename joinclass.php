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
</head>
<body>
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
            <a href="#" class="nav-link dropdown-toggle" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-bell"></i>
            </a>
            <ul class="dropdown-menu" aria-labelledby="notificationDropdown">
              <li><a class="dropdown-item" href="#">New message from Admin</a></li>
              <li><a class="dropdown-item" href="#">Update on your course</a></li>
              <li><a class="dropdown-item" href="#">System maintenance notice</a></li>
              <!-- Add more notifications as needed -->
            </ul>
          </li>
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

<main style="margin-top: 100px;"> <!-- Adjust the margin-top value as needed -->
  <!-- New Section for Module Page -->
  <section id="module-page" class="module-page">
    <div class="container">
      <div class="dashboard">
        <div class="main-content">
          <div class="left-panel">
            <div class="user-info rounded-box">
              <img src="assets/img/Python-Logo.png" alt="Python Logo" class="subject-logo"> <!-- Changed to Python logo -->
              <div class="user-details">
                <h2>Python Programming</h2>
                <hr>
                <p>Subject description goes here. Learn how to implement various features...</p>
              </div>
            </div>
            <div class="schedule rounded-box">
              <h3>Ongoing Session</h3>
              <p>February 21, 2025</p>
              <p>3:00 PM - 5:00 PM</p>
              <a href="classroom.php" class="join-class-btn">Join Class</a>
            </div>
            <div class="files rounded-box" style="height: auto;">
              <h3>Files</h3>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">View Files</button>
            </div>
          </div>
          <div class="right-panel">
            <div class="explore">
              <h2>Topics</h2>
              <div class="module-grid">
                <div class="module-container">
                  <div class="module-details">
                    <p>Python Programming</p>
                    <p><i class="bi bi-check-circle" id="lesson1-check"></i></p>
                  </div>
                  <hr>
                  <div class="module-details">
                    <p>Java Programming</p>
                    <p><i class="bi bi-check-circle" id="lesson2-check"></i></p>
                  </div>
                  <hr>
                  <div class="module-details">
                    <p>PHP Programming</p>
                    <p><i class="bi bi-check-circle" id="lesson2-check"></i></p>
                  </div>
                  <!-- Add more lessons as needed -->
                </div>
              </div>
            </div>
            <div class="jump-back-in">
              <h2>Jump Back In</h2>
              <div class="difficulty-levels">
                <button class="difficulty-btn" onclick="showLesson('beginner')">Beginner</button>
                <button class="difficulty-btn" id="intermediate-btn" onclick="showLesson('intermediate')" disabled>Intermediate</button>
                <button class="difficulty-btn" id="advanced-btn" onclick="showLesson('advanced')" disabled>Advanced</button>
              </div>
              <div id="lesson-details" class="trail-card">
                <h3>Python Programming</h3>
                <p>First Lesson.</p>
                <div class="trail-progress">
                  <p>+7,200 Points</p>
                  <div class="progress-bar">
                    <div class="progress" style="width: 13%;"></div>
                  </div>
                  <button id="continue-btn" disabled>Continue</button>
                </div>
              </div>
              <div id="intermediate-details" class="trail-card" style="display: none;">
                <h3>Python Programming</h3>
                <p>Intermediate Lesson.</p>
                <div class="trail-progress">
                  <p>0 Points</p>
                  <div class="progress-bar">
                    <div class="progress" style="width: 0%;"></div>
                  </div>
                  <button id="intermediate-continue-btn" disabled>Locked</button>
                </div>
              </div>
              <div id="advanced-details" class="trail-card" style="display: none;">
                <h3>Python Programming</h3>
                <p>Advanced Lesson.</p>
                <div class="trail-progress">
                  <p>0 Points</p>
                  <div class="progress-bar">
                    <div class="progress" style="width: 0%;"></div>
                  </div>
                  <button id="advanced-continue-btn" disabled>Locked</button>
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
  </section>
</main>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Make the modal larger -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="uploadModalLabel">View Files</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#chooseFileModal">Choose File</button>
        <div class="container mt-3 border p-3"> <!-- Add border to the container -->
          <h5>Uploaded Files</h5>
          <div class="row">
            <div class="col-3"><strong>Title</strong></div>
            <div class="col-3"><strong>Name</strong></div>
            <div class="col-3"><strong>Size</strong></div>
            <div class="col-3"><strong>Actions</strong></div>
          </div>
          <div id="fileDetailsList">
            <!-- File details will be displayed here -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Choose File Modal -->
<div class="modal fade" id="chooseFileModal" tabindex="-1" aria-labelledby="chooseFileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Make the modal larger -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="chooseFileModalLabel">Choose File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="uploadForm">
          <div class="mb-3">
            <label for="fileTitle" class="form-label">File Title</label>
            <input type="text" class="form-control" id="fileTitle" required>
          </div>
          <div class="mb-3">
            <label for="fileInput" class="form-label">Choose file</label>
            <input type="file" class="form-control" id="fileInput" required>
          </div>
          <button type="submit" class="btn btn-primary">Upload</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- File List Modal -->
<div class="modal fade" id="fileListModal" tabindex="-1" aria-labelledby="fileListModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fileListModalLabel">Uploaded Files</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="container">
          <ul class="list-group" id="uploadedFileList">
            <!-- Uploaded files will be displayed here -->
          </ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
  function showLesson(level) {
    document.getElementById('lesson-details').style.display = 'none';
    document.getElementById('intermediate-details').style.display = 'none';
    document.getElementById('advanced-details').style.display = 'none';
    
    if (level === 'beginner') {
      document.getElementById('lesson-details').style.display = 'block';
    } else if (level === 'intermediate') {
      document.getElementById('intermediate-details').style.display = 'block';
      document.getElementById('intermediate-details').innerHTML = document.getElementById('lesson-details').innerHTML.replace('First Lesson.', 'Intermediate Lesson.').replace('+7,200 Points', '0 Points').replace('width: 13%;', 'width: 0%;');
    } else if (level === 'advanced') {
      document.getElementById('advanced-details').style.display = 'block';
    }

    // Logic to unlock the button if the first lesson is completed
    const firstLessonCompleted = true; // Replace with actual logic
    if (firstLessonCompleted) {
      document.getElementById('continue-btn').disabled = false;
      document.getElementById('intermediate-btn').disabled = false;
      document.getElementById('advanced-btn').disabled = false;
      document.getElementById('intermediate-continue-btn').innerText = 'Continue';
      document.getElementById('intermediate-continue-btn').disabled = false;
      document.getElementById('advanced-continue-btn').innerText = 'Continue';
      document.getElementById('advanced-continue-btn').disabled = false;
    }
  }

  // Example logic to mark lessons as completed
  const lesson1Completed = true; // Replace with actual logic
  const lesson2Completed = false; // Replace with actual logic

  if (lesson1Completed) {
    document.getElementById('lesson1-check').classList.add('completed');
  }
  if (lesson2Completed) {
    document.getElementById('lesson2-check').classList.add('completed');
  }

  // Handle file upload
  document.getElementById('uploadForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const fileInput = document.getElementById('fileInput');
    const fileTitle = document.getElementById('fileTitle').value;
    const fileDetailsList = document.getElementById('fileDetailsList');

    if (fileInput.files.length > 0) {
      const file = fileInput.files[0];
      const fileName = file.name;
      const fileSize = (file.size / 1024).toFixed(2) + ' KB'; // Convert size to KB

      const fileDetails = document.createElement('div');
      fileDetails.className = 'row';
      fileDetails.innerHTML = `
        <div class="col-3">${fileTitle}</div>
        <div class="col-3">${fileName}</div>
        <div class="col-3">${fileSize}</div>
        <div class="col-3">
          <button class="btn btn-outline-danger btn-sm" onclick="deleteFile(this)"><i class="bi bi-trash"></i></button>
          <button class="btn btn-outline-primary btn-sm" onclick="downloadFile('${fileName}')"><i class="bi bi-download"></i></button>
        </div>`;
      fileDetailsList.appendChild(fileDetails);

      fileInput.value = ''; // Clear the input
      const chooseFileModal = bootstrap.Modal.getInstance(document.getElementById('chooseFileModal'));
      chooseFileModal.hide(); // Hide the choose file modal
      const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
      uploadModal.show(); // Show the upload modal
    }
  });

  function deleteFile(button) {
    const fileDetails = button.closest('.row');
    fileDetails.remove();
  }

  function downloadFile(fileName) {
    // Implement file download logic here
    alert(`Downloading ${fileName}`);
  }
</script>
</body>
</html>