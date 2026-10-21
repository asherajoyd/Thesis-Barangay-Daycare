<?php

$pageTitle = "Health Records";

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


// ==========================================================
// GET CHILDREN
// ==========================================================

$children = [];

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.first_name,
        c.middle_name,
        c.last_name,
        c.gender,
        c.birthdate
    FROM children c
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
// SELECTED CHILD
// ==========================================================

$selectedChildId = isset($_GET['child_id']) ? (int) $_GET['child_id'] : 0;

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
// GET RECORDS
// ==========================================================

$records = [];

if ($selectedChild) {
    $stmt = $conn->prepare("
        SELECT hr.*, u.first_name AS rec_first, u.last_name AS rec_last
        FROM health_records hr
        LEFT JOIN users u ON u.id = hr.recorded_by
        WHERE hr.child_id = ?
        ORDER BY hr.recorded_date DESC, hr.id DESC
    ");
    $stmt->bind_param("i", $selectedChildId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
}


include '../layout/header.php';

?>

<div class="health-records-page">

    <!-- PAGE HEADER -->
    <div class="mb-3">
        <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
        <p class="text-muted small mb-0">View your child's health records</p>
    </div>


    <?php if (empty($children)): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-exclamation fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Child Record</h6>
                <p class="text-muted small mb-0">Walang naka-link na bata sa account mo.</p>
            </div>
        </div>

    <?php else: ?>

        <!-- Child Selector -->
        <?php if (count($children) > 1): ?>
            <div class="card mb-3">
                <div class="card-body py-3 px-4">
                    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="text-muted small fw-bold mb-0 text-uppercase">
                            <i class="bi bi-person-hearts me-1"></i> Select Child
                        </label>
                        <select name="child_id" class="form-select form-select-sm" style="max-width: 300px;"
                                onchange="this.form.submit()">
                            <?php foreach ($children as $c): ?>
                                <?php $n = trim($c['first_name'] . ' ' . $c['middle_name'] . ' ' . $c['last_name']); ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $selectedChildId === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($n) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        <?php endif; ?>


        <?php if ($selectedChild): ?>

            <?php
            $cName = trim($selectedChild['first_name'] . ' ' . $selectedChild['middle_name'] . ' ' . $selectedChild['last_name']);
            ?>

            <!-- Child Summary -->
            <div class="card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold fs-4"
                             style="width: 56px; height: 56px;">
                            <?= strtoupper(substr($selectedChild['first_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="text-primary text-uppercase fw-bold small">Health Records for</div>
                            <div class="fw-bold fs-5"><?= e($cName) ?></div>
                            <small class="text-muted text-capitalize"><?= e($selectedChild['gender']) ?></small>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Records Table -->
            <div class="card p-0">

                <div class="card-header bg-paper border-bottom p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-heart-pulse text-primary me-2"></i>
                        Health Records
                        <span class="badge bg-paper-soft text-secondary border ms-2">
                            <?= count($records) ?>
                        </span>
                    </h6>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0 data-table text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>Height</th>
                                <th>Status</th>
                                <th>Dental</th>
                                <th>Remarks</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (!empty($records)): ?>

                                <?php foreach ($records as $r): ?>

                                    <?php
                                    $statusMeta = [
                                        'normal'      => ['label' => 'Normal',      'class' => 'bg-success'],
                                        'underweight' => ['label' => 'Underweight', 'class' => 'bg-warning text-dark'],
                                        'stunted'     => ['label' => 'Stunted',     'class' => 'bg-danger'],
                                        'overweight'  => ['label' => 'Overweight',  'class' => 'bg-info'],
                                    ];
                                    $meta = $statusMeta[$r['status']] ?? ['label' => 'Unknown', 'class' => 'bg-secondary'];

                                    $dentalMeta = [
                                        'healthy'         => ['label' => 'Healthy',         'class' => 'bg-success'],
                                        'needs_attention' => ['label' => 'Needs Attention', 'class' => 'bg-warning text-dark'],
                                        'with_cavities'   => ['label' => 'With Cavities',   'class' => 'bg-danger'],
                                        'referred'        => ['label' => 'Referred',        'class' => 'bg-info'],
                                    ];
                                    $dm = !empty($r['dental_status'])
                                        ? ($dentalMeta[$r['dental_status']] ?? ['label' => 'Unknown', 'class' => 'bg-secondary'])
                                        : null;

                                    $rec = trim(($r['rec_first'] ?? '') . ' ' . ($r['rec_last'] ?? ''));
                                    ?>

                                    <tr>
                                        <td><?= e(date('M d, Y', strtotime($r['recorded_date']))) ?></td>
                                        <td><?= !empty($r['weight']) ? e($r['weight']) . ' kg' : '—' ?></td>
                                        <td><?= !empty($r['height']) ? e($r['height']) . ' cm' : '—' ?></td>

                                        <td>
                                            <span class="badge <?= e($meta['class']) ?>">
                                                <?= e($meta['label']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($dm): ?>
                                                <span class="badge <?= e($dm['class']) ?>">
                                                    <?= e($dm['label']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= e($r['remarks'] ?: '—') ?></td>
                                        <td><small class="text-muted"><?= e($rec ?: '—') ?></small></td>

                                        <td>
                                            <a href="view.php?id=<?= (int) $r['id'] ?>"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye me-1"></i>
                                                View
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-heart-pulse fs-2 text-muted"></i>
                                        <h6 class="fw-semibold mt-2 mb-1">No Health Records Yet</h6>
                                        <p class="text-muted small mb-0">
                                            Wala pang health records ang anak mo. Ang teacher ang mag-a-add.
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