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

    // Mobile sidebar
    sidebarToggle?.addEventListener('click', function () {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });

    sidebarOverlay?.addEventListener('click', function () {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });


    // Desktop sidebar collapse
    sidebarCollapse?.addEventListener('click', function () {

        document.body.classList.toggle('sidebar-collapsed');

    });

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
</script>

</body>
</html>