<?php

$pageTitle = "Enrollment";

require_once '../guard.php';
include "../../../api/conn.php";


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
    FROM academic_year
    WHERE status = 'active'
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
    SELECT id, academic_year, status
    FROM academic_year
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

if ($filterAcademicId <= 0 && $currentYear) {
    $filterAcademicId = (int) $currentYear['id'];
}


// ==========================================================
// GET ENROLLMENTS (pending only)
// ==========================================================

$enrollments = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            e.id,
            e.status,
            e.academic_id,
            e.created_at,
            c.id AS child_id,
            c.first_name,
            c.middle_name,
            c.last_name,
            c.gender,
            c.birthdate,
            ay.academic_year
        FROM enrollment e
        INNER JOIN children c ON c.id = e.child_id
        LEFT JOIN academic_year ay ON ay.id = e.academic_id
        WHERE e.status = 'pending'
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

    $result = $conn->query("
        SELECT
            e.id,
            e.status,
            e.academic_id,
            e.created_at,
            c.id AS child_id,
            c.first_name,
            c.middle_name,
            c.last_name,
            c.gender,
            c.birthdate,
            ay.academic_year
        FROM enrollment e
        INNER JOIN children c ON c.id = e.child_id
        LEFT JOIN academic_year ay ON ay.id = e.academic_id
        WHERE e.status = 'pending'
        ORDER BY e.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $enrollments[] = $row;
        }
    }
}


include '../layout/header.php';

?>

<div class="enrollment-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <h2 class="fw-bold fs-4 mb-0">
            <?= e($pageTitle) ?>
        </h2>

        <a href="archive.php" class="btn btn-warning px-2 py-1"
           data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Archive">
            <i class="bi bi-archive fs-6 fw-bolder"></i>
        </a>

    </div>


    <!-- CURRENT ACADEMIC YEAR + FILTER -->
    <div class="card mb-3">
        <div class="card-body">

            <div class="d-flex justify-content-between">
                <div class="row g-2 w-100">

                    <div class="col-12 col-md-6 col-lg-7 col-xl-9 current-year-chip">
                        <div class="icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <div class="year-label-sm">Current Academic Year</div>
                            <div class="year-value-sm">
                                <?php if ($currentYear): ?>
                                    <?= e($currentYear['academic_year']) ?>
                                <?php else: ?>
                                    <span class="text-muted">None active</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form class="filter-form col-12 col-md-6 col-lg-5 col-xl-3" method="GET">
                        <label for="academic_id" class="filter-label">
                            <i class="bi bi-funnel"></i> Filter:
                        </label>
                        <select name="academic_id" id="academic_id"
                                class="form-select form-select-sm filter-select"
                                onchange="this.form.submit()">
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?= (int) $year['id'] ?>"
                                    <?= $filterAcademicId === (int) $year['id'] ? 'selected' : '' ?>>
                                    <?= e($year['academic_year']) ?>
                                    <?= $year['status'] === 'active' ? ' (Active)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                </div>
            </div>

        </div>
    </div>


    <!-- ENROLLMENT TABLE -->
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
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($enrollments) > 0): ?>

                        <?php foreach ($enrollments as $row): ?>

                            <tr>

                                <td><?= e($row['id']) ?></td>

                                <td>
                                    <?= e(trim(
                                        $row['first_name'] . ' ' .
                                        $row['middle_name'] . ' ' .
                                        $row['last_name']
                                    )) ?>
                                </td>

                                <td class="text-capitalize fw-bold"><?= e($row['gender']) ?></td>

                                <td><?= date('F d, Y', strtotime($row['birthdate'])) ?></td>

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
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </td>

                                <td>

                                    <a href="view.php?id=<?= urlencode($row['id']) ?>"
                                       class="btn btn-outline-secondary btn-sm">
                                        View
                                    </a>

                                    <a href="approve-enrollment.php?id=<?= urlencode($row['id']) ?>"
                                       class="btn btn-success btn-sm"
                                       onclick="return confirm('Approve this enrollment?');">
                                        Approve
                                    </a>

                                    <a href="reject-enrollment.php?id=<?= urlencode($row['id']) ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Reject this enrollment? This enrollment will be moved to archive.');">
                                        Reject
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4 bg-light">
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