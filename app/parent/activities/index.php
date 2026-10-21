<?php

$pageTitle = "Activities";

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
        en.class_id,
        cl.name AS class_name,
        s.name AS session_name
    FROM children c
    LEFT JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
    LEFT JOIN class cl ON cl.id = en.class_id
    LEFT JOIN session s ON s.id = cl.session_id
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
// GET ACTIVITIES
// ==========================================================

$activities = [];

if ($selectedChild && !empty($selectedChild['class_id'])) {

    $stmt = $conn->prepare("
        SELECT
            a.*,
            cl.name AS class_name,
            s.name AS session_name
        FROM activity a
        INNER JOIN class cl ON cl.id = a.class_id
        LEFT JOIN session s ON s.id = cl.session_id
        WHERE a.class_id = ?
        ORDER BY a.activity_date DESC, a.id DESC
    ");

    $stmt->bind_param("i", $selectedChild['class_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }

    $stmt->close();
}


include '../layout/header.php';

?>

<div class="activities-page">

    <!-- PAGE HEADER -->
    <div class="mb-3">
        <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
        <p class="text-muted small mb-0">View learning activities for your child</p>
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
                            <div class="text-primary text-uppercase fw-bold small">Activities for</div>
                            <div class="fw-bold fs-5"><?= e($cName) ?></div>
                            <small class="text-muted">
                                <?php if (!empty($selectedChild['class_name'])): ?>
                                    <?= e($selectedChild['class_name']) ?>
                                    <?php if (!empty($selectedChild['session_name'])): ?>
                                        (<?= e($selectedChild['session_name']) ?>)
                                    <?php endif; ?>
                                <?php else: ?>
                                    No class assigned yet
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>


            <?php if (empty($selectedChild['class_id'])): ?>

                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                        <h6 class="fw-semibold mt-2 mb-1">No Class Assigned Yet</h6>
                        <p class="text-muted small mb-0">
                            Hindi pa naka-assign sa class ang anak mo.
                        </p>
                    </div>
                </div>

            <?php else: ?>

                <!-- Activities List -->
                <div class="card p-0">

                    <div class="card-header bg-paper border-bottom p-3">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-journal-text text-primary me-2"></i>
                                Activities
                            </h6>
                            <span class="badge bg-paper-soft text-secondary border">
                                <?= count($activities) ?> activit<?= count($activities) !== 1 ? 'ies' : 'y' ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($activities)): ?>

                        <div class="card-body p-3">
                            <div class="d-flex flex-column gap-2">

                                <?php foreach ($activities as $a): ?>

                                    <div class="border rounded-3 p-3">

                                        <div class="d-flex flex-wrap align-items-start gap-3">

                                            <div class="rounded-3 bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0 border border-primary-subtle"
                                                 style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                <i class="bi bi-journal-text"></i>
                                            </div>

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
                                                    </span>
                                                </div>

                                            </div>

                                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                <a href="view.php?id=<?= (int) $a['id'] ?>&child_id=<?= (int) $selectedChildId ?>"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye me-1"></i> View
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
                            <h6 class="fw-semibold mt-2 mb-1">No Activities Yet</h6>
                            <p class="text-muted small mb-0">
                                Wala pang activities ang class ng anak mo.
                            </p>
                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>