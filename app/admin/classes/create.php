<?php

$pageTitle = "Add Class";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// FORM STATE
// ==========================================================

$academic_id = isset($_GET['academic_id']) ? (int) $_GET['academic_id'] : 0;
$session_id  = 0;
$teacher_id  = 0;
$name        = "";
$capacity    = 20;
$status      = "active";

$errorMessage = "";


// ==========================================================
// GET ACADEMIC YEARS
// ==========================================================

$academicYears = [];

$result = $conn->query("
    SELECT id, academic_year, status
    FROM academic_year
    ORDER BY status DESC, start_date DESC, id DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $academicYears[] = $row;
    }
}

// Default academic_id = active year
if ($academic_id <= 0) {
    foreach ($academicYears as $y) {
        if ($y['status'] === 'active') {
            $academic_id = (int) $y['id'];
            break;
        }
    }
}


// ==========================================================
// GET SESSIONS
// ==========================================================

$sessions = [];

$result = $conn->query("
    SELECT id, name, start_time, end_time
    FROM session
    WHERE status = 'active'
    ORDER BY start_time ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}


// ==========================================================
// GET TEACHERS
// ==========================================================

$teachers = [];

$result = $conn->query("
    SELECT id, first_name, middle_name, last_name
    FROM teacher
    ORDER BY last_name ASC, first_name ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $academic_id = (int) ($_POST['academic_id'] ?? 0);
    $session_id  = (int) ($_POST['session_id'] ?? 0);
    $teacher_id  = (int) ($_POST['teacher_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $capacity    = (int) ($_POST['capacity'] ?? 0);
    $status      = trim($_POST['status'] ?? 'active');

    // Validation
    if (
        $academic_id <= 0 ||
        $session_id <= 0 ||
        $teacher_id <= 0 ||
        empty($name)
    ) {

        $errorMessage = "All fields are required.";

    } elseif ($capacity <= 0) {

        $errorMessage = "Capacity must be greater than 0.";

    } elseif (!in_array($status, ['active', 'inactive'], true)) {

        $errorMessage = "Invalid status.";

    } else {

        // Check duplicate name sa same academic year + session
        $check = $conn->prepare("
            SELECT id FROM class
            WHERE academic_id = ? AND session_id = ? AND name = ?
            LIMIT 1
        ");

        $check->bind_param("iis", $academic_id, $session_id, $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $check->close();
            $errorMessage = "A class with this name already exists for the selected session and academic year.";

        } else {

            $check->close();

            try {

                $stmt = $conn->prepare("
                    INSERT INTO class
                        (academic_id, session_id, teacher_id, name, capacity, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iiisis",
                    $academic_id,
                    $session_id,
                    $teacher_id,
                    $name,
                    $capacity,
                    $status
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['status']  = "success";
                $_SESSION['message'] = "Class added successfully.";

                header("Location: index.php?academic_id=" . (int) $academic_id);
                exit;

            } catch (Exception $e) {

                $errorMessage = "Failed to add class. Please try again.";
            }
        }
    }
}


include '../layout/header.php';

?>

<div>

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

        <div class="min-w-0">
            <h2 class="fw-bold fs-4 mb-0">Add Class</h2>
            <small class="text-muted">Create a new class</small>
        </div>

        <a href="index.php?academic_id=<?= (int) $academic_id ?>" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>

    </div>


    <!-- ERROR -->
    <?php if (!empty($errorMessage)): ?>

        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= e($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php endif; ?>


    <!-- FORM CARD -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">

            <form action="create.php" method="POST" autocomplete="off">

                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">
                            Academic Year <span class="text-danger">*</span>
                        </label>
                        <select name="academic_id" class="form-select" required>
                            <option value="">Select academic year</option>
                            <?php foreach ($academicYears as $y): ?>
                                <option value="<?= (int) $y['id'] ?>"
                                    <?= $academic_id === (int) $y['id'] ? 'selected' : '' ?>>
                                    <?= e($y['academic_year']) ?>
                                    <?= $y['status'] === 'active' ? ' (Active)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">
                            Session <span class="text-danger">*</span>
                        </label>
                        <select name="session_id" class="form-select" required>
                            <option value="">Select session</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"
                                    <?= $session_id === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?>
                                    (<?= e(date('h:i A', strtotime($s['start_time']))) ?>
                                    – <?= e(date('h:i A', strtotime($s['end_time']))) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">
                            Teacher <span class="text-danger">*</span>
                        </label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">Select teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <?php
                                $tName = trim(
                                    $t['first_name'] . ' ' .
                                    $t['middle_name'] . ' ' .
                                    $t['last_name']
                                );
                                ?>
                                <option value="<?= (int) $t['id'] ?>"
                                    <?= $teacher_id === (int) $t['id'] ? 'selected' : '' ?>>
                                    <?= e($tName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">
                            Capacity <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="capacity" class="form-control"
                               min="1" max="100"
                               value="<?= (int) $capacity ?>" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">
                            Class Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="form-control"
                               placeholder="e.g. Morning A, Morning B"
                               value="<?= e($name) ?>" maxlength="50" required>
                        <div class="form-text">
                            Free text — halimbawa: "Morning A", "Afternoon Class 1"
                        </div>
                    </div>

                </div>


                <!-- FOOTER -->
                <div class="border-top mt-4 pt-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">

                        <a href="index.php?academic_id=<?= (int) $academic_id ?>"
                           class="btn btn-light border">Cancel</a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save Class
                        </button>

                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>