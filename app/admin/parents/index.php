<?php

$pageTitle = "Parents";

require_once '../guard.php';
include "../../../api/conn.php";


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
    SELECT id, academic_year, status, start_date
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
// DETERMINE ACADEMIC YEAR FILTER
// ==========================================================

$filterAcademicId = isset($_GET['academic_id'])
    ? (int) $_GET['academic_id']
    : 0;

if ($filterAcademicId <= 0 && $currentYear) {
    $filterAcademicId = (int) $currentYear['id'];
}

if ($filterAcademicId <= 0 && !empty($academicYears)) {
    $filterAcademicId = (int) $academicYears[0]['id'];
}


// ==========================================================
// GET PARENTS
// Source: users (role=parent)
// Link: children (user_id) → enrollment (child_id) → academic_year
// Only approved enrollments
// ==========================================================

$parents = [];

$sql = "
    SELECT
        u.id AS user_id,
        u.first_name AS account_first_name,
        u.middle_name AS account_middle_name,
        u.last_name AS account_last_name,
        u.email AS account_email,
        u.contact AS account_contact,
        u.status AS account_status,

        c.id AS child_id,
        c.first_name AS child_first_name,
        c.middle_name AS child_middle_name,
        c.last_name AS child_last_name,

        e.id AS enrollment_id,
        e.status AS enrollment_status,
        e.academic_id,

        ay.academic_year

    FROM users u

    INNER JOIN children c
        ON c.user_id = u.id

    INNER JOIN enrollment e
        ON e.child_id = c.id

    LEFT JOIN academic_year ay
        ON ay.id = e.academic_id

    WHERE u.role = 'parent'
      AND e.status = 'approved'
";

if ($filterAcademicId > 0) {
    $sql .= " AND e.academic_id = ?";
}

$sql .= " ORDER BY u.id DESC";

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


include '../layout/header.php';

?>

<div class="parents-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
        </div>
    </div>


    <!-- CURRENT ACADEMIC YEAR + FILTER -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-center">

                <div class="col-12 col-md-6 col-lg-8 current-year-chip">
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

                <form class="filter-form col-12 col-md-6 col-lg-4" method="GET">
                    <label for="academic_id" class="filter-label">
                        <i class="bi bi-funnel me-1"></i> Filter by Academic Year
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


    <!-- PARENTS TABLE -->
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
                        <th>User ID</th>
                        <th>Parent Name</th>
                        <th>Parent Email</th>
                        <th>Child Name</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($parents)): ?>

                        <?php foreach ($parents as $row): ?>

                            <tr>

                                <td>
                                    <span class="fw-semibold"><?= (int) $row['user_id'] ?></span>
                                </td>

                                <td>
                                    <?= e(trim(
                                        $row['account_first_name'] . ' ' .
                                        $row['account_middle_name'] . ' ' .
                                        $row['account_last_name']
                                    )) ?>
                                </td>

                                <td><?= e($row['account_email']) ?></td>

                                <td>
                                    <?= e(trim(
                                        $row['child_first_name'] . ' ' .
                                        $row['child_middle_name'] . ' ' .
                                        $row['child_last_name']
                                    )) ?>
                                </td>

                                <td>
                                    <?php if (!empty($row['academic_year'])): ?>
                                        <span class="badge bg-paper-soft text-secondary">
                                            <?= e($row['academic_year']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not assigned</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="view.php?id=<?= (int) $row['user_id'] ?>&child_id=<?= (int) $row['child_id'] ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        View
                                    </a>

                                    <a href="edit.php?id=<?= (int) $row['user_id'] ?>&child_id=<?= (int) $row['child_id'] ?>"
                                       class="btn btn-warning btn-sm">
                                        Edit
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-people fs-2 text-muted"></i>
                                <h6 class="fw-semibold mt-2">No Parents Found</h6>
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