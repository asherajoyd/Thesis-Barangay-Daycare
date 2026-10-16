<?php

$pageTitle = "Attendance History";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// CHECK TEACHER ID
// ==========================================================

if (empty($_SESSION['teacher_id'])) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";

    header("Location: ../dashboard/index.php");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


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
// FILTERS
// ==========================================================

$filterAcademicId = isset($_GET['academic_id'])
    ? (int) $_GET['academic_id']
    : 0;

if ($filterAcademicId <= 0 && $currentYear) {
    $filterAcademicId = (int) $currentYear['id'];
}

$filterClassId = isset($_GET['class_id'])
    ? (int) $_GET['class_id']
    : 0;

$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01');
$filterDateTo   = $_GET['date_to']   ?? date('Y-m-d');


// ==========================================================
// GET TEACHER'S CLASSES
// ==========================================================

$classes = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT cl.id, cl.name, s.name AS session_name
        FROM class cl
        LEFT JOIN session s ON s.id = cl.session_id
        WHERE cl.academic_id = ?
          AND cl.teacher_id = ?
        ORDER BY s.start_time ASC, cl.name ASC
    ");

    $stmt->bind_param("ii", $filterAcademicId, $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    $stmt->close();
}


// ==========================================================
// GET ATTENDANCE RECORDS
// ==========================================================

$records = [];
$summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

if ($filterClassId > 0 && $filterDateFrom && $filterDateTo) {

    // Verify class belongs to teacher
    $verifyStmt = $conn->prepare("
        SELECT id FROM class
        WHERE id = ? AND teacher_id = ?
        LIMIT 1
    ");

    $verifyStmt->bind_param("ii", $filterClassId, $teacherId);
    $verifyStmt->execute();
    $verifyStmt->store_result();

    if ($verifyStmt->num_rows === 0) {
        $verifyStmt->close();
        header("Location: history.php?academic_id=" . $filterAcademicId);
        exit();
    }

    $verifyStmt->close();


    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.date,
            a.status,
            a.remarks,

            c.first_name,
            c.middle_name,
            c.last_name

        FROM attendance a
        INNER JOIN enrollment e ON e.id = a.enrollment_id
        INNER JOIN children c ON c.id = e.child_id

        WHERE a.class_id = ?
          AND a.date BETWEEN ? AND ?

        ORDER BY a.date DESC, c.last_name ASC, c.first_name ASC
    ");

    $stmt->bind_param("iss", $filterClassId, $filterDateFrom, $filterDateTo);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
        if (isset($summary[$row['status']])) {
            $summary[$row['status']]++;
        }
    }

    $stmt->close();
}


include '../layout/header.php';

?>

<div class="attendance-history-page">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">View your class attendance records</p>
        </div>

        <a href="index.php?class_id=<?= (int) $filterClassId ?>&academic_id=<?= (int) $filterAcademicId ?>"
           class="btn btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i>
            Record Attendance
        </a>

    </div>


    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET">

                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Academic Year</label>
                        <select name="academic_id" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                            <?php
                            $yearsResult = $conn->query("
                                SELECT id, academic_year, status
                                FROM academic_year
                                ORDER BY status DESC, start_date DESC, id DESC
                            ");
                            while ($y = $yearsResult->fetch_assoc()):
                            ?>
                                <option value="<?= (int) $y['id'] ?>"
                                    <?= $filterAcademicId === (int) $y['id'] ? 'selected' : '' ?>>
                                    <?= e($y['academic_year']) ?>
                                    <?= $y['status'] === 'active' ? ' (Active)' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Class</label>
                        <select name="class_id" class="form-select form-select-sm" required>
                            <option value="">— Select class —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $filterClassId === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                    (<?= e($c['session_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               value="<?= e($filterDateFrom) ?>">
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               value="<?= e($filterDateTo) ?>">
                    </div>

                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>


    <?php if ($filterClassId > 0): ?>

        <!-- SUMMARY -->
        <div class="row g-3 mb-3">

            <div class="col-6 col-md-3">
                <div class="card border-start border-4 border-success">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase fw-bold">Present</div>
                        <div class="fs-4 fw-bold text-success"><?= (int) $summary['present'] ?></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-start border-4 border-danger">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase fw-bold">Absent</div>
                        <div class="fs-4 fw-bold text-danger"><?= (int) $summary['absent'] ?></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-start border-4 border-warning">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase fw-bold">Late</div>
                        <div class="fs-4 fw-bold text-warning"><?= (int) $summary['late'] ?></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-start border-4 border-info">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase fw-bold">Excused</div>
                        <div class="fs-4 fw-bold text-info"><?= (int) $summary['excused'] ?></div>
                    </div>
                </div>
            </div>

        </div>


        <!-- RECORDS -->
        <div class="card p-0">

            <div class="card-header bg-paper border-bottom p-3">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-list-check text-primary me-2"></i>
                        Records
                    </h6>
                    <span class="badge bg-paper-soft text-secondary border">
                        <?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <div class="table-responsive">

                <table class="table mb-0 data-table text-nowrap">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($records)): ?>

                            <?php foreach ($records as $r): ?>

                                <?php
                                $studentName = trim(
                                    $r['first_name'] . ' ' .
                                    $r['middle_name'] . ' ' .
                                    $r['last_name']
                                );

                                $statusMeta = [
                                    'present' => ['label' => 'Present',  'class' => 'bg-success'],
                                    'absent'  => ['label' => 'Absent',   'class' => 'bg-danger'],
                                    'late'    => ['label' => 'Late',     'class' => 'bg-warning text-dark'],
                                    'excused' => ['label' => 'Excused',  'class' => 'bg-info'],
                                ];

                                $meta = $statusMeta[$r['status']] ?? ['label' => 'Unknown', 'class' => 'bg-secondary'];
                                ?>

                                <tr>

                                    <td><?= e(date('M d, Y', strtotime($r['date']))) ?></td>

                                    <td class="fw-semibold"><?= e($studentName) ?></td>

                                    <td>
                                        <span class="badge <?= e($meta['class']) ?>">
                                            <?= e($meta['label']) ?>
                                        </span>
                                    </td>

                                    <td><?= e($r['remarks'] ?: '—') ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="bi bi-clipboard-x fs-2 text-muted"></i>
                                    <h6 class="fw-semibold mt-2">No Records Found</h6>
                                    <p class="text-muted small mb-0">
                                        Walang attendance records sa date range na ito.
                                    </p>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php else: ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-funnel fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2">Select a Class</h6>
                <p class="text-muted small mb-0">
                    Pumili ng class at date range para makita ang attendance history.
                </p>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>