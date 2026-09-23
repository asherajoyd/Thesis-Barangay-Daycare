<?php
require_once '../guard.php';
include "../../../api/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = $_GET['id'];

    // Get user_id
    $sql = "SELECT user_id FROM teacher WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $teacher = $stmt->get_result()->fetch_assoc();
    $user_id = $teacher['user_id'];

    // Delete teacher
    $sql = "DELETE FROM teacher WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Delete user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $_SESSION['status'] = "success";
    $_SESSION['message'] = "Teacher Deleted Successfully!";

    header("Location: index.php");
    exit();
}
?>