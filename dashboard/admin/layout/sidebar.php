<aside class="dashboard-sidebar" id="dashboardSidebar">

    <!-- Logo -->
    <div class="sidebar-brand">

        <a href="index.php" class="brand-link">

            <div class="brand-logo">
                <i class="bi bi-house-heart-fill"></i>
            </div>

            <div class="brand-text">
                <span class="brand-name">Barangay Daycare</span>
                <span class="brand-subtitle">Management System</span>
            </div>

        </a>

    </div>


    <!-- Navigation -->
    <div class="sidebar-content">


        <!-- =====================================================
             MAIN
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                MAIN
            </span>

            <nav class="sidebar-nav">

                <a
                    href="index.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-grid-1x2"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Dashboard
                    </span>
                </a>

            </nav>

        </div>


        <!-- =====================================================
             DAYCARE
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                DAYCARE
            </span>

            <nav class="sidebar-nav">

                <!-- Teachers -->
                <a
                    href="teachers.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Teachers' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-video3"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Teachers
                    </span>
                </a>

                
                <!-- Parents -->
                <a
                    href="parents.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Parents' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-people"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Parents
                    </span>
                </a>


                <!-- Children -->
                <a
                    href="children.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Children' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-hearts"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Children
                    </span>
                </a>




            </nav>

        </div>


        <!-- =====================================================
             RECORDS
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                RECORDS
            </span>

            <nav class="sidebar-nav">

                <!-- Attendance -->
                <a
                    href="attendance.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Attendance' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-calendar-check"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Attendance
                    </span>
                </a>


                <!-- Activities -->
                <a
                    href="activities.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Activities' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-journal-text"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Activities
                    </span>
                </a>


                <!-- Health Records -->
                <a
                    href="health-records.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Health Records' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Health Records
                    </span>
                </a>

            </nav>

        </div>


        <!-- =====================================================
             MANAGEMENT
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                MANAGEMENT
            </span>

            <nav class="sidebar-nav">

                <!-- Users -->
                <a
                    href="users.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Users' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-gear"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Users
                    </span>
                </a>


                <!-- Reports -->
                <a
                    href="reports.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Reports' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Reports
                    </span>
                </a>

            </nav>

        </div>


        <!-- =====================================================
             SYSTEM
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                SYSTEM
            </span>

            <nav class="sidebar-nav">

                <!-- Settings -->
                <a
                    href="settings.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Settings' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-gear"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Settings
                    </span>
                </a>

            </nav>

        </div>

    </div>


    <!-- Sidebar Bottom -->
    <div class="sidebar-footer">

        <div class="sidebar-user">

            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)) ?>
            </div>

            <div class="sidebar-user-info">

                <span>
                    <?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?>
                </span>

                <small>
                    <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>
                </small>

            </div>

        </div>

    </div>

</aside>


<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>