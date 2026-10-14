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
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>">
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
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Enrollment' ? 'active' : '' ?>">
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
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Announcement' ? 'active' : '' ?>">
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

                <!-- Parents -->
                <a href="/ashera/thesis/app/teacher/parents/" class="sidebar-link <?= ($pageTitle ?? '') === 'Parents List' ? 'active' : '' ?>">
                    <span class="sidebar-icon">
                        <i class="bi bi-people"></i>
                    </span>
                    <span class="sidebar-link-text">Parents</span>
                </a>

                <!-- Children -->
                <a href="/ashera/thesis/app/teacher/children/" class="sidebar-link <?= ($pageTitle ?? '') === 'Children' ? 'active' : '' ?>">
                    <span class="sidebar-icon">
                        <i class="bi bi-person-hearts"></i>
                    </span>
                    <span class="sidebar-link-text">Children</span>
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

            <!-- Schedule -->

            <a href="/ashera/thesis/app/teacher/schedules/" class="sidebar-link <?= ($pageTitle ?? '') === 'Schedules' ? 'active' : '' ?>">
                <span class="sidebar-icon">
                    <i class="bi bi-calendar-range"></i>
                </span>
                <span class="sidebar-link-text">Schedules</span>
            </a>

            <nav class="sidebar-nav">

                <!-- Health Records -->
                <a href="/ashera/thesis/app/teacher/health-records/" class="sidebar-link <?= ($pageTitle ?? '') === 'Health Records' ? 'active' : '' ?>">
                    <span class="sidebar-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </span>
                    <span class="sidebar-link-text">Health Records</span>
                </a>


                <!-- ACTIVITIES -->
                <a
                    href="<?= $baseUrl ?>activities.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Activities' ? 'active' : '' ?>">
                    <span class="sidebar-icon">
                        <i class="bi bi-journal-text"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Activities
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
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Settings' ? 'active' : '' ?>">
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