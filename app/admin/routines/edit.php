<?php

$pageTitle = "Edit Activity";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// FIXED CATEGORY LIST
// ==========================================================

$categories = [
    'Free Play',
    'Routine',
    'Learning',
    'Outdoor',
    'Feeding',
    'Rest',
    'Interactive',
    'Dismissal',
    'Other',
];


// ==========================================================
// FORM STATE
// ==========================================================

$id         = 0;
$session_id = 0;
$order_no   = 0;
$time_start = "";
$time_end   = "";
$duration   = "";
$category   = "";
$activity   = "";

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
        SELECT * FROM routine WHERE id = ? LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $routine = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$routine) {
        header("Location: index.php");
        exit;
    }

    $session_id = (int) $routine['session_id'];
    $order_no   = (int) $routine['order_no'];
    $time_start = $routine['time_start'];
    $time_end   = $routine['time_end'];
    $duration   = $routine['duration'];
    $category   = $routine['category'];
    $activity   = $routine['activity'];
}


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id         = (int) ($_POST['id'] ?? 0);
    $session_id = (int) ($_POST['session_id'] ?? 0);
    $time_start = trim($_POST['time_start'] ?? '');
    $time_end   = trim($_POST['time_end'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $activity   = trim($_POST['activity'] ?? '');

    if (
        $id <= 0 ||
        $session_id <= 0 ||
        empty($time_start) ||
        empty($time_end) ||
        empty($activity)
    ) {

        $errorMessage = "Please fill in all required fields.";

    } elseif (!in_array($category, $categories, true)) {

        $errorMessage = "Please select a valid category.";

    } elseif (strtotime($time_end) <= strtotime($time_start)) {

        $errorMessage = "End time must be later than start time.";

    } else {

        // Auto-compute duration
        $startSeconds = strtotime($time_start);
        $endSeconds   = strtotime($time_end);
        $diffMinutes  = (int) round(($endSeconds - $startSeconds) / 60);
        $duration     = $diffMinutes . ' Mins';

        try {

            $stmt = $conn->prepare("
                UPDATE routine
                SET time_start = ?, time_end = ?, duration = ?,
                    category = ?, activity = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssssi",
                $time_start,
                $time_end,
                $duration,
                $category,
                $activity,
                $id
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['status']  = "success";
            $_SESSION['message'] = "Activity updated successfully.";

            header("Location: index.php?session_id=" . (int) $session_id);
            exit;

        } catch (Exception $e) {

            $errorMessage = "Failed to update activity. Please try again.";
        }
    }
}


include '../layout/header.php';

?>

<div>

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

        <div class="min-w-0">
            <h2 class="fw-bold fs-4 mb-0">Edit Activity</h2>
            <small class="text-muted">
                Update routine activity (Order #<?= (int) $order_no ?>)
            </small>
        </div>

        <a href="index.php?session_id=<?= (int) $session_id ?>"
           class="btn btn-sm btn-light border">
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

            <form action="edit.php" method="POST" autocomplete="off">

                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <input type="hidden" name="session_id" value="<?= (int) $session_id ?>">

                <div class="row g-3">

                    <!-- Order No (readonly) -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Order No</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= (int) $order_no ?>" readonly>
                    </div>

                    <!-- Duration (auto) -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Duration</label>
                        <input type="text" class="form-control bg-light"
                               id="durationPreview" readonly
                               value="<?= e($duration) ?>">
                    </div>

                    <!-- Time Start -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">
                            Start Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" name="time_start" id="timeStart"
                               class="form-control"
                               value="<?= e($time_start) ?>" required>
                    </div>

                    <!-- Time End -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">
                            End Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" name="time_end" id="timeEnd"
                               class="form-control"
                               value="<?= e($time_end) ?>" required>
                    </div>

                    <!-- Category -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">
                            Category <span class="text-danger">*</span>
                        </label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c) ?>"
                                    <?= $category === $c ? 'selected' : '' ?>>
                                    <?= e($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Activity -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">
                            Activity <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="activity" class="form-control"
                               value="<?= e($activity) ?>" maxlength="150" required>
                    </div>

                </div>


                <!-- FOOTER -->
                <div class="border-top mt-4 pt-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">

                        <a href="index.php?session_id=<?= (int) $session_id ?>"
                           class="btn btn-light border">Cancel</a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Update Activity
                        </button>

                    </div>
                </div>

            </form>

        </div>
    </div>

</div>


<script>
(function () {
    const timeStart = document.getElementById('timeStart');
    const timeEnd   = document.getElementById('timeEnd');
    const duration  = document.getElementById('durationPreview');

    function compute() {
        if (!timeStart.value || !timeEnd.value) return;

        const s = timeStart.value.split(':');
        const e = timeEnd.value.split(':');

        const startMin = parseInt(s[0], 10) * 60 + parseInt(s[1], 10);
        const endMin   = parseInt(e[0], 10) * 60 + parseInt(e[1], 10);

        const diff = endMin - startMin;

        if (diff > 0) {
            duration.value = diff + ' Mins';
        }
    }

    timeStart.addEventListener('change', compute);
    timeEnd.addEventListener('change', compute);
})();
</script>

<?php include '../layout/footer.php'; ?>