<?php

$pageTitle = "Routines";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET ALL SESSIONS
// ==========================================================

$sessions = [];

$result = $conn->query("
    SELECT id, name, start_time, end_time, status
    FROM session
    ORDER BY start_time ASC, id ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}


// ==========================================================
// DETERMINE SELECTED SESSION
// ==========================================================

$selectedSessionId = isset($_GET['session_id'])
    ? (int) $_GET['session_id']
    : 0;

// Default sa first session kung walang pinili
if ($selectedSessionId <= 0 && !empty($sessions)) {
    $selectedSessionId = (int) $sessions[0]['id'];
}


// ==========================================================
// GET SELECTED SESSION INFO
// ==========================================================

$selectedSession = null;

foreach ($sessions as $s) {
    if ((int) $s['id'] === $selectedSessionId) {
        $selectedSession = $s;
        break;
    }
}


// ==========================================================
// GET ROUTINES FOR SELECTED SESSION
// ==========================================================

$routines = [];

if ($selectedSessionId > 0) {

    $stmt = $conn->prepare("
        SELECT *
        FROM routine
        WHERE session_id = ?
        ORDER BY order_no ASC, id ASC
    ");

    $stmt->bind_param("i", $selectedSessionId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $routines[] = $row;
    }

    $stmt->close();
}


include '../layout/header.php';

?>

<div class="routines-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">Manage daily activities per session</p>
        </div>

        <?php if ($selectedSession): ?>
            <a href="create.php?session_id=<?= (int) $selectedSessionId ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                Add Activity
            </a>
        <?php endif; ?>

    </div>


    <!-- SESSION FILTER + INFO -->
    <div class="card mb-3">
        <div class="card-body">

            <div class="row g-3 align-items-center">

                <div class="col-12 col-md-6 col-lg-8 current-year-chip">

                    <div class="icon">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <div>
                        <div class="year-label-sm">Current Session</div>
                        <div class="year-value-sm">
                            <?php if ($selectedSession): ?>
                                <?= e($selectedSession['name']) ?>
                                <small class="text-muted fw-normal ms-1">
                                    (<?= e(date('h:i A', strtotime($selectedSession['start_time']))) ?>
                                    – <?= e(date('h:i A', strtotime($selectedSession['end_time']))) ?>)
                                </small>
                            <?php else: ?>
                                <span class="text-muted">No session available</span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>


                <form class="filter-form col-12 col-md-6 col-lg-4" method="GET">
                    <label for="session_id" class="filter-label">
                        <i class="bi bi-funnel me-1"></i> Select Session
                    </label>
                    <select name="session_id" id="session_id"
                            class="form-select form-select-sm filter-select"
                            onchange="this.form.submit()">

                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"
                                <?= $selectedSessionId === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['name']) ?>
                                <?= $s['status'] === 'active' ? ' (Active)' : '' ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </form>

            </div>

        </div>
    </div>


    <!-- ROUTINE TABLE -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-list-check text-primary me-2"></i>
                    Activities
                </h6>
                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($routines) ?> activit<?= count($routines) !== 1 ? 'ies' : 'y' ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Time Slot</th>
                        <th>Duration</th>
                        <th>Category</th>
                        <th>Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($routines)): ?>

                        <?php foreach ($routines as $row): ?>

                            <tr>

                                <td>
                                    <span class="fw-bold"><?= (int) $row['order_no'] ?></span>
                                </td>

                                <td>
                                    <i class="bi bi-clock text-primary me-1"></i>
                                    <?= e(date('h:i A', strtotime($row['time_start']))) ?>
                                    –
                                    <?= e(date('h:i A', strtotime($row['time_end']))) ?>
                                </td>

                                <td>
                                    <span class="badge bg-paper-soft text-secondary border">
                                        <?= e($row['duration']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($row['category'] ?: '—') ?>
                                </td>

                                <td>
                                    <span class="fw-semibold"><?= e($row['activity']) ?></span>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-1">

                                        <a href="edit.php?id=<?= (int) $row['id'] ?>"
                                           class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <a href="delete.php?id=<?= (int) $row['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this activity?');">
                                            Delete
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-list-check fs-2 text-muted"></i>
                                <h6 class="fw-semibold mt-2">No Activities Found</h6>
                                <p class="text-muted small mb-0">
                                    <?php if ($selectedSession): ?>
                                        This session has no activities yet. Add the first one.
                                    <?php else: ?>
                                        Select a session to view its activities.
                                    <?php endif; ?>
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