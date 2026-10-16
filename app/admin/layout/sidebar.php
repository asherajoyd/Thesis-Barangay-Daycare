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
                    href="<?= $baseUrl ?>enrollment/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Enrollment' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Enrollment
                    </span>
                </a>


                <!-- Announcement -->
                <a
                    href="<?= $baseUrl ?>announcement/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Announcement' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-megaphone"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Announcement
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
                    href="<?= $baseUrl ?>teachers/"
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
                    href="<?= $baseUrl ?>parents"
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
                    href="<?= $baseUrl ?>children"
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
             Schedule
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                Schedule
            </span>

            <nav class="sidebar-nav">

                <!-- Sessions -->
                <a
                    href="<?= $baseUrl ?>sessions/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Sessions' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-clipboard-minus"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Sessions
                    </span>
                </a>


                <!-- Routines -->
                <a
                    href="<?= $baseUrl ?>routines/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Routines' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-calendar-event"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Routines
                    </span>
                </a>


                <!-- Classes -->
                <a
                    href="<?= $baseUrl ?>classes/"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Classes' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-calendar-range"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Classes
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
                    href="<?= $baseUrl ?>users"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Users' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-gear"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Users
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
                    href="<?= $baseUrl ?>settings"
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



