<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $action = $_GET['action'] ?? 'deactivate';

    if (!$id || $id <= 0) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Invalid session ID.";

        header("Location: index.php");
        exit();
    }


    // Check session exists
    $stmt = $conn->prepare("
        SELECT id, name, status FROM session WHERE id = ? LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if (!$session) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Session not found.";

        header("Location: index.php");
        exit();
    }


    // Toggle status
    $newStatus = ($action === 'activate') ? 'active' : 'inactive';


    try {

        $stmt = $conn->prepare("
            UPDATE session SET status = ? WHERE id = ?
        ");

        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();
        $stmt->close();


        $_SESSION['status']  = "success";
        $_SESSION['message'] = ($newStatus === 'active')
            ? "Session activated successfully."
            : "Session deactivated successfully.";

    } catch (Exception $e) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Failed to update session.";
    }

    header("Location: index.php");
    exit();
}