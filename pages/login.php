<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor | Login</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
  <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?php echo BASE; ?>assets/css/login.css" rel="stylesheet">

</head>

<body>
  <div class="login-container">
    <div class="login-left">
      <div class="login-form">
        <h2>Log into TechTutor</h2>
        
        <?php
          if (isset($_SESSION["msg"])) {
            echo '<div class="alert alert-warning"><p>' . $_SESSION["msg"] . '</p></div>';
          }
          unset($_SESSION["msg"]);
        ?>
        
        <form action="<?php echo BASE; ?>user-login" method="POST" id="loginForm">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="error-message">This field can't be empty</div>
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="error-message">This field can't be empty</div>
            <a href="<?php echo BASE; ?>forgot_password" class="forgot-password-link">Forgot Password?</a>
          </div>
          
          <div class="remember-me">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
          </div>
          
          <button type="submit" class="btn-login" name="signin" value="1">Log in</button>
        </form>
      </div>
    </div>
    
    <div class="login-right">
      <div class="logo">
        <a href="<?php echo BASE; ?>home">
          <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor Logo" width="40">
        </a>
      </div>
      
      <div class="welcome-content">
        <img src="<?php echo BASE; ?>assets/img/tutor-illustration.png" alt="Welcome" class="welcome-image">
        <h1>Welcome Back!</h1>
        <p>
          Already have an account?<br/>
          Great! Let's get you back to learning and growing with TechTutor.
        </p>
      </div>
      
      <a href="<?php echo BASE; ?>register" class="sign-up-link">
        <i class="bi bi-arrow-left"></i> Sign up
      </a>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const loginForm = document.getElementById('loginForm');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');
      
      // Function to validate form fields
      function validateField(field) {
        if (!field.value.trim()) {
          field.classList.add('is-invalid');
          return false;
        } else {
          field.classList.remove('is-invalid');
          return true;
        }
      }
      
      // Add event listeners for input validation
      emailInput.addEventListener('blur', function() {
        validateField(emailInput);
      });
      
      passwordInput.addEventListener('blur', function() {
        validateField(passwordInput);
      });
      
      // Remove validation styling when user starts typing
      emailInput.addEventListener('input', function() {
        emailInput.classList.remove('is-invalid');
      });
      
      passwordInput.addEventListener('input', function() {
        passwordInput.classList.remove('is-invalid');
      });
      
      // Form submission validation
      loginForm.addEventListener('submit', function(event) {
        let isValid = true;
        
        if (!validateField(emailInput)) isValid = false;
        if (!validateField(passwordInput)) isValid = false;
        
        if (!isValid) {
          event.preventDefault();
        }
      });
    });
  </script>
</body>
</html>