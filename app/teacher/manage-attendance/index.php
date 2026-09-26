<?php

    $pageTitle = "Attendance";
    require_once '../guard.php';
    include '../../../api/conn.php';

    /* =========================================================
    HELPER FUNCTIONS
    ========================================================= */

    /**
     * Safely escape output for HTML.
     */
    function clean($value)
    {
        return htmlspecialchars(
            $value ?? '',
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * Redirect back to attendance page.
     */
    function redirectToAttendance()
    {
        header("Location: index.php");
        exit();
    }

    /**
     * Get student name by student ID.
     */
    function getStudent($conn, $studentId)
    {
        $query = $conn->prepare("
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

        $query->bind_param("i", $studentId);
        $query->execute();

        $result = $query->get_result();
        $student = $result->fetch_assoc();

        $query->close();

        return $student ?: null;
    }


    /* =========================================================
    ADD ATTENDANCE
    ========================================================= */

    if (isset($_POST['add_attendance'])) {

        $studentId = intval($_POST['student_id']);
        $attendanceDate = $_POST['attendance_date'];
        $status = $_POST['status'];
        $reason = trim($_POST['reason']);

        // Get student information.
        $student = getStudent($conn, $studentId);

        if (!$student) {

            $_SESSION['error'] = "Student not found.";

            redirectToAttendance();
        }

        $studentName = $student['student_name'];

        // Check if attendance already exists.
        $check = $conn->prepare("
            SELECT id
            FROM attendance
            WHERE student_id = ?
            AND attendance_date = ?
        ");

        $check->bind_param(
            "is",
            $studentId,
            $attendanceDate
        );

        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $_SESSION['error'] =
                "This student already has an attendance record for this date.";

            $check->close();

            redirectToAttendance();
        }

        $check->close();

        // Insert attendance record.
        $insert = $conn->prepare("
            INSERT INTO attendance (
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
            $studentName,
            $studentId,
            $attendanceDate,
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

        redirectToAttendance();
    }


    /* =========================================================
    UPDATE ATTENDANCE
    ========================================================= */

    if (isset($_POST['update_attendance'])) {

        $id = intval($_POST['id']);
        $studentId = intval($_POST['student_id']);
        $attendanceDate = $_POST['attendance_date'];
        $status = $_POST['status'];
        $reason = trim($_POST['reason']);

        // Get updated student information.
        $student = getStudent($conn, $studentId);

        if (!$student) {

            $_SESSION['error'] = "Student not found.";

            redirectToAttendance();
        }

        $studentName = $student['student_name'];

        // Check for duplicate attendance.
        // Ignore the current attendance record.
        $check = $conn->prepare("
            SELECT id
            FROM attendance
            WHERE student_id = ?
            AND attendance_date = ?
            AND id != ?
        ");

        $check->bind_param(
            "isi",
            $studentId,
            $attendanceDate,
            $id
        );

        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $_SESSION['error'] =
                "This student already has an attendance record for this date.";

            $check->close();

            redirectToAttendance();
        }

        $check->close();

        // Update attendance record.
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
            $studentName,
            $studentId,
            $attendanceDate,
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

        redirectToAttendance();
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

        $delete->bind_param("i", $id);

        if ($delete->execute()) {

            $_SESSION['success'] =
                "Attendance deleted successfully.";

        } else {

            $_SESSION['error'] =
                "Error deleting attendance: " . $conn->error;
        }

        $delete->close();

        redirectToAttendance();
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

        $editId = intval($_GET['edit']);

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

        $editQuery->bind_param("i", $editId);
        $editQuery->execute();

        $editResult = $editQuery->get_result();

        if ($editResult->num_rows > 0) {
            $editRecord = $editResult->fetch_assoc();
        }

        $editQuery->close();
    }
    include '../layout/header.php';

?>



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

        padding-left: 40px !important;
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



<!-- =========================================================
     PAGE HEADING
========================================================= -->

<div>

    <h2 class="fw-bold fs-4 mb-0">Manage Attendance</h2>

    <p>
        Manage student attendance records
    </p>

</div>


<!-- =========================================================
     SUCCESS MESSAGE
========================================================= -->

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


<!-- =========================================================
     ERROR MESSAGE
========================================================= -->

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


<!-- =========================================================
     ADD / EDIT ATTENDANCE
========================================================= -->

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

        <form method="POST">

            <?php if ($editRecord): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= $editRecord['id'] ?>"
                >

            <?php endif; ?>


            <div class="row g-4">

                <!-- STUDENT -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
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
                                <?=
                                    (
                                        $editRecord &&
                                        $student['id'] == $editRecord['student_id']
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


                <!-- ATTENDANCE DATE -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Attendance Date
                    </label>

                    <input
                        type="date"
                        name="attendance_date"
                        class="form-control"
                        value="<?=
                            $editRecord
                                ? clean($editRecord['attendance_date'])
                                : date('Y-m-d')
                        ?>"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
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

                        <?php
                        $statuses = [
                            'Present',
                            'Absent',
                            'Late',
                            'Excused'
                        ];
                        ?>

                        <?php foreach ($statuses as $statusOption): ?>

                            <option
                                value="<?= $statusOption ?>"
                                <?=
                                    (
                                        $editRecord &&
                                        $editRecord['status'] === $statusOption
                                    )
                                        ? 'selected'
                                        : ''
                                ?>
                            >
                                <?= $statusOption ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- REASON -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Reason
                    </label>

                    <input
                        type="text"
                        name="reason"
                        class="form-control"
                        value="<?=
                            $editRecord
                                ? clean($editRecord['reason'])
                                : ''
                        ?>"
                        placeholder="Optional"
                    >

                </div>


                <!-- BUTTONS -->

                <div class="col-12">

                    <?php if ($editRecord): ?>

                        <button
                            type="submit"
                            name="update_attendance"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-save me-1"></i>
                            Update Attendance
                        </button>

                        <a
                            href="<?= $baseUrl ?>manage-attendance"
                            class="btn btn-secondary ms-1"
                        >
                            <i class="bi bi-x-circle me-1"></i>
                            Cancel
                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="add_attendance"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-plus-lg me-1"></i>
                            Add Attendance
                        </button>

                    <?php endif; ?>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     ATTENDANCE RECORDS
========================================================= -->

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



        <div class="table-wrapper">

            <table
                class="table table-hover data-table   mb-0"
                id="attendanceTable"
            >

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th class="text-center">Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php
                    $count = 1;
                    ?>

                    <?php if ($attendance && $attendance->num_rows > 0): ?>

                        <?php while ($row = $attendance->fetch_assoc()): ?>

                            <tr>

                                <!-- NUMBER -->

                                <td>
                                    <?= $count++ ?>
                                </td>


                                <!-- STUDENT -->

                                <td class="fw-semibold">
                                    <?= clean($row['student_name']) ?>
                                </td>


                                <!-- DATE -->

                                <td>
                                    <?= date(
                                        'M d, Y',
                                        strtotime($row['attendance_date'])
                                    ) ?>
                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php
                                    $statusClasses = [
                                        'Present' => 'badge-present',
                                        'Absent'  => 'badge-absent',
                                        'Late'    => 'badge-late',
                                        'Excused' => 'badge-excused'
                                    ];

                                    $statusClass =
                                        $statusClasses[$row['status']]
                                        ?? 'badge-excused';
                                    ?>

                                    <span
                                        class="attendance-badge <?= $statusClass ?>"
                                    >
                                        <?= clean($row['status']) ?>
                                    </span>

                                </td>


                                <!-- REASON -->

                                <td>

                                    <?php if (!empty($row['reason'])): ?>

                                        <?= clean($row['reason']) ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td class="text-center">

                                    <!-- EDIT -->

                                    <a
                                        href="<?= $baseUrl ?>manage-attendance?edit=<?= $row['id'] ?>"
                                        class="btn btn-sm btn-outline-primary action-btn me-1"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>


                                    <!-- DELETE -->

                                    <a
                                        href="<?= $baseUrl ?>manage-attendance?delete=<?= $row['id'] ?>"
                                        class="btn btn-sm btn-outline-danger action-btn"
                                        title="Delete"
                                        onclick="return confirm(
                                            'Are you sure you want to delete this attendance record?'
                                        );"
                                    >
                                        <i class="bi bi-trash"></i>
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


<?php include '../layout/footer.php'; ?>
