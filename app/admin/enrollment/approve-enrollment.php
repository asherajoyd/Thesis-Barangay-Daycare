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

    $conn->begin_transaction();

    try {

        // Check if pending
        $stmt = $conn->prepare("
            SELECT id, child_id, academic_id
            FROM enrollment
            WHERE id = ? AND status = 'pending'
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollment = $result->fetch_assoc();
        $stmt->close();

        if (!$enrollment) {
            throw new Exception("Enrollment not found or already processed.");
        }

        // Update status to approved
        $newStatus = 'approved';

        $stmt = $conn->prepare("
            UPDATE enrollment
            SET status = ?
            WHERE id = ? AND status = 'pending'
        ");

        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to approve enrollment.");
        }

        $stmt->close();

        $conn->commit();

        $_SESSION['status'] = "success";
        $_SESSION['message'] = "Enrollment approved successfully.";

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['status'] = "danger";
        $_SESSION['message'] = $e->getMessage();
    }

    header("Location: index.php");
    exit();
}