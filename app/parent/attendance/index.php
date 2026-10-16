<?php

$pageTitle = "Attendance";

 require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET PARENT'S CHILDREN
// ==========================================================

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit();
}


$children = [];

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.first_name,
        c.middle_name,
        c.last_name,
        c.gender,
        c.birthdate,

        e.id AS enrollment_id,
        e.academic_id,
        e.class_id,
        e.status AS enrollment_status,

        cl.name AS class_name,
        s.name AS session_name,
        s.start_time,
        s.end_time,

        t.first_name AS teacher_first_name,
        t.last_name AS teacher_last_name,

        ay.academic_year

    FROM children c

    LEFT JOIN enrollment e
        ON e.child_id = c.id
        AND e.status = 'approved'

    LEFT JOIN class cl
        ON cl.id = e.class_id

    LEFT JOIN session s
        ON s.id = cl.session_id

    LEFT JOIN teacher t
        ON t.id = cl.teacher_id

    LEFT JOIN academic_year ay
        ON ay.id = e.academic_id

    WHERE c.user_id = ?

    ORDER BY c.id ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

$stmt->close();


// ==========================================================
// DETERMINE SELECTED CHILD
// ==========================================================

$selectedChildId = isset($_GET['child_id'])
    ? (int) $_GET['child_id']
    : 0;

// Default sa first child
if ($selectedChildId <= 0 && !empty($children)) {
    $selectedChildId = (int) $children[0]['id'];
}


// ==========================================================
// GET SELECTED CHILD INFO
// ==========================================================

$selectedChild = null;

foreach ($children as $c) {
    if ((int) $c['id'] === $selectedChildId) {
        $selectedChild = $c;
        break;
    }
}


// ==========================================================
// DETERMINE DATE RANGE
// ==========================================================

$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01');
$filterDateTo   = $_GET['date_to']   ?? date('Y-m-d');


// ==========================================================
// GET ATTENDANCE RECORDS
// ==========================================================

$records = [];
$summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

if (
    $selectedChild &&
    !empty($selectedChild['enrollment_id']) &&
    !empty($selectedChild['class_id'])
) {

    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.date,
            a.status,
            a.remarks,

            u.first_name AS recorder_first_name,
            u.last_name AS recorder_last_name

        FROM attendance a
        LEFT JOIN users u ON u.id = a.recorded_by

        WHERE a.enrollment_id = ?
          AND a.date BETWEEN ? AND ?

        ORDER BY a.date DESC
    ");

    $stmt->bind_param(
        "iss",
        $selectedChild['enrollment_id'],
        $filterDateFrom,
        $filterDateTo
    );

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

<div class="attendance-page">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">
                View your child's attendance records
            </p>
        </div>

    </div>


    <?php if (empty($children)): ?>

        <!-- NO CHILDREN -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-exclamation fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2">No Child Record Found</h6>
                <p class="text-muted small mb-0">
                    Walang naka-link na bata sa account mo. Please contact the administrator.
                </p>
            </div>
        </div>

    <?php else: ?>

        <!-- CHILD SELECTOR + DATE FILTER -->
        <div class="card mb-3">
            <div class="card-body">

                <form method="GET">

                    <div class="row g-3 align-items-end">

                        <?php if (count($children) > 1): ?>

                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">Child</label>
                                <select name="child_id" class="form-select form-select-sm"
                                        onchange="this.form.submit()">
                                    <?php foreach ($children as $c): ?>
                                        <?php
                                        $childName = trim(
                                            $c['first_name'] . ' ' .
                                            $c['middle_name'] . ' ' .
                                            $c['last_name']
                                        );
                                        ?>
                                        <option value="<?= (int) $c['id'] ?>"
                                            <?= $selectedChildId === (int) $c['id'] ? 'selected' : '' ?>>
                                            <?= e($childName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        <?php else: ?>

                            <input type="hidden" name="child_id" value="<?= (int) $selectedChildId ?>">

                        <?php endif; ?>

                        <div class="col-12 col-md-3">
                            <label class="form-label small mb-1">From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                   value="<?= e($filterDateFrom) ?>">
                        </div>

                        <div class="col-12 col-md-3">
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


        <?php if (empty($selectedChild['class_id'])): ?>

            <!-- NO CLASS ASSIGNED -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                    <h6 class="fw-semibold mt-2">No Class Assigned Yet</h6>
                    <p class="text-muted small mb-0">
                        Ang anak mo ay hindi pa naka-assign sa isang class.
                        Please wait for the administrator to assign a class.
                    </p>
                </div>
            </div>

        <?php else: ?>

            <!-- CHILD INFO CARD -->
            <div class="card mb-3">
                <div class="card-body">

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                        <div class="d-flex align-items-center gap-3">

                            <?php
                            $childName = trim(
                                $selectedChild['first_name'] . ' ' .
                                $selectedChild['middle_name'] . ' ' .
                                $selectedChild['last_name']
                            );
                            $initial = strtoupper(substr($selectedChild['first_name'], 0, 1));
                            ?>

                            <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold fs-4 flex-shrink-0"
                                 style="width: 56px; height: 56px;">
                                <?= e($initial) ?>
                            </div>

                            <div>
                                <div class="text-primary text-uppercase fw-bold small">
                                    Attendance for
                                </div>
                                <div class="fw-bold fs-5"><?= e($childName) ?></div>
                                <small class="text-muted">
                                    <?= e($selectedChild['class_name']) ?>
                                    (<?= e($selectedChild['session_name']) ?>)
                                    <?php if (!empty($selectedChild['teacher_first_name'])): ?>
                                        •
                                        Teacher:
                                        <?= e(trim(
                                            $selectedChild['teacher_first_name'] . ' ' .
                                            $selectedChild['teacher_last_name']
                                        )) ?>
                                    <?php endif; ?>
                                </small>
                            </div>

                        </div>

                        <span class="badge bg-paper-soft text-secondary border">
                            <?= e($selectedChild['academic_year'] ?: 'No AY') ?>
                        </span>

                    </div>

                </div>
            </div>


            <!-- SUMMARY -->
            <div class="row g-3 mb-3">

                <div class="col-6 col-md-3">
                    <div class="card border-start border-4 border-success">
                        <div class="card-body py-3">
                            <div class="text-muted small text-uppercase fw-bold">Present</div>
                            <div class="fs-4 fw-bold text-success">
                                <?= (int) $summary['present'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card border-start border-4 border-danger">
                        <div class="card-body py-3">
                            <div class="text-muted small text-uppercase fw-bold">Absent</div>
                            <div class="fs-4 fw-bold text-danger">
                                <?= (int) $summary['absent'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card border-start border-4 border-warning">
                        <div class="card-body py-3">
                            <div class="text-muted small text-uppercase fw-bold">Late</div>
                            <div class="fs-4 fw-bold text-warning">
                                <?= (int) $summary['late'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card border-start border-4 border-info">
                        <div class="card-body py-3">
                            <div class="text-muted small text-uppercase fw-bold">Excused</div>
                            <div class="fs-4 fw-bold text-info">
                                <?= (int) $summary['excused'] ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <!-- RECORDS TABLE -->
            <div class="card p-0">

                <div class="card-header bg-paper border-bottom p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-list-check text-primary me-2"></i>
                            Attendance Records
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
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($records)): ?>

                                <?php foreach ($records as $r): ?>

                                    <?php
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

                                        <td>
                                            <span class="badge <?= e($meta['class']) ?>">
                                                <?= e($meta['label']) ?>
                                            </span>
                                        </td>

                                        <td><?= e($r['remarks'] ?: '—') ?></td>

                                        <td>
                                            <small class="text-muted">
                                                <?php
                                                $recorder = trim(
                                                    ($r['recorder_first_name'] ?? '') . ' ' .
                                                    ($r['recorder_last_name'] ?? '')
                                                );
                                                echo e($recorder ?: 'System');
                                                ?>
                                            </small>
                                        </td>

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

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>