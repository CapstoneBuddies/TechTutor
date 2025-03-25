<?php
http_response_code(500);
require_once "../../backends/config.php";
log_error("User accessed 500 page", 3); // Ensure log file exists

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
                            <h1>500</h1>
                        </div>
                        
                        <div class="contant_box_404">
                            <h3>Houston, We Have a Problem! 🚀</h3>
                            <p>Our servers decided to take an unexpected coffee break. ☕ Don't worry, we've sent our best hamsters to run the wheels again. Please check back in a bit!</p>
                            <a href="<?php echo BASE; ?>" class="link_404">Return to Earth</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once "../../components/footer.php"; ?>
</body>
</html>