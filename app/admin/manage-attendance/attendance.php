<?php

session_start();

/* =========================================================
   DATABASE CONNECTION
========================================================= */

$host = "localhost";
$username = "root";
$password = "";
$database = "barangaydaycare";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* =========================================================
   HELPER FUNCTION
========================================================= */

function clean($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   ADD ATTENDANCE
========================================================= */

if (isset($_POST['add_attendance'])) {

    $student_id = intval($_POST['student_id']);
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $reason = trim($_POST['reason']);

    /*
        Get student name from:
        children -> enrollment
    */

    $studentQuery = $conn->prepare("
        SELECT
            children.id,
            TRIM(
                CONCAT(
                    enrollment.c_first_name,
                    ' ',
                    COALESCE(enrollment.c_middle_name, ''),
                    ' ',
                    enrollment.c_last_name
                )
            ) AS student_name
        FROM children
        INNER JOIN enrollment
            ON children.enrollment_id = enrollment.id
        WHERE children.id = ?
    ");

    $studentQuery->bind_param(
        "i",
        $student_id
    );

    $studentQuery->execute();

    $studentResult = $studentQuery->get_result();

    if ($studentResult->num_rows > 0) {

        $student = $studentResult->fetch_assoc();

        $student_name = $student['student_name'];

        /*
            Check if attendance already exists
            for this student on this date.
        */

        $check = $conn->prepare("
            SELECT id
            FROM attendance
            WHERE student_id = ?
            AND attendance_date = ?
        ");

        $check->bind_param(
            "is",
            $student_id,
            $attendance_date
        );

        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $_SESSION['error'] =
                "This student already has an attendance record for this date.";

        } else {

            $insert = $conn->prepare("
                INSERT INTO attendance
                (
                    student_name,
                    student_id,
                    attendance_date,
                    status,
                    reason
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "sisss",
                $student_name,
                $student_id,
                $attendance_date,
                $status,
                $reason
            );

            if ($insert->execute()) {

                $_SESSION['success'] =
                    "Attendance added successfully.";

            } else {

                $_SESSION['error'] =
                    "Error adding attendance: " . $conn->error;
            }

            $insert->close();
        }

        $check->close();

    } else {

        $_SESSION['error'] =
            "Student not found.";
    }

    $studentQuery->close();

    header("Location: attendance.php");
    exit();
}


/* =========================================================
   UPDATE ATTENDANCE
========================================================= */

if (isset($_POST['update_attendance'])) {

    $id = intval($_POST['id']);
    $student_id = intval($_POST['student_id']);
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $reason = trim($_POST['reason']);

    /*
        Get updated student name
        from children -> enrollment
    */

    $studentQuery = $conn->prepare("
        SELECT
            children.id,
            TRIM(
                CONCAT(
                    enrollment.c_first_name,
                    ' ',
                    COALESCE(enrollment.c_middle_name, ''),
                    ' ',
                    enrollment.c_last_name
                )
            ) AS student_name
        FROM children
        INNER JOIN enrollment
            ON children.enrollment_id = enrollment.id
        WHERE children.id = ?
    ");

    $studentQuery->bind_param(
        "i",
        $student_id
    );

    $studentQuery->execute();

    $studentResult = $studentQuery->get_result();

    if ($studentResult->num_rows > 0) {

        $student = $studentResult->fetch_assoc();

        $student_name = $student['student_name'];

        /*
            Check duplicate date/student
            but ignore the current record.
        */

        $check = $conn->prepare("
            SELECT id
            FROM attendance
            WHERE student_id = ?
            AND attendance_date = ?
            AND id != ?
        ");

        $check->bind_param(
            "isi",
            $student_id,
            $attendance_date,
            $id
        );

        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $_SESSION['error'] =
                "This student already has an attendance record for this date.";

        } else {

            $update = $conn->prepare("
                UPDATE attendance
                SET
                    student_name = ?,
                    student_id = ?,
                    attendance_date = ?,
                    status = ?,
                    reason = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "sisssi",
                $student_name,
                $student_id,
                $attendance_date,
                $status,
                $reason,
                $id
            );

            if ($update->execute()) {

                $_SESSION['success'] =
                    "Attendance updated successfully.";

            } else {

                $_SESSION['error'] =
                    "Error updating attendance: " . $conn->error;
            }

            $update->close();
        }

        $check->close();

    } else {

        $_SESSION['error'] =
            "Student not found.";
    }

    $studentQuery->close();

    header("Location: attendance.php");
    exit();
}


/* =========================================================
   DELETE ATTENDANCE
========================================================= */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $delete = $conn->prepare("
        DELETE FROM attendance
        WHERE id = ?
    ");

    $delete->bind_param(
        "i",
        $id
    );

    if ($delete->execute()) {

        $_SESSION['success'] =
            "Attendance deleted successfully.";

    } else {

        $_SESSION['error'] =
            "Error deleting attendance: " . $conn->error;
    }

    $delete->close();

    header("Location: attendance.php");
    exit();
}


/* =========================================================
   GET STUDENTS
========================================================= */

$students = $conn->query("
    SELECT
        children.id,
        TRIM(
            CONCAT(
                enrollment.c_first_name,
                ' ',
                COALESCE(enrollment.c_middle_name, ''),
                ' ',
                enrollment.c_last_name
            )
        ) AS student_name
    FROM children
    INNER JOIN enrollment
        ON children.enrollment_id = enrollment.id
    ORDER BY
        enrollment.c_last_name ASC,
        enrollment.c_first_name ASC
");


/* =========================================================
   GET ATTENDANCE RECORDS
========================================================= */

$attendance = $conn->query("
    SELECT
        id,
        student_id,
        student_name,
        attendance_date,
        status,
        reason
    FROM attendance
    ORDER BY
        attendance_date DESC,
        student_name ASC
");


/* =========================================================
   GET RECORD FOR EDIT
========================================================= */

$editRecord = null;

if (isset($_GET['edit'])) {

    $edit_id = intval($_GET['edit']);

    $editQuery = $conn->prepare("
        SELECT
            id,
            student_id,
            student_name,
            attendance_date,
            status,
            reason
        FROM attendance
        WHERE id = ?
    ");

    $editQuery->bind_param(
        "i",
        $edit_id
    );

    $editQuery->execute();

    $editResult = $editQuery->get_result();

    if ($editResult->num_rows > 0) {

        $editRecord = $editResult->fetch_assoc();
    }

    $editQuery->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Attendance Management</title>


    <!-- Bootstrap CSS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        :root {

            --primary: #176b3a;
            --primary-dark: #0f542c;
            --primary-light: #e7f0eb;

            --text: #17221c;
            --muted: #6f7b74;

            --border: #e1e7e3;

            --background: #f5f8f6;

            --white: #ffffff;
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background-color: var(--background);

            color: var(--text);

            font-family: Arial, sans-serif;
        }


        /* =====================================================
           DASHBOARD WRAPPER
        ====================================================== */

        .dashboard-wrapper {

            min-height: 100vh;

            display: flex;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .dashboard-sidebar {

            width: 278px;

            min-height: 100vh;

            background-color: var(--white);

            border-right: 1px solid var(--border);

            display: flex;

            flex-direction: column;

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            z-index: 1000;
        }


        /* =====================================================
           SIDEBAR BRAND
        ====================================================== */

        .sidebar-brand {

            height: 76px;

            display: flex;

            align-items: center;

            padding: 0 22px;

            border-bottom: 1px solid var(--border);
        }


        .brand-logo {

            width: 42px;
            height: 42px;

            border-radius: 10px;

            background-color: var(--primary);

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            margin-right: 13px;
        }


        .brand-name {

            display: block;

            font-size: 16px;

            font-weight: 700;

            color: #17221c;
        }


        .brand-subtitle {

            display: block;

            margin-top: 2px;

            font-size: 12px;

            color: #758078;
        }


        /* =====================================================
           SIDEBAR CONTENT
        ====================================================== */

        .sidebar-content {

            flex: 1;

            padding: 22px 13px;

            overflow-y: auto;
        }


        .sidebar-section {

            margin-bottom: 25px;
        }


        .sidebar-section-title {

            padding: 0 13px;

            margin-bottom: 10px;

            font-size: 11px;

            font-weight: 700;

            color: #7a847e;

            letter-spacing: 0.5px;

            text-transform: uppercase;
        }


        .sidebar-link {

            display: flex;

            align-items: center;

            width: 100%;

            padding: 13px 14px;

            margin-bottom: 4px;

            border-radius: 10px;

            text-decoration: none;

            color: #344139;

            font-size: 15px;

            transition: all 0.2s ease;
        }


        .sidebar-link i {

            width: 24px;

            margin-right: 11px;

            font-size: 18px;

            color: #53635a;
        }


        .sidebar-link:hover {

            background-color: #f0f5f2;

            color: var(--primary);
        }


        .sidebar-link:hover i {

            color: var(--primary);
        }


        .sidebar-link.active {

            background-color: var(--primary-light);

            color: var(--primary);

            font-weight: 600;
        }


        .sidebar-link.active i {

            color: var(--primary);
        }


        /* =====================================================
           SIDEBAR USER
        ====================================================== */

        .sidebar-user {

            padding: 20px 24px;

            border-top: 1px solid var(--border);

            display: flex;

            align-items: center;
        }


        .user-avatar {

            width: 40px;
            height: 40px;

            border-radius: 10px;

            background-color: #edf3ef;

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

            margin-right: 11px;
        }


        .user-name {

            font-size: 14px;

            font-weight: 600;

            color: #17221c;
        }


        .user-role {

            margin-top: 2px;

            font-size: 12px;

            color: #78827c;
        }


        /* =====================================================
           MAIN
        ====================================================== */

        .dashboard-main {

            width: calc(100% - 278px);

            margin-left: 278px;

            min-height: 100vh;
        }


        /* =====================================================
           TOP HEADER
        ====================================================== */

        .dashboard-header {

            height: 76px;

            background-color: var(--white);

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 40px;
        }


        .dashboard-header-left {

            display: flex;

            align-items: center;
        }


        .header-icon {

            width: 24px;

            margin-right: 22px;

            font-size: 22px;

            color: #26362d;
        }


        .header-title {

            margin: 0;

            font-size: 18px;

            font-weight: 600;

            color: #17221c;
        }


        .header-user {

            display: flex;

            align-items: center;
        }


        .header-user-avatar {

            width: 40px;
            height: 40px;

            border-radius: 10px;

            background-color: #edf3ef;

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

            margin-right: 10px;
        }


        .header-user-info {

            line-height: 1.2;
        }


        .header-user-name {

            font-size: 14px;

            font-weight: 600;

            color: #17221c;
        }


        .header-user-role {

            font-size: 11px;

            color: #7a847e;
        }


        .header-chevron {

            margin-left: 15px;

            color: #7a847e;
        }


        /* =====================================================
           CONTENT
        ====================================================== */

        .dashboard-content {

            padding: 36px 40px 50px;
        }


        .page-heading {

            margin-bottom: 25px;
        }


        .page-heading h1 {

            margin: 0;

            font-size: 24px;

            font-weight: 700;

            color: #17221c;
        }


        .page-heading p {

            margin: 7px 0 0;

            font-size: 14px;

            color: var(--muted);
        }


        /* =====================================================
           ALERT
        ====================================================== */

        .alert {

            border-radius: 10px;

            border: 1px solid transparent;
        }


        /* =====================================================
           ATTENDANCE CARD
        ====================================================== */

        .attendance-card {

            background-color: var(--white);

            border: 1px solid var(--border);

            border-radius: 12px;

            box-shadow: none;

            margin-bottom: 24px;

            overflow: hidden;
        }


        .attendance-card-header {

            padding: 20px 24px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .attendance-card-title {

            margin: 0;

            font-size: 16px;

            font-weight: 600;

            color: #17221c;
        }


        .attendance-card-title i {

            color: var(--primary);

            margin-right: 8px;
        }


        .attendance-card-body {

            padding: 24px;
        }


        /* =====================================================
           FORM
        ====================================================== */

        .form-label {

            color: #344139;

            font-size: 14px;

            margin-bottom: 7px;
        }


        .form-control,
        .form-select {

            min-height: 43px;

            border: 1px solid #d9e1dc;

            border-radius: 8px;

            color: #26332c;

            background-color: #ffffff;

            box-shadow: none;
        }


        .form-control:focus,
        .form-select:focus {

            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(23, 107, 58, 0.10);
        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .btn-primary-custom {

            background-color: var(--primary);

            border: 1px solid var(--primary);

            color: white;

            border-radius: 8px;

            padding: 9px 17px;

            font-size: 14px;

            font-weight: 600;
        }


        .btn-primary-custom:hover {

            background-color: var(--primary-dark);

            border-color: var(--primary-dark);

            color: white;
        }


        .btn-secondary-custom {

            background-color: white;

            border: 1px solid #d9e1dc;

            color: #46534b;

            border-radius: 8px;

            padding: 9px 17px;

            font-size: 14px;
        }


        .btn-secondary-custom:hover {

            background-color: #f4f6f5;
        }


        /* =====================================================
           SEARCH
        ====================================================== */

        .search-wrapper {

            position: relative;

            width: 300px;

            max-width: 100%;
        }


        .search-wrapper i {

            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #7d8881;

            z-index: 2;
        }


        .search-wrapper input {

            padding-left: 40px;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .table-wrapper {

            overflow-x: auto;
        }


        .table {

            margin-bottom: 0;

            min-width: 750px;
        }


        .table thead th {

            background-color: #f7f9f8;

            color: #6c7770;

            font-size: 12px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            border-bottom: 1px solid var(--border);

            padding: 14px 16px;

            white-space: nowrap;
        }


        .table tbody td {

            padding: 15px 16px;

            border-bottom: 1px solid #edf0ee;

            color: #36423a;

            font-size: 14px;
        }


        .table tbody tr:last-child td {

            border-bottom: none;
        }


        .table tbody tr:hover {

            background-color: #fafcfb;
        }


        /* =====================================================
           STATUS BADGES
        ====================================================== */

        .attendance-badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;
        }


        .badge-present {

            background-color: #e6f4eb;

            color: #176b3a;
        }


        .badge-absent {

            background-color: #fdeaea;

            color: #c53030;
        }


        .badge-late {

            background-color: #fff4d8;

            color: #956d00;
        }


        .badge-excused {

            background-color: #edf0f2;

            color: #66717a;
        }


        /* =====================================================
           ACTION BUTTONS
        ====================================================== */

        .action-btn {

            width: 34px;
            height: 34px;

            padding: 0;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;
        }


        .btn-outline-primary {

            color: var(--primary);

            border-color: #b9d2c2;
        }


        .btn-outline-primary:hover {

            background-color: var(--primary);

            border-color: var(--primary);

            color: white;
        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 991px) {

            .dashboard-sidebar {

                width: 230px;
            }


            .dashboard-main {

                width: calc(100% - 230px);

                margin-left: 230px;
            }


            .dashboard-header {

                padding: 0 25px;
            }


            .dashboard-content {

                padding: 30px 25px;
            }

        }


        @media (max-width: 767px) {

            .dashboard-wrapper {

                display: block;
            }


            .dashboard-sidebar {

                position: relative;

                width: 100%;

                min-height: auto;
            }


            .dashboard-main {

                width: 100%;

                margin-left: 0;
            }


            .dashboard-header {

                height: auto;

                min-height: 70px;

                padding: 15px 20px;
            }


            .dashboard-content {

                padding: 25px 20px;
            }


            .sidebar-user {

                display: none;
            }


            .header-user-info,
            .header-chevron {

                display: none;
            }


            .attendance-card-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .search-wrapper {

                width: 100%;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-wrapper">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="dashboard-sidebar">


        <!-- BRAND -->

        <div class="sidebar-brand">

            <div class="brand-logo">

                <i class="bi bi-house-heart-fill"></i>

            </div>


            <div>

                <span class="brand-name">
                    Barangay Daycare
                </span>

                <span class="brand-subtitle">
                    Management System
                </span>

            </div>

        </div>


        <!-- SIDEBAR CONTENT -->

        <div class="sidebar-content">


            <!-- MAIN -->

            <div class="sidebar-section">

                <div class="sidebar-section-title">
                    Main
                </div>


                <a
                    href="dashboard.php"
                    class="sidebar-link"
                >

                    <i class="bi bi-grid"></i>

                    <span>
                        Dashboard
                    </span>

                </a>

            </div>


            <!-- DAYCARE -->

            <div class="sidebar-section">

                <div class="sidebar-section-title">
                    Daycare
                </div>


                <a
                    href="parents.php"
                    class="sidebar-link"
                >

                    <i class="bi bi-people"></i>

                    <span>
                        Parents
                    </span>

                </a>


                <a
                    href="children.php"
                    class="sidebar-link"
                >

                    <i class="bi bi-person-hearts"></i>

                    <span>
                        Children
                    </span>

                </a>

            </div>


            <!-- RECORDS -->

            <div class="sidebar-section">

                <div class="sidebar-section-title">
                    Records
                </div>


                <a
                    href="attendance.php"
                    class="sidebar-link active"
                >

                    <i class="bi bi-calendar-check"></i>

                    <span>
                        Attendance
                    </span>

                </a>


                <a
                    href="activities.php"
                    class="sidebar-link"
                >

                    <i class="bi bi-journal-text"></i>

                    <span>
                        Activities
                    </span>

                </a>


                <a
                    href="health-records.php"
                    class="sidebar-link"
                >

                    <i class="bi bi-heart-pulse"></i>

                    <span>
                        Health Records
                    </span>

                </a>

            </div>

        </div>


        <!-- SIDEBAR USER -->

        <div class="sidebar-user">

            <div class="user-avatar">
                L
            </div>


            <div>

                <div class="user-name">
                    lobi
                </div>

                <div class="user-role">
                    teacher
                </div>

            </div>

        </div>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="dashboard-main">


       


        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <div class="dashboard-content">


            <!-- PAGE HEADING -->

            <div class="page-heading">

                <h1>
                    Attendance
                </h1>

                <p>
                    Manage student attendance records
                </p>

            </div>


            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if (isset($_SESSION['success'])): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <i class="bi bi-check-circle me-2"></i>

                    <?= clean($_SESSION['success']) ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>


                <?php unset($_SESSION['success']); ?>

            <?php endif; ?>


            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if (isset($_SESSION['error'])): ?>

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >

                    <i class="bi bi-exclamation-circle me-2"></i>

                    <?= clean($_SESSION['error']) ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>


                <?php unset($_SESSION['error']); ?>

            <?php endif; ?>


            <!-- =================================================
                 ADD / EDIT ATTENDANCE
            ================================================== -->

            <div class="attendance-card">


                <div class="attendance-card-header">

                    <h2 class="attendance-card-title">

                        <?php if ($editRecord): ?>

                            <i class="bi bi-pencil-square"></i>

                            Edit Attendance

                        <?php else: ?>

                            <i class="bi bi-plus-circle"></i>

                            Add Attendance

                        <?php endif; ?>

                    </h2>

                </div>


                <div class="attendance-card-body">


                    <?php if ($editRecord): ?>


                        <!-- EDIT FORM -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $editRecord['id'] ?>"
                            >


                            <div class="row g-4">


                                <!-- STUDENT -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Student
                                    </label>


                                    <select
                                        name="student_id"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">
                                            Select Student
                                        </option>


                                        <?php

                                        $editStudents = $conn->query("
                                            SELECT
                                                children.id,
                                                TRIM(
                                                    CONCAT(
                                                        enrollment.c_first_name,
                                                        ' ',
                                                        COALESCE(
                                                            enrollment.c_middle_name,
                                                            ''
                                                        ),
                                                        ' ',
                                                        enrollment.c_last_name
                                                    )
                                                ) AS student_name
                                            FROM children
                                            INNER JOIN enrollment
                                                ON children.enrollment_id = enrollment.id
                                            ORDER BY
                                                enrollment.c_last_name ASC,
                                                enrollment.c_first_name ASC
                                        ");

                                        ?>


                                        <?php while ($student = $editStudents->fetch_assoc()): ?>

                                            <option
                                                value="<?= $student['id'] ?>"
                                                <?= (
                                                    $student['id']
                                                    ==
                                                    $editRecord['student_id']
                                                )
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >

                                                <?= clean($student['student_name']) ?>

                                            </option>

                                        <?php endwhile; ?>

                                    </select>

                                </div>


                                <!-- DATE -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Attendance Date
                                    </label>


                                    <input
                                        type="date"
                                        name="attendance_date"
                                        class="form-control"
                                        value="<?= clean($editRecord['attendance_date']) ?>"
                                        required
                                    >

                                </div>


                                <!-- STATUS -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Status
                                    </label>


                                    <select
                                        name="status"
                                        class="form-select"
                                        required
                                    >

                                        <option
                                            value="Present"
                                            <?= (
                                                $editRecord['status']
                                                ==
                                                'Present'
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            Present
                                        </option>


                                        <option
                                            value="Absent"
                                            <?= (
                                                $editRecord['status']
                                                ==
                                                'Absent'
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            Absent
                                        </option>


                                        <option
                                            value="Late"
                                            <?= (
                                                $editRecord['status']
                                                ==
                                                'Late'
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            Late
                                        </option>


                                        <option
                                            value="Excused"
                                            <?= (
                                                $editRecord['status']
                                                ==
                                                'Excused'
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            Excused
                                        </option>

                                    </select>

                                </div>


                                <!-- REASON -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Reason
                                    </label>


                                    <input
                                        type="text"
                                        name="reason"
                                        class="form-control"
                                        value="<?= clean($editRecord['reason']) ?>"
                                        placeholder="Optional"
                                    >

                                </div>


                                <!-- BUTTONS -->

                                <div class="col-12">

                                    <button
                                        type="submit"
                                        name="update_attendance"
                                        class="btn btn-primary-custom"
                                    >

                                        <i class="bi bi-save me-1"></i>

                                        Update Attendance

                                    </button>


                                    <a
                                        href="attendance.php"
                                        class="btn btn-secondary-custom ms-1"
                                    >

                                        <i class="bi bi-x-circle me-1"></i>

                                        Cancel

                                    </a>

                                </div>

                            </div>

                        </form>


                    <?php else: ?>


                        <!-- ADD FORM -->

                        <form method="POST">

                            <div class="row g-4">


                                <!-- STUDENT -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Student
                                    </label>


                                    <select
                                        name="student_id"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">
                                            Select Student
                                        </option>


                                        <?php while ($student = $students->fetch_assoc()): ?>

                                            <option
                                                value="<?= $student['id'] ?>"
                                            >

                                                <?= clean($student['student_name']) ?>

                                            </option>

                                        <?php endwhile; ?>

                                    </select>

                                </div>


                                <!-- DATE -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Attendance Date
                                    </label>


                                    <input
                                        type="date"
                                        name="attendance_date"
                                        class="form-control"
                                        value="<?= date('Y-m-d') ?>"
                                        required
                                    >

                                </div>


                                <!-- STATUS -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Status
                                    </label>


                                    <select
                                        name="status"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">
                                            Select Status
                                        </option>


                                        <option value="Present">
                                            Present
                                        </option>


                                        <option value="Absent">
                                            Absent
                                        </option>


                                        <option value="Late">
                                            Late
                                        </option>


                                        <option value="Excused">
                                            Excused
                                        </option>

                                    </select>

                                </div>


                                <!-- REASON -->

                                <div class="col-md-6">

                                    <label
                                        class="form-label fw-semibold"
                                    >
                                        Reason
                                    </label>


                                    <input
                                        type="text"
                                        name="reason"
                                        class="form-control"
                                        placeholder="Optional"
                                    >

                                </div>


                                <!-- SAVE -->

                                <div class="col-12">

                                    <button
                                        type="submit"
                                        name="add_attendance"
                                        class="btn btn-primary-custom"
                                    >

                                        <i class="bi bi-plus-lg me-1"></i>

                                        Add Attendance

                                    </button>

                                </div>

                            </div>

                        </form>


                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 ATTENDANCE RECORDS
            ================================================== -->

            <div class="attendance-card">


                <div class="attendance-card-header">


                    <h2 class="attendance-card-title">

                        <i class="bi bi-table"></i>

                        Attendance Records

                    </h2>


                    <div class="search-wrapper">

                        <i class="bi bi-search"></i>


                        <input
                            type="text"
                            id="searchAttendance"
                            class="form-control"
                            placeholder="Search student..."
                        >

                    </div>

                </div>


                <div class="attendance-card-body">


                    <div class="table-wrapper">


                        <table
                            class="table table-hover align-middle"
                            id="attendanceTable"
                        >


                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>


                                    <th>
                                        Student
                                    </th>


                                    <th>
                                        Date
                                    </th>


                                    <th>
                                        Status
                                    </th>


                                    <th>
                                        Reason
                                    </th>


                                    <th class="text-center">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php

                                $count = 1;

                                if (
                                    $attendance
                                    &&
                                    $attendance->num_rows > 0
                                ):

                                ?>


                                    <?php while ($row = $attendance->fetch_assoc()): ?>


                                        <tr>


                                            <!-- NUMBER -->

                                            <td>

                                                <?= $count++ ?>

                                            </td>


                                            <!-- STUDENT -->

                                            <td class="fw-semibold">

                                                <?= clean(
                                                    $row['student_name']
                                                ) ?>

                                            </td>


                                            <!-- DATE -->

                                            <td>

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $row['attendance_date']
                                                    )
                                                ) ?>

                                            </td>


                                            <!-- STATUS -->

                                            <td>


                                                <?php if (
                                                    $row['status']
                                                    ===
                                                    'Present'
                                                ): ?>


                                                    <span
                                                        class="attendance-badge badge-present"
                                                    >

                                                        Present

                                                    </span>


                                                <?php elseif (
                                                    $row['status']
                                                    ===
                                                    'Absent'
                                                ): ?>


                                                    <span
                                                        class="attendance-badge badge-absent"
                                                    >

                                                        Absent

                                                    </span>


                                                <?php elseif (
                                                    $row['status']
                                                    ===
                                                    'Late'
                                                ): ?>


                                                    <span
                                                        class="attendance-badge badge-late"
                                                    >

                                                        Late

                                                    </span>


                                                <?php elseif (
                                                    $row['status']
                                                    ===
                                                    'Excused'
                                                ): ?>


                                                    <span
                                                        class="attendance-badge badge-excused"
                                                    >

                                                        Excused

                                                    </span>


                                                <?php else: ?>


                                                    <span
                                                        class="attendance-badge badge-excused"
                                                    >

                                                        <?= clean(
                                                            $row['status']
                                                        ) ?>

                                                    </span>


                                                <?php endif; ?>


                                            </td>


                                            <!-- REASON -->

                                            <td>


                                                <?php if (
                                                    !empty(
                                                        $row['reason']
                                                    )
                                                ): ?>


                                                    <?= clean(
                                                        $row['reason']
                                                    ) ?>


                                                <?php else: ?>


                                                    <span
                                                        class="text-muted"
                                                    >
                                                        —
                                                    </span>


                                                <?php endif; ?>


                                            </td>


                                            <!-- ACTIONS -->

                                            <td class="text-center">


                                                <!-- EDIT -->

                                                <a
                                                    href="attendance.php?edit=<?= $row['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary action-btn me-1"
                                                    title="Edit"
                                                >

                                                    <i
                                                        class="bi bi-pencil"
                                                    ></i>

                                                </a>


                                                <!-- DELETE -->

                                                <a
                                                    href="attendance.php?delete=<?= $row['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger action-btn"
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this attendance record?');"
                                                >

                                                    <i
                                                        class="bi bi-trash"
                                                    ></i>

                                                </a>

                                            </td>

                                        </tr>


                                    <?php endwhile; ?>


                                <?php else: ?>


                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center py-5 text-muted"
                                        >

                                            <i
                                                class="bi bi-calendar-x fs-2 d-block mb-2"
                                            ></i>

                                            No attendance records found.

                                        </td>

                                    </tr>


                                <?php endif; ?>


                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


        </div>

    </main>

</div>


<!-- =====================================================
     BOOTSTRAP JS
====================================================== -->

<script>
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
</script>



<!-- =====================================================
     SEARCH
====================================================== -->

<script>

    document
        .getElementById("searchAttendance")
        .addEventListener("keyup", function () {

            let searchValue =
                this.value.toLowerCase();

            let rows =
                document.querySelectorAll(
                    "#attendanceTable tbody tr"
                );


            rows.forEach(function (row) {

                let text =
                    row.textContent.toLowerCase();


                if (text.includes(searchValue)) {

                    row.style.display = "";

                } else {

                    row.style.display = "none";

                }

            });

        });

</script>


</body>

</html>


<?php

$conn->close();

?>