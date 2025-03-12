<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';

    if (isset($_GET['token'])) {
      $success = verifyEmailToken($_GET['token']);
      if($success) {
        $_SESSION['msg'] = "Account has been successfully verified. Please log in";
        header("location: login");
        exit();
      }
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>TechTutor | Verify Your Email</title>
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
  <link href="<?php echo BASE; ?>assets/css/verify.css" rel="stylesheet">
  <style>
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      z-index: 9999;
      display: none;
      justify-content: center;
      align-items: center;
    }
    .loading-spinner {
      border: 4px solid #f3f3f3;
      border-top: 4px solid #FF6B00;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
  <div class="verify-container">
    <div class="verify-left">
      <div class="logo">
        <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor Logo">
      </div>
      
      <div class="verification-title">
        <h1>Verification code on your Email.</h1>
      </div>
      
      <a href="<?php echo BASE; ?>login" class="log-in-link">
        <i class="bi bi-arrow-left"></i> Log in
      </a>
    </div>
    
    <div class="verify-right">
      <div class="verification-content">
        <img src="<?php echo BASE; ?>assets/img/email-icon.svg" alt="Email" class="email-icon">
        <h2>Verify your email</h2>
        <p>
          We will send verification code on your email. If you don't see our email in your inbox, please check your spam or junk folder. Need another email?
        </p>
        
        <form action="<?php echo BASE; ?>verify_code" method="POST" id="verificationForm">
          <div class="code-inputs">
            <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
            <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
            <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
            <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
            <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
            <input type="number" maxlength="1" name="code[]" required inputmode="numeric" pattern="\d{1}" autocomplete="one-time-code" class="code-input">
          </div>
          
          <div class="code-timer">
            Code expires in: <span id="countdown">3:00</span>
          </div>
          
          <button type="submit" class="btn-confirm">Confirm</button>
          <button type="button" class="btn-resend" id="resendBtn" disabled>Send again</button>
        </form>
      </div>
    </div>
  </div>
  <div class='loading-overlay'>
    <div class='loading-spinner'></div>
  </div>

  <!-- Vendor JS Files -->
  <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  
  <?php if(isset($_SESSION['msg'])): ?>
  <script>
    alert('<?php echo $_SESSION['msg']; ?>');
    <?php unset($_SESSION['msg']); ?>
  </script>
  <?php endif; ?>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const codeInputs = document.querySelectorAll('.code-input');
      const form = document.getElementById('verificationForm');
      const resendBtn = document.getElementById('resendBtn');
      const countdownEl = document.getElementById('countdown');
      
      // Set focus on the first input when page loads
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
            form.submit();
          }, 300);
        }
      }

      // Setup resend button click handler
      resendBtn.addEventListener('click', function() {
        window.location.href = '<?php echo BASE; ?>resend-verification-code';
      });
      
      // Check if there's a stored end time
      let endTime = localStorage.getItem('verificationEndTime');
      let timeLeft;
      
      if (endTime) {
        // Calculate remaining time
        const now = Math.floor(Date.now() / 1000);
        endTime = parseInt(endTime);
        timeLeft = Math.max(0, endTime - now);
        
        // If time has already elapsed, enable the resend button immediately
        if (timeLeft <= 0) {
          resendBtn.disabled = false;
          countdownEl.textContent = '0:00';
          return; // Exit early as no timer is needed
        }
      } else {
        // Set new end time (3 minutes from now)
        timeLeft = 3 * 60;
        const endTime = Math.floor(Date.now() / 1000) + timeLeft;
        localStorage.setItem('verificationEndTime', endTime);
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
          localStorage.removeItem('verificationEndTime'); // Clear the stored time
        } else {
          timeLeft--;
        }
      }
      
      // Initial call and start interval
      updateCountdown();
      const countdownTimer = setInterval(updateCountdown, 1000);
    });
  </script>
  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
      document.querySelector('.loading-overlay').style.display = 'flex';
    });
  </script>
</body>
</html>