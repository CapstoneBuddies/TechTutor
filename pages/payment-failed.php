<?php
require_once '../backends/config.php';
require_once ROOT_PATH . '/backends/main.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE . "login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | Payment Failed</title>

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">

    <style>
        .failed-card {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .failed-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="container py-4">
        <div class="failed-card">
            <i class="bi bi-x-circle-fill failed-icon"></i>
            <h2 class="mb-3">Payment Failed</h2>
            <p class="text-muted mb-4">We couldn't process your payment. This could be due to insufficient funds, incorrect payment details, or a temporary issue. Please try again.</p>
            <div class="d-grid gap-2">
                <a href="<?php echo BASE; ?>payment" class="btn btn-primary">Try Again</a>
                <a href="<?php echo BASE; ?>dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
        </div>
    </main>

    <!-- Vendor JS Files -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
</body>
</html>
