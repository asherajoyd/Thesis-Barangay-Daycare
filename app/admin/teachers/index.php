<?php

$pageTitle = "Teachers";

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
// DETERMINE FILTER
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
// GET TEACHERS
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
            t.contact,
            ta.academic_id,
            ay.academic_year
        FROM teacher t
        LEFT JOIN teacher_assignment ta
            ON ta.teacher_id = t.id
            AND ta.academic_id = ?
        LEFT JOIN academic_year ay
            ON ay.id = ta.academic_id
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

    $result = $conn->query("
        SELECT
            t.id,
            t.first_name,
            t.middle_name,
            t.last_name,
            t.email,
            t.contact,
            NULL AS academic_id,
            NULL AS academic_year
        FROM teacher t
        ORDER BY t.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
    }
}


include '../layout/header.php';

?>

<div class="teachers-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <h2 class="fw-bold fs-4 mb-0"><?= e($pageTitle) ?></h2>

        <a href="create-teacher.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add Teacher
        </a>

    </div>


    <!-- CURRENT ACADEMIC YEAR + FILTER -->
    <div class="card mb-3">
        <div class="card-body">

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


    <!-- TEACHERS TABLE -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">

                <h6 class="fw-bold mb-0">
                    <i class="bi bi-person-workspace text-primary me-2"></i>
                    Teachers
                </h6>

                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($teachers) ?> record<?= count($teachers) !== 1 ? 's' : '' ?>
                </span>

            </div>
        </div>

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

                                <td>
                                    <span class="fw-semibold"><?= (int) $row['id'] ?></span>
                                </td>

                                <td><?= e($row['first_name']) ?></td>
                                <td><?= e($row['middle_name'] ?: '—') ?></td>
                                <td><?= e($row['last_name']) ?></td>
                                <td><?= e($row['email']) ?></td>

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
                                    <div class="d-flex align-items-center gap-1">

                                        <a href="view.php?id=<?= (int) $row['id'] ?>"
                                           class="btn btn-outline-primary btn-sm"
                                           title="View Teacher">
                                            View
                                        </a>

                                        <a href="edit-teacher.php?id=<?= (int) $row['id'] ?>"
                                           class="btn btn-warning btn-sm"
                                           title="Edit Teacher">
                                            Edit
                                        </a>

                                        <a href="delete-teacher.php?id=<?= (int) $row['id'] ?>"
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

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-person-workspace fs-2 text-muted"></i>
                                <h6 class="fw-semibold mt-2">No Teachers Found</h6>
                                <p class="text-muted small mb-0">
                                    <?php if ($filterAcademicId > 0): ?>
                                        No teachers were found for the selected academic year.
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