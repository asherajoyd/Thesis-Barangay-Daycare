<?php

require_once '../guard.php';
include "../../../api/conn.php";

header('Content-Type: application/json');


$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$id  = (int) ($_POST['id'] ?? 0);
$all = isset($_POST['all']) && $_POST['all'] === '1';


if ($all) {

    $stmt = $conn->prepare("
        UPDATE notification SET is_read = 1 WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);

} elseif ($id > 0) {

    $stmt = $conn->prepare("
        UPDATE notification SET is_read = 1 WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false]);
}