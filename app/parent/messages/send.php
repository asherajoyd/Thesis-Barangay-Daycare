<?php

require_once '../guard.php';
include "../../../api/conn.php";

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$userId         = (int) ($_SESSION['user_id'] ?? 0);
$conversationId = (int) ($_POST['conversation_id'] ?? 0);
$message        = trim($_POST['message'] ?? '');

if ($userId <= 0 || $conversationId <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Verify user owns this conversation
$stmt = $conn->prepare("
    SELECT id FROM conversation
    WHERE id = ? AND parent_user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $conversationId, $userId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}
$stmt->close();

try {

    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO message (conversation_id, sender_user_id, message)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iis", $conversationId, $userId, $message);
    $stmt->execute();
    $messageId = $stmt->insert_id;
    $stmt->close();

    // Update conversation last_message_at
    $stmt = $conn->prepare("
        UPDATE conversation SET last_message_at = NOW() WHERE id = ?
    ");
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $stmt->close();

    // ==========================================================
    // NOTIFY TEACHER
    // ==========================================================

    require_once '../../../api/notification-helper.php';

    // Get teacher_id from conversation
    $stmt = $conn->prepare("
        SELECT teacher_id FROM conversation WHERE id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $convRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($convRow) {
        $parentName = trim(
            ($_SESSION['first_name'] ?? '') . ' ' .
            ($_SESSION['last_name'] ?? '')
        );

        notifyTeacher(
            $conn,
            (int) $convRow['teacher_id'],
            'message',
            'New message from ' . $parentName,
            mb_strimwidth($message, 0, 80, '...'),
            '../messages/'
        );
    }

    // Fetch created message
    $stmt = $conn->prepare("
        SELECT m.*, u.first_name AS sender_first, u.last_name AS sender_last
        FROM message m
        INNER JOIN users u ON u.id = m.sender_user_id
        WHERE m.id = ?
    ");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $msg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => [
            'id'              => (int) $msg['id'],
            'sender_user_id'  => (int) $msg['sender_user_id'],
            'message'         => $msg['message'],
            'created_at'      => date('M d, h:i A', strtotime($msg['created_at']))
        ]
    ]);

} catch (Exception $ex) {
    echo json_encode(['success' => false, 'error' => 'Failed to send']);
}