document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const toggleSidebarBtn = document.getElementById('toggleSidebarBtn');
    const logoContainer = document.querySelector('.logo-container');

    // Toggle sidebar function
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('expanded');
        logoContainer.classList.toggle('active');
    }

    // Event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 991;
        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedToggleButton = toggleSidebarBtn.contains(event.target);
        const clickedLogoContainer = logoContainer.contains(event.target);

        if (isMobile && !clickedInsideSidebar && !clickedToggleButton && !clickedLogoContainer && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            sidebar.classList.remove('active');
            mainContent.classList.remove('expanded');
            logoContainer.classList.remove('active');
        }
    });
});
