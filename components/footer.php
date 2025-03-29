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
                    <img src="<?php echo IMG; ?>stand_alone_logo.png" alt="TechTutor" width="30" class="me-2">
                    TechTutor
                </h5>
                <p class="text-muted"><?php echo $_SERVER['HTTP_HOST'];?> Empowering students through personalized online tutoring</p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE; ?>terms#terms-of-use">Terms of Service</a></li>
                    <li><a href="<?php echo BASE; ?>terms#data-protection">Privacy Policy</a></li>
                    <li><a href="<?php echo BASE; ?>#contact">Contact Us</a></li>
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

<!-- Vendor JavaScript Section -->
<script src="<?php echo BASE; ?>assets/vendor/jQuery/jquery-3.6.4.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/aos/aos.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="<?php echo BASE; ?>assets/vendor/clockpicker/dist/bootstrap-clockpicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.1/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js" defer></script>

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
    const loadingIndicator = document.createElement("div");
    loadingIndicator.id = "loadingIndicator";
    loadingIndicator.className = "d-none"; // Initially hidden
    loadingIndicator.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;

    // Append before </body>
    document.body.appendChild(loadingIndicator);
});

function showToast(type, message) {
    const toastContainer = document.createElement('div');
    toastContainer.style.position = 'fixed';
    toastContainer.style.top = '20px';
    toastContainer.style.right = '20px';
    toastContainer.style.zIndex = '9999';

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}-fill me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);

    const bsToast = new bootstrap.Toast(toast, {
        animation: true,
        autohide: true,
        delay: 1000
    });

    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        document.body.removeChild(toastContainer);
    });
}

// Helper function to show/hide loading indicator
function showLoading(show) {
    const loadingIndicator = document.getElementById("loadingIndicator");
    if (loadingIndicator) {
        if (show) {
            loadingIndicator.classList.remove("d-none");
        } else {
            loadingIndicator.classList.add("d-none");
        }
    }
}

// Logging Errors
function logError(error, actions, components) {
    let err_msg = String(error);
    let action = actions ? actions : null;;
    let component = components ? components : null;
    fetch(BASE+'log', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            error: err_msg,
            component: component,
            action: action
        })
    })
    .then(response => response.json())
    .catch(error => {
        console.error('There was a problem with the fetch operation:', error);
    });
}
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
?>
