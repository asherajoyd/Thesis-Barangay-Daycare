<?php
$baseUrl = '/ashera/thesis/app/admin/';
$classManagementPages = [
    'Academic Year',
    'Sections',
    'Class Schedule',
];
$classManagementIsActive = in_array($pageTitle ?? '', $classManagementPages, true);
?>

<aside class="dashboard-sidebar" id="dashboardSidebar">

    <!-- Logo -->
    <div class="sidebar-brand">

        <a href="<?= $baseUrl ?>" class="brand-link">

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

                <!-- Dashboard -->
                <a
                    href="<?= $baseUrl ?>dashboard/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-grid-1x2"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Dashboard
                    </span>
                </a>


                <!-- Enrollment -->
                <a
                    href="<?= $baseUrl ?>manage-enrollment/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Enrollment' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Enrollment
                    </span>
                </a>


                <!-- Class Management -->
                <div class="sidebar-submenu-wrapper">

                    <button
                        type="button"
                        class="sidebar-link sidebar-submenu-toggle <?= $classManagementIsActive ? 'active' : '' ?>"
                        id="classManagementToggle"
                        aria-expanded="<?= $classManagementIsActive ? 'true' : 'false' ?>"
                        aria-controls="classManagementSubmenu"
                    >
                        <span class="sidebar-icon">
                            <i class="bi bi-building"></i>
                        </span>

                        <span class="sidebar-link-text">
                            Class Management
                        </span>

                        <span
                            class="submenu-arrow <?= $classManagementIsActive ? 'rotate' : '' ?>"
                            id="classManagementArrow"
                        >
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </button>


                    <!-- Submenu -->
                    <div
                        class="sidebar-submenu <?= $classManagementIsActive ? 'open' : '' ?>"
                        id="classManagementSubmenu"
                    >

                        <a
                            href="<?= $baseUrl ?>school-years/"
                            class="sidebar-sublink <?= ($pageTitle ?? '') === 'School Year' ? 'active' : '' ?>"
                        >
                            <span>Academic Year</span>
                        </a>

                        <a
                            href="<?= $baseUrl ?>classes/"
                            class="sidebar-sublink <?= ($pageTitle ?? '') === 'Classes / Sections' ? 'active' : '' ?>"
                        >
                            <span>Sections</span>
                        </a>

                        <a
                            href="<?= $baseUrl ?>class-schedule/"
                            class="sidebar-sublink <?= ($pageTitle ?? '') === 'Class Schedule' ? 'active' : '' ?>"
                        >
                            <span>Class Schedule</span>
                        </a>

                    </div>

                </div>


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
                    href="<?= $baseUrl ?>manage-teachers/"
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
                    href="<?= $baseUrl ?>manage-parents"
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
                    href="<?= $baseUrl ?>manage-children"
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


                <!-- Health Records -->
                <a
                    href="<?= $baseUrl ?>health-records.php"
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
                    href="<?= $baseUrl ?>users.php"
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
                    href="<?= $baseUrl ?>reports.php"
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
                    href="<?= $baseUrl ?>settings.php"
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



