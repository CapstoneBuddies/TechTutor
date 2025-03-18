<?php 
  require_once 'backends/main.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor - Personalized IT Tutoring Platform</title>
  <meta name="description" content="Connect with expert IT tutors for personalized learning. Flexible scheduling, real-time online sessions, and comprehensive course materials.">
  <meta name="keywords" content="IT tutoring, online learning, programming tutorials, tech education, flexible learning">

  <!-- Favicons -->
  <link href="assets/img/stand_alone_logo.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  
  <!-- Additional CSS for Landing Page -->
  <style>
    
  </style>
</head>

<body class="index-page">
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="home" class="logo d-flex align-items-center me-auto">
        <img src="assets/img/stand_alone_logo.png" alt="TechTutor Logo">
        <img src="assets/img/TechTutor_text.png" alt="TechTutor">
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="home" class="active">Home</a></li>
          <li><a href="#features">Features</a></li>
          <li><a href="#how-it-works">How It Works</a></li>
          <li><a href="#testimonials">Success Stories</a></li>
          <li><a href="#contact">Contact</a></li>
          <a id="login" class="btn-getstarted flex-md-shrink-0 signin" href="login">Sign In</a>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">
    <!-- Hero Section -->
    <section id="hero" class="hero section">
      <div class="container">
        <div class="row gy-5 align-items-center">
          <div class="col-lg-6 order-2 order-lg-1">
            <h1 data-aos="fade-up">Master Tech Skills</h1>
            <h1 data-aos="fade-up" data-aos-delay="100">With Expert Tutors</h1>
            <p data-aos="fade-up" data-aos-delay="200">
              Connect with experienced IT tutors for personalized learning that fits your schedule. 
              Whether you're catching up with coursework or advancing your tech career, our platform 
              makes learning accessible and effective.
            </p>
            <div class="d-flex gap-3 mt-4" data-aos="fade-up" data-aos-delay="300">
              <a href="register" class="btn btn-primary btn-lg">Start Learning</a>
              <a href="register?role=tutor" class="btn btn-outline-primary btn-lg">Become a Tutor</a>
            </div>
          </div>
          <div class="col-lg-6 order-1 order-lg-2 hero-img" data-aos="zoom-out">
            <img src="assets/img/hero-img.png" class="img-fluid animated" alt="Online Tutoring Illustration">
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
      <div class="container" data-aos="fade-up">
        <div class="section-title text-center mb-5">
          <h2>Platform Features</h2>
          <p>Everything You Need to Succeed</p>
        </div>

        <div class="row g-4">
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-calendar-check"></i>
              </div>
              <h3>Flexible Scheduling</h3>
              <p>Choose from multiple time slots that fit your schedule. Book sessions when it's convenient for you.</p>
            </div>
          </div>

          <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-camera-video"></i>
              </div>
              <h3>Live Online Sessions</h3>
              <p>High-quality video meetings integrated right into our platform for seamless learning experience.</p>
            </div>
          </div>

          <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-book"></i>
              </div>
              <h3>Learning Management</h3>
              <p>Access course materials, track progress, and manage your learning journey all in one place.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="section light-background">
      <div class="container" data-aos="fade-up">
        <div class="section-title text-center mb-5">
          <h2>How It Works</h2>
          <p>Simple Steps to Start Learning</p>
        </div>

        <div class="row g-4">
          <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
            <div class="how-it-works-step">
              <div class="step-number">1</div>
              <h4>Browse Tutors</h4>
              <p>Find expert tutors specializing in your desired tech subject.</p>
            </div>
          </div>

          <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
            <div class="how-it-works-step">
              <div class="step-number">2</div>
              <h4>Choose Schedule</h4>
              <p>Select from available time slots that work best for you.</p>
            </div>
          </div>

          <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
            <div class="how-it-works-step">
              <div class="step-number">3</div>
              <h4>Book Session</h4>
              <p>Secure your spot with our simple booking process.</p>
            </div>
          </div>

          <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
            <div class="how-it-works-step">
              <div class="step-number">4</div>
              <h4>Start Learning</h4>
              <p>Join your online session and begin your learning journey.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="section">
      <div class="container" data-aos="fade-up">
        <div class="section-title text-center mb-5">
          <h2>Success Stories</h2>
          <p>What Our TechKids Say</p>
        </div>

        <div class="row g-4">
          <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="testimonial-card">
              <img src="https://ui-avatars.com/api/?name=Trisha+M&background=012970&color=fff&size=100" alt="TechKid" class="testimonial-avatar">
              <p class="testimonial-quote">"Thanks to my TechGuru's flexible schedule, I was able to catch up with my Python programming class while managing my busy college schedule."</p>
              <h5>Trisha M.</h5>
              <p class="text-muted">TechKid - Computer Science</p>
            </div>
          </div>

          <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="testimonial-card">
              <img src="https://ui-avatars.com/api/?name=Allan+G&background=012970&color=fff&size=100" alt="TechGuru" class="testimonial-avatar">
              <p class="testimonial-quote">"As a working professional, I can share my knowledge during my free time and help TechKids excel in their tech journey."</p>
              <h5>Allan G.</h5>
              <p class="text-muted">TechGuru - Python Programming</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact section light-background">
      <div class="container section-title" data-aos="fade-up">
        <h2>Contact</h2>
        <p>Get in Touch</p>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <div class="col-lg-6">
            <div class="row gy-4">
              <div class="col-md-6">
                <div class="info-item" data-aos="fade" data-aos-delay="400">
                  <i class="bi bi-envelope"></i>
                  <h3>Email Us</h3>
                  <p>support@techtutor.cfd</p>
                </div>
              </div>

              <div class="col-md-6">
                <div class="info-item" data-aos="fade" data-aos-delay="500">
                  <i class="bi bi-clock"></i>
                  <h3>Support Hours</h3>
                  <p>Monday - Friday</p>
                  <p>9:00AM - 05:00PM</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <form action="sent" method="post" class="php-email-form" id="contactForm" data-aos="fade-up" data-aos-delay="200">
              <div class="row gy-4">
                <div class="col-md-6">
                  <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                </div>

                <div class="col-md-6">
                  <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                </div>

                <div class="col-12">
                  <textarea class="form-control" name="message" rows="6" placeholder="Message" required></textarea>
                </div>

                <div class="col-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>
                  <button type="submit" id="send-concern" class="btn btn-primary btn-lg">Send Message</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Scroll Top Button -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php include ROOT_PATH . '/components/footer.php'; ?>
  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  
  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/contact.js"></script>
</body>
</html>