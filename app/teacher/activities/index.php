<?php

$pageTitle = "Activities";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


if (empty($_SESSION['teacher_id'])) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";
    header("Location: ../dashboard/");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// GET TEACHER'S CLASSES
// ==========================================================

$classes = [];

$stmt = $conn->prepare("
    SELECT cl.id, cl.name, s.name AS session_name
    FROM class cl
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE cl.teacher_id = ? AND cl.status = 'active'
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
// FILTERS
// ==========================================================

$filterClassId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$filterFrom    = $_GET['date_from'] ?? '';
$filterTo      = $_GET['date_to'] ?? '';


// ==========================================================
// GET ACTIVITIES
// ==========================================================

$sql = "
    SELECT
        a.*,
        cl.name AS class_name,
        s.name AS session_name
    FROM activity a
    INNER JOIN class cl ON cl.id = a.class_id
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE a.teacher_id = ?
";

$params = [$teacherId];
$types  = 'i';

if ($filterClassId > 0) {
    $sql .= " AND a.class_id = ?";
    $params[] = $filterClassId;
    $types .= 'i';
}

if (!empty($filterFrom)) {
    $sql .= " AND a.activity_date >= ?";
    $params[] = $filterFrom;
    $types .= 's';
}

if (!empty($filterTo)) {
    $sql .= " AND a.activity_date <= ?";
    $params[] = $filterTo;
    $types .= 's';
}

$sql .= " ORDER BY a.activity_date DESC, a.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$activities = [];

while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

$stmt->close();


include '../layout/header.php';

?>

<div class="activities-page">

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
            <p class="text-muted small mb-0">Manage learning activities for your classes</p>
        </div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Activity
        </a>
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

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               value="<?= e($filterFrom) ?>">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               value="<?= e($filterTo) ?>">
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
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


    <!-- ACTIVITIES LIST -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-journal-text text-primary me-2"></i>
                    Activities
                </h6>
                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($activities) ?> record<?= count($activities) !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <?php if (!empty($activities)): ?>

            <div class="card-body p-3">
                <div class="d-flex flex-column gap-2">

                    <?php foreach ($activities as $a): ?>

                        <div class="border rounded-3 p-3">

                            <div class="d-flex flex-wrap align-items-start gap-3">

                                <!-- Icon -->
                                <div class="rounded-3 bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0 border border-primary-subtle"
                                     style="width: 48px; height: 48px; font-size: 1.2rem;">
                                    <i class="bi bi-journal-text"></i>
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1 min-w-0">

                                    <div class="fw-bold text-break mb-1"><?= e($a['title']) ?></div>

                                    <?php if (!empty($a['description'])): ?>
                                        <p class="text-muted small mb-2 text-break">
                                            <?= e(mb_strimwidth($a['description'], 0, 150, '...')) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                        <span>
                                            <i class="bi bi-calendar me-1"></i>
                                            <?= e(date('M d, Y', strtotime($a['activity_date']))) ?>
                                        </span>
                                        <span class="badge bg-paper-soft text-secondary border">
                                            <i class="bi bi-door-open me-1"></i>
                                            <?= e($a['class_name']) ?>
                                            <?= !empty($a['session_name']) ? '(' . e($a['session_name']) . ')' : '' ?>
                                        </span>
                                    </div>

                                </div>

                                <!-- Actions -->
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    <a href="view.php?id=<?= (int) $a['id'] ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= (int) $a['id'] ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?= (int) $a['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this activity?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>
            </div>

        <?php else: ?>

            <div class="card-body text-center py-5">
                <i class="bi bi-journal-text fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Activities</h6>
                <p class="text-muted small mb-0">
                    Walang activities sa filter na ito. Start by adding one.
                </p>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include '../layout/footer.php'; ?>