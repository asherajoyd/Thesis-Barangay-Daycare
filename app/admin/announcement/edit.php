<?php

$pageTitle = "Edit Announcement";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


$id       = 0;
$title    = "";
$content  = "";
$audience = "all";
$priority = "normal";
$status   = "published";

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
        SELECT * FROM announcement WHERE id = ? LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $announcement = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$announcement) {
        header("Location: index.php");
        exit;
    }

    $title    = $announcement['title'];
    $content  = $announcement['content'];
    $audience = $announcement['audience'];
    $priority = $announcement['priority'];
    $status   = $announcement['status'];
}


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = (int) ($_POST['id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $audience = trim($_POST['audience'] ?? 'all');
    $priority = trim($_POST['priority'] ?? 'normal');
    $status   = trim($_POST['status'] ?? 'published');

    if (
        $id <= 0 ||
        empty($title) ||
        empty($content)
    ) {

        $errorMessage = "Title and content are required.";

    } elseif (!in_array($audience, ['all', 'parent', 'teacher'], true)) {

        $errorMessage = "Invalid audience.";

    } elseif (!in_array($priority, ['normal', 'important', 'urgent'], true)) {

        $errorMessage = "Invalid priority.";

    } elseif (!in_array($status, ['published', 'draft'], true)) {

        $errorMessage = "Invalid status.";

    } else {

        try {

            $stmt = $conn->prepare("
                UPDATE announcement
                SET title = ?, content = ?, audience = ?, priority = ?, status = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssssi",
                $title,
                $content,
                $audience,
                $priority,
                $status,
                $id
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['status']  = "success";
            $_SESSION['message'] = "Announcement updated successfully.";

            header("Location: index.php");
            exit;

        } catch (Exception $e) {

            $errorMessage = "Failed to update announcement. Please try again.";
        }
    }
}


include '../layout/header.php';

?>

<div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

        <div class="min-w-0">
            <h2 class="fw-bold fs-4 mb-0">Edit Announcement</h2>
            <small class="text-muted">Update announcement details</small>
        </div>

        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
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

                    <div class="col-12">
                        <label class="form-label small mb-1">
                            Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" class="form-control"
                               value="<?= e($title) ?>" maxlength="200" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">
                            Content <span class="text-danger">*</span>
                        </label>
                        <textarea name="content" class="form-control"
                                  rows="6" required><?= e($content) ?></textarea>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Audience</label>
                        <select name="audience" class="form-select">
                            <option value="all"     <?= $audience === 'all'     ? 'selected' : '' ?>>Everyone</option>
                            <option value="parent"  <?= $audience === 'parent'  ? 'selected' : '' ?>>Parents Only</option>
                            <option value="teacher" <?= $audience === 'teacher' ? 'selected' : '' ?>>Teachers Only</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="normal"    <?= $priority === 'normal'    ? 'selected' : '' ?>>Normal</option>
                            <option value="important" <?= $priority === 'important' ? 'selected' : '' ?>>Important</option>
                            <option value="urgent"    <?= $priority === 'urgent'    ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft"     <?= $status === 'draft'     ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>

                </div>


                <div class="border-top mt-4 pt-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">

                        <a href="index.php" class="btn btn-light border">Cancel</a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Update Announcement
                        </button>

                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>