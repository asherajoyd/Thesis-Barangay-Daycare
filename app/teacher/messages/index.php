<?php

$pageTitle = "Messages";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// CHECK TEACHER
// ==========================================================

if (empty($_SESSION['teacher_id'])) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";
    header("Location: ../dashboard/");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];
$userId    = (int) ($_SESSION['user_id'] ?? 0);


// ==========================================================
// GET PARENTS OF TEACHER'S STUDENTS
// ==========================================================

$parents = [];

$stmt = $conn->prepare("
    SELECT DISTINCT
        u.id AS parent_user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        c.id AS child_id,
        c.first_name AS child_first_name,
        c.last_name AS child_last_name,
        cl.name AS class_name
    FROM class cl
    INNER JOIN enrollment en ON en.class_id = cl.id AND en.status = 'approved'
    INNER JOIN children c ON c.id = en.child_id
    INNER JOIN users u ON u.id = c.user_id
    WHERE cl.teacher_id = ?
      AND cl.status = 'active'
    ORDER BY u.last_name ASC, u.first_name ASC
");

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $parents[] = $row;
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
        cv.parent_user_id,
        u.first_name,
        u.middle_name,
        u.last_name,

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
    INNER JOIN users u ON u.id = cv.parent_user_id
    WHERE cv.teacher_id = ?
    ORDER BY cv.last_message_at DESC, cv.id DESC
");

$stmt->bind_param("ii", $userId, $teacherId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $conversations[$row['parent_user_id']] = $row;
}

$stmt->close();


include '../layout/header.php';

?>

<div class="messages-page">

    <div class="mb-3">
        <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
        <p class="text-muted small mb-0">Chat with your students' parents</p>
    </div>


    <?php if (empty($parents)): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-chat-dots fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Parents Available</h6>
                <p class="text-muted small mb-0">
                    Walang parents sa class mo.
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

                <?php foreach ($parents as $p): ?>

                    <?php
                    $parentName = trim($p['first_name'] . ' ' . $p['middle_name'] . ' ' . $p['last_name']);
                    $childName  = trim($p['child_first_name'] . ' ' . $p['child_last_name']);
                    $conv       = $conversations[$p['parent_user_id']] ?? null;
                    $unread     = $conv ? (int) $conv['unread_count'] : 0;
                    $initial    = strtoupper(substr($p['first_name'], 0, 1));
                    ?>

                    <a href="chat.php?parent_id=<?= (int) $p['parent_user_id'] ?>&child_id=<?= (int) $p['child_id'] ?>"
                       class="list-group-item list-group-item-action p-3">

                        <div class="d-flex align-items-center gap-3">

                            <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width: 48px; height: 48px;">
                                <?= e($initial) ?>
                            </div>

                            <div class="flex-grow-1 min-w-0">

                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <div class="fw-bold text-break">
                                        <?= e($parentName) ?>
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
                                    <i class="bi bi-person-hearts me-1"></i>
                                    Parent of <strong><?= e($childName) ?></strong>
                                    • <?= e($p['class_name']) ?>
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