<?php
/**
 * Centralized footer component for TechTutor
 * Includes common footer elements and JavaScript dependencies
 */

// Define pages that should NOT display the footer
$noFooterPages = ['login.php', 'signup.php', 'forgot-password.php', 'reset-password.php', 'class-edit.php', 'class-details.php'];
$currentPage = basename($_SERVER['PHP_SELF']);

if (!in_array($currentPage, $noFooterPages)): ?>
<footer class="footer bg-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <!-- Branding & Description -->
            <div class="col-md-6">
                <h5>
                    <img src="<?php echo BASE; ?>assets/img/stand_alone_logo.png" alt="TechTutor" width="30" class="me-2">
                    TechTutor
                </h5>
                <p class="text-muted">Empowering students through personalized online tutoring</p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE; ?>terms#terms-of-use">Terms of Service</a></li>
                    <li><a href="<?php echo BASE; ?>terms#data-protection">Privacy Policy</a></li>
                    <li><a href="<?php echo BASE; ?>contact">Contact Us</a></li>
                </ul>
            </div>

            <!-- Support Links -->
            <div class="col-md-3">
                <h6>Support</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE; ?>faq">FAQ</a></li>
                    <li><a href="<?php echo BASE; ?>help">Help Center</a></li>
                </ul>
            </div>
        </div>

        <hr>

        <!-- Copyright Notice -->
        <div class="row">
            <div class="col-12 text-center">
                <p class="copyright mb-0">&copy; <?php echo date('Y'); ?> TechTutor. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Footer JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const footerLinks = document.querySelectorAll('.footer a');
    
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (!href.startsWith('http') && !href.startsWith('/')) { 
                e.preventDefault();
                console.error('Invalid link clicked: ' + href);
            }
        });
    });
});
</script>

<?php
// Load role-based JavaScript if available
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
