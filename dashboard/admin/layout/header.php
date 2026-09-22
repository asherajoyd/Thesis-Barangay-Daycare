<?php include('guard.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- css -->
    <link href="../../theme.css" rel="stylesheet">
    <link href="../../dashboard.css" rel="stylesheet">

    <!-- bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <!-- bootstrap js and icon -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- js library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <title><?= $pageTitle .'- Barangay Daycare' ?></title>
</head>
<body>
  



<div class="dashboard-wrapper">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Area -->
    <div class="dashboard-main">

        <!-- Header -->
        <header class="dashboard-header">

            <div class="header-left">

                <!-- Mobile Sidebar Button -->
                <button
                    type="button"
                    class="btn header-menu-btn d-lg-none"
                    id="sidebarToggle"
                >
                    <i class="bi bi-list"></i>
                </button>

                <!-- Desktop Sidebar Toggle -->
                <button
                    type="button"
                    class="btn header-menu-btn d-none d-lg-flex"
                    id="sidebarCollapse"
                >
                    <i class="bi bi-layout-sidebar"></i>
                </button>

                <div class="page-heading">
                    <span class="page-heading-title">
                        <?= $pageTitle ?? 'Dashboard' ?>
                    </span>
                </div>

            </div>


            <div class="header-right">

                <!-- <div class="header-search d-none d-md-flex">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        placeholder="Search..."
                        aria-label="Search"
                    >
                    <span class="search-shortcut">⌘ K</span>
                </div>


                <button class="header-icon-btn position-relative">
                    <i class="bi bi-bell"></i>
                    <span class="notification-dot"></span>
                </button>


                <div class="header-divider"></div> -->


                <!-- User -->
                <div class="dropdown">

                    <button
                        class="user-dropdown"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)) ?>
                        </div>

                        <div class="user-info d-none d-md-block">

                            <span class="user-name">
                                <?= htmlspecialchars(
                                    ($_SESSION['first_name'] ?? '') . ' ' .
                                    ($_SESSION['last_name'] ?? '')
                                ) ?>
                            </span>

                            <span class="user-role">
                                <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>
                            </span>

                        </div>

                        <i class="bi bi-chevron-down user-chevron"></i>

                    </button>


                    <ul class="dropdown-menu dropdown-menu-end user-menu">

                        <li class="user-menu-header">

                            <div class="user-avatar user-avatar-lg">
                                <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)) ?>
                            </div>

                            <div>
                                <strong>
                                    <?= htmlspecialchars(
                                        ($_SESSION['first_name'] ?? '') . ' ' .
                                        ($_SESSION['last_name'] ?? '')
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
                                </small>
                            </div>

                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i>
                                Profile
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="settings.php">
                                <i class="bi bi-gear"></i>
                                Settings
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a href="../../api/logout.php" class="dropdown-item"> <i class="bi bi-box-arrow-right me-2"></i> Sign Out </a>
                        </li>

                    </ul>

                </div>

            </div>

        </header>


        <!-- Page Content -->
        <div class="dashboard-content">


        <?php include 'sidebar.php'; ?>
