<?php
$baseUrl = '/ashera/thesis/app/teacher/';

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
             MY CLASSES
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                MY CLASSES
            </span>

            <nav class="sidebar-nav">

                <!-- My Classes -->
                <a
                    href="<?= $baseUrl ?>classes/"
                    class="sidebar-link <?= $currentPage === 'My Classes' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-door-open"></i>
                    </span>

                    <span class="sidebar-link-text">
                        My Classes
                    </span>
                </a>


                <!-- Attendance -->
                <a
                    href="<?= $baseUrl ?>attendance/"
                    class="sidebar-link <?= $currentPage === 'Attendance' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-calendar-check"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Attendance
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
                        Attendance History
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