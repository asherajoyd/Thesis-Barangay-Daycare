<?php

require_once '../guard.php';
include "../../../api/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Invalid enrollment ID.";
        header("Location: index.php");
        exit();
    }

    $conn->begin_transaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 1. GET ENROLLMENT + GUARDIAN INFO
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                e.id AS enrollment_id,
                e.child_id,
                e.status,

                p.id AS guardian_id,
                p.first_name AS guardian_first_name,
                p.middle_name AS guardian_middle_name,
                p.last_name AS guardian_last_name,
                p.email AS guardian_email,
                p.contact AS guardian_contact

            FROM enrollment e
            INNER JOIN parent_info p
                ON p.child_id = e.child_id
                AND p.relationship = 'guardian'
                AND p.is_primary = 1

            WHERE e.id = ? AND e.status = 'pending'
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

        $childId         = (int) $enrollment['child_id'];
        $guardianId      = (int) $enrollment['guardian_id'];
        $guardianEmail   = $enrollment['guardian_email'];
        $guardianContact = $enrollment['guardian_contact'];

        if (empty($guardianEmail)) {
            throw new Exception("Guardian email is missing. Cannot create account.");
        }


        /*
        |--------------------------------------------------------------------------
        | 2. CHECK IF GUARDIAN EMAIL EXISTS
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id FROM users WHERE email = ? LIMIT 1
        ");

        $stmt->bind_param("s", $guardianEmail);
        $stmt->execute();
        $emailResult = $stmt->get_result();
        $existingUser = $emailResult->fetch_assoc();
        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | 3. GET OR CREATE USER ACCOUNT
        |--------------------------------------------------------------------------
        */

        if ($existingUser) {

            // Link sa existing user
            $userId = (int) $existingUser['id'];

        } else {

            // Gumawa ng bagong user account
            $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
            $role            = 'parent';
            $userStatus      = 'active';

            $stmt = $conn->prepare("
                INSERT INTO users (
                    first_name,
                    middle_name,
                    last_name,
                    email,
                    password,
                    contact,
                    role,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssssss",
                $enrollment['guardian_first_name'],
                $enrollment['guardian_middle_name'],
                $enrollment['guardian_last_name'],
                $guardianEmail,
                $defaultPassword,
                $guardianContact,
                $role,
                $userStatus
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to create guardian account: ' . $stmt->error);
            }

            $userId = $stmt->insert_id;
            $stmt->close();
        }


        /*
        |--------------------------------------------------------------------------
        | 4. LINK USER SA CHILDREN
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            UPDATE children SET user_id = ? WHERE id = ?
        ");

        $stmt->bind_param("ii", $userId, $childId);

        if (!$stmt->execute()) {
            throw new Exception('Failed to link child to guardian: ' . $stmt->error);
        }

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | 5. LINK USER SA PARENT_INFO (guardian)
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            UPDATE parent_info SET user_id = ? WHERE id = ?
        ");

        $stmt->bind_param("ii", $userId, $guardianId);

        if (!$stmt->execute()) {
            throw new Exception('Failed to link guardian info: ' . $stmt->error);
        }

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | 6. UPDATE ENROLLMENT STATUS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        $conn->commit();

        $message = "Enrollment approved successfully.";

        if (!$existingUser) {
            $message .= " Guardian account created — default password: password123";
        }

        $_SESSION['status']  = "success";
        $_SESSION['message'] = $message;

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['status']  = "danger";
        $_SESSION['message'] = $e->getMessage();
    }

    header("Location: index.php");
    exit();
}