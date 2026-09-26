<?php

require_once('conn.php');


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | ENROLLMENT ACTION
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['action']) && $_POST['action'] === 'enrollment') {

        /*
        |--------------------------------------------------------------------------
        | CHILD INFORMATION
        |--------------------------------------------------------------------------
        */

        $c_first_name     = $_POST['c_first_name'] ?? '';
        $c_middle_name    = $_POST['c_middle_name'] ?? '';
        $c_last_name      = $_POST['c_last_name'] ?? '';
        $c_gender         = $_POST['c_gender'] ?? '';
        $c_birthdate      = $_POST['c_birthdate'] ?? '';
        $c_address        = $_POST['c_address'] ?? '';
        $c_place_of_birth = $_POST['c_place_of_birth'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | FATHER INFORMATION
        |--------------------------------------------------------------------------
        */

        $f_first_name  = $_POST['f_first_name'] ?? '';
        $f_middle_name = $_POST['f_middle_name'] ?? '';
        $f_last_name   = $_POST['f_last_name'] ?? '';
        $f_email       = $_POST['f_email'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | MOTHER INFORMATION
        |--------------------------------------------------------------------------
        */

        $m_first_name  = $_POST['m_first_name'] ?? '';
        $m_middle_name = $_POST['m_middle_name'] ?? '';
        $m_last_name   = $_POST['m_last_name'] ?? '';
        $m_email       = $_POST['m_email'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | GUARDIAN INFORMATION
        |--------------------------------------------------------------------------
        */

        $g_first_name  = $_POST['g_first_name'] ?? '';
        $g_middle_name = $_POST['g_middle_name'] ?? '';
        $g_last_name   = $_POST['g_last_name'] ?? '';
        $g_email       = $_POST['g_email'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($c_first_name) ||
            empty($c_last_name) ||
            empty($c_gender) ||
            empty($c_birthdate) ||
            empty($c_address) ||
            empty($c_place_of_birth)
        ) {

            echo json_encode([
                'status'  => 'error',
                'message' => 'Please complete all required child information.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | GET ACTIVE ACADEMIC YEAR
        |--------------------------------------------------------------------------
        */

        $academic_id = null;

        $academicQuery = $conn->query("
            SELECT id
            FROM academicyear
            WHERE status = 1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($academicQuery && $academicQuery->num_rows > 0) {

            $academicRow = $academicQuery->fetch_assoc();

            $academic_id = (int) $academicRow['id'];
        }


        if (!$academic_id) {

            echo json_encode([
                'status'  => 'error',
                'message' => 'No active academic year. Please contact the administrator.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT ENROLLMENT
        |--------------------------------------------------------------------------
        */

        $sql = "INSERT INTO enrollment (
                    academic_id,
                    c_first_name, c_middle_name, c_last_name, c_gender, c_birthdate, c_address, c_place_of_birth,
                    f_first_name, f_middle_name, f_last_name, f_email,
                    m_first_name, m_middle_name, m_last_name, m_email,
                    g_first_name, g_middle_name, g_last_name, g_email,
                    status
                ) VALUES (
                    ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?
                )";

        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to prepare enrollment request.'
            ]);

            exit;
        }


        $status = 0;


        /*
        |--------------------------------------------------------------------------
        | BIND PARAMETERS
        |--------------------------------------------------------------------------
        |
        | academic_id  →  i
        | everything else →  s
        |
        */

        $stmt->bind_param(
            "isssssssssssssssssssi",

            $academic_id,

            $c_first_name,
            $c_middle_name,
            $c_last_name,
            $c_gender,
            $c_birthdate,
            $c_address,
            $c_place_of_birth,

            $f_first_name,
            $f_middle_name,
            $f_last_name,
            $f_email,

            $m_first_name,
            $m_middle_name,
            $m_last_name,
            $m_email,

            $g_first_name,
            $g_middle_name,
            $g_last_name,
            $g_email,

            $status
        );


        /*
        |--------------------------------------------------------------------------
        | EXECUTE
        |--------------------------------------------------------------------------
        */

        if ($stmt->execute()) {

            echo json_encode([
                'status'  => 'success',
                'message' => 'Enrollment application submitted successfully.'
            ]);

        } else {

            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to submit enrollment application.'
            ]);
        }


        $stmt->close();
        $conn->close();
    }
}