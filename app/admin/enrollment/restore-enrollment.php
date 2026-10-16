<?php

require_once '../guard.php';
include "../../../api/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        $_SESSION['status'] = "danger";
        $_SESSION['message'] = "Invalid enrollment ID.";
        header("Location: index.php");
        exit();
    }

    $newStatus = 'pending';

    $stmt = $conn->prepare("
        UPDATE enrollment
        SET status = ?
        WHERE id = ? AND status = 'rejected'
    ");

    $stmt->bind_param("si", $newStatus, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['status'] = "success";
    $_SESSION['message'] = "Enrollment restored successfully!";

    header("Location: index.php");
    exit();
}