<?php

$pageTitle = "View Health Record";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// CHECK PARENT
// ==========================================================

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../../login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$record = null;

if ($id > 0) {

    $stmt = $conn->prepare("
        SELECT
            hr.*,
            c.first_name, c.middle_name, c.last_name,
            c.gender, c.birthdate,
            cl.name AS class_name,
            s.name AS session_name,
            u.first_name AS rec_first_name,
            u.last_name AS rec_last_name
        FROM health_records hr
        INNER JOIN children c ON c.id = hr.child_id
        LEFT JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
        LEFT JOIN class cl ON cl.id = en.class_id
        LEFT JOIN session s ON s.id = cl.session_id
        LEFT JOIN users u ON u.id = hr.recorded_by
        WHERE hr.id = ? AND c.user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$record) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Record not found or access denied.";
    header("Location: index.php");
    exit;
}


// ==========================================================
// PAGE DATA
// ==========================================================

$childName = trim($record['first_name'] . ' ' . $record['middle_name'] . ' ' . $record['last_name']);

$age = null;
if (!empty($record['birthdate'])) {
    $dob = new DateTime($record['birthdate']);
    $age = $dob->diff(new DateTime())->y;
}

$statusMeta = [
    'normal'      => ['label' => 'Normal',      'class' => 'bg-success',           'icon' => 'bi-check-circle'],
    'underweight' => ['label' => 'Underweight', 'class' => 'bg-warning text-dark', 'icon' => 'bi-arrow-down-circle'],
    'stunted'     => ['label' => 'Stunted',     'class' => 'bg-danger',            'icon' => 'bi-exclamation-triangle'],
    'overweight'  => ['label' => 'Overweight',  'class' => 'bg-info',              'icon' => 'bi-arrow-up-circle'],
];

$meta = $statusMeta[$record['status']] ?? ['label' => 'Unknown', 'class' => 'bg-secondary', 'icon' => 'bi-question'];

$dentalMeta = [
    'healthy'         => ['label' => 'Healthy',         'class' => 'bg-success',           'icon' => 'bi-emoji-smile'],
    'needs_attention' => ['label' => 'Needs Attention', 'class' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-circle'],
    'with_cavities'   => ['label' => 'With Cavities',   'class' => 'bg-danger',            'icon' => 'bi-emoji-frown'],
    'referred'        => ['label' => 'Referred',        'class' => 'bg-info',              'icon' => 'bi-arrow-right-circle'],
];

$dm = !empty($record['dental_status'])
    ? ($dentalMeta[$record['dental_status']] ?? ['label' => 'Unknown', 'class' => 'bg-secondary', 'icon' => 'bi-question'])
    : null;

$statusDesc = [
    'healthy'         => 'Malusog ang ngipin, walang nakita na problema.',
    'needs_attention' => 'May mild issue na kailangang bantayan.',
    'with_cavities'   => 'May tooth decay / cavities na kailangang alagaan.',
    'referred'        => 'Kailangan i-refer sa dentist para sa treatment.',
];

$recorder = trim(($record['rec_first_name'] ?? '') . ' ' . ($record['rec_last_name'] ?? ''));


include '../layout/header.php';

?>

<div>

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0">View Health Record</h2>
            <small class="text-muted">Record details for <?= e($childName) ?></small>
        </div>
        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>


    <!-- CHILD HEADER -->
    <div class="card mb-3">
        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                <div class="d-flex align-items-center gap-3">

                    <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold fs-4 flex-shrink-0"
                         style="width: 56px; height: 56px;">
                        <?= strtoupper(substr($record['first_name'], 0, 1)) ?>
                    </div>

                    <div>
                        <div class="text-primary text-uppercase fw-bold small">Child</div>
                        <h3 class="fw-bold fs-5 mb-0"><?= e($childName) ?></h3>
                        <small class="text-muted text-capitalize">
                            <?= e($record['gender']) ?>
                            <?php if ($age !== null): ?>
                                • <?= (int) $age ?> years old
                            <?php endif; ?>
                        </small>
                    </div>

                </div>

                <span class="badge <?= e($meta['class']) ?> px-3 py-2">
                    <i class="bi <?= e($meta['icon']) ?> me-1"></i>
                    <?= e($meta['label']) ?>
                </span>

            </div>

        </div>
    </div>


    <!-- DETAILS -->
    <div class="row g-3">

        <!-- MEASUREMENTS -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-paper border-bottom p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-activity text-primary me-2"></i>
                        Growth Measurements
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted text-uppercase small">Weight</div>
                            <div class="fs-4 fw-bold text-primary">
                                <?= !empty($record['weight']) ? e($record['weight']) : '—' ?>
                                <?php if (!empty($record['weight'])): ?><small class="text-muted fs-6">kg</small><?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted text-uppercase small">Height</div>
                            <div class="fs-4 fw-bold text-primary">
                                <?= !empty($record['height']) ? e($record['height']) : '—' ?>
                                <?php if (!empty($record['height'])): ?><small class="text-muted fs-6">cm</small><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- RECORD INFO -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-paper border-bottom p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Record Info
                    </h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-6 text-muted fw-normal">Date Recorded</dt>
                        <dd class="col-6 text-end fw-bold mb-0">
                            <?= e(date('M d, Y', strtotime($record['recorded_date']))) ?>
                        </dd>

                        <dt class="col-6 text-muted fw-normal">Recorded By</dt>
                        <dd class="col-6 text-end fw-bold mb-0">
                            <?= e($recorder ?: 'System') ?>
                        </dd>

                        <?php if (!empty($record['class_name'])): ?>
                            <dt class="col-6 text-muted fw-normal">Class</dt>
                            <dd class="col-6 text-end fw-bold mb-0">
                                <?= e($record['class_name']) ?> (<?= e($record['session_name']) ?>)
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>


        <!-- DENTAL HEALTH -->
        <?php if ($dm): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-emoji-smile text-primary me-2"></i>
                            Dental Health
                        </h6>
                    </div>
                    <div class="card-body p-4">

                        <!-- STATUS -->
                        <div class="mb-4">
                            <div class="text-muted text-uppercase small mb-2">
                                <i class="bi bi-clipboard-check me-1"></i> Oral Health Status
                            </div>
                            <div class="d-flex align-items-center gap-3 border rounded-3 bg-light p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= e($dm['class']) ?>"
                                     style="width: 48px; height: 48px;">
                                    <i class="bi <?= e($dm['icon']) ?> fs-4 text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5"><?= e($dm['label']) ?></div>
                                    <small class="text-muted">
                                        <?= e($statusDesc[$record['dental_status']] ?? '') ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- FINDINGS -->
                        <?php if (!empty($record['dental_findings'])): ?>
                            <div class="mb-3">
                                <div class="text-muted text-uppercase small mb-2">
                                    <i class="bi bi-search me-1"></i> Findings
                                </div>
                                <div class="border-start border-4 border-warning bg-light rounded-3 p-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="bi bi-clipboard-pulse text-warning fs-5"></i>
                                        <div class="text-break" >
                                            <?= e($record['dental_findings']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- TREATMENT -->
                        <?php if (!empty($record['dental_treatment'])): ?>
                            <div>
                                <div class="text-muted text-uppercase small mb-2">
                                    <i class="bi bi-prescription2 me-1"></i> Treatment / Action
                                </div>
                                <div class="border-start border-4 border-success bg-light rounded-3 p-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="bi bi-check2-circle text-success fs-5"></i>
                                        <div class="text-break" >
                                            <?= e($record['dental_treatment']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- GROWTH REMARKS -->
        <?php if (!empty($record['remarks'])): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-chat-left-text text-primary me-2"></i>
                            Growth Remarks
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="border-start border-4 border-primary bg-light rounded-3 p-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-quote text-primary fs-4"></i>
                                <div class="text-break" >
                                    <?= e($record['remarks']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php include '../layout/footer.php'; ?>