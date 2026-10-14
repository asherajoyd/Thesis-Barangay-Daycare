<?php

$pageTitle = "Children";

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

// If still nothing, fall back to the first available year
if ($filterAcademicId <= 0 && !empty($academicYears)) {
    $filterAcademicId = (int) $academicYears[0]['id'];
}


// ==========================================================
// GET CHILDREN (filtered by academic year via enrollment)
// ==========================================================

$children = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            c.id,
            e.id AS enrollment_id,
            e.c_first_name,
            e.c_middle_name,
            e.c_last_name,
            e.c_gender,
            e.c_birthdate,
            e.academic_id,
            ay.academic_year
        FROM children c
        LEFT JOIN enrollment e
            ON c.enrollment_id = e.id
        LEFT JOIN academicyear ay
            ON ay.id = e.academic_id
        WHERE e.academic_id = ?
        ORDER BY c.id DESC
    ");

    $stmt->bind_param("i", $filterAcademicId);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }

    $stmt->close();

} else {

    // No filter and no academic year → show all
    $result = $conn->query("
        SELECT
            c.id,
            e.id AS enrollment_id,
            e.c_first_name,
            e.c_middle_name,
            e.c_last_name,
            e.c_gender,
            e.c_birthdate,
            e.academic_id,
            ay.academic_year
        FROM children c
        LEFT JOIN enrollment e
            ON c.enrollment_id = e.id
        LEFT JOIN academicyear ay
            ON ay.id = e.academic_id
        ORDER BY c.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }
    }
}


// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<link rel="stylesheet" href="style.css">


<div class="children-page">


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
                data-bs-dismiss="alert"
                aria-label="Close">
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

    </div>


    <!-- ======================================================
         CURRENT ACADEMIC YEAR + FILTER
         ====================================================== -->

    <div class="card   mb-3">
        <div class="card-body">

             <div class="d-flex justify-content-between">


               <div class="row g-2 w-100">
                 <!-- CURRENT ACADEMIC YEAR -->

                <div class="col-12 col-md-6 col-lg-7 col-xl-9 current-year-chip">

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

                <form class="filter-form col-12 col-md-6 col-lg-5 col-xl-3"
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
         CHILDREN TABLE
         ====================================================== -->

    <div class="card p-0">

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Child Name</th>

                        <th>Gender</th>

                        <th>Birthdate</th>

                        <th>Academic Year</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($children) > 0): ?>

                        <?php foreach ($children as $row): ?>

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


                                <td class="text-capitalize">
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

                                    <a
                                        href="view-child.php?id=<?= urlencode($row['id']) ?>"
                                        class="btn btn-primary btn-sm">

                                        View

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted py-4 bg-light">

                                <?php if ($filterAcademicId > 0): ?>

                                    No children found for this academic year.

                                <?php else: ?>

                                    No children available.

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