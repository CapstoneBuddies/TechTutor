<?php 
    require_once '../backends/config.php';
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | Terms and Conditions</title>

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
    <link href="<?php echo BASE; ?>assets/css/main.css" rel="stylesheet">

    <style>
        .terms-container {
            max-width: 800px;
            margin: 80px auto;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        .terms-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .terms-content h3 {
            color: var(--heading-color);
            margin-top: 30px;
        }
        .terms-content p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="terms-container">
        <div class="terms-header">
            <h1>Terms and Conditions</h1>
            <p>Last updated: <?php echo date('F d, Y'); ?></p>
        </div>

        <div class="terms-content">
            <h3>1. Introduction</h3>
            <p>Welcome to TechTutor! These terms and conditions outline the rules and regulations for using our platform.</p>

            <h3>2. Platform Overview</h3>
            <p>TechTutor is a platform connecting TechKids (students) with TechGurus (tutors) for personalized IT learning experiences.</p>

            <h3>3. User Roles</h3>
            <p><strong>TechKids:</strong> Learners seeking IT knowledge and skills through our platform.<br>
            <strong>TechGurus:</strong> Qualified tutors providing IT education and guidance.</p>

            <h3>4. Privacy & Data</h3>
            <p>We respect your privacy and protect your personal information. Your contact information may be used for platform-related communications and connecting with tutors/students.</p>

            <h3>5. Terms of Use</h3>
            <p>By using our platform, you agree to:
                <ul>
                    <li>Provide accurate information during registration</li>
                    <li>Maintain professional conduct during sessions</li>
                    <li>Respect intellectual property rights</li>
                    <li>Follow our community guidelines</li>
                </ul>
            </p>

            <p class="text-muted mt-5"><em>Note: These terms are currently in development and may be updated. Please check back regularly for the final version.</em></p>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
