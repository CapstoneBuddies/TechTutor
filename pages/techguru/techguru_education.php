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
      <h1 class="h2">Class Subject > Java Programming</h1>
      <div class="search-bar d-flex">
        <input type="text" id="classSearch" placeholder="Search classes..." class="form-control me-2">
        <button class="btn btn-primary" onclick="searchClasses()" >Search</button>
      </div>
      </div>


      <div class="row">
        <div class="col-md-6">
            <div class="row mb-3">
              <div class="col-md-12 d-flex justify-content-between">
                <h3>Introduction to Java</h3>  
                <button class="btn btn-primary mt-3" onclick="addTopic()">Add Topic</button>
              
              </div>
            </div>
          <ul class="list-group">
            
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.
            </p>
            <p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.
            </p>
          </ul>
        </div>

        <div class="col-md-6">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Materials</h3>
          <div>
            <button class="btn btn-primary mt-3" onclick="addMaterial()">Add Material</button>
            <button class="btn btn-danger mt-3" onclick="toggleDeleteMode()">Delete Material</button>
          </div>
              </div>
              <div class="card-body">
          <ul class="list-group mt-3" id="materialList">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <a href="#">Java Basics.pdf</a>
              <input type="checkbox" class="delete-checkbox" style="display: none;">
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <a href="#">OOP Concepts.pptx</a>
              <input type="checkbox" class="delete-checkbox" style="display: none;">
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <a href="#">Advanced Java.docx</a>
              <input type="checkbox" class="delete-checkbox" style="display: none;">
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <a href="#">Control Flow Statements.pdf</a>
              <input type="checkbox" class="delete-checkbox" style="display: none;">
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <a href="#">Exception Handling.pptx</a>
              <input type="checkbox" class="delete-checkbox" style="display: none;">
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <a href="#">Java Collections Framework.docx</a>
              <input type="checkbox" class="delete-checkbox" style="display: none;">
            </li>
          </ul>
          <div class="mt-3 d-flex justify-content-end" id="deleteActions" style="display: none;">
            <button class="btn btn-secondary me-2" onclick="cancelDelete()">Cancel</button>
            <button class="btn btn-danger" onclick="deleteSelectedMaterials()">Delete</button>
          </div>
              </div>
            </div>
          </div>

      <script>
      function toggleDeleteMode() {
        const checkboxes = document.querySelectorAll('.delete-checkbox');
        const deleteActions = document.getElementById('deleteActions');
        checkboxes.forEach(checkbox => {
          checkbox.style.display = checkbox.style.display === 'none' ? 'block' : 'none';
        });
        deleteActions.style.display = deleteActions.style.display === 'none' ? 'flex' : 'none';
      }

      function cancelDelete() {
        const checkboxes = document.querySelectorAll('.delete-checkbox');
        const deleteActions = document.getElementById('deleteActions');
        checkboxes.forEach(checkbox => {
          checkbox.style.display = 'none';
          checkbox.checked = false;
        });
        deleteActions.style.display = 'none';
      }

      function deleteSelectedMaterials() {
        const checkboxes = document.querySelectorAll('.delete-checkbox:checked');
        checkboxes.forEach(checkbox => {
          checkbox.closest('li').remove();
        });
        cancelDelete();
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