<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id <= 0) {

        $_SESSION['status'] = "danger";
        $_SESSION['message'] = "Invalid teacher ID.";

        header("Location: index.php");
        exit();
    }

    // Get user_id
    $stmt = $conn->prepare("
        SELECT user_id FROM teacher WHERE id = ? LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if (!$teacher) {

        $_SESSION['status'] = "danger";
        $_SESSION['message'] = "Teacher not found.";

        header("Location: index.php");
        exit();
    }


    $user_id = (int) $teacher['user_id'];


    try {

        $conn->begin_transaction();

        // Delete user (CASCADE na sa teacher + teacher_assignment)
        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ? AND role = 'teacher'
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $_SESSION['status'] = "success";
        $_SESSION['message'] = "Teacher deleted successfully!";

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['status'] = "danger";
        $_SESSION['message'] = "Failed to delete teacher.";
    }

    header("Location: index.php");
    exit();
}