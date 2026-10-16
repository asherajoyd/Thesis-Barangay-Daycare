<?php
$baseUrl = '/ashera/thesis/app/parent/';

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

            </nav>

        </div>


        <!-- =====================================================
             MY CHILDREN
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                MY CHILDREN
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


                <!-- Activities -->
                <a
                    href="<?= $baseUrl ?>activities/"
                    class="sidebar-link <?= $currentPage === 'Activities' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-journal-text"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Activities
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

            </nav>

        </div>


        <!-- =====================================================
             UPDATES
        ====================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                UPDATES
            </span>

            <nav class="sidebar-nav">

                <!-- Announcements -->
                <a
                    href="<?= $baseUrl ?>announcement/"
                    class="sidebar-link <?= $currentPage === 'Announcement' ? 'active' : '' ?>"
                >
                    <span class="sidebar-icon">
                        <i class="bi bi-megaphone"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Announcements
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