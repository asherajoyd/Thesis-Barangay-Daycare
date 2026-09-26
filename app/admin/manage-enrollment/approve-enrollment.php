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

        /*
         * Get enrollment information
         */
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


        /*
         * Parent information
         */
        $first_name  = $enrollment['g_first_name'];
        $middle_name = $enrollment['g_middle_name'];
        $last_name   = $enrollment['g_last_name'];
        $email       = $enrollment['g_email'];

        $role = "parent";

        // Temporary password
        $temporaryPassword = "password123";
        $password = password_hash($temporaryPassword, PASSWORD_DEFAULT);


        /*
         * Check if parent email already exists
         */
        $sql = "SELECT id
                FROM users
                WHERE email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $userResult = $stmt->get_result();
        $existingUser = $userResult->fetch_assoc();

        $stmt->close();


        /*
         * Get or create parent user
         */
        if ($existingUser) {

            // Existing parent account
            $user_id = (int) $existingUser['id'];

        } else {

            // Create new parent account
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

            // Get newly created users.id
            $user_id = $conn->insert_id;

            $stmt->close();
        }


        /*
         * Approve enrollment
         */
        $status = 1;

        $sql = "UPDATE enrollment
                SET status = ?
                WHERE id = ? AND status = 0";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to approve enrollment.");
        }

        $stmt->close();


        /*
         * Insert child record
         */
        $sql = "INSERT INTO children (
                    enrollment_id,
                    user_id
                )
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();


        /*
         * Insert parent record
         */
        $sql = "INSERT INTO parent (
                    enrollment_id,
                    user_id
                )
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();


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
