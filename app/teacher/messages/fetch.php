<?php

require_once '../guard.php';
include "../../../api/conn.php";

header('Content-Type: application/json');


if (empty($_SESSION['teacher_id'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$teacherId      = (int) $_SESSION['teacher_id'];
$userId         = (int) ($_SESSION['user_id'] ?? 0);
$conversationId = (int) ($_GET['conversation_id'] ?? 0);
$lastId         = (int) ($_GET['last_id'] ?? 0);

if ($conversationId <= 0) {
    echo json_encode(['messages' => []]);
    exit;
}

// Verify ownership
$stmt = $conn->prepare("
    SELECT id FROM conversation
    WHERE id = ? AND teacher_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $conversationId, $teacherId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    echo json_encode(['messages' => []]);
    exit;
}
$stmt->close();

// Get new messages
$stmt = $conn->prepare("
    SELECT m.*, u.first_name AS sender_first, u.last_name AS sender_last
    FROM message m
    INNER JOIN users u ON u.id = m.sender_user_id
    WHERE m.conversation_id = ? AND m.id > ?
    ORDER BY m.id ASC
");
$stmt->bind_param("ii", $conversationId, $lastId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id'              => (int) $row['id'],
        'sender_user_id'  => (int) $row['sender_user_id'],
        'message'         => $row['message'],
        'created_at'      => date('M d, h:i A', strtotime($row['created_at']))
    ];
}

$stmt->close();

// Mark incoming as read
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

echo json_encode(['messages' => $messages]);