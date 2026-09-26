<?php

$pageTitle = "Settings";

require_once '../guard.php';
include "../../../api/conn.php";


// ==========================================================
// HELPER
// ==========================================================

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// LOAD PARTIALS (BACKEND FIRST — OUTPUT BUFFERED)
// ==========================================================

ob_start();
include 'academic-year.php';
$academicYearTab = ob_get_clean();

ob_start();
include 'account.php';
$accountTab = ob_get_clean();


// ==========================================================
// FLASH MESSAGE (after partials set them)
// ==========================================================

$success = $_SESSION['settings_success'] ?? null;
$error   = $_SESSION['settings_error'] ?? null;

unset(
    $_SESSION['settings_success'],
    $_SESSION['settings_error']
);


// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<link rel="stylesheet" href="style.css">


<div class="settings-page">


    <!-- ======================================================
         PAGE HEADER
         ====================================================== -->

    <div class="settings-header d-flex align-items-center justify-content-between">

        <div class="d-flex align-items-center gap-3">

            <div class="settings-header-icon">
                <i class="bi bi-sliders2"></i>
            </div>

            <div>

                <h2 class="fw-bold fs-5 mb-1">
                    Settings
                </h2>

                <p class="text-muted small mb-0">
                    Manage your daycare system configuration.
                </p>

            </div>

        </div>

    </div>


    <!-- ======================================================
         ALERTS
         ====================================================== -->

    <?php if ($success): ?>

        <div
            class="alert alert-success alert-dismissible fade show mt-3 fw-bold"
            role="alert">

            <i class="bi bi-check-circle me-2"></i>

            <?= e($success) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div
            class="alert alert-danger alert-dismissible fade show mt-3"
            role="alert">

            <i class="bi bi-exclamation-circle me-2"></i>

            <?= e($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         SETTINGS TABS
         ====================================================== -->

    <div class="settings-tabs mt-3">

        <ul class="nav nav-pills settings-nav-pills" role="tablist">

            <li class="nav-item" role="presentation">

                <button
                    class="nav-link active"
                    data-bs-toggle="tab"
                    data-bs-target="#academicYear"
                    type="button">

                    <i class="bi bi-calendar3 me-2"></i>

                    Academic Year

                </button>

            </li>


            <li class="nav-item" role="presentation">

                <button
                    class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#account"
                    type="button">

                    <i class="bi bi-person-circle me-2"></i>

                    My Account

                </button>

            </li>

        </ul>


        <div class="tab-content mt-3">

            <?= $academicYearTab ?>

            <?= $accountTab ?>

        </div>

    </div>

</div>


<script src="script.js"></script>

<?php include '../layout/footer.php'; ?>