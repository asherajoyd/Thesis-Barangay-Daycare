<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id <= 0) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Invalid activity ID.";

        header("Location: index.php");
        exit();
    }


    // Get session_id for redirect
    $stmt = $conn->prepare("
        SELECT session_id FROM routine WHERE id = ? LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $routine = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if (!$routine) {

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Activity not found.";

        header("Location: index.php");
        exit();
    }


    $sessionId = (int) $routine['session_id'];


    try {

        $conn->begin_transaction();

        // Delete the routine
        $stmt = $conn->prepare("
            DELETE FROM routine WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();


        // Re-order remaining routines (no gaps)
        $stmt = $conn->prepare("
            SELECT id FROM routine
            WHERE session_id = ?
            ORDER BY order_no ASC, id ASC
        ");
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
        $stmt->close();


        // Update order_no
        $updateStmt = $conn->prepare("
            UPDATE routine SET order_no = ? WHERE id = ?
        ");

        $order = 1;
        foreach ($ids as $rid) {
            $updateStmt->bind_param("ii", $order, $rid);
            $updateStmt->execute();
            $order++;
        }

        $updateStmt->close();

        $conn->commit();

        $_SESSION['status']  = "success";
        $_SESSION['message'] = "Activity deleted successfully.";

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Failed to delete activity.";
    }


    header("Location: index.php?session_id=" . $sessionId);
    exit();
}