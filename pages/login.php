<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Welcome Back</title>
    <meta name="description" content="Log in to TechTutor - Your personalized IT learning platform. Access your courses, connect with TechGurus, and continue your tech journey.">
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
        <h2>Welcome Back to TechTutor!</h2>
        <?php
          if (isset($_SESSION["msg"])) {
            echo '<div class="alert alert-warning"><p>' . $_SESSION["msg"] . '</p></div>';
          }
          unset($_SESSION["msg"]);
        ?>
        
        <form action="<?php echo BASE; ?>user-login" method="POST" id="loginForm">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="error-message">Email is required</div>
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="error-message">Password is required</div>
            <a href="<?php echo BASE; ?>forgot" class="forgot-password-link">Forgot Password?</a>
          </div>
          
          <div class="remember-me">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Keep me signed in</label>
          </div>
          
          <button type="submit" class="btn-login" name="signin" value="1">Sign In</button>
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
        <h1>Ready to Learn?</h1>
        <p>
          New to TechTutor?<br/>
          Join our community of TechKids and TechGurus to start your tech learning journey.
        </p>
      </div>
      
      <a href="<?php echo BASE; ?>register" class="sign-up-link">
        Create Account&nbsp;
        <i class="bi bi-arrow-right"></i> 
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
      
      // Function to validate email format
      function validateEmail(email) {
        const value = email.value.trim();
        if (!value) {
          email.classList.add('is-invalid');
          email.nextElementSibling.textContent = 'Email is required';
          return false;
        }
        
        // Using the same email validation as PHP's filter_var
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!re.test(value)) {
          email.classList.add('is-invalid');
          email.nextElementSibling.textContent = 'Invalid email format';
          return false;
        }
        
        email.classList.remove('is-invalid');
        return true;
      }
      
      // Function to validate password
      function validatePassword(password) {
        const value = password.value;
        if (!value) {
          password.classList.add('is-invalid');
          password.nextElementSibling.textContent = 'Password is required';
          return false;
        }
        
        // Basic validation for login - we don't check complexity on login
        password.classList.remove('is-invalid');
        return true;
      }
      
      // Real-time validation
      emailInput.addEventListener('input', () => validateEmail(emailInput));
      passwordInput.addEventListener('input', () => validatePassword(passwordInput));
      
      // Form submission handler
      loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isEmailValid = validateEmail(emailInput);
        const isPasswordValid = validatePassword(passwordInput);
        
        if (isEmailValid && isPasswordValid) {
          this.submit();
        } else {
          // Focus the first invalid field
          const firstInvalid = loginForm.querySelector('.is-invalid');
          if (firstInvalid) {
            firstInvalid.focus();
          }
        }
      });
      
      // Clear validation on focus
      const inputs = [emailInput, passwordInput];
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.classList.remove('is-invalid');
          this.nextElementSibling.textContent = '';
        });
      });
    });
  </script>
</body>
</html>