<?php

// ==========================================================
// ACADEMIC YEAR — BACKEND
// ==========================================================


// ==========================================================
// HANDLE POST REQUESTS
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    // ======================================================
    // CREATE ACADEMIC YEAR
    // ======================================================

    if ($action === 'create_academic_year') {

        $academic_year = trim($_POST['academic_year'] ?? '');
        $start_date    = $_POST['start_date'] ?? '';
        $end_date      = $_POST['end_date'] ?? '';


        if (
            $academic_year === '' ||
            $start_date === '' ||
            $end_date === ''
        ) {

            $_SESSION['settings_error'] =
                "Please complete all Academic Year fields.";

            header("Location: ./");
            exit;
        }


        if ($end_date <= $start_date) {

            $_SESSION['settings_error'] =
                "End date must be later than the start date.";

            header("Location: ./");
            exit;
        }


        if (!preg_match('/^\d{4}\s-\s\d{4}$/', $academic_year)) {

            $_SESSION['settings_error'] =
                "Academic Year must follow this format: 2026 - 2027.";

            header("Location: ./");
            exit;
        }


        $check = $conn->prepare("
            SELECT id
            FROM academicyear
            WHERE academic_year = ?
            LIMIT 1
        ");

        $check->bind_param("s", $academic_year);

        $check->execute();
        $check->store_result();


        if ($check->num_rows > 0) {

            $check->close();

            $_SESSION['settings_error'] =
                "Academic Year " . $academic_year . " already exists.";

            header("Location: ./");
            exit;
        }

        $check->close();


        try {

            $stmt = $conn->prepare("
                INSERT INTO academicyear
                (
                    academic_year,
                    start_date,
                    end_date,
                    status
                )
                VALUES (?, ?, ?, 0)
            ");

            $stmt->bind_param(
                "sss",
                $academic_year,
                $start_date,
                $end_date
            );

            $stmt->execute();

            $stmt->close();


            $_SESSION['settings_success'] =
                "Academic Year " . $academic_year .
                " has been created. You can activate it when ready.";

        } catch (Exception $e) {

            $_SESSION['settings_error'] =
                "Unable to create Academic Year.";
        }


        header("Location: ./");
        exit;
    }


    // ======================================================
    // ACTIVATE ACADEMIC YEAR
    // ======================================================

    if ($action === 'activate_academic_year') {

        $id = (int)($_POST['id'] ?? 0);


        if ($id <= 0) {

            $_SESSION['settings_error'] =
                "Invalid Academic Year.";

            header("Location: ./");
            exit;
        }


        $check = $conn->prepare("
            SELECT academic_year
            FROM academicyear
            WHERE id = ?
            LIMIT 1
        ");

        $check->bind_param("i", $id);

        $check->execute();

        $result = $check->get_result();

        $year = $result->fetch_assoc();

        $check->close();


        if (!$year) {

            $_SESSION['settings_error'] =
                "Academic Year not found.";

            header("Location: ./");
            exit;
        }


        try {

            $conn->begin_transaction();


            $stmt = $conn->prepare("
                UPDATE academicyear
                SET status = 0
                WHERE status = 1
                AND id != ?
            ");

            $stmt->bind_param("i", $id);

            $stmt->execute();

            $stmt->close();


            $stmt = $conn->prepare("
                UPDATE academicyear
                SET status = 1
                WHERE id = ?
            ");

            $stmt->bind_param("i", $id);

            $stmt->execute();

            $stmt->close();


            $conn->commit();


            $_SESSION['settings_success'] =
                "Academic Year " .
                $year['academic_year'] .
                " is now active.";

        } catch (Exception $e) {

            $conn->rollback();

            $_SESSION['settings_error'] =
                "Unable to activate Academic Year.";
        }


        header("Location: ./");
        exit;
    }


    // ======================================================
    // UPDATE ACADEMIC YEAR
    // ======================================================

    if ($action === 'update_academic_year') {

        $id            = (int)($_POST['id'] ?? 0);
        $academic_year = trim($_POST['academic_year'] ?? '');
        $start_date    = $_POST['start_date'] ?? '';
        $end_date      = $_POST['end_date'] ?? '';


        if (
            $id <= 0 ||
            $academic_year === '' ||
            $start_date === '' ||
            $end_date === ''
        ) {

            $_SESSION['settings_error'] =
                "Please complete all Academic Year fields.";

            header("Location: ./");
            exit;
        }


        if ($end_date <= $start_date) {

            $_SESSION['settings_error'] =
                "End date must be later than the start date.";

            header("Location: ./");
            exit;
        }


        if (!preg_match('/^\d{4}\s-\s\d{4}$/', $academic_year)) {

            $_SESSION['settings_error'] =
                "Academic Year must follow this format: 2026 - 2027.";

            header("Location: ./");
            exit;
        }


        $check = $conn->prepare("
            SELECT id
            FROM academicyear
            WHERE academic_year = ?
            AND id != ?
            LIMIT 1
        ");

        $check->bind_param(
            "si",
            $academic_year,
            $id
        );

        $check->execute();
        $check->store_result();


        if ($check->num_rows > 0) {

            $check->close();

            $_SESSION['settings_error'] =
                "Academic Year " . $academic_year . " already exists.";

            header("Location: ./");
            exit;
        }

        $check->close();


        $stmt = $conn->prepare("
            UPDATE academicyear
            SET
                academic_year = ?,
                start_date = ?,
                end_date = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssi",
            $academic_year,
            $start_date,
            $end_date,
            $id
        );


        if ($stmt->execute()) {

            $_SESSION['settings_success'] =
                "Academic Year updated successfully.";

        } else {

            $_SESSION['settings_error'] =
                "Unable to update Academic Year.";
        }


        $stmt->close();


        header("Location: ./");
        exit;
    }
}


// ==========================================================
// GET CURRENT ACTIVE ACADEMIC YEAR
// ==========================================================

$currentYear = null;

$result = $conn->query("
    SELECT *
    FROM academicyear
    WHERE status = 1
    ORDER BY id DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {

    $currentYear = $result->fetch_assoc();
}


// ==========================================================
// GET ALL ACADEMIC YEARS
// ==========================================================

$academicYears = [];

$result = $conn->query("
    SELECT *
    FROM academicyear
    ORDER BY
        status DESC,
        start_date DESC,
        id DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $academicYears[] = $row;
    }
}

?>


<!-- ==================================================
     ACADEMIC YEAR TAB
     ================================================== -->

<div
    class="tab-pane fade show active"
    id="academicYear">


    <!-- ==============================================
         CURRENT ACADEMIC YEAR
         ============================================== -->

    <div class="settings-card mb-4">


        <div
            class="settings-card-header d-flex align-items-center justify-content-between gap-3">

            <div>

                <h5 class="fw-bold mb-1">
                    Academic Year
                </h5>

                <p class="text-muted small mb-0">
                    Configure the academic period used by your daycare.
                </p>

            </div>


            <button
                class="btn btn-primary btn-sm px-2 py-1"
                data-bs-toggle="modal"
                data-bs-target="#addAcademicYearModal">

                <i class="bi bi-plus-lg"></i>

            </button>

        </div>


        <div class="settings-card-body">


            <?php if ($currentYear): ?>


                <div class="current-year">

                    <div class="current-year-content">

                        <div class="current-year-info">

                            <div class="icon">

                                <i class="bi bi-calendar-check"></i>

                            </div>


                            <div>

                                <div class="year-label">
                                    Current Academic Year
                                </div>

                                <div class="year-value">

                                    <?= e($currentYear['academic_year']) ?>

                                </div>

                            </div>

                        </div>


                        <span class="status-active">

                            <span class="status-dot"></span>

                            Active

                        </span>

                    </div>

                </div>


            <?php else: ?>


                <!-- ==================================
                     NO ACTIVE ACADEMIC YEAR
                     ================================== -->

                <div class="text-center py-5">

                    <div class="mb-3">

                        <i
                            class="bi bi-calendar-x"
                            style="
                                font-size: 40px;
                                color: var(--color-text-muted);
                            ">
                        </i>

                    </div>


                    <h6 class="fw-bold">
                        No Active Academic Year
                    </h6>


                    <p class="text-muted small mb-3">
                        Create an academic year and activate it
                        when you are ready.
                    </p>


                    <button
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addAcademicYearModal">

                        <i class="bi bi-plus-lg me-1"></i>

                        Add Academic Year

                    </button>

                </div>


            <?php endif; ?>


        </div>

    </div>


    <!-- ==================================================
         ACADEMIC YEAR HISTORY
         ================================================== -->

    <div class="settings-card">


        <div class="settings-card-header">

            <h5 class="fw-bold mb-1">
                Academic Year History
            </h5>

            <p class="text-muted small mb-0">
                View and manage academic years.
            </p>

        </div>


        <div class="table-responsive">

        <table class="table data-table mb-0">

                <thead>

                    <tr>

                        <th>
                            Academic Year
                        </th>

                        <th>
                            Start Date
                        </th>

                        <th>
                            End Date
                        </th>

                        <th>
                            Status
                        </th>

                        <th class="text-end">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (count($academicYears) > 0): ?>


                        <?php foreach ($academicYears as $year): ?>


                            <tr>


                                <td class="fw-semibold">

                                    <?= e($year['academic_year']) ?>

                                </td>


                                <td>

                                    <?= date(
                                        'F j, Y',
                                        strtotime($year['start_date'])
                                    ) ?>

                                </td>


                                <td>

                                    <?= date(
                                        'F j, Y',
                                        strtotime($year['end_date'])
                                    ) ?>

                                </td>


                                <td>


                                    <?php if (
                                        (int)$year['status'] === 1
                                    ): ?>


                                        <span class="status-active">

                                            <span class="status-dot"></span>

                                            Active

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="badge bg-paper-soft text-secondary">

                                            Inactive

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <td class="text-end">


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning px-2 py-1"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAcademicYearModal"

                                        data-id="<?= (int)$year['id'] ?>"

                                        data-academic-year="<?= e(
                                            $year['academic_year']
                                        ) ?>"

                                        data-start-date="<?= e(
                                            $year['start_date']
                                        ) ?>"

                                        data-end-date="<?= e(
                                            $year['end_date']
                                        ) ?>">

                                        <i class="bi bi-pencil"></i>

                                    </button>


                                    <?php if (
                                            (int)$year['status'] === 0
                                        ): ?>


                                            <!-- ACTIVATE -->

                                            <form
                                                method="POST"
                                                action=""
                                                class="d-inline">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="activate_academic_year">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$year['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Set as Active"
                                                    onclick="return confirm('Set <?= e(
                                                        $year['academic_year']
                                                    ) ?> as the active academic year? The current active academic year will be archived.')">

                                                    <i class="bi bi-arrow-repeat"></i>

                                                    <span>Set Active</span>

                                                </button>

                                            </form>


                                        <?php endif; ?>



                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="5"
                                class="text-center text-muted py-4">

                                No academic years found.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</div>


<!-- ==========================================================
     ADD ACADEMIC YEAR MODAL
     ========================================================== -->

<div
    class="modal fade"
    id="addAcademicYearModal"
    tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div
            class="modal-content border-0"
            style="border-radius: 16px;">


            <div class="modal-header border-0 px-4 pt-4">

                <div>

                    <h5 class="fw-bold mb-1">
                        Add Academic Year
                    </h5>

                    <p class="text-muted small mb-0">
                        Create a new academic year.
                    </p>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>


            <form method="POST" action="">


                <input
                    type="hidden"
                    name="action"
                    value="create_academic_year">


                <div class="modal-body px-4">


                    <div class="mb-3">

                        <label class="form-label">
                            Academic Year
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="academic_year"
                            placeholder="2027 - 2028"
                            maxlength="11"
                            required>

                    </div>


                    <div class="row g-3">


                        <div class="col-6">

                            <label class="form-label">
                                Start Date
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="start_date"
                                required>

                        </div>


                        <div class="col-6">

                            <label class="form-label">
                                End Date
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="end_date"
                                required>

                        </div>


                    </div>


                    <div class="mt-3 p-3 rounded-3 bg-paper-soft">

                        <div class="d-flex gap-2">

                            <i class="bi bi-info-circle text-primary"></i>

                            <small class="text-secondary">

                                The academic year will be created as
                                <strong>Inactive</strong>.
                                You can activate it later.

                            </small>

                        </div>

                    </div>


                </div>


                <div class="modal-footer border-0 px-4 pb-4">


                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary btn-sm">

                        <i class="bi bi-plus-lg me-1"></i>

                        Create Academic Year

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<!-- ==========================================================
     EDIT ACADEMIC YEAR MODAL
     ========================================================== -->

<div
    class="modal fade"
    id="editAcademicYearModal"
    tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div
            class="modal-content border-0"
            style="border-radius: 16px;">


            <div class="modal-header border-0 px-4 pt-4">

                <div>

                    <h5 class="fw-bold mb-1">
                        Edit Academic Year
                    </h5>

                    <p class="text-muted small mb-0">
                        Update the academic year information.
                    </p>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>


            <form method="POST" action="">


                <input
                    type="hidden"
                    name="action"
                    value="update_academic_year">


                <input
                    type="hidden"
                    name="id"
                    id="edit_id">


                <div class="modal-body px-4">


                    <div class="mb-3">

                        <label class="form-label">
                            Academic Year
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="academic_year"
                            id="edit_academic_year"
                            maxlength="11"
                            required>

                    </div>


                    <div class="row g-3">


                        <div class="col-6">

                            <label class="form-label">
                                Start Date
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="start_date"
                                id="edit_start_date"
                                required>

                        </div>


                        <div class="col-6">

                            <label class="form-label">
                                End Date
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="end_date"
                                id="edit_end_date"
                                required>

                        </div>


                    </div>


                </div>


                <div class="modal-footer border-0 px-4 pb-4">


                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary btn-sm">

                        <i class="bi bi-check2 me-1"></i>

                        Save Changes

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>