<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Create An Account</title>
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

    <!-- Main CSS File -->
    <link href="<?php echo BASE; ?>assets/css/signup.css" rel="stylesheet">
</head>

<body>
  <div class="signup-container">
    <div class="signup-left">
      <div class="logo">
        <a href="<?php echo BASE; ?>home">
          <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor Logo" width="40">
        </a>
      </div>
      
      <div class="welcome-content">
        <img src="<?php echo BASE; ?>assets/img/tutor-illustration.png" alt="Welcome" class="welcome-image">
        <h1>Welcome!</h1>
        <p>
          You've just taken the first step toward something awesome. We're here to help you build, grow, and explore. Let's get your profile set up and tailor this experience just for you.
        </p>
      </div>
      
      <a href="<?php echo BASE; ?>login" class="log-in-link">
        <i class="bi bi-arrow-left"></i> Log in
      </a>
    </div>
    
    <div class="signup-right">
      <h2>Create an Account</h2>
      
      <?php
        if (isset($_SESSION["msg"])) {
          echo '<div class="alert alert-warning"><p>' . $_SESSION["msg"] . '</p></div>';
        }
        unset($_SESSION["msg"]);
      ?>
      
      <form action="<?php echo BASE; ?>user-register" method="POST" id="signupForm">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="firstname">Firstname</label>
              <input type="text" class="form-control" id="firstname" name="first-name" required>
              <div class="error-message">This field can't be empty</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label for="lastname">Lastname</label>
              <input type="text" class="form-control" id="lastname" name="last-name" required>
              <div class="error-message">This field can't be empty</div>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="role">I am a/an...</label>
          <select class="form-control" id="role" name="role" required>
            <option value="" selected disabled>Select your role</option>
            <option value="TECHGURU">TechGuru</option>
            <option value="TECHKIDS">TechKids</option>
          </select>
          <div class="error-message">Please select a role</div>
        </div>
        
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" class="form-control" id="email" name="email" required>
          <div class="error-message">Please enter a valid email address</div>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
          <div class="error-message">Password must be at least 6 characters</div>
        </div>
        
        <div class="form-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" class="form-control" id="confirm-password" name="confirm-password" required>
          <div class="error-message">Passwords do not match</div>
        </div>
        
        <p class="terms">
          People who use our service may have upload your contact information to TechTutor. <a href="#">Learn more</a>.
        </p>
        
        <p class="terms">
          By clicking Sign Up, you agree to disagree one half one fourth, disappear appear. You may receive email from us for verification purposes, bantay ka.
        </p>
        
        <button type="submit" class="btn-signup" name="register">Sign up</button>
      </form>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const signupForm = document.getElementById('signupForm');
      const firstnameInput = document.getElementById('firstname');
      const lastnameInput = document.getElementById('lastname');
      const roleSelect = document.getElementById('role');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirm-password');
      const fileUploadGroup = document.getElementById('file-upload-group');
      
      // Initially hide the file upload group
      fileUploadGroup.style.display = 'none';
      
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
      
      // Function to validate email format
      function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = re.test(email.value.trim());
        if (!isValid) {
          email.classList.add('is-invalid');
          return false;
        } else {
          email.classList.remove('is-invalid');
          return true;
        }
      }
      
      // Function to validate password
      function validatePassword(password) {
        if (password.value.length < 6) {
          password.classList.add('is-invalid');
          return false;
        } else {
          password.classList.remove('is-invalid');
          return true;
        }
      }
      
      // Function to validate password confirmation
      function validatePasswordConfirmation(password, confirmPassword) {
        if (password.value !== confirmPassword.value) {
          confirmPassword.classList.add('is-invalid');
          return false;
        } else {
          confirmPassword.classList.remove('is-invalid');
          return true;
        }
      }
      
      // Function to toggle file upload based on role
      roleSelect.addEventListener('change', function() {
        if (this.value === 'TECHGURU') {
          fileUploadGroup.style.display = 'block';
        } else {
          fileUploadGroup.style.display = 'none';
        }
      });
      
      // Add event listeners for input validation
      firstnameInput.addEventListener('blur', function() {
        validateField(firstnameInput);
      });
      
      lastnameInput.addEventListener('blur', function() {
        validateField(lastnameInput);
      });
      
      roleSelect.addEventListener('blur', function() {
        validateField(roleSelect);
      });
      
      emailInput.addEventListener('blur', function() {
        validateEmail(emailInput);
      });
      
      passwordInput.addEventListener('blur', function() {
        validatePassword(passwordInput);
      });
      
      confirmPasswordInput.addEventListener('blur', function() {
        validatePasswordConfirmation(passwordInput, confirmPasswordInput);
      });
      
      // Remove validation styling when user starts typing
      const inputs = [firstnameInput, lastnameInput, emailInput, passwordInput, confirmPasswordInput];
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          this.classList.remove('is-invalid');
        });
      });
      
      roleSelect.addEventListener('input', function() {
        this.classList.remove('is-invalid');
      });
      
      // Form submission validation
      signupForm.addEventListener('submit', function(event) {
        let isValid = true;
        
        if (!validateField(firstnameInput)) isValid = false;
        if (!validateField(lastnameInput)) isValid = false;
        if (!validateField(roleSelect)) isValid = false;
        if (!validateEmail(emailInput)) isValid = false;
        if (!validatePassword(passwordInput)) isValid = false;
        if (!validatePasswordConfirmation(passwordInput, confirmPasswordInput)) isValid = false;
        
        if (!isValid) {
          event.preventDefault();
        }
      });
    });
  </script>
</body>
</html>