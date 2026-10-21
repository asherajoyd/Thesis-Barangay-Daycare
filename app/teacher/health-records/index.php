<?php

$pageTitle = "Health Records";

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
    header("Location: ../dashboard/");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// GET TEACHER'S CLASSES (for filter)
// ==========================================================

$classes = [];

$stmt = $conn->prepare("
    SELECT
        cl.id,
        cl.name,
        s.name AS session_name
    FROM class cl
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE cl.teacher_id = ?
      AND cl.status = 'active'
    ORDER BY s.start_time ASC, cl.name ASC
");

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

$stmt->close();


// ==========================================================
// DETERMINE FILTERS
// ==========================================================

$filterClassId = isset($_GET['class_id'])  ? (int) $_GET['class_id']  : 0;
$filterStatus  = $_GET['status'] ?? '';
$filterFrom    = $_GET['date_from'] ?? '';
$filterTo      = $_GET['date_to']   ?? '';


// ==========================================================
// BUILD QUERY
// ==========================================================

$sql = "
    SELECT
        hr.*,
        c.first_name,
        c.middle_name,
        c.last_name,
        c.gender,
        c.birthdate,
        cl.name AS class_name,
        s.name AS session_name,
        u.first_name AS recorder_first_name,
        u.last_name AS recorder_last_name
    FROM health_records hr
    INNER JOIN children c ON c.id = hr.child_id
    LEFT JOIN enrollment en
        ON en.child_id = c.id
        AND en.status = 'approved'
    LEFT JOIN class cl ON cl.id = en.class_id
    LEFT JOIN session s ON s.id = cl.session_id
    LEFT JOIN users u ON u.id = hr.recorded_by
    WHERE cl.teacher_id = ?
";

$params = [$teacherId];
$types  = 'i';

if ($filterClassId > 0) {
    $sql .= " AND cl.id = ?";
    $params[] = $filterClassId;
    $types .= 'i';
}

if (in_array($filterStatus, ['normal', 'underweight', 'stunted', 'overweight'], true)) {
    $sql .= " AND hr.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if (!empty($filterFrom)) {
    $sql .= " AND hr.recorded_date >= ?";
    $params[] = $filterFrom;
    $types .= 's';
}

if (!empty($filterTo)) {
    $sql .= " AND hr.recorded_date <= ?";
    $params[] = $filterTo;
    $types .= 's';
}

$sql .= " ORDER BY hr.recorded_date DESC, hr.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$records = [];

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

$stmt->close();


// ==========================================================
// SUMMARY COUNTS
// ==========================================================

$summary = ['normal' => 0, 'underweight' => 0, 'stunted' => 0, 'overweight' => 0];

foreach ($records as $r) {
    if (isset($summary[$r['status']])) {
        $summary[$r['status']]++;
    }
}


include '../layout/header.php';

?>

<div class="health-records-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['status'], $_SESSION['message']); ?>
    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">Monitor children's growth and dental health</p>
        </div>

        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add Record
        </a>

    </div>


    <!-- SUMMARY CARDS -->
    <div class="row g-2 mb-3">

        <div class="col-6 col-md-3">
            <div class="card border-start border-4 border-success">
                <div class="card-body py-2 px-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Normal</div>
                    <div class="fs-4 fw-bold text-success"><?= (int) $summary['normal'] ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-start border-4 border-warning">
                <div class="card-body py-2 px-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Underweight</div>
                    <div class="fs-4 fw-bold text-warning"><?= (int) $summary['underweight'] ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-start border-4 border-danger">
                <div class="card-body py-2 px-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Stunted</div>
                    <div class="fs-4 fw-bold text-danger"><?= (int) $summary['stunted'] ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-start border-4 border-info">
                <div class="card-body py-2 px-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Overweight</div>
                    <div class="fs-4 fw-bold text-info"><?= (int) $summary['overweight'] ?></div>
                </div>
            </div>
        </div>

    </div>


    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET">

                <div class="row g-2 align-items-end">

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Class</label>
                        <select name="class_id" class="form-select form-select-sm">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $filterClassId === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?> (<?= e($c['session_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="normal"      <?= $filterStatus === 'normal'      ? 'selected' : '' ?>>Normal</option>
                            <option value="underweight" <?= $filterStatus === 'underweight' ? 'selected' : '' ?>>Underweight</option>
                            <option value="stunted"     <?= $filterStatus === 'stunted'     ? 'selected' : '' ?>>Stunted</option>
                            <option value="overweight"  <?= $filterStatus === 'overweight'  ? 'selected' : '' ?>>Overweight</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               value="<?= e($filterFrom) ?>">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               value="<?= e($filterTo) ?>">
                    </div>

                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        <a href="index.php" class="btn btn-light border btn-sm flex-fill">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </a>
                    </div>

                </div>

            </form>

        </div>
    </div>


    <!-- RECORDS TABLE -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-heart-pulse text-primary me-2"></i>
                    Health Records
                </h6>
                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap align-middle">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Child</th>
                        <th>Class</th>
                        <th>Weight</th>
                        <th>Height</th>
                        <th>Status</th>
                        <th>Dental</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($records)): ?>

                        <?php foreach ($records as $r): ?>

                            <?php
                            $childName = trim(
                                $r['first_name'] . ' ' .
                                $r['middle_name'] . ' ' .
                                $r['last_name']
                            );

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

                            $recorder = trim(
                                ($r['recorder_first_name'] ?? '') . ' ' .
                                ($r['recorder_last_name'] ?? '')
                            );
                            ?>

                            <tr>

                                <td>
                                    <?= e(date('M d, Y', strtotime($r['recorded_date']))) ?>
                                </td>

                                <td>
                                    <div class="fw-semibold"><?= e($childName) ?></div>
                                    <small class="text-muted text-capitalize">
                                        <?= e($r['gender']) ?>
                                    </small>
                                </td>

                                <td>
                                    <?php if (!empty($r['class_name'])): ?>
                                        <span class="badge bg-paper-soft text-secondary border">
                                            <?= e($r['class_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= !empty($r['weight']) ? e($r['weight']) . ' kg' : '—' ?>
                                </td>

                                <td>
                                    <?= !empty($r['height']) ? e($r['height']) . ' cm' : '—' ?>
                                </td>

                                <td>
                                    <span class="badge <?= e($meta['class']) ?>">
                                        <?= e($meta['label']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($dm): ?>
                                        <span class="badge <?= e($dm['class']) ?>">
                                            <i class="bi bi-emoji-smile me-1"></i>
                                            <?= e($dm['label']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <small class="text-muted"><?= e($recorder ?: '—') ?></small>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-1">

                                        <a href="view.php?id=<?= (int) $r['id'] ?>"
                                           class="btn btn-outline-primary btn-sm">
                                            View
                                        </a>

                                        <a href="edit.php?id=<?= (int) $r['id'] ?>"
                                           class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <a href="delete.php?id=<?= (int) $r['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this record?');">
                                            Delete
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-heart-pulse fs-2 text-muted"></i>
                                <h6 class="fw-semibold mt-2 mb-1">No Health Records</h6>
                                <p class="text-muted small mb-0">
                                    Walang health records sa filter na ito. Start by adding one.
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