<?php

$pageTitle = "Add Session";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


$name       = "";
$start_time = "";
$end_time   = "";
$status     = "active";

$errorMessage = "";


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time   = trim($_POST['end_time'] ?? '');
    $status     = trim($_POST['status'] ?? 'active');

    // Validation
    if (empty($name) || empty($start_time) || empty($end_time)) {

        $errorMessage = "All fields are required.";

    } elseif (!in_array($status, ['active', 'inactive'], true)) {

        $errorMessage = "Invalid status.";

    } elseif (strtotime($end_time) <= strtotime($start_time)) {

        $errorMessage = "End time must be later than start time.";

    } else {

        // Check duplicate name
        $check = $conn->prepare("
            SELECT id FROM session WHERE name = ? LIMIT 1
        ");

        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $check->close();
            $errorMessage = "A session with this name already exists.";

        } else {

            $check->close();

            try {

                $stmt = $conn->prepare("
                    INSERT INTO session (name, start_time, end_time, status)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $start_time,
                    $end_time,
                    $status
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['status']  = "success";
                $_SESSION['message'] = "Session added successfully.";

                header("Location: index.php");
                exit;

            } catch (Exception $e) {

                $errorMessage = "Failed to add session. Please try again.";
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
            <h2 class="fw-bold fs-4 mb-0">Add Session</h2>
            <small class="text-muted">Create a new session schedule</small>
        </div>

        <a href="index.php" class="btn btn-sm btn-light border">
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
                            Session Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="form-control"
                               placeholder="e.g. Morning, Midday, Afternoon"
                               value="<?= e($name) ?>" maxlength="50" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">
                            Start Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" name="start_time" class="form-control"
                               value="<?= e($start_time) ?>" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">
                            End Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" name="end_time" class="form-control"
                               value="<?= e($end_time) ?>" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                </div>


                <!-- FOOTER -->
                <div class="border-top mt-4 pt-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">

                        <a href="index.php" class="btn btn-light border">Cancel</a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save Session
                        </button>

                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>