<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['teacher_id'])) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";
    header("Location: ../dashboard/");
    exit;
}

$teacherId = (int) $_SESSION['teacher_id'];
$id        = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Invalid record ID.";
    header("Location: index.php");
    exit;
}

try {

    // Verify ownership
    $stmt = $conn->prepare("
        SELECT hr.id
        FROM health_records hr
        INNER JOIN children c ON c.id = hr.child_id
        INNER JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
        INNER JOIN class cl ON cl.id = en.class_id
        WHERE hr.id = ? AND cl.teacher_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $id, $teacherId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        throw new Exception("Record not found or access denied.");
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM health_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['status']  = "success";
    $_SESSION['message'] = "Health record deleted.";

} catch (Exception $ex) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = $ex->getMessage();
}

header("Location: index.php");
exit;