<?php

$pageTitle = "Sessions";

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
    SELECT
        s.id,
        s.name,
        s.start_time,
        s.end_time,
        s.status,
        s.created_at,
        (SELECT COUNT(*) FROM routine r WHERE r.session_id = s.id) AS routine_count,
        (SELECT COUNT(*) FROM class c WHERE c.session_id = s.id) AS class_count
    FROM session s
    ORDER BY s.start_time ASC, s.id ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}


include '../layout/header.php';

?>

<div class="sessions-page">

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
            <p class="text-muted small mb-0">Manage daily sessions and their schedules</p>
        </div>

        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add Session
        </a>

    </div>


    <!-- SESSIONS TABLE -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>
                    Sessions
                </h6>
                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($sessions) ?> record<?= count($sessions) !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Session Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Activities</th>
                        <th>Classes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($sessions)): ?>

                        <?php foreach ($sessions as $row): ?>

                            <tr>

                                <td>
                                    <span class="fw-semibold"><?= (int) $row['id'] ?></span>
                                </td>

                                <td>
                                    <i class="bi bi-clock text-primary me-1"></i>
                                    <?= e($row['name']) ?>
                                </td>

                                <td><?= e(date('h:i A', strtotime($row['start_time']))) ?></td>

                                <td><?= e(date('h:i A', strtotime($row['end_time']))) ?></td>

                                <td>
                                    <span class="badge bg-paper-soft text-secondary border">
                                        <?= (int) $row['routine_count'] ?> activities
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-paper-soft text-secondary border">
                                        <?= (int) $row['class_count'] ?> classes
                                    </span>
                                </td>

                                <td>
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-1">

                                        <a href="edit.php?id=<?= (int) $row['id'] ?>"
                                           class="btn btn-warning btn-sm"
                                           title="Edit Session">
                                            Edit
                                        </a>

                                        <?php if ($row['status'] === 'active'): ?>

                                            <a href="delete.php?id=<?= (int) $row['id'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Deactivate this session?');"
                                               title="Deactivate">
                                                Deactivate
                                            </a>

                                        <?php else: ?>

                                            <a href="delete.php?id=<?= (int) $row['id'] ?>&action=activate"
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Activate this session?');"
                                               title="Activate">
                                                Activate
                                            </a>

                                        <?php endif; ?>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-clock-history fs-2 text-muted"></i>
                                <h6 class="fw-semibold mt-2">No Sessions Found</h6>
                                <p class="text-muted small mb-0">
                                    Start by adding your first session.
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