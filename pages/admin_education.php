<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor Admin Dashboard</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="../assets/img/stand_alone_logo.png" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../assets/css/main.css" rel="stylesheet">
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
                  <p>Manage your educational content and users here.</p>
                  <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#editBioModal">Edit Bio</button>
                </div>
              </div>
              <div class="favorites">
                <h3>Quick Actions</h3>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#addCourseModal">Add New Subject</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#manageUsersModal">Manage Users</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">Upload Class Material</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#announcementModal">Create an Announcement</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#viewReportsModal">View Reports</a></p>
              </div>
            </div>
            <div class="right-panel">
              <div class="explore">
                <h2>Courses</h2>
                <div class="module-grid">
                  <div class="module" onclick="showCourseDetails('Python Programming', 'Learn the basics of Python programming.', 120, '5 hours', 'Beginner');" style="cursor: pointer;">
                    <img src="module1.jpg" alt="Course 1">
                    <h3>Python Programming</h3>
                    <p>Manage course content and settings.</p>
                    <div class="module-details">
                      <p>Enrolled: 120</p>
                      <p>Duration: 5 hours</p>
                    </div>
                  </div>
                  <div class="module" onclick="showCourseDetails('Networking', 'Learn the fundamentals of networking.', 80, '4 hours', 'Intermediate');" style="cursor: pointer;">
                    <img src="module2.jpg" alt="Course 2">
                    <h3>Networking</h3>
                    <p>Manage course content and settings.</p>
                    <div class="module-details">
                      <p>Enrolled: 80</p>
                      <p>Duration: 4 hours</p>
                    </div>
                  </div>
                  <div class="module" onclick="showCourseDetails('UI/UX Designing', 'Learn the principles of UI/UX design.', 150, '6 hours', 'Expert');" style="cursor: pointer;">
                    <img src="module3.jpg" alt="Course 3">
                    <h3>UI/UX Designing</h3>
                    <p>Manage course content and settings.</p>
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
                    <button onclick="location.href='edit_course.php';">Edit Course</button>
                  </div>
                </div>
              </div>
             
              </div>
              
              <div class="badges">
              <!--  
              <h2>Admin Tools</h2>
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
                   Add more tools as needed -->
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
              <label for="adminName" class="form-label">Name</label>
              <input type="text" class="form-control" id="adminName" value="Admin Name">
            </div>
            <div class="mb-3">
              <label for="adminEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="adminEmail" value="admin@example.com">
            </div>
            <div class="mb-3">
              <label for="adminBio" class="form-label">Bio</label>
              <textarea class="form-control" id="adminBio" rows="3">Admin bio goes here...</textarea>
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

  <!-- Add New Course Modal -->
  <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addCourseModalLabel">Add New Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="courseImage" class="form-label">Course Image</label>
              <input type="file" class="form-control" id="courseImage">
            </div>
            <div class="mb-3">
              <label for="courseName" class="form-label">Course Name</label>
              <input type="text" class="form-control" id="courseName">
            </div>
            <div class="mb-3">
              <label for="courseDescription" class="form-label">Course Description</label>
              <textarea class="form-control" id="courseDescription" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label for="courseLevel" class="form-label">Level</label>
              <select class="form-select" id="courseLevel">
                <option selected>Beginner</option>
                <option>Intermediate</option>
                <option>Expert</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="courseDuration" class="form-label">Duration</label>
              <input type="text" class="form-control" id="courseDuration">
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

  <!-- Manage Users Modal -->
  <div class="modal fade" id="manageUsersModal" tabindex="-1" aria-labelledby="manageUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="manageUsersModalLabel">Manage Users</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="assignTechGuru" class="form-label">Assign TechGuru to Course</label>
              <select class="form-select" id="assignTechGuru">
                <option selected>Select TechGuru</option>
                <option>TechGuru 1</option>
                <option>TechGuru 2</option>
                <option>TechGuru 3</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="assignCourse" class="form-label">Select Course</label>
              <select class="form-select" id="assignCourse">
                <option selected>Select Course</option>
                <option>Python Programming</option>
                <option>Networking</option>
                <option>UI/UX Designing</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseDetailsModal">Save changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Course Details Modal -->
  <div class="modal fade" id="courseDetailsModal" tabindex="-1" aria-labelledby="courseDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="courseDetailsModalLabel">Course Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Details about the selected course will be displayed here.</p>
          <!-- Add more details as needed -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  
  <!-- Upload Class Material Modal -->
  
  <!-- Upload Class Material Modal -->
  <div class="modal fade" id="uploadMaterialModal" tabindex="-1" aria-labelledby="uploadMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="uploadMaterialModalLabel">Upload Class Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="materialCourse" class="form-label">Select Course</label>
              <select class="form-select" id="materialCourse">
                <option selected>Select Course</option>
                <option>Python Programming</option>
                <option>Networking</option>
                <option>UI/UX Designing</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="materialFile" class="form-label">Class Material</label>
              <input type="file" class="form-control" id="materialFile">
            </div>
            <div class="mb-3">
              <label for="materialDescription" class="form-label">Material Description</label>
              <textarea class="form-control" id="materialDescription" rows="3"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Upload</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="announcementModalLabel">Create Announcement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label for="announcementTitle" class="form-label">Subject line</label>
            <input type="text" class="form-control" id="announcementTitle" required>
          </div>
          <div class="mb-3">
            <label for="announcementMessage" class="form-label">Message</label>
            <textarea class="form-control" id="announcementMessage" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label for="announcementTarget" class="form-label">Target</label>
            <select class="form-select" id="announcementTarget" required>
              <option selected>Select Target</option>
              <option value="techkids">TechKids</option>
              <option value="techgurus">TechGurus</option>
              <option value="classes">Classes</option>
              <option value="subjects">Subjects</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="announcementTargetSpecific" class="form-label">Specific Target</label>
            <input type="text" class="form-control" id="announcementTargetSpecific" placeholder="Enter specific target (e.g., Class ID, Subject Name)">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Create Announcement</button>
      </div>
    </div>
  </div>
</div>


  <!-- View Reports Modal -->
  <div class="modal fade" id="viewReportsModal" tabindex="-1" aria-labelledby="viewReportsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewReportsModalLabel">View Reports</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="reportType" class="form-label">Report Type</label>
              <select class="form-select" id="reportType">
                <option selected>Select Report Type</option>
                <option>Enrollment Report</option>
                <option>Course Completion Report</option>
                <option>Performance Report</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="reportDuration" class="form-label">Duration</label>
              <select class="form-select" id="reportDuration">
                <option selected>Select Duration</option>
                <option>Last 7 days</option>
                <option>Last 30 days</option>
                <option>Last 6 months</option>
                <option>Last 1 year</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Generate Report</button>
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