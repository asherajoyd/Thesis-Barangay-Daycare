<?php

$pageTitle = "Notifications";

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

// Mark all as read when visiting page
$stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

// Get all notifications
$stmt = $conn->prepare("
    SELECT id, type, title, message, link, is_read, created_at
    FROM notification
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
    LIMIT 50
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();


include '../layout/header.php';

?>

<div class="notifications-page">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0"><?= e($pageTitle) ?></h2>
            <small class="text-muted">All your notifications</small>
        </div>
        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>


    <?php if (empty($notifications)): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-bell-slash fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Notifications</h6>
                <p class="text-muted small mb-0">Wala kang notifications sa ngayon.</p>
            </div>
        </div>

    <?php else: ?>

        <div class="card p-0">

            <div class="list-group list-group-flush">

                <?php foreach ($notifications as $n): ?>

                    <?php
                    $icon  = 'bi-bell';
                    $color = 'text-primary';

                    switch ($n['type']) {
                        case 'message':      $icon = 'bi-chat-dots';   $color = 'text-primary'; break;
                        case 'announcement': $icon = 'bi-megaphone';    $color = 'text-warning'; break;
                        case 'enrollment':   $icon = 'bi-person-check'; $color = 'text-success'; break;
                    }
                    ?>

                    <?php if (!empty($n['link'])): ?>
                        <a href="<?= e($n['link']) ?>" class="list-group-item list-group-item-action p-3">
                    <?php else: ?>
                        <div class="list-group-item p-3">
                    <?php endif; ?>

                        <div class="d-flex align-items-start gap-3">

                            <div class="rounded-circle bg-paper-soft d-flex align-items-center justify-content-center flex-shrink-0 <?= e($color) ?>"
                                 style="width: 40px; height: 40px;">
                                <i class="bi <?= e($icon) ?>"></i>
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-break"><?= e($n['title']) ?></div>

                                <?php if (!empty($n['message'])): ?>
                                    <div class="text-muted small text-break mt-1"><?= e($n['message']) ?></div>
                                <?php endif; ?>

                                <small class="text-muted d-block mt-1">
                                    <?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?>
                                </small>
                            </div>

                        </div>

                    <?php if (!empty($n['link'])): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>