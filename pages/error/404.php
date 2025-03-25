<?php
require_once "../../backends/config.php";

// Get the requested URI
$requestUri = trim($_SERVER['REQUEST_URI'], '/');

$rolePaths = [
    'ADMIN' => 'dashboard/a/',
    'TECHGURU' => 'dashboard/t/',
    'TECHKID' => 'dashboard/s/',
];

// Check if user is logged in and has a role
if (isset($_SESSION['role']) && isset($rolePaths[$_SESSION['role']]) && !isset($_SESSION['redirect'])) {
    $expectedPath = $rolePaths[$_SESSION['role']];
    
    // If user accessed "dashboard/" without the correct role subdirectory
    if (strpos($requestUri, 'dashboard/') === 0 && strpos($requestUri, $expectedPath) === false) {
        // Extract the page name after "dashboard/"
        $page = substr($requestUri, strlen('dashboard/'));

        // Redirect to correct dashboard path
        $_SESSION['redirect'] = 'redirect';
        header("Location: " . BASE . $expectedPath . $page);
        exit();
    }
}

http_response_code(404);
log_error("User accessed 404 page", 3); // Ensure log file exists
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
<body data-base="<?php echo BASE; ?>">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Arvo'>
    <link rel='stylesheet' href="<?php echo CSS.'error.css'; ?>">
    
    <section class="page_404">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="text-center">
                        <div class="four_zero_four_bg">
                            <h1>404</h1>
                        </div>
                        
                        <div class="contant_box_404">
                            <h3>Looks like you're lost</h3>
                            <p>The page you are looking for is not available!</p>
                            <a href="<?php echo BASE; ?>" class="link_404">Go to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once "../../components/footer.php"; ?>
</body>
</html>
