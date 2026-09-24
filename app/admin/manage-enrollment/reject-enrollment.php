<?php
require_once '../guard.php';
include "../../../api/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = $_GET['id'];

    $status = 2;

   // Update users
    $sql = "UPDATE enrollment
            SET status = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ii",
        $status,
        $id
    );

    $stmt->execute();
    $stmt->close();

    $_SESSION['status'] = "success";
    $_SESSION['message'] = "Enrollment Rejected!";

    header("Location: index.php");
    exit();
}
?>