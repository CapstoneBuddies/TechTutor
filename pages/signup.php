<?php 
    require_once '../backends/main.php';
    $role = '';
    if(isset($_GET['role'])) {
      $role = $_GET['role'];
    }
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
    <link href="<?php echo CSS; ?>signup.css" rel="stylesheet">
</head>

<body>
  <div class="signup-container">
    <div class="signup-left">
      <div class="logo">
        <a href="<?php echo BASE; ?>home">
          <img src="<?php echo IMG; ?>stand_alone_logo.png" alt="TechTutor Logo" width="40">
        </a>
      </div>
      
      <div class="welcome-content">
        <img src="<?php echo IMG; ?>tutor-illustration.png" alt="Welcome" class="welcome-image">
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
              <input type="text" class="form-control" id="firstname" name="first-name" required autocomplete="given-name">
              <div class="error-message">Please enter a valid name (letters, spaces, hyphens only)</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label for="lastname">Lastname</label>
              <input type="text" class="form-control" id="lastname" name="last-name" required autocomplete="family-name">
              <div class="error-message">Please enter a valid name (letters, spaces, hyphens only)</div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="gender">Gender</label>
          <select class="form-control" id="gender" name="gender" required>
            <option value="" selected disabled hidden>Select your gender</option>
            <option value="M">Male</option>
            <option value="F">Female</option>
            <option value="U">Prefer Not to Say</option>
          </select>
          <div class="error-message">Please select your</div>
        </div>
        
        <div class="form-group">
          <label for="role">I am a/an...</label>
          <select class="form-control" id="role" name="role" required>
            <?php if($role == 'tutor'):?>
            <option value="" disabled hidden>Select your role</option>
            <option value="TECHGURU" selected>TechGuru</option>
            <option value="TECHKID">TechKids</option>
            <?php else: ?>
            <option value="" selected disabled hidden>Select your role</option>
            <option value="TECHGURU">TechGuru</option>
            <option value="TECHKID">TechKids</option>
            <?php endif; ?>
          </select>
          <div class="error-message">Please select a role</div>
        </div>
        
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
          <div class="error-message">Please enter a valid email address</div>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
          <div class="error-message">Password must be 8-16 characters and include uppercase, lowercase, number, and special character (*-_!)</div>
        </div>
        
        <div class="form-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" class="form-control" id="confirm-password" name="confirm-password" required autocomplete="new-password">
          <div class="error-message">Passwords do not match</div>
        </div>
        
        <p class="terms">
          By signing up, you acknowledge that TechTutor may store and process your information to provide our services. <a href="<?php echo BASE; ?>terms#data-protection" class="terms-link">Learn more</a>
        </p>
        
        <p class="terms">
          By clicking Sign Up, you agree to TechTutor's <a href="<?php echo BASE; ?>terms#terms-of-use" class="terms-link">Terms and Conditions</a>. You'll receive a verification email to activate your account and start your learning journey with us.
        </p>
        
        <button type="submit" class="btn-signup" name="register" value='1'>Sign up</button>
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
      const genderSelect = document.getElementById('gender');
      const roleSelect = document.getElementById('role');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirm-password');
      
      // Function to validate form fields
      function validateField(field) {
        const value = field.value.trim();
        if (!value) {
          field.classList.add('is-invalid');
          field.nextElementSibling.textContent = 'This field is required';
          return false;
        }
        
        // Additional validation for names
        if (field === firstnameInput || field === lastnameInput) {
          if (!/^[a-zA-Z\s-']{2,}$/.test(value)) {
            field.classList.add('is-invalid');
            field.nextElementSibling.textContent = 'Please enter a valid name (letters, spaces, hyphens only)';
            return false;
          }
        }
        
        field.classList.remove('is-invalid');
        return true;
      }
      
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
        
        // Using the exact same regex as the backend
        const passwordRegex = /^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*\-_!]))[A-Za-z\d*\-_!]{8,16}$/;
        if (!passwordRegex.test(value)) {
          password.classList.add('is-invalid');
          password.nextElementSibling.textContent = 'Password must be 8-16 characters and contain uppercase, lowercase, number, and special character (*-_!)';
          return false;
        }
        
        password.classList.remove('is-invalid');
        return true;
      }
      
      // Function to validate password confirmation
      function validatePasswordConfirmation() {
        const value = confirmPasswordInput.value;
        if (!value) {
          confirmPasswordInput.classList.add('is-invalid');
          confirmPasswordInput.nextElementSibling.textContent = 'Please confirm your password';
          return false;
        }
        
        if (passwordInput.value !== value) {
          confirmPasswordInput.classList.add('is-invalid');
          confirmPasswordInput.nextElementSibling.textContent = 'Passwords do not match';
          return false;
        }
        
        confirmPasswordInput.classList.remove('is-invalid');
        return true;
      }
      
      // Real-time validation
      firstnameInput.addEventListener('input', () => validateField(firstnameInput));
      lastnameInput.addEventListener('input', () => validateField(lastnameInput));
      genderSelect.addEventListener('change', () => validateField(genderSelect));
      roleSelect.addEventListener('change', () => validateField(roleSelect));
      emailInput.addEventListener('input', () => validateEmail(emailInput));
      passwordInput.addEventListener('input', () => {
        validatePassword(passwordInput);
        if (confirmPasswordInput.value) {
          validatePasswordConfirmation();
        }
      });
      confirmPasswordInput.addEventListener('input', validatePasswordConfirmation);
      
      // Form submission handler
      signupForm.addEventListener('submit', function(e) {
        // e.preventDefault();
        
        const isFirstNameValid = validateField(firstnameInput);
        const isLastNameValid = validateField(lastnameInput);
        const isGenderValid = validateField(genderSelect);
        const isRoleValid = validateField(roleSelect);
        const isEmailValid = validateEmail(emailInput);
        const isPasswordValid = validatePassword(passwordInput);
        const isConfirmPasswordValid = validatePasswordConfirmation();
        
        if (isFirstNameValid && isLastNameValid && isGenderValid && isRoleValid && 
            isEmailValid && isPasswordValid && isConfirmPasswordValid) {
          this.submit();
        } else {
          // Focus the first invalid field
          const firstInvalid = signupForm.querySelector('.is-invalid');
          if (firstInvalid) {
            firstInvalid.focus();
          }
        }
      });
      
      // Clear validation on focus
      const inputs = [firstnameInput, lastnameInput, emailInput, passwordInput, confirmPasswordInput];
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.classList.remove('is-invalid');
        });
      });
    });
  </script>
</body>
</html>