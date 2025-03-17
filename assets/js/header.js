document.addEventListener("DOMContentLoaded", function () {
    const BASE = document.body.getAttribute("data-base") || "/";

    // Sidebar Toggle
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content");

    if (sidebarToggle && sidebar && mainContent) {
        const overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        document.body.appendChild(overlay);

        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            if (window.innerWidth <= 991) {
                sidebar.classList.toggle("active");
                overlay.classList.toggle("active");
            } else {
                sidebar.classList.toggle("collapsed");
                mainContent.classList.toggle("expanded");
            }
        });

        overlay.addEventListener("click", function () {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });

        // Window resize handling
        window.addEventListener("resize", function () {
            if (window.innerWidth > 991) {
                sidebar.classList.remove("active");
                overlay.classList.remove("active");
            }
        });
    }

    // Notifications Dropdown
    const notificationIcon = document.querySelector(".notification-icon");
    const notificationDropdown = document.querySelector(".notification-dropdown");

    if (notificationIcon && notificationDropdown) {
        notificationIcon.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            notificationDropdown.classList.toggle("show");
        });

        document.addEventListener("click", function (e) {
            if (!e.target.closest(".notification-icon") && !e.target.closest(".notification-dropdown")) {
                notificationDropdown.classList.remove("show");
            }
        });

        notificationDropdown.addEventListener("click", function (e) {
            e.stopPropagation();
        });
    }
});
