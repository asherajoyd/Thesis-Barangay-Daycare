<?php

$pageTitle = "Dashboard";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET PARENT INFO
// ==========================================================

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../../login.php");
    exit();
}


// ==========================================================
// GET PARENT'S CHILDREN
// ==========================================================

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

if ($selectedChildId <= 0 && !empty($children)) {
    $selectedChildId = (int) $children[0]['id'];
}

$selectedChild = null;

foreach ($children as $c) {
    if ((int) $c['id'] === $selectedChildId) {
        $selectedChild = $c;
        break;
    }
}


// ==========================================================
// GET ATTENDANCE SUMMARY FOR SELECTED CHILD (THIS MONTH)
// ==========================================================

$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

$summary = [
    'present' => 0,
    'absent'  => 0,
    'late'    => 0,
    'excused' => 0,
    'total'   => 0,
];

if ($selectedChild && !empty($selectedChild['enrollment_id'])) {

    $enrollmentId = (int) $selectedChild['enrollment_id'];

    $summaryStmt = $conn->prepare("
        SELECT status, COUNT(*) AS count
        FROM attendance
        WHERE enrollment_id = ?
          AND date BETWEEN ? AND ?
        GROUP BY status
    ");

    $summaryStmt->bind_param("iss", $enrollmentId, $monthStart, $monthEnd);
    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();

    while ($row = $summaryResult->fetch_assoc()) {
        $status = $row['status'];
        $count  = (int) $row['count'];

        if (isset($summary[$status])) {
            $summary[$status] = $count;
            $summary['total'] += $count;
        }
    }

    $summaryStmt->close();
}


include '../layout/header.php';

?>

<div class="parent-dashboard">

    <!-- =====================================================
         WELCOME HEADER (compact)
    ====================================================== -->

    <div class="card mb-3">
        <div class="card-body py-3 px-4">

            <div class="d-flex align-items-center gap-3">

                <div class="rounded-circle bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                     style="width: 44px; height: 44px; font-size: 1.1rem;">
                    <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)) ?>
                </div>

                <div class="min-w-0">
                    <h5 class="fw-bold mb-0">
                        Welcome back, <?= e($_SESSION['first_name'] ?? 'Parent') ?>!
                    </h5>
                    <small class="text-muted">
                        Here's what's happening with your child today.
                    </small>
                </div>

            </div>

        </div>
    </div>


    <?php if (empty($children)): ?>

        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-exclamation fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Child Record Found</h6>
                <p class="text-muted small mb-0">
                    Walang naka-link na bata sa account mo.
                </p>
            </div>
        </div>

    <?php else: ?>

        <!-- =====================================================
             CHILD SELECTOR (dropdown kung 2+)
        ====================================================== -->

        <?php if (count($children) > 1): ?>

            <div class="card mb-3">
                <div class="card-body py-3 px-4">

                    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">

                        <label for="child_id" class="text-muted small fw-bold mb-0 text-uppercase">
                            <i class="bi bi-person-hearts me-1"></i>
                            Select Child
                        </label>

                        <select name="child_id"
                                id="child_id"
                                class="form-select form-select-sm"
                                style="max-width: 300px;"
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

                    </form>

                </div>
            </div>

        <?php endif; ?>


        <!-- =====================================================
             SELECTED CHILD CARD
        ====================================================== -->

        <?php if ($selectedChild): ?>

            <?php
            $childName = trim(
                $selectedChild['first_name'] . ' ' .
                $selectedChild['middle_name'] . ' ' .
                $selectedChild['last_name']
            );

            $initial = strtoupper(substr($selectedChild['first_name'], 0, 1));

            $hasClass = !empty($selectedChild['class_id']);
            ?>

            <div class="card">

                <div class="card-body p-3">

                    <!-- Header row -->
                    <div class="d-flex align-items-center gap-3 mb-3">

                        <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width: 48px; height: 48px; font-size: 1.1rem;">
                            <?= e($initial) ?>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold text-break">
                                <?= e($childName) ?>
                            </div>
                            <small class="text-muted text-capitalize">
                                <?= e($selectedChild['gender']) ?>
                                <?php if (!empty($selectedChild['birthdate'])): ?>
                                    • <?= e(date('M d, Y', strtotime($selectedChild['birthdate']))) ?>
                                <?php endif; ?>
                            </small>
                        </div>

                        <?php if ($hasClass): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0">
                                Enrolled
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle flex-shrink-0">
                                No Class
                            </span>
                        <?php endif; ?>

                    </div>


                    <?php if ($hasClass): ?>

                        <!-- Class info (compact) -->
                        <div class="row g-2 small mb-3">

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Class</div>
                                <div class="fw-semibold"><?= e($selectedChild['class_name']) ?></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Session</div>
                                <div class="fw-semibold"><?= e($selectedChild['session_name']) ?></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Schedule</div>
                                <div class="fw-semibold">
                                    <?= e(date('h:i A', strtotime($selectedChild['start_time']))) ?>
                                    –
                                    <?= e(date('h:i A', strtotime($selectedChild['end_time']))) ?>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Teacher</div>
                                <div class="fw-semibold text-truncate">
                                    <?= e(trim(
                                        ($selectedChild['teacher_first_name'] ?? '') . ' ' .
                                        ($selectedChild['teacher_last_name'] ?? '')
                                    ) ?: 'Not assigned') ?>
                                </div>
                            </div>

                        </div>


                        <!-- Attendance summary -->
                        <?php if ($summary['total'] > 0): ?>

                            <div class="border-top pt-3">

                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                                        Attendance (<?= e(date('F Y')) ?>)
                                    </small>
                                    <small class="text-muted">
                                        <?= (int) $summary['total'] ?> days recorded
                                    </small>
                                </div>

                                <div class="d-flex flex-wrap gap-2">

                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i>
                                        <?= (int) $summary['present'] ?> Present
                                    </span>

                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-x-circle me-1"></i>
                                        <?= (int) $summary['absent'] ?> Absent
                                    </span>

                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= (int) $summary['late'] ?> Late
                                    </span>

                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?= (int) $summary['excused'] ?> Excused
                                    </span>

                                </div>

                            </div>

                        <?php else: ?>

                            <div class="border-top pt-3 text-center text-muted small">
                                No attendance records for this month yet.
                            </div>

                        <?php endif; ?>

                    <?php else: ?>

                        <div class="alert alert-warning py-2 px-3 small mb-0">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Waiting for class assignment from admin.
                        </div>

                    <?php endif; ?>

                </div>

                <!-- Footer: quick actions -->
                <div class="card-footer bg-white border-top px-3 py-2">
                    <div class="d-flex gap-2">
                        <a href="../attendance/?child_id=<?= (int) $selectedChild['id'] ?>"
                           class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-calendar-check me-1"></i>
                            Attendance
                        </a>
                        <a href="../activities/?child_id=<?= (int) $selectedChild['id'] ?>"
                           class="btn btn-sm btn-outline-secondary flex-fill">
                            <i class="bi bi-journal-text me-1"></i>
                            Activities
                        </a>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>