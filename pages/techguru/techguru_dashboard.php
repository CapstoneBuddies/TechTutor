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

      <a href="home" class="logo d-flex align-items-center me-auto">
        <img src="assets/img/stand_alone_logo.png" alt="">
        <img src="assets/img/TechTutor_text.png" alt="">
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
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
                <img src=<?php echo $_SESSION['profile']; ?> alt="Educator Avatar" class="avatar">
                <div class="user-details">
                  <h2>Welcome, Educator!</h2>
                  <p>Manage your classes and materials here.</p>
                  <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#editBioModal">Edit Bio</button>
                </div>
              </div>
              <div class="favorites">
                <h3>Quick Actions</h3>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#requestJoinClassModal">Request to Join Class</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#viewSubjectsModal">View Subjects</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#viewClassesModal">View Available Classes</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#updateClassInfoModal">Update Class Information</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#createMaterialModal">Create Class Material</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#updateMaterialModal">Update Class Material</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#viewMaterialModal">View Class Material</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#deleteMaterialModal">Delete Class Material</a></p>
              </div>
            </div>
            <div class="right-panel">
              <div class="explore">
                <h2>Classes</h2>
                <div class="module-grid">
                  <div class="module" onclick="showClassDetails('Python Programming', 'Learn the basics of Python programming.', 120, '5 hours', 'Beginner');" style="cursor: pointer;">
                    <img src=<?php echo CLASS_IMG.'Python.png'; ?> alt="Class 1">
                    <h3>Python Programming</h3>
                    <p>Manage class content and settings.</p>
                    <div class="module-details">
                      <p>Enrolled: 120</p>
                      <p>Duration: 5 hours</p>
                    </div>
                  </div>
                  <div class="module" onclick="showClassDetails('Networking', 'Learn the fundamentals of networking.', 80, '4 hours', 'Intermediate');" style="cursor: pointer;">
                    <img src="module2.jpg" alt="Class 2">
                    <h3>Networking</h3>
                    <p>Manage class content and settings.</p>
                    <div class="module-details">
                      <p>Enrolled: 80</p>
                      <p>Duration: 4 hours</p>
                    </div>
                  </div>
                  <div class="module" onclick="showClassDetails('UI/UX Designing', 'Learn the principles of UI/UX design.', 150, '6 hours', 'Expert');" style="cursor: pointer;">
                    <img src="module3.jpg" alt="Class 3">
                    <h3>UI/UX Designing</h3>
                    <p>Manage class content and settings.</p>
                    <div class="module-details">
                      <p>Enrolled: 150</p>
                      <p>Duration: 6 hours</p>
                    </div>
                  </div>
                  <!-- Add more modules as needed -->
                </div>
              </div>
              <div class="jump-back-in">
                <h2>Recent Activities</h2>
                <div class="trail-card">
                  <h3>Python Programming</h3>
                  <p>Last updated: 2 days ago.</p>
                  <div class="trail-progress">
                    <p>Enrolled: 120</p>
                    <div class="progress-bar">
                      <div class="progress" style="width: 75%;"></div>
                    </div>
                    <button onclick="location.href='edit_class.php';">Edit Class</button>
                  </div>
                </div>
              </div>
              <div class="badges">
                <h2>Educator Tools</h2>
                <div class="badge-grid">
                  <div class="badge">
                    <img src="badge1.jpg" alt="Tool 1">
                    <p>Reports</p>
                  </div>
                  <div class="badge">
                    <img src="badge2.jpg" alt="Tool 2">
                    <p>User Management</p>
                  </div>
                  <div class="badge">
                    <img src="badge3.jpg" alt="Tool 3">
                    <p>Settings</p>
                  </div>
                  <!-- Add more tools as needed -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->
  </main>

  <!-- Edit Bio Modal -->
  <div class="modal fade" id="editBioModal" tabindex="-1" aria-labelledby="editBioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editBioModalLabel">Edit Bio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="bio" class="form-label">Bio</label>
              <textarea class="form-control" id="bio" rows="3"></textarea>
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

  <!-- Request to Join Class Modal -->
  <div class="modal fade" id="requestJoinClassModal" tabindex="-1" aria-labelledby="requestJoinClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="requestJoinClassModalLabel">Request to Join Class</h5>
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
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Request</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Subjects Modal -->
  <div class="modal fade" id="viewSubjectsModal" tabindex="-1" aria-labelledby="viewSubjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewSubjectsModalLabel">View Subjects</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Add content for viewing subjects here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Available Classes Modal -->
  <div class="modal fade" id="viewClassesModal" tabindex="-1" aria-labelledby="viewClassesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewClassesModalLabel">View Available Classes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Add content for viewing available classes here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Class Information Modal -->
  <div class="modal fade" id="updateClassInfoModal" tabindex="-1" aria-labelledby="updateClassInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateClassInfoModalLabel">Update Class Information</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="classNameUpdate" class="form-label">Class Name</label>
              <input type="text" class="form-control" id="classNameUpdate" required>
            </div>
            <div class="mb-3">
              <label for="classDescriptionUpdate" class="form-label">Class Description</label>
              <textarea class="form-control" id="classDescriptionUpdate" rows="3" required></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Update</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Class Material Modal -->
  <div class="modal fade" id="createMaterialModal" tabindex="-1" aria-labelledby="createMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createMaterialModalLabel">Create Class Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="materialTitle" class="form-label">Title</label>
              <input type="text" class="form-control" id="materialTitle" required>
            </div>
            <div class="mb-3">
              <label for="materialContent" class="form-label">Content</label>
              <textarea class="form-control" id="materialContent" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label for="materialFile" class="form-label">Upload File</label>
              <input type="file" class="form-control" id="materialFile" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Create</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Class Material Modal -->
  <div class="modal fade" id="updateMaterialModal" tabindex="-1" aria-labelledby="updateMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateMaterialModalLabel">Update Class Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="materialTitleUpdate" class="form-label">Title</label>
              <input type="text" class="form-control" id="materialTitleUpdate" required>
            </div>
            <div class="mb-3">
              <label for="materialContentUpdate" class="form-label">Content</label>
              <textarea class="form-control" id="materialContentUpdate" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label for="materialFileUpdate" class="form-label">Upload File</label>
              <input type="file" class="form-control" id="materialFileUpdate" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Update</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Class Material Modal -->
  <div class="modal fade" id="viewMaterialModal" tabindex="-1" aria-labelledby="viewMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewMaterialModalLabel">View Class Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Add content for viewing class material here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Class Material Modal -->
  <div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-labelledby="deleteMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteMaterialModalLabel">Delete Class Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Add content for deleting class material here -->
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
</body>
</html>