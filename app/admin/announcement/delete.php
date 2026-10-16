<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id <= 0) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Invalid announcement ID.";

        header("Location: index.php");
        exit();
    }


    try {

        $stmt = $conn->prepare("
            DELETE FROM announcement WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['status']  = "success";
            $_SESSION['message'] = "Announcement deleted successfully.";

        } else {

            $_SESSION['status']  = "danger";
            $_SESSION['message'] = "Announcement not found.";
        }

        $stmt->close();

    } catch (Exception $e) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Failed to delete announcement.";
    }

    header("Location: index.php");
    exit();
}