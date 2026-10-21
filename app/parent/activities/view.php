<?php

$pageTitle = "View Activity";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../../login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$activity = null;

if ($id > 0) {
    $stmt = $conn->prepare("
        SELECT
            a.*,
            cl.name AS class_name,
            s.name AS session_name,
            t.first_name AS teacher_first_name,
            t.last_name AS teacher_last_name,
            c.first_name AS child_first_name,
            c.last_name AS child_last_name
        FROM activity a
        INNER JOIN class cl ON cl.id = a.class_id
        LEFT JOIN session s ON s.id = cl.session_id
        LEFT JOIN teacher t ON t.id = cl.teacher_id
        INNER JOIN enrollment en ON en.class_id = cl.id AND en.status = 'approved'
        INNER JOIN children c ON c.id = en.child_id AND c.user_id = ?
        WHERE a.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $userId, $id);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$activity) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Activity not found or access denied.";
    header("Location: index.php");
    exit;
}


$teacherName = trim(
    ($activity['teacher_first_name'] ?? '') . ' ' .
    ($activity['teacher_last_name'] ?? '')
);

$childName = trim(
    ($activity['child_first_name'] ?? '') . ' ' .
    ($activity['child_last_name'] ?? '')
);


include '../layout/header.php';

?>

<div>

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0">View Activity</h2>
            <small class="text-muted">Activity details</small>
        </div>
        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>


    <!-- ACTIVITY HERO -->
    <div class="card mb-3">
        <div class="card-body p-4">

            <div class="d-flex align-items-start gap-3 flex-wrap">

                <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width: 64px; height: 64px;">
                    <i class="bi bi-journal-text fs-3"></i>
                </div>

                <div class="flex-grow-1 min-w-0">
                    <div class="text-primary text-uppercase fw-bold small mb-1">
                        Activity
                    </div>
                    <h3 class="fw-bold fs-4 mb-2 text-break">
                        <?= e($activity['title']) ?>
                    </h3>

                    <div class="d-flex flex-wrap gap-3 small text-muted">
                        <span>
                            <i class="bi bi-calendar me-1"></i>
                            <?= e(date('F d, Y', strtotime($activity['activity_date']))) ?>
                        </span>
                        <span>
                            <i class="bi bi-door-open me-1"></i>
                            <?= e($activity['class_name']) ?>
                            <?php if (!empty($activity['session_name'])): ?>
                                (<?= e($activity['session_name']) ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </div>


    <!-- MAIN CONTENT -->
    <div class="card">

        <!-- DESCRIPTION -->
        <div class="card-header bg-paper border-bottom p-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-card-text text-primary me-2"></i>
                Description
            </h6>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($activity['description'])): ?>
                <div class="border-start border-4 border-primary bg-light rounded-3 p-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-quote text-primary fs-4"></i>
                        <div class="text-break" style="white-space: pre-line;">
                            <?= e($activity['description']) ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">No description provided.</p>
            <?php endif; ?>
        </div>


        <!-- DETAILS -->
        <div class="card-header bg-paper border-top border-bottom p-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-info-circle text-primary me-2"></i>
                Details
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 small">

                <div class="col-6 col-md-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Child</div>
                    <div class="fw-semibold"><?= e($childName) ?></div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Class</div>
                    <div class="fw-semibold">
                        <?= e($activity['class_name']) ?>
                        <?php if (!empty($activity['session_name'])): ?>
                            <br><small class="text-muted"><?= e($activity['session_name']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($teacherName): ?>
                    <div class="col-6 col-md-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Teacher</div>
                        <div class="fw-semibold"><?= e($teacherName) ?></div>
                    </div>
                <?php endif; ?>

                <div class="col-6 col-md-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Activity Date</div>
                    <div class="fw-semibold">
                        <?= e(date('M d, Y', strtotime($activity['activity_date']))) ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<?php include '../layout/footer.php'; ?>