<?php

$pageTitle = "Parents";

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// HELPER
// ==========================================================

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET CURRENT ACTIVE ACADEMIC YEAR
// ==========================================================

$currentYear = null;

$result = $conn->query("
    SELECT id, academic_year, status
    FROM academicyear
    WHERE status = 1
    ORDER BY id DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $currentYear = $result->fetch_assoc();
}


// ==========================================================
// GET ALL ACADEMIC YEARS
// ==========================================================

$academicYears = [];

$result = $conn->query("
    SELECT id, academic_year, status, start_date
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
// DETERMINE ACADEMIC YEAR FILTER
// ==========================================================

$filterAcademicId = isset($_GET['academic_id'])
    ? (int) $_GET['academic_id']
    : 0;

// Default to active academic year
if ($filterAcademicId <= 0 && $currentYear) {
    $filterAcademicId = (int) $currentYear['id'];
}

// Fallback to first available academic year
if ($filterAcademicId <= 0 && !empty($academicYears)) {
    $filterAcademicId = (int) $academicYears[0]['id'];
}


// ==========================================================
// GET PARENTS / GUARDIANS
// Only approved enrollments: e.status = 1
// ==========================================================

$parents = [];

$sql = "
    SELECT
        p.id AS parent_id,
        p.user_id,
        p.enrollment_id,

        u.first_name AS guardian_first_name,
        u.middle_name AS guardian_middle_name,
        u.last_name AS guardian_last_name,
        u.email AS guardian_email,

        e.c_first_name,
        e.c_middle_name,
        e.c_last_name,
        e.academic_id,

        ay.academic_year

    FROM `parent` p

    INNER JOIN users u
        ON u.id = p.user_id

    INNER JOIN enrollment e
        ON e.id = p.enrollment_id

    LEFT JOIN academicyear ay
        ON ay.id = e.academic_id

    WHERE e.status = 1
";

// Apply academic year filter
if ($filterAcademicId > 0) {
    $sql .= " AND e.academic_id = ?";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);

if ($filterAcademicId > 0) {
    $stmt->bind_param("i", $filterAcademicId);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $parents[] = $row;
}

$stmt->close();


// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<div class="parents-page">

    <!-- ======================================================
         ALERTS
         ====================================================== -->

    <?php if (!empty($_SESSION['message'])): ?>

        <div
            class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show"
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

        <div>
            <h2 class="fw-bold fs-4 mb-1">
                <?= e($pageTitle) ?>
            </h2>
        </div>

    </div>


    <!-- ======================================================
         CURRENT ACADEMIC YEAR + FILTER
         ====================================================== -->

    <div class="card mb-3">

        <div class="card-body">

            <div class="row g-3 align-items-center">

                <!-- CURRENT ACADEMIC YEAR -->

                <div class="col-12 col-md-6 col-lg-8 current-year-chip">

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

                                <span class="text-muted">
                                    None active
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- ACADEMIC YEAR FILTER -->

                <form
                    class="filter-form col-12 col-md-6 col-lg-4"
                    method="GET"
                    action="">

                    <label
                        for="academic_id"
                        class="filter-label">

                        <i class="bi bi-funnel me-1"></i>
                        Filter by Academic Year

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


    <!-- ======================================================
         PARENTS TABLE
         ====================================================== -->

    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">

            <div class="d-flex align-items-center justify-content-between gap-2">

                <h6 class="fw-bold mb-0">
                    <i class="bi bi-people text-primary me-2"></i>
                    Parents and Guardians
                </h6>

                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($parents) ?> record<?= count($parents) !== 1 ? 's' : '' ?>
                </span>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>

                    <tr>

                        <th>Parent ID</th>

                        <th>Guardian Name</th>

                        <th>Guardian Email</th>

                        <th>Child Name</th>

                        <th>Academic Year</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($parents)): ?>

                        <?php foreach ($parents as $row): ?>

                            <tr>

                                <!-- ACTUAL PARENT TABLE ID -->

                                <td>
                                    <span class="fw-semibold">
                                        <?= (int) $row['parent_id'] ?>
                                    </span>
                                </td>


                                <!-- GUARDIAN NAME FROM USERS -->

                                <td>

                                    <?= e(trim(
                                        $row['guardian_first_name'] . ' ' .
                                        $row['guardian_middle_name'] . ' ' .
                                        $row['guardian_last_name']
                                    )) ?>

                                </td>


                                <!-- GUARDIAN EMAIL FROM USERS -->

                                <td>
                                    <?= e($row['guardian_email']) ?>
                                </td>


                                <!-- CHILD NAME FROM ENROLLMENT -->

                                <td>

                                    <?= e(trim(
                                        $row['c_first_name'] . ' ' .
                                        $row['c_middle_name'] . ' ' .
                                        $row['c_last_name']
                                    )) ?>

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

                                    <a
                                        href="view.php?id=<?= (int) $row['parent_id'] ?>"
                                        class="btn btn-outline-primary btn-sm"
                                        title="View Parent">

                                        View

                                    </a>

                                    <a
                                        href="edit.php?id=<?= (int) $row['parent_id'] ?>"
                                        class="btn btn-warning btn-sm"
                                        title="Edit Parent">
                                        Edit

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5">

                                <i class="bi bi-people fs-2 text-muted"></i>

                                <h6 class="fw-semibold mt-2">
                                    No Parents Found
                                </h6>

                                <p class="text-muted small mb-0">
                                    <?php if ($filterAcademicId > 0): ?>

                                        No approved parent enrollments were found
                                        for the selected academic year.

                                    <?php else: ?>

                                        No approved parent enrollments are available.

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