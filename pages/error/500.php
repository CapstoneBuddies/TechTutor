<?php
http_response_code(500);
require_once "../../backends/config.php";
log_error("User accessed 500 page", 3); // Ensure log file exists

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include ROOT_PATH . '/components/head.php'; ?>
</head>
<body>

<div class="container-fluid mt-4 error-page">
    <div class="text-center">
        <div class="error mx-auto" data-text="500">500</div>
        <p class="lead text-gray-800 mb-3">Internal Server Error</p>
        <p class="text-gray-500 mb-0">Something went wrong on our end. Please try again later.</p>
        <a href="<?php echo BASE_URL; ?>">&larr; Back to Home</a>
    </div>
</div>

<?php require_once "../../components/footer.php"; ?>

</body>
</html>
