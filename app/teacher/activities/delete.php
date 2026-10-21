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
    $_SESSION['message'] = "Invalid activity ID.";
    header("Location: index.php");
    exit;
}

try {

    $stmt = $conn->prepare("DELETE FROM activity WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $id, $teacherId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['status']  = "success";
        $_SESSION['message'] = "Activity deleted successfully.";
    } else {
        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Activity not found or access denied.";
    }

    $stmt->close();

} catch (Exception $ex) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Failed to delete activity.";
}

header("Location: index.php");
exit;