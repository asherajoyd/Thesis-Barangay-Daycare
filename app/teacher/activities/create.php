<?php

$pageTitle = "Add Activity";

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
// GET TEACHER'S CLASSES
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

$class_id      = 0;
$title         = "";
$description   = "";
$activity_date = date('Y-m-d');

$errorMessage = "";


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_id      = (int) ($_POST['class_id'] ?? 0);
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $activity_date = trim($_POST['activity_date'] ?? '');

    // Validate
    if ($class_id <= 0) {
        $errorMessage = "Please select a class.";
    } elseif (empty($title)) {
        $errorMessage = "Title is required.";
    } elseif (empty($activity_date)) {
        $errorMessage = "Activity date is required.";
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
                    INSERT INTO activity (teacher_id, class_id, title, description, activity_date)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iisss",
                    $teacherId,
                    $class_id,
                    $title,
                    $description,
                    $activity_date
                );

                if (!$stmt->execute()) {
                    throw new Exception($stmt->error);
                }

                $stmt->close();
                // ==========================================================
                // NOTIFY PARENTS OF CLASS
                // ==========================================================

                require_once '../../../api/notification-helper.php';

                notifyParentsOfClass(
                    $conn,
                    $class_id,
                    'activity',
                    'New Activity: ' . $title,
                    mb_strimwidth($description, 0, 100, '...'),
                    '../activities/'
                );

                $_SESSION['status']  = "success";
                $_SESSION['message'] = "Activity added successfully.";
                header("Location: index.php");
                exit;

            } catch (Exception $ex) {
                $errorMessage = "Failed to add activity: " . $ex->getMessage();
            }
        }
    }
}


include '../layout/header.php';

?>

<div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0">Add Activity</h2>
            <small class="text-muted">Create a learning activity for your class</small>
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

            <form action="create.php" method="POST" autocomplete="off">

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
                               value="<?= e($title) ?>" maxlength="150"
                               placeholder="e.g. Color the Shapes" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Description</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Describe the activity..."><?= e($description) ?></textarea>
                    </div>

                </div>


                <div class="border-top mt-4 pt-3">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Activity
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>