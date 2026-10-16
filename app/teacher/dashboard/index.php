<?php

$pageTitle = "Dashboard";

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
    $_SESSION['message'] = "Teacher profile not found. Please contact the administrator.";

    header("Location: ../../logout.php");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// GET CURRENT ACTIVE ACADEMIC YEAR
// ==========================================================

$currentYear = null;

$result = $conn->query("
    SELECT id, academic_year
    FROM academic_year
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $currentYear = $result->fetch_assoc();
}


$academicId = $currentYear ? (int) $currentYear['id'] : 0;


// ==========================================================
// GET TEACHER'S CLASSES
// ==========================================================

$classes = [];

if ($academicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            cl.id,
            cl.name,
            cl.capacity,
            cl.session_id,

            s.name AS session_name,
            s.start_time,
            s.end_time,

            (SELECT COUNT(*)
             FROM enrollment e
             WHERE e.class_id = cl.id
               AND e.status = 'approved') AS enrolled_count

        FROM class cl
        LEFT JOIN session s ON s.id = cl.session_id

        WHERE cl.teacher_id = ?
          AND cl.academic_id = ?
          AND cl.status = 'active'

        ORDER BY s.start_time ASC, cl.name ASC
    ");

    $stmt->bind_param("ii", $teacherId, $academicId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    $stmt->close();
}


// ==========================================================
// COMPUTE STATS
// ==========================================================

$totalClasses  = count($classes);
$totalStudents = 0;
$classIds      = [];

foreach ($classes as $c) {
    $totalStudents += (int) $c['enrolled_count'];
    $classIds[] = (int) $c['id'];
}


// ==========================================================
// TODAY'S ATTENDANCE SUMMARY
// ==========================================================

$today = date('Y-m-d');

$todaySummary = [
    'present' => 0,
    'absent'  => 0,
    'late'    => 0,
    'excused' => 0,
    'total'   => 0,
];

if (!empty($classIds)) {

    $placeholders = implode(',', array_fill(0, count($classIds), '?'));
    $types = str_repeat('i', count($classIds));

    $sql = "
        SELECT status, COUNT(*) AS count
        FROM attendance
        WHERE class_id IN ($placeholders)
          AND date = ?
        GROUP BY status
    ";

    $stmt = $conn->prepare($sql);

    // Build params
    $params = $classIds;
    $params[] = $today;

    $bindTypes = $types . 's';

    $stmt->bind_param($bindTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        $count  = (int) $row['count'];

        if (isset($todaySummary[$status])) {
            $todaySummary[$status] = $count;
            $todaySummary['total'] += $count;
        }
    }

    $stmt->close();
}


include '../layout/header.php';

?>

<div class="teacher-dashboard">

    <!-- =====================================================
         WELCOME HEADER
    ====================================================== -->

    <div class="card mb-3">
        <div class="card-body py-3 px-4">

            <div class="d-flex align-items-center gap-3">

                <div class="rounded-circle bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                     style="width: 44px; height: 44px; font-size: 1.1rem;">
                    <?= strtoupper(substr($_SESSION['first_name'] ?? 'T', 0, 1)) ?>
                </div>

                <div class="min-w-0">
                    <h5 class="fw-bold mb-0">
                        Welcome back, <?= e($_SESSION['first_name'] ?? 'Teacher') ?>!
                    </h5>
                    <small class="text-muted">
                        Here's your class overview.
                        <?php if ($currentYear): ?>
                            • AY <?= e($currentYear['academic_year']) ?>
                        <?php endif; ?>
                    </small>
                </div>

            </div>

        </div>
    </div>


    <?php if (empty($classes)): ?>

        <!-- NO CLASSES -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Classes Assigned</h6>
                <p class="text-muted small mb-0">
                    Wala kang naka-assign na class sa academic year na ito.
                    Please contact the administrator.
                </p>
            </div>
        </div>

    <?php else: ?>

        <!-- =====================================================
             STATS CARDS
        ====================================================== -->

        <div class="row g-3 mb-3">

            <!-- Total Classes -->
            <div class="col-6 col-md-3">
                <div class="card h-100 border-start border-4 border-primary">
                    <div class="card-body py-3">

                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            My Classes
                        </div>

                        <div class="d-flex align-items-end justify-content-between">
                            <div class="fs-3 fw-bold text-primary">
                                <?= (int) $totalClasses ?>
                            </div>
                            <i class="bi bi-door-open text-primary fs-4 opacity-50"></i>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Total Students -->
            <div class="col-6 col-md-3">
                <div class="card h-100 border-start border-4 border-info">
                    <div class="card-body py-3">

                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            My Students
                        </div>

                        <div class="d-flex align-items-end justify-content-between">
                            <div class="fs-3 fw-bold text-info">
                                <?= (int) $totalStudents ?>
                            </div>
                            <i class="bi bi-people text-info fs-4 opacity-50"></i>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Present Today -->
            <div class="col-6 col-md-3">
                <div class="card h-100 border-start border-4 border-success">
                    <div class="card-body py-3">

                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            Present Today
                        </div>

                        <div class="d-flex align-items-end justify-content-between">
                            <div class="fs-3 fw-bold text-success">
                                <?= (int) $todaySummary['present'] ?>
                            </div>
                            <i class="bi bi-check-circle text-success fs-4 opacity-50"></i>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Absent Today -->
            <div class="col-6 col-md-3">
                <div class="card h-100 border-start border-4 border-danger">
                    <div class="card-body py-3">

                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            Absent Today
                        </div>

                        <div class="d-flex align-items-end justify-content-between">
                            <div class="fs-3 fw-bold text-danger">
                                <?= (int) $todaySummary['absent'] ?>
                            </div>
                            <i class="bi bi-x-circle text-danger fs-4 opacity-50"></i>
                        </div>

                    </div>
                </div>
            </div>

        </div>


        <!-- =====================================================
             CLASS LIST
        ====================================================== -->

        <div class="card">

            <div class="card-header bg-paper border-bottom p-3">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-door-open text-primary me-2"></i>
                        My Classes
                    </h6>
                    <span class="badge bg-paper-soft text-secondary border">
                        <?= count($classes) ?> class<?= count($classes) !== 1 ? 'es' : '' ?>
                    </span>
                </div>
            </div>

            <div class="table-responsive">

                <table class="table mb-0 data-table text-nowrap">

                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Session</th>
                            <th>Schedule</th>
                            <th>Students</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($classes as $c): ?>

                            <?php
                            $enrolled = (int) $c['enrolled_count'];
                            $capacity = (int) $c['capacity'];
                            $isFull   = $enrolled >= $capacity;
                            ?>

                            <tr>

                                <td>
                                    <i class="bi bi-door-open text-primary me-1"></i>
                                    <span class="fw-semibold"><?= e($c['name']) ?></span>
                                </td>

                                <td>
                                    <span class="badge bg-paper-soft text-secondary border">
                                        <?= e($c['session_name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <small class="text-muted">
                                        <?= e(date('h:i A', strtotime($c['start_time']))) ?>
                                        –
                                        <?= e(date('h:i A', strtotime($c['end_time']))) ?>
                                    </small>
                                </td>

                                <td>
                                    <span class="badge <?= $isFull ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $enrolled ?> / <?= $capacity ?>
                                    </span>
                                </td>

                                <td>
                                    <?= (int) $capacity ?>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-1">

                                        <a href="../attendance/?class_id=<?= (int) $c['id'] ?>"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            Attendance
                                        </a>

                                        <a href="../attendance/history.php?class_id=<?= (int) $c['id'] ?>"
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-clock-history me-1"></i>
                                            History
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>