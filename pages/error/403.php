<?php
http_response_code(403);
require_once "../../backends/config.php";
log_error("User accessed 403 page", 3); // Ensure log file exists

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
                            <h1>403</h1>
                        </div>
                        
                        <div class="contant_box_404">
                            <h3>Whoa there, VIP area ahead! 🚫</h3>
                            <p>Looks like you're trying to sneak into the cool kids' club! Unfortunately, your all-access pass seems to be missing. Maybe try logging in first? 😉</p>
                            <a href="<?php echo BASE; ?>" class="link_404">Escape to Safety</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once "../../components/footer.php"; ?>
</body>
</html>
