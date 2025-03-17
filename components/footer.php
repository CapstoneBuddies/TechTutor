<?php
/**
 * Centralized footer component for TechTutor
 * Includes common footer elements and JavaScript dependencies
 */
?>
<!-- Load footer styles -->
<link href="<?php echo CSS;?>/footer.css" rel="stylesheet">

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>
                    <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor" width="30" class="me-2">
                    TechTutor
                </h5>
                <p class="text-muted">Empowering students through personalized online tutoring</p>
            </div>
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE; ?>terms">Terms of Service</a></li>
                    <li><a href="<?php echo BASE; ?>privacy">Privacy Policy</a></li>
                    <li><a href="<?php echo BASE; ?>contact">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Support</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE; ?>faq">FAQ</a></li>
                    <li><a href="<?php echo BASE; ?>help">Help Center</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12 text-center">
                <p class="copyright mb-0">&copy; <?php echo date('Y'); ?> TechTutor. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Common JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Common footer functionality
    const footerLinks = document.querySelectorAll('.footer a');
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.getAttribute('href').startsWith('http')) {
                const href = this.getAttribute('href');
                if (!href.includes('.php')) {
                    e.preventDefault();
                    log_error('Invalid link clicked: ' + href, 4);
                }
            }
        });
    });
});
</script>

<?php
// Role-specific JavaScript
if (isset($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);
    $js_file = ROOT_PATH . "/assets/js/{$role}.js";
    if (file_exists($js_file)) {
        echo "<script src='" . BASE . "assets/js/{$role}.js' defer></script>";
    }
}

// Log page visit for analytics
if (function_exists('log_error')) {
    log_error("Page visited: " . $_SERVER['REQUEST_URI'], 4);
}
?>