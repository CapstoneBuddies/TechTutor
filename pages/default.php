<?php 
    require_once '../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TechTutor | </title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="<?php echo IMG; ?>stand_alone_logo.png" rel="icon">
    <link href="<?php echo IMG; ?>apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS Files -->
    <link href="<?php echo CSS; ?>dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>
    <!-- Main Content -->

    <!-- END Main Content -->


</main>
</div>
<!-- End header -->

    <!-- REPLACE THIS FOR HIDDEN MODALS AND WHATNOT -->

    <!-- JavaScript Section -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.dashboard-content');

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('active');
            });
        });
    </script>
</body>
</html>