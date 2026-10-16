<?php
$baseUrl = '/ashera/thesis/app/admin/';

$currentPage = $pageTitle ?? '';
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
                    class="sidebar-link <?= $currentPage === 'Dashboard' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-grid-1x2"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Dashboard
                    </span>
                </a>


                <!-- Announcement -->
                <a
                    href="<?= $baseUrl ?>announcement/"
                    class="sidebar-link <?= $currentPage === 'Announcement' ? 'active' : '' ?>"
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
             ENROLLMENT
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                ENROLLMENT
            </span>

            <nav class="sidebar-nav">

                <!-- Enrollments -->
                <a
                    href="<?= $baseUrl ?>enrollment/"
                    class="sidebar-link <?= $currentPage === 'Enrollment' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Enrollments
                    </span>
                </a>


                <!-- Assign Classes -->
                <a
                    href="<?= $baseUrl ?>enrollment/assign-class.php"
                    class="sidebar-link <?= $currentPage === 'Assign Classes' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Assign Classes
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

                <!-- Children -->
                <a
                    href="<?= $baseUrl ?>children/"
                    class="sidebar-link <?= $currentPage === 'Children' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-hearts"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Children
                    </span>
                </a>


                <!-- Parents -->
                <a
                    href="<?= $baseUrl ?>parents/"
                    class="sidebar-link <?= $currentPage === 'Parents' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-people"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Parents
                    </span>
                </a>


                <!-- Teachers -->
                <a
                    href="<?= $baseUrl ?>teachers/"
                    class="sidebar-link <?= $currentPage === 'Teachers' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-person-video3"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Teachers
                    </span>
                </a>

            </nav>

        </div>


        <!-- =====================================================
             SCHEDULE
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                SCHEDULE
            </span>

            <nav class="sidebar-nav">

                <!-- Sessions -->
                <a
                    href="<?= $baseUrl ?>sessions/"
                    class="sidebar-link <?= $currentPage === 'Sessions' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-clock-history"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Sessions
                    </span>
                </a>


                <!-- Routines -->
                <a
                    href="<?= $baseUrl ?>routines/"
                    class="sidebar-link <?= $currentPage === 'Routines' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-list-check"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Routines
                    </span>
                </a>


                <!-- Classes -->
                <a
                    href="<?= $baseUrl ?>classes/"
                    class="sidebar-link <?= $currentPage === 'Classes' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-door-open"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Classes
                    </span>
                </a>

            </nav>

        </div>


        <!-- =====================================================
             ATTENDANCE
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                ATTENDANCE
            </span>

            <nav class="sidebar-nav">

                <!-- Attendance -->
                <a
                    href="<?= $baseUrl ?>attendance/"
                    class="sidebar-link <?= $currentPage === 'Attendance' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-file-earmark-check"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Record Attendance
                    </span>
                </a>


                <!-- Attendance History -->
                <a
                    href="<?= $baseUrl ?>attendance/history.php"
                    class="sidebar-link <?= $currentPage === 'Attendance History' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-clock-history"></i>
                    </span>

                    <span class="sidebar-link-text">
                        History
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
                    href="<?= $baseUrl ?>users/"
                    class="sidebar-link <?= $currentPage === 'Users' ? 'active' : '' ?>"
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
                    href="<?= $baseUrl ?>settings/"
                    class="sidebar-link <?= $currentPage === 'Settings' ? 'active' : '' ?>"
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