<?php 
    require_once '../backends/main.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor - Help Center</title>
  <meta name="description" content="Get help with TechTutor's platform, find answers to common questions, and access support resources.">
  <meta name="keywords" content="TechTutor help, online tutoring support, tech education help">

  <!-- Favicons -->
  <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
  <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="<?php echo BASE; ?>assets/css/main.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/css/footer.css" rel="stylesheet">
  
  <style>
    .help-center-section {
      padding: 80px 0;
      background-color: #f6f9ff;
    }
    
    .help-card {
      background: #fff;
      border-radius: 8px;
      padding: 30px;
      height: 100%;
      transition: 0.3s;
      border: 1px solid rgba(0,0,0,0.1);
    }
    
    .help-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .help-card i {
      font-size: 2.5rem;
      color: #4154f1;
      margin-bottom: 20px;
    }
    
    .help-card h3 {
      font-size: 1.5rem;
      margin-bottom: 15px;
      color: #012970;
    }
    
    .help-card p {
      color: #666;
      margin-bottom: 20px;
    }
    
    .help-card .btn {
      padding: 8px 20px;
      border-radius: 4px;
    }
    
    .search-container {
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .search-container .input-group {
      box-shadow: 0 0 20px rgba(1, 41, 112, 0.1);
      border-radius: 50px;
      overflow: hidden;
      background-color: #fff;
    }
    
    .search-container .input-group-text {
      border-radius: 50px 0 0 50px;
      border: none;
      background: #fff;
      padding-left: 20px;
      color: #012970;
    }
    
    #help-search {
      border-radius: 0 50px 50px 0;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
    }
    
    #help-search:focus {
      outline: none;
      box-shadow: none;
    }
    
    .quick-links {
      margin-top: 40px;
    }
    
    .quick-links h3 {
      margin-bottom: 20px;
      color: #012970;
    }
    
    .quick-links ul {
      list-style: none;
      padding: 0;
    }
    
    .quick-links li {
      margin-bottom: 10px;
    }
    
    .quick-links a {
      color: #666;
      text-decoration: none;
      transition: 0.3s;
    }
    
    .quick-links a:hover {
      color: #4154f1;
      padding-left: 5px;
    }
  </style>
</head>

<body class="page-help-center">
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
      <a href="<?php echo BASE; ?>home" class="logo d-flex align-items-center">
        <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor Logo">
        <img src="<?php echo BASE; ?>assets/img/TechTutor_text.png" alt="TechTutor">
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="<?php echo BASE; ?>home">Home</a></li>
          <li><a href="<?php echo BASE; ?>home#features">Features</a></li>
          <li><a href="<?php echo BASE; ?>home#how-it-works">How It Works</a></li>
          <li><a href="<?php echo BASE; ?>home#testimonials">Success Stories</a></li>
          <li><a href="<?php echo BASE; ?>home#contact">Contact</a></li>
          <li><a href="<?php echo BASE; ?>faqs">FAQS</a></li>
          <li><a href="<?php echo BASE; ?>help_center" class="active">Help Center</a></li>
          <?php if(isset($_SESSION['user'])): ?>
            <a class="btn-getstarted" href="<?php echo BASE; ?>dashboard">Dashboard</a>
          <?php else: ?>
            <a class="btn-getstarted" href="<?php echo BASE; ?>login">Sign In</a>
          <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main id="main">
    <!-- Page title section -->
    <div class="breadcrumbs">
      <div class="container">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Help Center</h2>
          <ol>
            <li><a href="<?php echo BASE; ?>home">Home</a></li>
            <li>Help Center</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Help Center Section -->
    <section id="help-center" class="help-center-section">
      <div class="container" data-aos="fade-up">
        <div class="row">
          <div class="col-lg-10 mx-auto">
            <!-- Search -->
            <div class="search-container" data-aos="fade-up" data-aos-delay="100">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-search"></i>
                </span>
                <input type="text" id="help-search" class="form-control" placeholder="Search for help...">
              </div>
            </div>
            
            <!-- Help Categories -->
            <div class="row g-4" data-aos="fade-up" data-aos-delay="200">
              <div class="col-md-6 col-lg-4">
                <div class="help-card">
                  <i class="bi bi-question-circle"></i>
                  <h3>FAQs</h3>
                  <p>Find answers to frequently asked questions about our platform, features, and services.</p>
                  <a href="<?php echo BASE; ?>faqs" class="btn btn-primary">View FAQs</a>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="help-card">
                  <i class="bi bi-book"></i>
                  <h3>Guides & Tutorials</h3>
                  <p>Step-by-step guides and video tutorials to help you get the most out of TechTutor.</p>
                  <a href="#" class="btn btn-primary">View Guides</a>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="help-card">
                  <i class="bi bi-headset"></i>
                  <h3>Contact Support</h3>
                  <p>Need personalized help? Our support team is here to assist you with any questions.</p>
                  <a href="#contact" class="btn btn-primary">Contact Us</a>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="help-card">
                  <i class="bi bi-gear"></i>
                  <h3>Technical Support</h3>
                  <p>Having technical issues? Get help with platform access, video calls, and more.</p>
                  <a href="mailto:technical@techtutor.cfd" class="btn btn-primary">Get Help</a>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="help-card">
                  <i class="bi bi-credit-card"></i>
                  <h3>Billing & Payments</h3>
                  <p>Questions about payments, refunds, or your account balance? We're here to help.</p>
                  <a href="mailto:payments@techtutor.cfd" class="btn btn-primary">Payment Help</a>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="help-card">
                  <i class="bi bi-shield-check"></i>
                  <h3>Safety & Security</h3>
                  <p>Learn about our security measures and how to keep your account safe.</p>
                  <a href="#" class="btn btn-primary">Learn More</a>
                </div>
              </div>
            </div>
            
            <!-- Quick Links -->
            <div class="quick-links" data-aos="fade-up" data-aos-delay="300">
              <h3>Quick Links</h3>
              <div class="row">
                <div class="col-md-4">
                  <ul>
                    <li><a href="<?php echo BASE; ?>faqs#account">Account Help</a></li>
                    <li><a href="<?php echo BASE; ?>faqs#classes">Classes & Learning</a></li>
                    <li><a href="<?php echo BASE; ?>faqs#payment">Payment Questions</a></li>
                  </ul>
                </div>
                <div class="col-md-4">
                  <ul>
                    <li><a href="<?php echo BASE; ?>faqs#files">Files & Materials</a></li>
                    <li><a href="<?php echo BASE; ?>faqs#technical">Technical Support</a></li>
                    <li><a href="#">Platform Updates</a></li>
                  </ul>
                </div>
                <div class="col-md-4">
                  <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Community Guidelines</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Contact Us Section -->
    <section id="contact" class="contact section">
      <div class="container" data-aos="fade-up">
        <div class="section-title text-center mb-5">
          <h2>Contact Us</h2>
          <p>Still Have Questions?</p>
        </div>

        <div class="row gy-4">
          <div class="col-lg-6">
            <div class="contact-info">
              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="100">
                <i class="bi bi-envelope flex-shrink-0"></i>
                <div>
                  <h4>Email:</h4>
                  <p>support@techtutor.cfd</p>
                </div>
              </div>

              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="200">
                <i class="bi bi-clock flex-shrink-0"></i>
                <div>
                  <h4>Support Hours:</h4>
                  <p>Monday - Friday: 9 AM to 6 PM</p>
                </div>
              </div>
              
              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="300">
                <i class="bi bi-question-circle flex-shrink-0"></i>
                <div>
                  <h4>For Technical Issues:</h4>
                  <p>technical@techtutor.cfd</p>
                </div>
              </div>
              
              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="400">
                <i class="bi bi-credit-card flex-shrink-0"></i>
                <div>
                  <h4>For Payment Questions:</h4>
                  <p>payments@techtutor.cfd</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="250">
            <form action="#" method="post" class="contact-form">
              <div class="row">
                <div class="col-md-6 form-group">
                  <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required>
                </div>
                <div class="col-md-6 form-group mt-3 mt-md-0">
                  <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" required>
                </div>
              </div>
              <div class="form-group mt-3">
                <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" required>
              </div>
              <div class="form-group mt-3">
                <textarea class="form-control" name="message" rows="5" placeholder="Message" required></textarea>
              </div>
              <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include '../components/footer.php';?>

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="<?php echo BASE; ?>assets/vendor/jQuery/jquery-3.6.4.min.js"></script>
  <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
  <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          easing: 'ease-in-out',
          once: true,
          mirror: false
        });
      }
      
      // Search functionality
      const searchInput = document.getElementById('help-search');
      const helpCards = document.querySelectorAll('.help-card');
      
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        helpCards.forEach(card => {
          const title = card.querySelector('h3').textContent.toLowerCase();
          const description = card.querySelector('p').textContent.toLowerCase();
          
          if (title.includes(searchTerm) || description.includes(searchTerm)) {
            card.closest('.col-md-6').style.display = 'block';
          } else {
            card.closest('.col-md-6').style.display = 'none';
          }
        });
      });
    });
  </script>
</body>
</html> 