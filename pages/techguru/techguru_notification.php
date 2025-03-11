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



<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar vh-100">
      <div class="position-sticky">
      <center><img src="../../assets/img/stand_alone_logo.png" alt="" style="width: 50px; height: 50px; margin: 20px auto;"></center>
      <!-- User Profile Section -->
      <div class="text-center py-4">
        <img src="<?php echo $_SESSION['profile']; ?>" alt="User Avatar" class="rounded-circle profile" width="80" height="80">
        <h5 class="mt-2" style="font-size: 20px;"><?php echo $_SESSION['name']; ?></h5>
        <span style="font-size: 16px;">Educator</span>
      </div>

      <!-- Sidebar Menu -->
      <ul class="nav flex-column">
        <li class="nav-item">
        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="dashboard">
          <i class="bi bi-house-door"></i>
          <span class="ms-2">Dashboard</span>
        </a>
        </li>
        <li class="nav-item">
        <a class="nav-link d-flex align-items-center px-3 py-2 rounded active" href="classes" style="background-color: #0F52BA;">
          <i class="bi bi-journal-bookmark"></i>
          <span class="ms-2">Classes</span>
        </a>
        </li>
        <li class="nav-item">
        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="notifications">
          <i class="bi bi-bell"></i>
          <span class="ms-2">Notifications</span>
        </a>
        </li>
        <li class="nav-item">
        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="transactions">
          <i class="bi bi-currency-dollar"></i>
          <span class="ms-2">Transactions</span>
        </a>
        </li>
        <li class="nav-item">
        <a class="nav-link d-flex align-items-center px-3 py-2 rounded" href="certificates">
          <i class="bi bi-award"></i>
          <span class="ms-2">Certificates</span>
        </a>
        </li>
        
      </ul>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

    <div class="container-fluid container-xl position-relative d-flex align-items-center">

<a href="home" class="logo d-flex align-items-center me-auto">
  <img src="assets/img/stand_alone_logo.png" alt="">
  <img src="assets/img/TechTutor_text.png" alt="">
</a>

<nav id="navmenu" class="navmenu">
  <ul class="d-flex align-items-center">
  <li class="nav-item dropdown">
        <a href="#" class="nav-link" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
          <li><a class="dropdown-item" href="#">Sender 1</a></li><hr>
          <li><a class="dropdown-item" href="#">Sender 2</a></li><hr>
          <li><a class="dropdown-item" href="#">Sender 3</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#">See all notifications</a></li>
        </ul>
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

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2">Notification</h1>
      <div class="search-bar d-flex">
        <button class="btn btn-primary" style="background-color: #0F52BA;" onclick="openCreateClassModal()">Create Notification</button>
      </div>

      <!-- Create Class Modal -->
      <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createClassModalLabel">Create Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="createClassForm">
            <div class="mb-3">
          <label for="newCourse" class="form-label">Course</label>
          <input type="text" class="form-control" id="newCourse" required>
            </div>
            <div class="mb-3">
          <label for="newSubject" class="form-label">Subject</label>
          <input type="text" class="form-control" id="newSubject" required>
            </div>
            <div class="mb-3">
          <label for="newDescription" class="form-label">Description</label>
          <textarea class="form-control" id="newDescription" rows="3" required></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="createClass()">Create</button>
        </div>
          </div>
        </div>
      </div>

      <script>
        function openCreateClassModal() {
          var createClassModal = new bootstrap.Modal(document.getElementById('createClassModal'));
          createClassModal.show();
        }

        function createClass() {
          // Perform the create class logic here
          var createClassModal = bootstrap.Modal.getInstance(document.getElementById('createClassModal'));
          createClassModal.hide();

          // Show notification or update the class list
        }
      </script>
      </div>

      <div class="row">
        <table class="table table-striped">
          <thead>
        <tr>
          <th scope="col">Subject</th>
          <th scope="col">Message</th>
          <th scope="col">Time</th>
          <th scope="col">Action</th>
        </tr>
          </thead>
          <tbody>
        <tr>
          <td>Computer Programming</td>
          <td>lorem ipsum bantay ka bata 163</td>
          <td>Monday, 10:00 AM - 12:00 PM</td>
            <td>
            <button class="btn btn-danger" onclick="deleteNotification('Computer Programming')">
              <i class="bi bi-trash"></i>
            </button>
            </td>
        </tr>
        <tr>
          <td>Computer Networking</td>
          <td>lorem ipsum bantay ka bata 163</td>
          <td>Wednesday, 2:00 PM - 4:00 PM</td>
          <td>
            <button class="btn btn-danger" onclick="deleteNotification('Computer Programming')">
              <i class="bi bi-trash"></i>
            </button>
            </td>
        </tr>
        <tr>
          <td>UI/UX Design</td>
          <td>lorem ipsum bantay ka bata 163</td>
          <td>Friday, 1:00 PM - 3:00 PM</td>
          <td>
            <button class="btn btn-danger" onclick="deleteNotification('Computer Programming')">
              <i class="bi bi-trash"></i>
            </button>
            </td>
        </tr>
          </tbody>
        </table>
      </div>

      <!-- Update Class Modal -->
      <div class="modal fade" id="updateClassModal" tabindex="-1" aria-labelledby="updateClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateClassModalLabel">Update Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="updateClassForm">
            <div class="mb-3">
          <label for="course" class="form-label">Course</label>
          <input type="text" class="form-control" id="course" required>
            </div>
            <div class="mb-3">
          <label for="subject" class="form-label">Subject</label>
          <input type="text" class="form-control" id="subject" required>
            </div>
            <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" id="description" rows="3" required></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="confirmUpdate()">Update</button>
        </div>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          You are about to update class information. Do you want to continue?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="updateClass()">Confirm</button>
        </div>
          </div>
        </div>
      </div>

      <!-- Notification Modal -->
      <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="notificationModalLabel">Notification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Your class information has been successfully updated.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
          </div>
        </div>
      </div>

      <script>
        function openUpdateModal(subject, description) {
          document.getElementById('subject').value = subject;
          document.getElementById('description').value = description;
          var updateClassModal = new bootstrap.Modal(document.getElementById('updateClassModal'));
          updateClassModal.show();
        }

        function confirmUpdate() {
          var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
          confirmationModal.show();
        }

        function updateClass() {
          var confirmationModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
          confirmationModal.hide();

          // Perform the update class logic here

          var notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
          notificationModal.show();
        }
      </script>
      
      
      
    </main>
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

<script>
function searchClasses() {
  const searchTerm = document.getElementById('classSearch').value.toLowerCase();
  const modules = document.querySelectorAll('.module');

  modules.forEach(module => {
    const className = module.querySelector('.card-title').textContent.toLowerCase();
    if (className.includes(searchTerm)) {
      module.style.display = 'block';
    } else {
      module.style.display = 'none';
    }
  });
}
</script>

</body>
</html>