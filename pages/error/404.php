<?php
http_response_code(404);
require_once "../../backends/config.php";
log_error("User accessed 404 page", 3); // Ensure log file exists

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include ROOT_PATH . '/components/head.php'; ?>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="text-center">
        <div class="error mx-auto" data-text="404">404</div>
        <p class="lead text-gray-800 mb-3">Page Not Found</p>
        <p class="text-gray-500 mb-0">It seems you've found a glitch in the matrix...</p>
        <a href="<?php echo BASE; ?>dashboard">&larr; Back to Dashboard</a>
    </div>
</div>

<?php require_once "../../components/footer.php"; ?>

</body>
</html>
