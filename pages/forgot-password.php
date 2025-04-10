<?php 
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">

<!-- Test -->
<?php include ROOT_PATH . '/components/head.php'; ?>

<body>
  <div class="reset-container">
    <div class="reset-left">
      <div class="logo">
        <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor Logo">
      </div>
      
      <div class="reset-title">
        <h1>Reset your password</h1>
      </div>
      
      <a href="<?php echo BASE; ?>login" class="log-in-link">
        <i class="bi bi-arrow-left"></i> Back to login
      </a>
    </div>
    
    <div class="reset-right">
      <div class="reset-content">
        <img src="<?php echo BASE; ?>assets/img/reset-password-icon.svg" alt="Reset Password" class="reset-icon">
        
        <div id="step1" class="reset-step active">
          <h2>Forgot your password?</h2>
          <p>
            Enter your email address and we'll send you a verification code to reset your password.
          </p>
          
          <form action="<?php echo BASE; ?>forgot-password" method="POST" id="resetForm">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" class="form-control" id="email" name="email" required>
              <div class="error-message">Please enter a valid email address</div>
            </div>
            
            <button type="submit" class="btn-reset" name="send_reset_code" value="1">Send Reset Code</button>
          </form>
        </div>
        
        <div id="step2" class="reset-step">
          <h2>Enter verification code</h2>
          <p>
            We've sent a verification code to your email. Please enter it below.
          </p>
          
          <form action="<?php echo BASE; ?>forgot-password" method="POST" id="verificationForm">
            <input type="hidden" name="email" id="hiddenEmail">
            
            <div class="code-inputs">
              <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
              <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
              <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
              <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
              <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
              <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
            </div>
            
            <div class="code-timer">
              Code expires in: <span id="countdown">5:00</span>
            </div>
            
            <button type="submit" class="btn-reset" name="verify_reset_code" value="1">Verify Code</button>
            <button type="button" class="btn-resend" id="resendBtn" disabled>Send again</button>
          </form>
        </div>
        
        <div id="step3" class="reset-step">
          <h2>Create new password</h2>
          <p>
            Please enter your new password. Make sure it's secure.
          </p>
          
          <form action="<?php echo BASE; ?>forgot-password" method="POST" id="newPasswordForm">
            <input type="hidden" name="email" id="hiddenEmail2">
            <input type="hidden" name="token" id="hiddenToken">
            
            <div class="form-group">
              <label for="new_password">New Password</label>
              <input type="password" class="form-control" id="new_password" name="new_password" required>
              <div class="error-message">Password must be 8-16 characters with letters, numbers, and special characters</div>
              <div class="password-requirements">
                <p>Password must contain:</p>
                <ul>
                  <li id="length">8-16 characters</li>
                  <li id="uppercase">At least one uppercase letter</li>
                  <li id="lowercase">At least one lowercase letter</li>
                  <li id="number">At least one number</li>
                  <li id="special">At least one special character (*-_!)</li>
                </ul>
              </div>
            </div>
            
            <div class="form-group">
              <label for="confirm_password">Confirm Password</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              <div class="error-message">Passwords do not match</div>
            </div>
            
            <button type="submit" class="btn-reset" name="reset_password" value="1">Reset Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Get URL parameters to determine which step to show
      const urlParams = new URLSearchParams(window.location.search);
      const step = urlParams.get('step');
      const email = urlParams.get('email');
      const token = urlParams.get('token');
      
      // Elements
      const step1 = document.getElementById('step1');
      const step2 = document.getElementById('step2');
      const step3 = document.getElementById('step3');
      const hiddenEmail = document.getElementById('hiddenEmail');
      const hiddenEmail2 = document.getElementById('hiddenEmail2');
      const hiddenToken = document.getElementById('hiddenToken');
      
      // Show appropriate step based on URL parameter
      if (step === '2' && email) {
        step1.classList.remove('active');
        step2.classList.add('active');
        step3.classList.remove('active');
        
        // Set hidden email field
        if (hiddenEmail) hiddenEmail.value = email;
        
        // Setup verification code inputs
        setupVerificationInputs();
        
        // Start countdown timer
        startCountdown();
      } else if (step === '3' && email && token) {
        step1.classList.remove('active');
        step2.classList.remove('active');
        step3.classList.add('active');
        
        // Set hidden fields
        if (hiddenEmail2) hiddenEmail2.value = email;
        if (hiddenToken) hiddenToken.value = token;
        
        // Setup password validation
        setupPasswordValidation();
      } else {
        // Default to step 1
        step1.classList.add('active');
        step2.classList.remove('active');
        step3.classList.remove('active');
        
        // Setup email form validation
        setupEmailValidation();
      }
      
      // Functions for different steps
      function setupEmailValidation() {
        const resetForm = document.getElementById('resetForm');
        const emailInput = document.getElementById('email');
        
        if (!resetForm || !emailInput) return;
        
        emailInput.addEventListener('input', function() {
          emailInput.classList.remove('is-invalid');
        });
        
        resetForm.addEventListener('submit', function(event) {
          if (!validateEmail(emailInput.value)) {
            event.preventDefault();
            emailInput.classList.add('is-invalid');
          }
        });
      }
      
      function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
      }
      
      function setupVerificationInputs() {
        const codeInputs = document.querySelectorAll('.code-input');
        const verificationForm = document.getElementById('verificationForm');
        const resendBtn = document.getElementById('resendBtn');
        
        if (!codeInputs.length || !verificationForm || !resendBtn) return;
        
        // Set focus on the first input
        codeInputs[0].focus();
        
        // Handle input for each code field
        codeInputs.forEach((input, index) => {
          // Only allow numbers
          input.addEventListener('keypress', function(e) {
            if (e.key < '0' || e.key > '9') {
              e.preventDefault();
            }
          });
          
          // Handle input
          input.addEventListener('input', function(e) {
            // Get the input value
            let value = e.target.value;
            
            // If the input has a value
            if (value.length >= 1) {
              // Keep only the first character if more than one is entered
              if (value.length > 1) {
                e.target.value = value.charAt(0);
              }
              
              // Move focus to the next input if available
              if (index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
              }
              
              // Check if all inputs are filled
              checkAllInputs();
            }
          });
          
          // Handle backspace key
          input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
              // Move focus to the previous input if current is empty
              codeInputs[index - 1].focus();
            }
          });
          
          // Handle paste event
          input.addEventListener('paste', function(e) {
            e.preventDefault();
            
            // Get pasted data
            const pastedData = (e.clipboardData || window.clipboardData).getData('text');
            
            // If we have data and it's numeric
            if (pastedData && /^\d+$/.test(pastedData)) {
              // Distribute the pasted numbers across inputs
              for (let i = 0; i < Math.min(pastedData.length, codeInputs.length - index); i++) {
                codeInputs[index + i].value = pastedData.charAt(i);
              }
              
              // Focus the appropriate input after paste
              const nextIndex = Math.min(index + pastedData.length, codeInputs.length - 1);
              codeInputs[nextIndex].focus();
              
              // Check if all inputs are filled
              checkAllInputs();
            }
          });
        });
        
        // Function to check if all inputs are filled
        function checkAllInputs() {
          const allFilled = Array.from(codeInputs).every(input => input.value.length === 1);
          
          if (allFilled) {
            // Submit the form automatically
            setTimeout(() => {
              verificationForm.submit();
            }, 300);
          }
        }
      }
      
      function startCountdown() {
        const countdownEl = document.getElementById('countdown');
        const resendBtn = document.getElementById('resendBtn');
        
        if (!countdownEl || !resendBtn) return;
        
        // Check if there's a stored end time
        let endTime = localStorage.getItem('resetCodeEndTime');
        let timeLeft;
        
        if (endTime) {
          // Calculate remaining time
          const now = Math.floor(Date.now() / 1000);
          endTime = parseInt(endTime);
          timeLeft = Math.max(0, endTime - now);
        } else {
          // Set new end time (5 minutes from now)
          timeLeft = 5 * 60;
          const endTime = Math.floor(Date.now() / 1000) + timeLeft;
          localStorage.setItem('resetCodeEndTime', endTime);
        }
        
        function updateCountdown() {
          const minutes = Math.floor(timeLeft / 60);
          let seconds = timeLeft % 60;
          seconds = seconds < 10 ? '0' + seconds : seconds;
          
          countdownEl.textContent = `${minutes}:${seconds}`;
          
          if (timeLeft <= 0) {
            clearInterval(countdownTimer);
            countdownEl.textContent = '0:00';
            resendBtn.disabled = false;
          } else {
            timeLeft--;
            resendBtn.disabled = true;
          }
        }
        
        // Initial call and start interval
        updateCountdown();
        const countdownTimer = setInterval(updateCountdown, 1000);
        
        // Resend button
        resendBtn.addEventListener('click', function() {
          if (!resendBtn.disabled) {
            // Clear the stored end time
            localStorage.removeItem('resetCodeEndTime');
            
            // Redirect to step 1 to resend code
            window.location.href = '<?php echo BASE; ?>forgot-password';
          }
        });
      }
      
      function setupPasswordValidation() {
        const newPasswordForm = document.getElementById('newPasswordForm');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (!newPasswordForm || !newPasswordInput || !confirmPasswordInput) return;
        
        // Password requirements elements
        const lengthReq = document.getElementById('length');
        const uppercaseReq = document.getElementById('uppercase');
        const lowercaseReq = document.getElementById('lowercase');
        const numberReq = document.getElementById('number');
        const specialReq = document.getElementById('special');
        
        // Check password requirements as user types
        newPasswordInput.addEventListener('input', function() {
          const password = newPasswordInput.value;
          
          // Check length
          if (password.length >= 8 && password.length <= 16) {
            lengthReq.classList.add('valid');
          } else {
            lengthReq.classList.remove('valid');
          }
          
          // Check uppercase
          if (/[A-Z]/.test(password)) {
            uppercaseReq.classList.add('valid');
          } else {
            uppercaseReq.classList.remove('valid');
          }
          
          // Check lowercase
          if (/[a-z]/.test(password)) {
            lowercaseReq.classList.add('valid');
          } else {
            lowercaseReq.classList.remove('valid');
          }
          
          // Check number
          if (/\d/.test(password)) {
            numberReq.classList.add('valid');
          } else {
            numberReq.classList.remove('valid');
          }
          
          // Check special character
          if (/[*\-_!]/.test(password)) {
            specialReq.classList.add('valid');
          } else {
            specialReq.classList.remove('valid');
          }
          
          // Check if passwords match
          if (password && confirmPasswordInput.value && password !== confirmPasswordInput.value) {
            confirmPasswordInput.classList.add('is-invalid');
          } else {
            confirmPasswordInput.classList.remove('is-invalid');
          }
        });
        
        // Check if passwords match as user types in confirm field
        confirmPasswordInput.addEventListener('input', function() {
          if (newPasswordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.classList.add('is-invalid');
          } else {
            confirmPasswordInput.classList.remove('is-invalid');
          }
        });
        
        // Form submission validation
        newPasswordForm.addEventListener('submit', function(event) {
          const password = newPasswordInput.value;
          const confirmPassword = confirmPasswordInput.value;
          let isValid = true;
          
          // Check password complexity
          if (!password.match(/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*\-_!]))[A-Za-z\d*\-_!]{8,16}$/)) {
            newPasswordInput.classList.add('is-invalid');
            isValid = false;
          } else {
            newPasswordInput.classList.remove('is-invalid');
          }
          
          // Check if passwords match
          if (password !== confirmPassword) {
            confirmPasswordInput.classList.add('is-invalid');
            isValid = false;
          } else {
            confirmPasswordInput.classList.remove('is-invalid');
          }
          
          if (!isValid) {
            event.preventDefault();
          }
        });
      }
    });
  </script>
</body>
</html>
