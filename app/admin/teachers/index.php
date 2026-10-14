<?php

$pageTitle = "Teachers";

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
// GET TEACHERS (filtered by academic year)
// ==========================================================

$teachers = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            t.id,
            t.first_name,
            t.middle_name,
            t.last_name,
            t.email,
            t.academic_id,
            ay.academic_year
        FROM teacher t
        LEFT JOIN academicyear ay
            ON ay.id = t.academic_id
        WHERE t.academic_id = ?
        ORDER BY t.id DESC
    ");

    $stmt->bind_param("i", $filterAcademicId);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }

    $stmt->close();

} else {

    // No filter and no academic year → show all
    $result = $conn->query("
        SELECT
            t.id,
            t.first_name,
            t.middle_name,
            t.last_name,
            t.email,
            t.academic_id,
            ay.academic_year
        FROM teacher t
        LEFT JOIN academicyear ay
            ON ay.id = t.academic_id
        ORDER BY t.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
    }
}


// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<div class="teachers-page">


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


        <a
            href="create-teacher.php"
            class="btn btn-primary">

            <i class="bi bi-plus-lg me-1"></i>

            Add Teacher

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
     TEACHERS TABLE
     ====================================================== -->

    <div class="card p-0">

        <!-- CARD HEADER -->
        <div class="card-header bg-paper border-bottom p-3">

            <div class="d-flex align-items-center justify-content-between gap-2">

                <h6 class="fw-bold mb-0">
                    <i class="bi bi-person-workspace text-primary me-2"></i>
                    Teachers
                </h6>

                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($teachers) ?>
                    record<?= count($teachers) !== 1 ? 's' : '' ?>
                </span>

            </div>

        </div>

        <!-- TABLE -->
        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>

                    <tr>
                        <th>Teacher ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($teachers)): ?>

                        <?php foreach ($teachers as $row): ?>

                            <tr>

                                <!-- TEACHER ID -->
                                <td>
                                    <span class="fw-semibold">
                                        <?= (int) $row['id'] ?>
                                    </span>
                                </td>

                                <!-- FIRST NAME -->
                                <td>
                                    <?= e($row['first_name']) ?>
                                </td>

                                <!-- MIDDLE NAME -->
                                <td>
                                    <?= e($row['middle_name'] ?: '—') ?>
                                </td>

                                <!-- LAST NAME -->
                                <td>
                                    <?= e($row['last_name']) ?>
                                </td>

                                <!-- EMAIL -->
                                <td>
                                    <?= e($row['email']) ?>
                                </td>

                                <!-- ACADEMIC YEAR -->
                                <td>

                                    <?php if (!empty($row['academic_year'])): ?>

                                        <span class="badge bg-paper-soft text-secondary">
                                            <?= e($row['academic_year']) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted small">
                                            Not assigned
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- ACTIONS -->
                                <td>

                                    <div class="d-flex align-items-center gap-1">

                                        <a
                                            href="view.php?id=<?= (int) $row['id'] ?>"
                                            class="btn btn-outline-primary btn-sm"
                                            title="View Teacher">

                                            View

                                        </a>

                                        <a
                                            href="edit-teacher.php?id=<?= (int) $row['id'] ?>"
                                            class="btn btn-warning btn-sm"
                                            title="Edit Teacher">

                                            Edit

                                        </a>

                                        <a
                                            href="delete-teacher.php?id=<?= (int) $row['id'] ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete this teacher?');"
                                            title="Delete Teacher">

                                            Delete

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <!-- EMPTY STATE -->
                        <tr>

                            <td colspan="7" class="text-center py-5">

                                <i class="bi bi-person-workspace fs-2 text-muted"></i>

                                <h6 class="fw-semibold mt-2">
                                    No Teachers Found
                                </h6>

                                <p class="text-muted small mb-0">

                                    <?php if ($filterAcademicId > 0): ?>

                                        No teachers were found for the selected
                                        academic year.

                                    <?php else: ?>

                                        No teachers are currently available.

                                    <?php endif; ?>

                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php include '../layout/footer.php'; ?>