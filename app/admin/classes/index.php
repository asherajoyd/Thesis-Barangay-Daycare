<?php

$pageTitle = "Classes";

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
// GET CLASSES
// ==========================================================

$classes = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.name,
            c.capacity,
            c.status,
            c.created_at,
            c.academic_id,
            c.session_id,
            c.teacher_id,

            s.name AS session_name,
            s.start_time,
            s.end_time,

            t.first_name AS teacher_first_name,
            t.middle_name AS teacher_middle_name,
            t.last_name AS teacher_last_name,

            (SELECT COUNT(*)
             FROM enrollment e
             WHERE e.class_id = c.id
               AND e.status = 'approved') AS enrolled_count

        FROM class c
        LEFT JOIN session s ON s.id = c.session_id
        LEFT JOIN teacher t ON t.id = c.teacher_id
        WHERE c.academic_id = ?
        ORDER BY s.start_time ASC, c.name ASC
    ");

    $stmt->bind_param("i", $filterAcademicId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    $stmt->close();
}


include '../layout/header.php';

?>

<div class="classes-page">

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
            <p class="text-muted small mb-0">Manage classes per session and academic year</p>
        </div>

        <a href="create.php?academic_id=<?= (int) $filterAcademicId ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add Class
        </a>

    </div>


    <!-- ACADEMIC YEAR FILTER -->
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


    <!-- CLASSES TABLE -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-door-open text-primary me-2"></i>
                    Classes
                </h6>
                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($classes) ?> record<?= count($classes) !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class Name</th>
                        <th>Session</th>
                        <th>Teacher</th>
                        <th>Capacity</th>
                        <th>Enrolled</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($classes)): ?>

                        <?php foreach ($classes as $row): ?>

                            <?php
                            $teacherName = trim(
                                ($row['teacher_first_name'] ?? '') . ' ' .
                                ($row['teacher_middle_name'] ?? '') . ' ' .
                                ($row['teacher_last_name'] ?? '')
                            ) ?: 'Not assigned';

                            $enrolled = (int) $row['enrolled_count'];
                            $capacity = (int) $row['capacity'];
                            $isFull   = $enrolled >= $capacity;
                            ?>

                            <tr>

                                <td>
                                    <span class="fw-semibold"><?= (int) $row['id'] ?></span>
                                </td>

                                <td>
                                    <i class="bi bi-door-open text-primary me-1"></i>
                                    <span class="fw-semibold"><?= e($row['name']) ?></span>
                                </td>

                                <td>
                                    <?php if (!empty($row['session_name'])): ?>
                                        <span class="badge bg-paper-soft text-secondary border">
                                            <?= e($row['session_name']) ?>
                                        </span>
                                        <small class="text-muted d-block mt-1">
                                            <?= e(date('h:i A', strtotime($row['start_time']))) ?>
                                            –
                                            <?= e(date('h:i A', strtotime($row['end_time']))) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= e($teacherName) ?></td>

                                <td><?= (int) $capacity ?></td>

                                <td>
                                    <span class="badge <?= $isFull ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $enrolled ?> / <?= $capacity ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-1">

                                        <a href="edit.php?id=<?= (int) $row['id'] ?>"
                                           class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <?php if ($row['status'] === 'active'): ?>

                                            <a href="delete.php?id=<?= (int) $row['id'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Deactivate this class?');">
                                                Deactivate
                                            </a>

                                        <?php else: ?>

                                            <a href="delete.php?id=<?= (int) $row['id'] ?>&action=activate"
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Activate this class?');">
                                                Activate
                                            </a>

                                        <?php endif; ?>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-door-open fs-2 text-muted"></i>
                                <h6 class="fw-semibold mt-2">No Classes Found</h6>
                                <p class="text-muted small mb-0">
                                    <?php if ($filterAcademicId > 0): ?>
                                        No classes available for this academic year.
                                    <?php else: ?>
                                        No classes available.
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