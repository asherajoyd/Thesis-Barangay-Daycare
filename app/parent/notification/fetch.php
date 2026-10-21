<?php

require_once '../guard.php';
include "../../../api/conn.php";

header('Content-Type: application/json');


$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['unread' => 0]);
    exit;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notification
    WHERE user_id = ? AND is_read = 0
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'unread' => (int) ($result['total'] ?? 0)
]);