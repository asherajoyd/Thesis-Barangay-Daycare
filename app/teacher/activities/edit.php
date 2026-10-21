<?php

$pageTitle = "Edit Activity";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


if (empty($_SESSION['teacher_id'])) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";
    header("Location: ../dashboard/");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// GET CLASSES
// ==========================================================

$classes = [];

$stmt = $conn->prepare("
    SELECT cl.id, cl.name, s.name AS session_name
    FROM class cl
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE cl.teacher_id = ? AND cl.status = 'active'
    ORDER BY s.start_time ASC, cl.name ASC
");

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

$stmt->close();


// ==========================================================
// FORM STATE
// ==========================================================

$id            = 0;
$class_id      = 0;
$title         = "";
$description   = "";
$activity_date = "";

$errorMessage = "";


// ==========================================================
// HANDLE GET
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        header("Location: index.php");
        exit;
    }

    $stmt = $conn->prepare("
        SELECT * FROM activity WHERE id = ? AND teacher_id = ? LIMIT 1
    ");

    $stmt->bind_param("ii", $id, $teacherId);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$activity) {
        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Activity not found or access denied.";
        header("Location: index.php");
        exit;
    }

    $class_id      = (int) $activity['class_id'];
    $title         = $activity['title'];
    $description   = $activity['description'] ?? '';
    $activity_date = $activity['activity_date'];
}


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id            = (int) ($_POST['id'] ?? 0);
    $class_id      = (int) ($_POST['class_id'] ?? 0);
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $activity_date = trim($_POST['activity_date'] ?? '');

    if ($id <= 0 || $class_id <= 0 || empty($title) || empty($activity_date)) {
        $errorMessage = "Please complete all required fields.";
    } else {

        // Verify class belongs to teacher
        $verifyStmt = $conn->prepare("
            SELECT id FROM class WHERE id = ? AND teacher_id = ? LIMIT 1
        ");
        $verifyStmt->bind_param("ii", $class_id, $teacherId);
        $verifyStmt->execute();
        $verifyStmt->store_result();

        if ($verifyStmt->num_rows === 0) {
            $verifyStmt->close();
            $errorMessage = "Class not found or access denied.";
        } else {
            $verifyStmt->close();

            try {

                $stmt = $conn->prepare("
                    UPDATE activity
                    SET class_id = ?, title = ?, description = ?, activity_date = ?
                    WHERE id = ? AND teacher_id = ?
                ");

                $stmt->bind_param(
                    "isssii",
                    $class_id,
                    $title,
                    $description,
                    $activity_date,
                    $id,
                    $teacherId
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['status']  = "success";
                $_SESSION['message'] = "Activity updated successfully.";
                header("Location: index.php");
                exit;

            } catch (Exception $ex) {
                $errorMessage = "Failed to update: " . $ex->getMessage();
            }
        }
    }
}


include '../layout/header.php';

?>

<div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0">Edit Activity</h2>
            <small class="text-muted">Update activity details</small>
        </div>
        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>


    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= e($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>


    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">

            <form action="edit.php" method="POST" autocomplete="off">

                <input type="hidden" name="id" value="<?= (int) $id ?>">

                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">— Select class —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $class_id === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?> (<?= e($c['session_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Activity Date <span class="text-danger">*</span></label>
                        <input type="date" name="activity_date" class="form-control"
                               value="<?= e($activity_date) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= e($title) ?>" maxlength="150" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= e($description) ?></textarea>
                    </div>

                </div>


                <div class="border-top mt-4 pt-3">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Update Activity
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>