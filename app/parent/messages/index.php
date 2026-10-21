<?php

$pageTitle = "Messages";

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
// GET TEACHERS OF PARENT'S CHILDREN
// ==========================================================

$teachers = [];

$stmt = $conn->prepare("
    SELECT DISTINCT
        t.id AS teacher_id,
        t.first_name,
        t.middle_name,
        t.last_name,
        t.email,
        cl.name AS class_name,
        s.name AS session_name,
        c.id AS child_id,
        c.first_name AS child_first_name,
        c.last_name AS child_last_name
    FROM children c
    INNER JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
    INNER JOIN class cl ON cl.id = en.class_id
    INNER JOIN teacher t ON t.id = cl.teacher_id
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE c.user_id = ?
    ORDER BY t.last_name ASC, t.first_name ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

$stmt->close();


// ==========================================================
// GET EXISTING CONVERSATIONS
// ==========================================================

$conversations = [];

$stmt = $conn->prepare("
    SELECT
        cv.id AS conversation_id,
        cv.last_message_at,
        t.id AS teacher_id,
        t.first_name,
        t.middle_name,
        t.last_name,

        (SELECT COUNT(*)
         FROM message m
         WHERE m.conversation_id = cv.id
           AND m.sender_user_id != ?
           AND m.is_read = 0) AS unread_count,

        (SELECT m.message
         FROM message m
         WHERE m.conversation_id = cv.id
         ORDER BY m.id DESC
         LIMIT 1) AS last_message
    FROM conversation cv
    INNER JOIN teacher t ON t.id = cv.teacher_id
    WHERE cv.parent_user_id = ?
    ORDER BY cv.last_message_at DESC, cv.id DESC
");

$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $conversations[$row['teacher_id']] = $row;
}

$stmt->close();


include '../layout/header.php';

?>

<div class="messages-page">

    <!-- PAGE HEADER -->
    <div class="mb-3">
        <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
        <p class="text-muted small mb-0">Chat with your child's teacher</p>
    </div>


    <?php if (empty($teachers)): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-chat-dots fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Teachers Available</h6>
                <p class="text-muted small mb-0">
                    Walang teacher na naka-assign sa class ng anak mo.
                </p>
            </div>
        </div>

    <?php else: ?>

        <div class="card p-0">

            <div class="card-header bg-paper border-bottom p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-chat-dots text-primary me-2"></i>
                    Conversations
                </h6>
            </div>

            <div class="list-group list-group-flush">

                <?php foreach ($teachers as $t): ?>

                    <?php
                    $teacherName = trim($t['first_name'] . ' ' . $t['middle_name'] . ' ' . $t['last_name']);
                    $conv = $conversations[$t['teacher_id']] ?? null;
                    $unread = $conv ? (int) $conv['unread_count'] : 0;
                    $initial = strtoupper(substr($t['first_name'], 0, 1));
                    ?>

                    <a href="chat.php?teacher_id=<?= (int) $t['teacher_id'] ?>&child_id=<?= (int) $t['child_id'] ?>"
                       class="list-group-item list-group-item-action p-3">

                        <div class="d-flex align-items-center gap-3">

                            <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width: 48px; height: 48px;">
                                <?= e($initial) ?>
                            </div>

                            <div class="flex-grow-1 min-w-0">

                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <div class="fw-bold text-break text-capitalize">
                                        <?= e($teacherName) ?>
                                    </div>

                                    <?php if ($unread > 0): ?>
                                        <span class="badge bg-danger rounded-pill">
                                            <?= $unread ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="small text-muted text-break">
                                    <?php if ($conv && !empty($conv['last_message'])): ?>
                                        <?= e(mb_strimwidth($conv['last_message'], 0, 50, '...')) ?>
                                    <?php else: ?>
                                        <span class="fst-italic">No messages yet</span>
                                    <?php endif; ?>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-door-open me-1"></i>
                                    <?= e($t['class_name']) ?>
                                    <?php if (!empty($t['session_name'])): ?>
                                        (<?= e($t['session_name']) ?>)
                                    <?php endif; ?>
                                </small>

                            </div>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>