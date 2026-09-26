        </div>
        <!-- End dashboard-content -->

    </div>
    <!-- End dashboard-main -->

</div>
<!-- End dashboard-wrapper -->
<script>

    const sidebar = document.getElementById('dashboardSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebarOverlay = document.getElementById('sidebarOverlay');


    // =========================================================
    // MOBILE SIDEBAR
    // =========================================================

    sidebarToggle?.addEventListener('click', function () {

        sidebar.classList.toggle('show');

        sidebarOverlay.classList.toggle('show');

    });


    sidebarOverlay?.addEventListener('click', function () {

        sidebar.classList.remove('show');

        sidebarOverlay.classList.remove('show');

    });


    // =========================================================
    // DESKTOP SIDEBAR COLLAPSE
    // =========================================================

    sidebarCollapse?.addEventListener('click', function () {

        document.body.classList.toggle('sidebar-collapsed');

    });


    // =========================================================
    // CLASS MANAGEMENT SUBMENU
    // =========================================================

    const classManagementToggle =
        document.getElementById('classManagementToggle');

    const classManagementSubmenu =
        document.getElementById('classManagementSubmenu');

    const classManagementArrow =
        document.getElementById('classManagementArrow');


    if (
        classManagementToggle &&
        classManagementSubmenu &&
        classManagementArrow
    ) {
        classManagementToggle.addEventListener('click', function () {

            const isOpen =
                classManagementSubmenu.classList.toggle('open');

            classManagementArrow.classList.toggle('rotate', isOpen);

            classManagementToggle.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

        });
    }


    // =========================================================
    // BOOTSTRAP TOOLTIPS
    // =========================================================

    const tooltipTriggerList =
        document.querySelectorAll('[data-bs-toggle="tooltip"]');

    const tooltipList =
        [...tooltipTriggerList].map(
            tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl)
        );

</script>

</body>
</html>
