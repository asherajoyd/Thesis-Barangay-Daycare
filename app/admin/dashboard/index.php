<?php

$pageTitle = "Dashboard";

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
    SELECT id, academic_year, start_date, end_date
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
// STATS: TOTAL CHILDREN
// ==========================================================

$totalChildren = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total FROM children
");

if ($result) {
    $totalChildren = (int) $result->fetch_assoc()['total'];
}


// ==========================================================
// STATS: TOTAL PARENTS
// ==========================================================

$totalParents = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'parent'
      AND status = 'active'
");

if ($result) {
    $totalParents = (int) $result->fetch_assoc()['total'];
}


// ==========================================================
// STATS: TOTAL TEACHERS
// ==========================================================

$totalTeachers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total FROM teacher
");

if ($result) {
    $totalTeachers = (int) $result->fetch_assoc()['total'];
}


// ==========================================================
// STATS: PENDING ENROLLMENTS
// ==========================================================

$pendingEnrollments = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM enrollment
    WHERE status = 'pending'
");

if ($result) {
    $pendingEnrollments = (int) $result->fetch_assoc()['total'];
}


// ==========================================================
// STATS: TOTAL CLASSES (current AY)
// ==========================================================

$totalClasses = 0;

if ($academicId > 0) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM class
        WHERE academic_id = ?
          AND status = 'active'
    ");

    $stmt->bind_param("i", $academicId);
    $stmt->execute();
    $totalClasses = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}


// ==========================================================
// STATS: TOTAL SESSIONS
// ==========================================================

$totalSessions = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM session
    WHERE status = 'active'
");

if ($result) {
    $totalSessions = (int) $result->fetch_assoc()['total'];
}


// ==========================================================
// RECENT PENDING ENROLLMENTS (last 5)
// ==========================================================

$recentEnrollments = [];

$result = $conn->query("
    SELECT
        e.id,
        e.status,
        e.created_at,
        e.academic_id,

        c.first_name,
        c.middle_name,
        c.last_name,
        c.gender,

        ay.academic_year

    FROM enrollment e
    INNER JOIN children c ON c.id = e.child_id
    LEFT JOIN academic_year ay ON ay.id = e.academic_id

    WHERE e.status = 'pending'

    ORDER BY e.created_at DESC, e.id DESC

    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentEnrollments[] = $row;
    }
}


include '../layout/header.php';

?>

<div class="admin-dashboard">

    <!-- =====================================================
         WELCOME HEADER
    ====================================================== -->

    <div class="card mb-3">
        <div class="card-body py-3 px-4">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                <div class="d-flex align-items-center gap-3">

                    <div class="rounded-circle bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                         style="width: 44px; height: 44px; font-size: 1.1rem;">
                        <?= strtoupper(substr($_SESSION['first_name'] ?? 'A', 0, 1)) ?>
                    </div>

                    <div class="min-w-0">
                        <h5 class="fw-bold mb-0">
                            Welcome back, <?= e($_SESSION['first_name'] ?? 'Admin') ?>!
                        </h5>
                        <small class="text-muted">
                            Here's your system overview.
                        </small>
                    </div>

                </div>


                <?php if ($currentYear): ?>
                    <div class="d-flex align-items-center gap-2">

                        <i class="bi bi-calendar-check text-success fs-5"></i>

                        <div>
                            <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                                Active Academic Year
                            </div>
                            <div class="fw-bold">
                                <?= e($currentYear['academic_year']) ?>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>


    <!-- =====================================================
         STATS CARDS
    ====================================================== -->

    <div class="row g-3 mb-3">

        <!-- Children -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body py-3">

                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                        Children
                    </div>

                    <div class="d-flex align-items-end justify-content-between">
                        <div class="fs-3 fw-bold text-primary">
                            <?= (int) $totalChildren ?>
                        </div>
                        <i class="bi bi-person-hearts text-primary fs-4 opacity-50"></i>
                    </div>

                </div>
            </div>
        </div>

        <!-- Parents -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 border-start border-4 border-info">
                <div class="card-body py-3">

                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                        Parents
                    </div>

                    <div class="d-flex align-items-end justify-content-between">
                        <div class="fs-3 fw-bold text-info">
                            <?= (int) $totalParents ?>
                        </div>
                        <i class="bi bi-people text-info fs-4 opacity-50"></i>
                    </div>

                </div>
            </div>
        </div>

        <!-- Teachers -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 border-start border-4 border-success">
                <div class="card-body py-3">

                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                        Teachers
                    </div>

                    <div class="d-flex align-items-end justify-content-between">
                        <div class="fs-3 fw-bold text-success">
                            <?= (int) $totalTeachers ?>
                        </div>
                        <i class="bi bi-person-video3 text-success fs-4 opacity-50"></i>
                    </div>

                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 border-start border-4 border-warning">
                <div class="card-body py-3">

                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                        Pending
                    </div>

                    <div class="d-flex align-items-end justify-content-between">
                        <div class="fs-3 fw-bold text-warning">
                            <?= (int) $pendingEnrollments ?>
                        </div>
                        <i class="bi bi-hourglass-split text-warning fs-4 opacity-50"></i>
                    </div>

                </div>
            </div>
        </div>

        <!-- Classes -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 border-start border-4 border-secondary">
                <div class="card-body py-3">

                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                        Classes
                    </div>

                    <div class="d-flex align-items-end justify-content-between">
                        <div class="fs-3 fw-bold text-secondary">
                            <?= (int) $totalClasses ?>
                        </div>
                        <i class="bi bi-door-open text-secondary fs-4 opacity-50"></i>
                    </div>

                </div>
            </div>
        </div>

        <!-- Sessions -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 border-start border-4 border-dark">
                <div class="card-body py-3">

                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">
                        Sessions
                    </div>

                    <div class="d-flex align-items-end justify-content-between">
                        <div class="fs-3 fw-bold text-dark">
                            <?= (int) $totalSessions ?>
                        </div>
                        <i class="bi bi-clock-history text-dark fs-4 opacity-50"></i>
                    </div>

                </div>
            </div>
        </div>

    </div>


    <!-- =====================================================
         RECENT PENDING ENROLLMENTS
    ====================================================== -->

    <div class="card mb-3">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-hourglass-split text-warning me-2"></i>
                    Recent Pending Enrollments
                </h6>
                <a href="../enrollment/" class="btn btn-sm btn-outline-primary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <?php if (!empty($recentEnrollments)): ?>

            <div class="card-body p-3">

                <div class="d-flex flex-column gap-2">

                    <?php foreach ($recentEnrollments as $r): ?>

                        <?php
                        $childName = trim(
                            $r['first_name'] . ' ' .
                            $r['middle_name'] . ' ' .
                            $r['last_name']
                        );
                        $initial = strtoupper(substr($r['first_name'], 0, 1));
                        ?>

                        <div class="border rounded-3 p-3 d-flex flex-wrap align-items-center gap-3 hover-lift">

                            <!-- Avatar -->
                            <div class="rounded-3 bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width: 48px; height: 48px; font-size: 1.1rem;">
                                <?= e($initial) ?>
                            </div>

                            <!-- Info -->
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-break"><?= e($childName) ?></div>
                                <small class="text-muted text-capitalize">
                                    <?= e($r['gender']) ?>
                                    <span class="mx-1">•</span>
                                    Submitted <?= e(date('M d, Y', strtotime($r['created_at']))) ?>
                                    <span class="mx-1">•</span>
                                    AY <?= e($r['academic_year'] ?: '—') ?>
                                </small>
                            </div>

                            <!-- Status + Action -->
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    Pending
                                </span>
                                <a href="../enrollment/view.php?id=<?= (int) $r['id'] ?>"
                                   class="btn btn-sm btn-primary">
                                    View <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php else: ?>

            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle fs-2 text-success"></i>
                <h6 class="fw-semibold mt-2 mb-1">All Caught Up!</h6>
                <p class="text-muted small mb-0">
                    Walang pending na enrollment sa ngayon.
                </p>
            </div>

        <?php endif; ?>

    </div>


    <!-- =====================================================
         QUICK ACTIONS
    ====================================================== -->

    <div class="card">
        <div class="card-body p-4">

            <h6 class="fw-bold mb-3">
                <i class="bi bi-lightning-charge text-primary me-2"></i>
                Quick Actions
            </h6>

            <div class="row g-2">

                <div class="col-6 col-md-3">
                    <a href="../enrollment/" class="btn btn-outline-primary w-100">
                        <i class="bi bi-person-lines-fill me-1"></i>
                        Enrollments
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="../teachers/create-teacher.php" class="btn btn-outline-success w-100">
                        <i class="bi bi-person-plus me-1"></i>
                        Add Teacher
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="../classes/create.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-door-open me-1"></i>
                        Add Class
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="../attendance/" class="btn btn-outline-info w-100">
                        <i class="bi bi-calendar-check me-1"></i>
                        Attendance
                    </a>
                </div>

            </div>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>