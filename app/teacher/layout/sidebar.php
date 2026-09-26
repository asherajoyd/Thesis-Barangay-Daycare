<?php

$baseUrl = '/ashera/thesis/app/teacher/';

?>

<aside class="dashboard-sidebar" id="dashboardSidebar">

    <!-- =====================================================
         LOGO
    ====================================================== -->

    <div class="sidebar-brand">

        <a
            href="<?= $baseUrl ?>dashboard/"
            class="brand-link">

            <div class="brand-logo">
                <i class="bi bi-house-heart-fill"></i>
            </div>

            <div class="brand-text">

                <span class="brand-name">
                    Barangay Daycare
                </span>

                <span class="brand-subtitle">
                    Management System
                </span>

            </div>

        </a>

    </div>


    <!-- =====================================================
         SIDEBAR CONTENT
    ====================================================== -->

    <div class="sidebar-content">


        <!-- =================================================
             MAIN
        ================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                MAIN
            </span>

            <nav class="sidebar-nav">

                <!-- DASHBOARD -->

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

            </nav>

        </div>


        <!-- =================================================
             DAYCARE
        ================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                DAYCARE
            </span>

            <nav class="sidebar-nav">

                <!-- PARENTS -->

                <a
                    href="<?= $baseUrl ?>parents.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Parents' ? 'active' : '' ?>">

                    <span class="sidebar-icon">
                        <i class="bi bi-people"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Parents
                    </span>

                </a>


                <!-- CHILDREN -->

                <a
                    href="<?= $baseUrl ?>children.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Children' ? 'active' : '' ?>">

                    <span class="sidebar-icon">
                        <i class="bi bi-person-hearts"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Children
                    </span>

                </a>

            </nav>

        </div>


        <!-- =================================================
             RECORDS
        ================================================== -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                RECORDS
            </span>

            <nav class="sidebar-nav">


                <!-- ATTENDANCE -->

                <a
                    href="<?= $baseUrl ?>../admin/manage-attendance/attendance.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Attendance' ? 'active' : '' ?>">
                    <span class="sidebar-icon">
                        <i class="bi bi-calendar-check"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Attendance
                    </span>
                </a>

                <!-- ASSESSMENT -->

                  <a
                    href="/ashera/thesis/app/teacher/activities.php"
                    class="sidebar-link <?= $pageTitle === 'Activities' ? 'active' : '' ?>"
                >

                    <span class="sidebar-icon">
                        <i class="bi bi-journal-text"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Activities
                    </span>

                </a>

                <!-- HEALTH RECORDS -->

                <a
                    href="<?= $baseUrl ?>health-records.php"
                    class="sidebar-link <?= ($pageTitle ?? '') === 'Health Records' ? 'active' : '' ?>">

                    <span class="sidebar-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </span>

                    <span class="sidebar-link-text">
                        Health Records
                    </span>

                </a>

            </nav>

        </div>

    </div>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->

    <div class="sidebar-footer">

        <div class="sidebar-user">

            <div class="user-avatar">

                <?= strtoupper(
                    substr(
                        $_SESSION['first_name'] ?? 'U',
                        0,
                        1
                    )
                ) ?>

            </div>


            <div class="sidebar-user-info">

                <span>

                    <?= htmlspecialchars(
                        $_SESSION['first_name'] ?? 'User',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

                <small>

                    <?= htmlspecialchars(
                        $_SESSION['role'] ?? 'User',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </small>

            </div>

        </div>

    </div>

</aside>


<!-- =====================================================
     MOBILE OVERLAY
====================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"></div>