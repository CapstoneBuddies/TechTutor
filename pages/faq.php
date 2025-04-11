<?php 
    require_once '../backends/main.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor - Frequently Asked Questions</title>
  <meta name="description" content="Find answers to common questions about TechTutor's platform, classes, payments, and more.">
  <meta name="keywords" content="TechTutor FAQ, online tutoring help, tech education questions">

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
  
  <!-- Additional CSS for FAQ Page -->
  <style>
    .faq-section {
      padding: 80px 0;
      background-color: #f6f9ff;
    }
    
    .faq-header {
      margin-bottom: 60px;
    }
    
    .faq-categories {
      margin-bottom: 40px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }
    
    .accordion-item {
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 16px;
      border: 1px solid rgba(0,0,0,0.1);
      background-color: #fff;
    }
    
    .accordion-button {
      font-weight: 500;
      padding: 16px 24px;
      background-color: #fff;
      border: none;
      box-shadow: none;
      position: relative;
      width: 100%;
      text-align: left;
    }
    
    .accordion-button:not(.collapsed) {
      color: #012970;
      background-color: rgba(1, 41, 112, 0.05);
      box-shadow: none;
    }
    
    .accordion-button:focus {
      box-shadow: none;
      border-color: rgba(0,0,0,0.1);
      outline: none;
    }
    
    .accordion-body {
      padding: 20px 24px;
      background-color: #fff;
    }
    
    .accordion-collapse {
      height: 0;
      overflow: hidden;
      transition: height 0.35s ease;
    }
    
    .accordion-collapse.show {
      height: auto;
    }
    
    .accordion-button::after {
      content: "";
      width: 16px;
      height: 16px;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23212529'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-size: 16px;
      transition: transform 0.2s ease-in-out;
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
    }
    
    .accordion-button:not(.collapsed)::after {
      transform: translateY(-50%) rotate(-180deg);
    }
    
    .faq-category-title {
      color: #012970;
      margin-top: 40px;
      margin-bottom: 24px;
      font-weight: 600;
      position: relative;
      padding-bottom: 10px;
    }
    
    .faq-category-title::after {
      content: '';
      position: absolute;
      display: block;
      width: 50px;
      height: 3px;
      background: #4154f1;
      bottom: 0;
      left: 0;
    }
    
    .search-container {
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
      display: flex;
    }
    
    #faq-search {
      padding: 12px 20px;
      border-radius: 50px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 0 50px 50px 0;
      font-size: 16px;
      border-left: none;
    }
    .search-container .input-group {
      display: flex;
      flex-direction: row;
    }
    .search-container .input-group-text {
      border-radius: 50px 0 0 50px;
      background: white;
      width: 50px;
      height: 50px;
    }
    #faq-search:hover {
      border-color: #007bff;
    }
    
    #faq-search {
    }
    
    #faq-search:focus {
      outline: none;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.75);
      border-color: #007bff;
    }
  </style>
</head>

<body class="page-faq">
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
          <li><a href="<?php echo BASE; ?>faqs" class="active">FAQS</a></li>
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
          <h2>Frequently Asked Questions</h2>
          <ol>
            <li><a href="<?php echo BASE; ?>home">Home</a></li>
            <li>FAQ</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Content will be added in next edits -->
    
    <!-- FAQ Section -->
    <section id="faq" class="faq-section">
      <div class="container" data-aos="fade-up">
        <div class="row">
          <div class="col-lg-10 mx-auto">
            
            <!-- Search -->
            <div class="search-container" data-aos="fade-up" data-aos-delay="100">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-search"></i>
                </span>
                <input type="text" id="faq-search" class="form-control" placeholder="Search for answers...">
              </div>
            </div>
            
            <!-- Category Filter -->
            <div class="faq-categories d-flex flex-wrap gap-2 justify-content-center" data-aos="fade-up" data-aos-delay="200">
              <button class="btn btn-primary rounded-pill px-4 active" data-category="all">All Questions</button>
              <button class="btn btn-outline-primary rounded-pill px-4" data-category="account">Account</button>
              <button class="btn btn-outline-primary rounded-pill px-4" data-category="classes">Classes</button>
              <button class="btn btn-outline-primary rounded-pill px-4" data-category="payment">Payment</button>
              <button class="btn btn-outline-primary rounded-pill px-4" data-category="files">Files</button>
              <button class="btn btn-outline-primary rounded-pill px-4" data-category="technical">Technical</button>
            </div>
            
            <!-- No Results Message (Hidden by default) -->
            <div id="no-results" class="text-center py-5 d-none" data-aos="fade-up" data-aos-delay="300">
              <i class="bi bi-search fs-1 text-primary"></i>
              <h4 class="mt-3">No matching FAQs found</h4>
              <p class="text-muted">Please try different keywords or check our contact section below for assistance</p>
            </div>
            
            <!-- FAQ Accordions -->
            <div class="accordion" id="faqAccordion">
              
              <!-- TEST ACCORDION ITEM - For debugging -->
              <div class="accordion-item" style="margin-bottom: 30px; border: 2px solid #4154f1;">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#test-item">
                    TEST ITEM - Click me to test accordion functionality
                  </button>
                </h2>
                <div id="test-item" class="accordion-collapse collapse">
                  <div class="accordion-body">
                    <p>If you can see this content and collapse it again by clicking the header, the accordion is working correctly!</p>
                  </div>
                </div>
              </div>
              
              <!-- Account Questions -->
              <div class="faq-category" data-category="account">
                <h3 class="faq-category-title">Account Questions</h3>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account1">
                      How do I create an account?
                    </button>
                  </h2>
                  <div id="account1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <p>To create an account, click on the "Start Learning" button on the homepage or "Create Account" on the login page. Fill in your information, verify your email address, and you're ready to go! You can choose between TechKid (student) or TechGuru (tutor) roles during registration.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account2">
                      What's the difference between TechKid and TechGuru roles?
                    </button>
                  </h2>
                  <div id="account2" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p><strong>TechKid (Student)</strong>: You can enroll in classes, attend sessions, access learning materials, and track your progress.</p>
                      <p><strong>TechGuru (Tutor)</strong>: You can create and manage classes, schedule sessions, upload teaching materials, and interact with enrolled students.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account3">
                      I forgot my password. How can I reset it?
                    </button>
                  </h2>
                  <div id="account3" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>On the login page, click the "Forgot Password?" link. Enter your registered email address, and we'll send you a password reset link. Click the link in the email and follow the instructions to create a new password.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account4">
                      How do I update my profile information?
                    </button>
                  </h2>
                  <div id="account4" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>After logging in, click on your profile picture or name in the sidebar, then select "Profile." You can update your personal information, change your profile picture, and manage your account settings from there.</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Classes Questions -->
              <div class="faq-category" data-category="classes">
                <h3 class="faq-category-title">Classes & Learning</h3>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#classes1">
                      How do I enroll in a class?
                    </button>
                  </h2>
                  <div id="classes1" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>As a TechKid, navigate to "My Classes" and browse available classes. Once you find a class you're interested in, click on it to view details, then click the "Enroll" button. For paid classes, you'll need to complete the payment process before enrollment is confirmed.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#classes2">
                      How do I create and manage a class as a TechGuru?
                    </button>
                  </h2>
                  <div id="classes2" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>To create a class, navigate to "Classes" in your TechGuru dashboard and click "Create New Class." Fill in the class details, including title, description, schedule, and pricing. After creation, you can manage your class by adding materials, scheduling sessions, and tracking student progress.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#classes3">
                      How do online sessions work?
                    </button>
                  </h2>
                  <div id="classes3" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Our online sessions use BigBlueButton, an integrated video conferencing platform. At the scheduled time, navigate to your class and click "Join Session." You'll enter a virtual classroom where you can use video, audio, screen sharing, and chat features. Sessions can be recorded for later review if the tutor enables this option.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#classes4">
                      Can I get a certificate for completing a class?
                    </button>
                  </h2>
                  <div id="classes4" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Yes! After successfully completing a class, your TechGuru can issue a certificate of completion. You can view and download all your certificates from the "My Certificates" section in your dashboard. Each certificate has a unique verification code that can be used to verify its authenticity.</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Payment Questions -->
              <div class="faq-category" data-category="payment">
                <h3 class="faq-category-title">Payment & Tokens</h3>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment1">
                      How does the token system work?
                    </button>
                  </h2>
                  <div id="payment1" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Tokens are our platform's virtual currency. You purchase tokens with real money and then use them to enroll in paid classes. 1 token is equivalent to 1 PHP. You can view your token balance at the top of your dashboard and add more tokens anytime through the "Payment" page.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment2">
                      What payment methods are accepted?
                    </button>
                  </h2>
                  <div id="payment2" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>We currently accept the following payment methods:</p>
                      <ul>
                        <li>GCash</li>
                        <li>Maya</li>
                        <li>GrabPay</li>
                        <li>Credit/Debit Cards</li>
                      </ul>
                      <p>All payments are processed securely through PayMongo, our payment gateway partner.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment3">
                      Is there a minimum token purchase?
                    </button>
                  </h2>
                  <div id="payment3" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Yes, the minimum token purchase is 25 tokens (₱25). There is a 10% VAT and a small 0.2% service charge applied to all token purchases.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment4">
                      What happens if my payment fails?
                    </button>
                  </h2>
                  <div id="payment4" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>If your payment fails, no tokens will be added to your account, and you'll be redirected to a payment failure page with information about what went wrong. You can try again with the same or a different payment method. If you experience repeated issues, please contact our support team.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment5">
                      Can I get a refund for unused tokens?
                    </button>
                  </h2>
                  <div id="payment5" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Tokens are generally non-refundable. However, if you have a legitimate concern or issue, you can file a dispute from your transaction history page for our support team to review on a case-by-case basis.</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Files Questions -->
              <div class="faq-category" data-category="files">
                <h3 class="faq-category-title">Files & Materials</h3>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#files1">
                      How do I access class materials?
                    </button>
                  </h2>
                  <div id="files1" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Class materials can be accessed from the "Files" section of each class. Navigate to "My Classes," select the class, and then click on "Files" to view all available materials uploaded by your TechGuru.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#files2">
                      How can I upload files as a TechGuru?
                    </button>
                  </h2>
                  <div id="files2" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>In your class management dashboard, navigate to the "Files" section and click "Upload File." You can drag and drop files or browse to select them. Set visibility permissions to control who can access each file (private, class-only, or public).</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#files3">
                      What file types are supported?
                    </button>
                  </h2>
                  <div id="files3" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>We support a wide range of file types, including:</p>
                      <ul>
                        <li>Documents: PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, TXT</li>
                        <li>Images: JPG/JPEG, PNG, GIF</li>
                        <li>Audio: MP3, WAV</li>
                        <li>Video: MP4, WebM</li>
                        <li>Programming: ZIP, RAR, and various code files</li>
                      </ul>
                      <p>Individual file size is limited to 100MB.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#files4">
                      Can I organize my files into folders?
                    </button>
                  </h2>
                  <div id="files4" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Yes, you can create folders to organize your files. In the Files section, click "Create Folder," give it a name, and set permissions. You can then upload files directly to specific folders or move existing files between folders.</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Technical Questions -->
              <div class="faq-category" data-category="technical">
                <h3 class="faq-category-title">Technical Support</h3>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technical1">
                      What are the system requirements?
                    </button>
                  </h2>
                  <div id="technical1" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p><strong>For best experience, we recommend:</strong></p>
                      <ul>
                        <li>Modern web browser (Chrome, Firefox, Edge, or Safari - latest version)</li>
                        <li>Stable internet connection (minimum 1 Mbps, recommended 5+ Mbps)</li>
                        <li>Webcam and microphone for participating in live sessions</li>
                        <li>JavaScript enabled in your browser</li>
                      </ul>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technical2">
                      I'm having audio/video issues in a session. What should I do?
                    </button>
                  </h2>
                  <div id="technical2" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>If you're experiencing audio/video issues:</p>
                      <ol>
                        <li>Check that your microphone and camera are properly connected</li>
                        <li>Ensure you've granted browser permissions for microphone and camera</li>
                        <li>Try refreshing the page</li>
                        <li>Test with another browser or device</li>
                        <li>Check your internet connection speed</li>
                        <li>Close other applications that might be using your webcam or microphone</li>
                      </ol>
                      <p>If issues persist, contact technical support.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technical3">
                      Is my data secure on TechTutor?
                    </button>
                  </h2>
                  <div id="technical3" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>Yes, we take data security seriously. We implement the following measures:</p>
                      <ul>
                        <li>Secure HTTPS connections for all site traffic</li>
                        <li>Password hashing and secure storage</li>
                        <li>Regular security updates and monitoring</li>
                        <li>Secure payment processing through trusted providers</li>
                        <li>Strict data access controls</li>
                      </ul>
                      <p>For more details, please review our <a href="<?php echo BASE; ?>terms#data-protection">Privacy Policy</a>.</p>
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technical4">
                      How can I contact support?
                    </button>
                  </h2>
                  <div id="technical4" class="accordion-collapse collapse">
                    <div class="accordion-body">
                      <p>You can contact our support team through multiple channels:</p>
                      <ul>
                        <li>Email: <a href="mailto:support@techtutor.cfd">support@techtutor.cfd</a></li>
                        <li>Technical support: <a href="mailto:technical@techtutor.cfd">technical@techtutor.cfd</a></li>
                        <li>Payment issues: <a href="mailto:payments@techtutor.cfd">payments@techtutor.cfd</a></li>
                        <li>Use the Contact form in the section below</li>
                      </ul>
                      <p>Our support team is available Monday to Friday, 9 AM to 6 PM.</p>
                    </div>
                  </div>
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
      console.log("DOM fully loaded");
      
      // Manually implement the accordion functionality
      var accordionButtons = document.querySelectorAll('.accordion-button');
      console.log("Found accordion buttons:", accordionButtons.length);
      
      accordionButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          console.log("Button clicked");
          
          var targetId = this.getAttribute('data-bs-target');
          var targetElement = document.querySelector(targetId);
          console.log("Target:", targetId, targetElement);
          
          if(targetElement) {
            // Toggle the button's collapsed state
            this.classList.toggle('collapsed');
            
            // Toggle the collapse element's show state
            targetElement.classList.toggle('show');
            
            console.log("Toggled states");
          }
        });
      });
      
      // Initialize AOS
      if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          easing: 'ease-in-out',
          once: true,
          mirror: false
        });
      }
      
      // Category filtering
      const categoryButtons = document.querySelectorAll('.faq-categories button');
      const faqCategories = document.querySelectorAll('.faq-category');
      
      categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
          categoryButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');
          
          const selectedCategory = this.getAttribute('data-category');
          
          if (selectedCategory === 'all') {
            faqCategories.forEach(category => category.style.display = 'block');
          } else {
            faqCategories.forEach(category => {
              category.style.display = category.getAttribute('data-category') === selectedCategory ? 'block' : 'none';
            });
          }
          
          searchFaqs('');
          document.getElementById('faq-search').value = '';
        });
      });
      
      // Search functionality
      const searchInput = document.getElementById('faq-search');
      const noResults = document.getElementById('no-results');
      
      searchInput.addEventListener('input', function() {
        searchFaqs(this.value.toLowerCase().trim());
      });
      
      function searchFaqs(searchTerm) {
        let resultsFound = false;
        
        if (searchTerm === '') {
          const activeCategory = document.querySelector('.faq-categories button.active').getAttribute('data-category');
          
          faqCategories.forEach(category => {
            const shouldShowCategory = activeCategory === 'all' || category.getAttribute('data-category') === activeCategory;
            category.style.display = shouldShowCategory ? 'block' : 'none';
            
            if (shouldShowCategory) {
              category.querySelectorAll('.accordion-item').forEach(item => {
                item.style.display = 'block';
                resultsFound = true;
              });
            }
          });
        } else {
          faqCategories.forEach(category => {
            let categoryHasResults = false;
            
            category.querySelectorAll('.accordion-item').forEach(item => {
              const question = item.querySelector('.accordion-button').textContent.toLowerCase();
              const answer = item.querySelector('.accordion-body').textContent.toLowerCase();
              
              if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                item.style.display = 'block';
                categoryHasResults = true;
                resultsFound = true;
              } else {
                item.style.display = 'none';
              }
            });
            
            category.style.display = categoryHasResults ? 'block' : 'none';
          });
        }
        
        noResults.classList.toggle('d-none', resultsFound || searchTerm === '');
      }
    });
  </script>
</body>
</html> 