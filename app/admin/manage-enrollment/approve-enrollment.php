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

        $sql = "SELECT 
                    g_first_name,
                    g_middle_name,
                    g_last_name,
                    g_email
                FROM enrollment
                WHERE id = ? AND status = 0";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $enrollment = $result->fetch_assoc();

        $stmt->close();

        if (!$enrollment) {
            throw new Exception("Enrollment not found or already processed.");
        }

        
        $status = 1;

        $sql = "UPDATE enrollment
                SET status = ?
                WHERE id = ? AND status = 0";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        $stmt->close();


        /*
         * Insert enrollment ID into children
         */
        $sql = "INSERT INTO children (enrollment_id)
                VALUES (?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();


        /*
         * Insert enrollment ID into parent
         */
        $sql = "INSERT INTO parent (enrollment_id)
                VALUES (?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();


        /*
         * Create parent/guardian user account
         */
        $first_name  = $enrollment['g_first_name'];
        $middle_name = $enrollment['g_middle_name'];
        $last_name   = $enrollment['g_last_name'];
        $email       = $enrollment['g_email'];

        $role = "parent";

        // Temporary password
        $temporaryPassword = "Parent@123";
        $password = password_hash($temporaryPassword, PASSWORD_DEFAULT);


        /*
         * Check if email already exists
         */
        $sql = "SELECT id FROM users WHERE email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $userResult = $stmt->get_result();
        $existingUser = $userResult->fetch_assoc();

        $stmt->close();


        if (!$existingUser) {

            $sql = "INSERT INTO users (
                        first_name,
                        middle_name,
                        last_name,
                        email,
                        password,
                        role
                    )
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssssss",
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $password,
                $role
            );

            $stmt->execute();
            $stmt->close();
        }


        /*
         * Save everything
         */
        $conn->commit();

        $_SESSION['status'] = "success";
        $_SESSION['message'] = "Student enrolled successfully.";

    } catch (Exception $e) {

        /*
         * Undo all database changes
         */
        $conn->rollback();

        $_SESSION['status'] = "danger";
        $_SESSION['message'] = "Failed to enroll student.";

    }

    header("Location: index.php");
    exit();
}
?>
