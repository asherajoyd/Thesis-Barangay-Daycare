<?php

    $pageTitle = "Enrollment";

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
    // GET CURRENT ACTIVE ACADEMIC YEAR
    // ==========================================================

    $currentYear = null;

    $result = $conn->query("
        SELECT *
        FROM academicyear
        WHERE status = 1
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($result && $result->num_rows > 0) {
        $currentYear = $result->fetch_assoc();
    }


    // ==========================================================
    // GET ALL ACADEMIC YEARS (for filter dropdown)
    // ==========================================================

    $academicYears = [];

    $result = $conn->query("
        SELECT id, academic_year, status
        FROM academicyear
        ORDER BY
            status DESC,
            start_date DESC,
            id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $academicYears[] = $row;
        }
    }


    // ==========================================================
    // DETERMINE FILTER
    // ==========================================================

    $filterAcademicId = isset($_GET['academic_id'])
        ? (int) $_GET['academic_id']
        : 0;


    // Default: use current active year if no filter selected
    if ($filterAcademicId <= 0 && $currentYear) {
        $filterAcademicId = (int) $currentYear['id'];
    }


    // ==========================================================
    // GET ENROLLMENTS (filtered)
    // ==========================================================

    $enrollments = [];

    if ($filterAcademicId > 0) {

        $stmt = $conn->prepare("
            SELECT
                e.id,
                e.c_first_name,
                e.c_middle_name,
                e.c_last_name,
                e.c_gender,
                e.c_birthdate,
                e.status,
                e.academic_id,
                ay.academic_year
            FROM enrollment e
            LEFT JOIN academicyear ay
                ON ay.id = e.academic_id
            WHERE e.status = 0
            AND e.academic_id = ?
            ORDER BY e.id DESC
        ");

        $stmt->bind_param("i", $filterAcademicId);

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $enrollments[] = $row;
        }

        $stmt->close();

    } else {

        // No active year and no filter → show all pending
        $result = $conn->query("
            SELECT
                e.id,
                e.c_first_name,
                e.c_middle_name,
                e.c_last_name,
                e.c_gender,
                e.c_birthdate,
                e.status,
                e.academic_id,
                ay.academic_year
            FROM enrollment e
            LEFT JOIN academicyear ay
                ON ay.id = e.academic_id
            WHERE e.status = 0
            ORDER BY e.id DESC
        ");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $enrollments[] = $row;
            }
        }
    }


    // ==========================================================
    // PAGE
    // ==========================================================

    include '../layout/header.php';

?>

<link rel="stylesheet" href="style.css">

<div class="enrollment-page">


    <!-- ======================================================
         ALERTS
         ====================================================== -->

    <?php if (!empty($_SESSION['message'])): ?>

        <div
            class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show"
            role="alert">

            <?= e($_SESSION['message']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

        <?php
        unset($_SESSION['status'], $_SESSION['message']);
        ?>

    <?php endif; ?>


    <!-- ======================================================
         PAGE HEADER
         ====================================================== -->

    <div class="d-flex align-items-center justify-content-between mb-3">

        <h2 class="fw-bold fs-4 mb-0">
            <?= e($pageTitle) ?>
        </h2>


        <a
            href="archive.php"
            class="btn btn-warning px-3"
            data-bs-toggle="tooltip"
            data-bs-placement="left"
            data-bs-title="Archive">

            <i class="bi bi-archive"></i>

        </a>

    </div>


    <!-- ======================================================
         CURRENT ACADEMIC YEAR + FILTER
         ====================================================== -->

    <div class="card   mb-3">
        <div class="card-body">

             <div class="d-flex justify-content-between">


               <div class="row g-2 w-100">
                 <!-- CURRENT ACADEMIC YEAR -->

                <div class="col-12 col-md-6 col-lg-8 col-xl-9 current-year-chip">

                    <div class="icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>

                    <div>

                        <div class="year-label-sm">
                            Current Academic Year
                        </div>

                        <div class="year-value-sm">

                            <?php if ($currentYear): ?>

                                <?= e($currentYear['academic_year']) ?>

                            <?php else: ?>

                                <span class="text-muted">None active</span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- FILTER -->

                <form class="filter-form col-12 col-md-6 col-lg-4 col-xl-3"
                    method="GET"
                    action="">

                    <label
                        for="academic_id"
                        class="filter-label">

                        <i class="bi bi-funnel"></i>

                        Filter:

                    </label>


                    <select
                        name="academic_id"
                        id="academic_id"
                        class="form-select form-select-sm filter-select"
                        onchange="this.form.submit()">

                        <?php foreach ($academicYears as $year): ?>

                            <option
                                value="<?= (int) $year['id'] ?>"
                                <?= $filterAcademicId === (int) $year['id'] ? 'selected' : '' ?>>

                                <?= e($year['academic_year']) ?>

                                <?= (int) $year['status'] === 1 ? ' (Active)' : '' ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </form>
               </div>

            </div>

        </div>
    </div>


    <!-- ======================================================
         ENROLLMENT TABLE
         ====================================================== -->

    <div class="card   p-0">

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Child Name</th>

                        <th>Gender</th>

                        <th>Birthdate</th>

                        <th>Academic Year</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($enrollments) > 0): ?>

                        <?php foreach ($enrollments as $row): ?>

                            <?php $status = (int) $row['status']; ?>

                            <tr>

                                <td>
                                    <?= e($row['id']) ?>
                                </td>


                                <td>

                                    <?= e(trim(
                                        $row['c_first_name'] . ' ' .
                                        $row['c_middle_name'] . ' ' .
                                        $row['c_last_name']
                                    )) ?>

                                </td>


                                <td class="text-capitalize fw-bold">
                                    <?= e($row['c_gender']) ?>
                                </td>


                                <td>
                                    <?= date('F d, Y', strtotime($row['c_birthdate'])) ?>
                                </td>


                                <td>

                                    <?php if (!empty($row['academic_year'])): ?>

                                        <span class="badge bg-paper-soft text-secondary">
                                            <?= e($row['academic_year']) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted small">—</span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if ($status === 0): ?>

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    <?php elseif ($status === 1): ?>

                                        <span class="badge bg-success">
                                            Approved
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <!-- View -->
                                    <a
                                        href="view-enrollment.php?id=<?= urlencode($row['id']) ?>"
                                        class="btn btn-outline-secondary btn-sm">

                                        View

                                    </a>


                                    <?php if ($status === 0): ?>

                                        <!-- Approve -->
                                        <a
                                            href="approve-enrollment.php?id=<?= urlencode($row['id']) ?>"
                                            class="btn btn-success btn-sm"
                                            onclick="return confirm('Approve this enrollment?');">

                                            Approve

                                        </a>


                                        <!-- Reject -->
                                        <a
                                            href="reject-enrollment.php?id=<?= urlencode($row['id']) ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Reject this enrollment? This enrollment will be moved to archive.');">

                                            Reject

                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-muted py-4 bg-light">

                                <?php if ($filterAcademicId > 0): ?>

                                    No pending enrollments for this academic year.

                                <?php else: ?>

                                    No enrollment data available.

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php include '../layout/footer.php'; ?>