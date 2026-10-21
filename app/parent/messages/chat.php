<?php

$pageTitle = "Chat";

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

$teacherId = (int) ($_GET['teacher_id'] ?? 0);
$childId   = (int) ($_GET['child_id'] ?? 0);

if ($teacherId <= 0) {
    header("Location: index.php");
    exit;
}


// ==========================================================
// VERIFY TEACHER BELONGS TO PARENT'S CHILD
// ==========================================================

$stmt = $conn->prepare("
    SELECT
        t.id, t.first_name, t.middle_name, t.last_name,
        cl.name AS class_name, s.name AS session_name
    FROM teacher t
    INNER JOIN class cl ON cl.teacher_id = t.id
    INNER JOIN enrollment en ON en.class_id = cl.id AND en.status = 'approved'
    INNER JOIN children c ON c.id = en.child_id AND c.user_id = ?
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE t.id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $userId, $teacherId);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher not found or access denied.";
    header("Location: index.php");
    exit;
}


// ==========================================================
// GET OR CREATE CONVERSATION
// ==========================================================

$stmt = $conn->prepare("
    SELECT id FROM conversation
    WHERE parent_user_id = ? AND teacher_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $userId, $teacherId);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($conv) {
    $conversationId = (int) $conv['id'];
} else {
    // Create new conversation
    $stmt = $conn->prepare("
        INSERT INTO conversation (parent_user_id, teacher_id, child_id)
        VALUES (?, ?, ?)
    ");
    $null = null;
    $stmt->bind_param("iii", $userId, $teacherId, $childId);
    $stmt->execute();
    $conversationId = $stmt->insert_id;
    $stmt->close();
}


// ==========================================================
// MARK INCOMING MESSAGES AS READ
// ==========================================================

$stmt = $conn->prepare("
    UPDATE message
    SET is_read = 1
    WHERE conversation_id = ?
      AND sender_user_id != ?
      AND is_read = 0
");

$stmt->bind_param("ii", $conversationId, $userId);
$stmt->execute();
$stmt->close();


// ==========================================================
// GET MESSAGES
// ==========================================================

$messages = [];

$stmt = $conn->prepare("
    SELECT m.*, u.first_name AS sender_first, u.last_name AS sender_last
    FROM message m
    INNER JOIN users u ON u.id = m.sender_user_id
    WHERE m.conversation_id = ?
    ORDER BY m.id ASC
");

$stmt->bind_param("i", $conversationId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();


$teacherName = trim($teacher['first_name'] . ' ' . $teacher['middle_name'] . ' ' . $teacher['last_name']);


include '../layout/header.php';

?>

<div class="chat-page">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0">
                <?= e($teacherName) ?>
            </h2>
            <small class="text-muted">
                <i class="bi bi-door-open me-1"></i>
                <?= e($teacher['class_name']) ?>
                <?php if (!empty($teacher['session_name'])): ?>
                    (<?= e($teacher['session_name']) ?>)
                <?php endif; ?>
            </small>
        </div>
        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>


    <!-- CHAT BOX -->
    <div class="card">

        <div class="card-body p-0" style="height: 500px; overflow-y: auto;" id="chatBox">

            <div class="p-3 d-flex flex-column gap-2" id="messageList">

                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-5" id="emptyState">
                        <i class="bi bi-chat-dots fs-2"></i>
                        <p class="small mb-0 mt-2">Walang messages pa. Start the conversation.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($messages as $m): ?>

                    <?php
                    $isOwn = ((int) $m['sender_user_id'] === $userId);
                    $time  = date('M d, h:i A', strtotime($m['created_at']));
                    ?>

                    <div class="d-flex <?= $isOwn ? 'justify-content-end' : 'justify-content-start' ?>">

                        <div class="message-bubble <?= $isOwn ? 'bg-primary text-white' : 'bg-light' ?> rounded-3 px-3 py-2"
                             style="max-width: 70%;">

                            <div class="text-break"><?= nl2br(e($m['message'])) ?></div>

                            <div class="small mt-1 <?= $isOwn ? 'text-white-50' : 'text-muted' ?>" style="font-size: 0.7rem;">
                                <?= e($time) ?>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- INPUT -->
        <div class="card-footer bg-white border-top p-3">

            <form id="chatForm" class="d-flex gap-2">

                <textarea
                    id="messageInput"
                    class="form-control"
                    rows="1"
                    placeholder="Type your message..."
                    required
                    style="resize: none;"></textarea>

                <button type="submit" class="btn btn-primary px-4" id="sendBtn">
                    <i class="bi bi-send"></i>
                </button>

            </form>

        </div>

    </div>

</div>


<script>
(function () {
    const conversationId = <?= (int) $conversationId ?>;
    const currentUserId  = <?= (int) $userId ?>;
    const chatBox        = document.getElementById('chatBox');
    const messageList    = document.getElementById('messageList');
    const form           = document.getElementById('chatForm');
    const input          = document.getElementById('messageInput');
    const sendBtn        = document.getElementById('sendBtn');

    let lastMessageId = <?= !empty($messages) ? (int) end($messages)['id'] : 0 ?>;

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    scrollToBottom();

    function appendMessage(msg) {
        const empty = document.getElementById('emptyState');
        if (empty) empty.remove();

        const isOwn = parseInt(msg.sender_user_id) === currentUserId;
        const div   = document.createElement('div');
        div.className = 'd-flex ' + (isOwn ? 'justify-content-end' : 'justify-content-start');

        div.innerHTML = `
            <div class="message-bubble ${isOwn ? 'bg-primary text-white' : 'bg-light'} rounded-3 px-3 py-2" style="max-width: 70%;">
                <div class="text-break">${msg.message.replace(/\n/g, '<br>')}</div>
                <div class="small mt-1 ${isOwn ? 'text-white-50' : 'text-muted'}" style="font-size: 0.7rem;">
                    ${msg.created_at}
                </div>
            </div>
        `;

        messageList.appendChild(div);
        scrollToBottom();
    }

    // Poll for new messages every 5 seconds
    setInterval(function () {
        fetch('fetch.php?conversation_id=' + conversationId + '&last_id=' + lastMessageId)
            .then(res => res.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(function (m) {
                        appendMessage(m);
                        lastMessageId = parseInt(m.id);
                    });
                }
            })
            .catch(err => console.error(err));
    }, 5000);

    // Send message
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const message = input.value.trim();
        if (!message) return;

        sendBtn.disabled = true;

        const formData = new FormData();
        formData.append('conversation_id', conversationId);
        formData.append('message', message);

        fetch('send.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    appendMessage(data.message);
                    lastMessageId = parseInt(data.message.id);
                    input.value = '';
                    input.focus();
                } else {
                    alert(data.error || 'Failed to send');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to send');
            })
            .finally(function () {
                sendBtn.disabled = false;
            });
    });

    // Auto-resize textarea
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Enter to send (Shift+Enter for new line)
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });
})();
</script>

<?php include '../layout/footer.php'; ?>