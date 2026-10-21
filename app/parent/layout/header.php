<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- css -->
    <link href="../../../theme.css" rel="stylesheet">
    <link href="../../../dashboard.css" rel="stylesheet">

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
                    class="btn header-menu-btn d-lg-none p-0"
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

                <!-- NOTIFICATION BELL -->
                <div class="dropdown">

                    <button class="btn btn-sm btn-light border position-relative"
                            type="button"
                            id="notifBell"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false">

                        <i class="bi bi-bell"></i>

                        <span id="notifBadge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            style="font-size: 0.6rem;">
                            0
                        </span>

                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-0 shadow"
                        style="width: 340px; max-height: 450px; overflow-y: auto;"
                        id="notifDropdown">

                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                            <h6 class="fw-bold mb-0">Notifications</h6>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0"
                                    id="markAllReadBtn" style="font-size: 0.75rem;">
                                Mark all read
                            </button>
                        </div>

                        <div id="notifList" class="p-0">
                            <div class="text-center text-muted py-4 small">
                                Loading...
                            </div>
                        </div>

                        <a href="<?= $baseUrl ?>notification/"
                        class="dropdown-item text-center text-primary fw-semibold py-2 border-top">
                            View All Notifications
                        </a>

                    </div>

                </div>


                <script>
                (function () {

                    const notifBadge     = document.getElementById('notifBadge');
                    const notifList      = document.getElementById('notifList');
                    const markAllReadBtn = document.getElementById('markAllReadBtn');

                    function loadCount() {
                        fetch('<?= $baseUrl ?>notification/fetch.php')
                            .then(res => res.json())
                            .then(data => {
                                if (data.unread > 0) {
                                    notifBadge.textContent = data.unread > 9 ? '9+' : data.unread;
                                    notifBadge.classList.remove('d-none');
                                } else {
                                    notifBadge.classList.add('d-none');
                                }
                            })
                            .catch(() => {});
                    }

                    function loadList() {
                        fetch('<?= $baseUrl ?>notification/fetch-list.php')
                            .then(res => res.json())
                            .then(data => {
                                if (!data.notifications || data.notifications.length === 0) {
                                    notifList.innerHTML = '<div class="text-center text-muted py-4 small">No notifications</div>';
                                    return;
                                }

                                let html = '';

                                data.notifications.forEach(function (n) {
                                    html += `
                                        <a href="${n.link || '#'}" class="dropdown-item border-bottom p-2 ${n.is_read === 0 ? 'bg-light' : ''}">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bi ${n.icon} ${n.color} fs-5"></i>
                                                <div class="min-w-0">
                                                    <div class="fw-semibold small text-break">${n.title}</div>
                                                    ${n.message ? `<div class="text-muted text-break" style="font-size: 0.75rem;">${n.message}</div>` : ''}
                                                    <div class="text-muted" style="font-size: 0.7rem;">${n.time_ago}</div>
                                                </div>
                                            </div>
                                        </a>
                                    `;
                                });

                                notifList.innerHTML = html;
                            })
                            .catch(() => {});
                    }

                    // Load on page load
                    loadCount();

                    // Load list when dropdown opens
                    document.getElementById('notifBell').addEventListener('click', function () {
                        loadList();
                    });

                    // Mark all as read
                    markAllReadBtn.addEventListener('click', function (e) {
                        e.preventDefault();

                        const formData = new FormData();
                        formData.append('all', '1');

                        fetch('<?= $baseUrl ?>notification/mark-read.php', { method: 'POST', body: formData })
                            .then(() => {
                                loadCount();
                                loadList();
                            });
                    });

                    // Poll for new count every 30 seconds
                    setInterval(loadCount, 30000);

                })();
                </script>


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
                            <a class="dropdown-item" href="<?= $baseUrl ?>profile/">
                                <i class="bi bi-person"></i>
                                Profile
                            </a>
                        </li>

                        <li>
                            <a href="../../../api/logout.php" class="dropdown-item"> <i class="bi bi-box-arrow-right"></i> Sign Out </a>
                        </li>

                    </ul>

                </div>

            </div>

        </header>


        <!-- Page Content -->
        <div class="dashboard-content">


        <?php include 'sidebar.php'; ?>
