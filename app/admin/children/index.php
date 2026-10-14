<?php

$pageTitle = "Children";

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
// GET CHILDREN
// Only approved enrollments: e.status = 1
// ==========================================================

$children = [];

$sql = "
    SELECT
        c.id AS child_id,
        c.user_id AS parent_user_id,
        c.enrollment_id,

        e.c_first_name,
        e.c_middle_name,
        e.c_last_name,
        e.c_gender,
        e.c_birthdate,
        e.academic_id,

        ay.academic_year,

        u.first_name AS parent_first_name,
        u.middle_name AS parent_middle_name,
        u.last_name AS parent_last_name,
        u.email AS parent_email

    FROM children c

    INNER JOIN enrollment e
        ON e.id = c.enrollment_id

    LEFT JOIN academicyear ay
        ON ay.id = e.academic_id

    LEFT JOIN users u
        ON u.id = c.user_id

    WHERE e.status = 1
";

// Apply academic year filter
if ($filterAcademicId > 0) {
    $sql .= " AND e.academic_id = ?";
}

$sql .= " ORDER BY c.id DESC";

$stmt = $conn->prepare($sql);

if ($filterAcademicId > 0) {
    $stmt->bind_param("i", $filterAcademicId);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

$stmt->close();


// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<div class="children-page">

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
         CHILDREN TABLE
         ====================================================== -->

    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">

            <div class="d-flex align-items-center justify-content-between gap-2">

                <h6 class="fw-bold mb-0">
                    <i class="bi bi-people text-primary me-2"></i>
                    Children List
                </h6>

                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($children) ?>
                    record<?= count($children) !== 1 ? 's' : '' ?>
                </span>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>

                    <tr>

                        <th>Child ID</th>

                        <th>Child Name</th>

                        <th>Parent Name</th>

                        <th>Gender</th>

                        <th>Birthdate</th>

                        <th>Academic Year</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($children)): ?>

                        <?php foreach ($children as $row): ?>

                            <tr>

                                <!-- ACTUAL CHILD TABLE ID -->

                                <td>
                                    <span class="fw-semibold">
                                        <?= (int) $row['child_id'] ?>
                                    </span>
                                </td>


                                <!-- CHILD NAME -->

                                <td>
                                    <?= e(trim(
                                        $row['c_first_name'] . ' ' .
                                        $row['c_middle_name'] . ' ' .
                                        $row['c_last_name']
                                    )) ?>
                                </td>


                                <!-- PARENT NAME FROM USERS -->

                                <td>

                                    <?php
                                    $parentName = trim(
                                        ($row['parent_first_name'] ?? '') . ' ' .
                                        ($row['parent_middle_name'] ?? '') . ' ' .
                                        ($row['parent_last_name'] ?? '')
                                    );
                                    ?>

                                    <?= e($parentName ?: 'Not available') ?>

                                </td>


                                <!-- GENDER -->

                                <td class="text-capitalize">
                                    <?= e($row['c_gender'] ?: 'Not specified') ?>
                                </td>


                                <!-- BIRTHDATE -->

                                <td>

                                    <?php if (!empty($row['c_birthdate'])): ?>

                                        <?= e(date(
                                            'F d, Y',
                                            strtotime($row['c_birthdate'])
                                        )) ?>

                                    <?php else: ?>

                                        <span class="text-muted">—</span>

                                    <?php endif; ?>

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
                                        href="view.php?id=<?= (int) $row['child_id'] ?>"
                                        class="btn btn-outline-primary btn-sm"
                                        title="View Child">

                                        View

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="text-center py-5">

                                <i class="bi bi-people fs-2 text-muted"></i>

                                <h6 class="fw-semibold mt-2">
                                    No Children Found
                                </h6>

                                <p class="text-muted small mb-0">

                                    <?php if ($filterAcademicId > 0): ?>

                                        No children with approved enrollments
                                        were found for this academic year.

                                    <?php else: ?>

                                        No children with approved enrollments
                                        are available.

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