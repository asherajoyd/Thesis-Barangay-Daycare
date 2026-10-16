<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $action = $_GET['action'] ?? 'deactivate';

    if (!$id || $id <= 0) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Invalid class ID.";

        header("Location: index.php");
        exit();
    }


    // Check class exists
    $stmt = $conn->prepare("
        SELECT id, academic_id, status FROM class WHERE id = ? LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $class = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if (!$class) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Class not found.";

        header("Location: index.php");
        exit();
    }


    $academicId = (int) $class['academic_id'];
    $newStatus  = ($action === 'activate') ? 'active' : 'inactive';


    try {

        $stmt = $conn->prepare("
            UPDATE class SET status = ? WHERE id = ?
        ");

        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();
        $stmt->close();


        $_SESSION['status']  = "success";
        $_SESSION['message'] = ($newStatus === 'active')
            ? "Class activated successfully."
            : "Class deactivated successfully.";

    } catch (Exception $e) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Failed to update class.";
    }

    header("Location: index.php?academic_id=" . $academicId);
    exit();
}