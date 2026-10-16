<?php

$pageTitle = "Assign Classes";

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
// GET UNASSIGNED ENROLLMENTS
// Approved pero walang class
// ==========================================================

$unassigned = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            e.id AS enrollment_id,
            e.status AS enrollment_status,
            e.academic_id,
            e.class_id,

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

        WHERE e.status = 'approved'
          AND e.class_id IS NULL
          AND e.academic_id = ?

        ORDER BY e.id DESC
    ");

    $stmt->bind_param("i", $filterAcademicId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $unassigned[] = $row;
    }

    $stmt->close();
}


// ==========================================================
// GET ALL CLASSES (grouped by session)
// ==========================================================

$classesBySession = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            cl.id,
            cl.name,
            cl.capacity,
            cl.session_id,
            cl.teacher_id,

            s.name AS session_name,
            s.start_time,
            s.end_time,

            t.first_name AS teacher_first_name,
            t.middle_name AS teacher_middle_name,
            t.last_name AS teacher_last_name,

            (SELECT COUNT(*)
             FROM enrollment e
             WHERE e.class_id = cl.id
               AND e.status = 'approved') AS enrolled_count

        FROM class cl
        LEFT JOIN session s ON s.id = cl.session_id
        LEFT JOIN teacher t ON t.id = cl.teacher_id

        WHERE cl.academic_id = ?
          AND cl.status = 'active'

        ORDER BY s.start_time ASC, cl.name ASC
    ");

    $stmt->bind_param("i", $filterAcademicId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classesBySession[(int) $row['session_id']][] = $row;
    }

    $stmt->close();
}


// ==========================================================
// GET SESSIONS (for labels)
// ==========================================================

$sessionsMap = [];

$result = $conn->query("
    SELECT id, name, start_time, end_time
    FROM session
    ORDER BY start_time ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessionsMap[(int) $row['id']] = $row;
    }
}


include '../layout/header.php';

?>

<div class="assign-class-page">

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

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">
                Assign approved children to classes
            </p>
        </div>

    </div>


    <!-- FILTER -->
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
                        <i class="bi bi-funnel me-1"></i> Filter:
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


    <!-- FORM -->
    <form method="POST" action="assign-class-save.php">

        <input type="hidden" name="academic_id" value="<?= (int) $filterAcademicId ?>">

        <div class="card p-0">

            <div class="card-header bg-paper border-bottom p-3">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-people text-primary me-2"></i>
                        Unassigned Children
                    </h6>
                    <span class="badge bg-paper-soft text-secondary border">
                        <?= count($unassigned) ?> child<?= count($unassigned) !== 1 ? 'ren' : '' ?>
                    </span>
                </div>
            </div>

            <div class="table-responsive">

                <table class="table mb-0 data-table text-nowrap">

                    <thead>
                        <tr>
                            <th>Child ID</th>
                            <th>Child Name</th>
                            <th>Gender</th>
                            <th>Birthdate</th>
                            <th>Assign Class</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($unassigned)): ?>

                            <?php foreach ($unassigned as $row): ?>

                                <?php
                                $childName = trim(
                                    $row['first_name'] . ' ' .
                                    $row['middle_name'] . ' ' .
                                    $row['last_name']
                                );

                                $birthdate = !empty($row['birthdate'])
                                    ? date('M d, Y', strtotime($row['birthdate']))
                                    : '—';
                                ?>

                                <tr>

                                    <td>
                                        <span class="fw-semibold"><?= (int) $row['child_id'] ?></span>
                                    </td>

                                    <td><?= e($childName) ?></td>

                                    <td class="text-capitalize"><?= e($row['gender']) ?></td>

                                    <td><?= e($birthdate) ?></td>

                                    <td>

                                        <select
                                            name="assignments[<?= (int) $row['enrollment_id'] ?>]"
                                            class="form-select form-select-sm"
                                            style="min-width: 240px;">

                                            <option value="">— Select class —</option>

                                            <?php foreach ($classesBySession as $sessionId => $classes): ?>

                                                <optgroup label="<?= e($sessionsMap[$sessionId]['name'] ?? 'Unknown') ?>">

                                                    <?php foreach ($classes as $c): ?>

                                                        <?php
                                                        $enrolled = (int) $c['enrolled_count'];
                                                        $capacity = (int) $c['capacity'];
                                                        $isFull   = $enrolled >= $capacity;
                                                        $label = $c['name'] . ' (' . $enrolled . '/' . $capacity . ')';
                                                        ?>

                                                        <option value="<?= (int) $c['id'] ?>">
                                                            <?= e($label) ?>
                                                            <?= $isFull ? ' — FULL' : '' ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </optgroup>

                                            <?php endforeach; ?>

                                        </select>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-check-circle fs-2 text-success"></i>
                                    <h6 class="fw-semibold mt-2">All Caught Up!</h6>
                                    <p class="text-muted small mb-0">
                                        Walang approved na batang walang class sa academic year na ito.
                                    </p>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <?php if (!empty($unassigned)): ?>

                <div class="card-footer bg-white border-top p-3">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save All Assignments
                        </button>
                    </div>
                </div>

            <?php endif; ?>

        </div>

    </form>

</div>

<?php include '../layout/footer.php'; ?>