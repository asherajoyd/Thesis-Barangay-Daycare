<?php

$pageTitle = "Edit Health Record";

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


$id              = 0;
$child_id        = 0;
$weight          = "";
$height          = "";
$status          = "normal";
$remarks         = "";
$recorded_date   = "";

$dental_status    = "";
$dental_findings  = "";
$dental_treatment = "";

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
        SELECT hr.*
        FROM health_records hr
        INNER JOIN children c ON c.id = hr.child_id
        INNER JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
        INNER JOIN class cl ON cl.id = en.class_id
        WHERE hr.id = ? AND cl.teacher_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $id, $teacherId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$record) {
        $_SESSION['status']  = "danger";
        $_SESSION['message'] = "Record not found or access denied.";
        header("Location: index.php");
        exit;
    }

    $child_id         = (int) $record['child_id'];
    $weight           = $record['weight'] ?? '';
    $height           = $record['height'] ?? '';
    $status           = $record['status'];
    $remarks          = $record['remarks'] ?? '';
    $recorded_date    = $record['recorded_date'];

    $dental_status    = $record['dental_status'] ?? '';
    $dental_findings  = $record['dental_findings'] ?? '';
    $dental_treatment = $record['dental_treatment'] ?? '';
}


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id               = (int) ($_POST['id'] ?? 0);
    $weight           = trim($_POST['weight'] ?? '');
    $height           = trim($_POST['height'] ?? '');
    $status           = trim($_POST['status'] ?? 'normal');
    $remarks          = trim($_POST['remarks'] ?? '');
    $recorded_date    = trim($_POST['recorded_date'] ?? '');

    $dental_status    = trim($_POST['dental_status'] ?? '');
    $dental_findings  = trim($_POST['dental_findings'] ?? '');
    $dental_treatment = trim($_POST['dental_treatment'] ?? '');

    if ($id <= 0 || empty($recorded_date)) {
        $errorMessage = "Invalid request.";
    } elseif (!in_array($status, ['normal', 'underweight', 'stunted', 'overweight'], true)) {
        $errorMessage = "Invalid nutritional status.";
    } else {

        $weightVal = $weight !== '' ? (float) $weight : null;
        $heightVal = $height !== '' ? (float) $height : null;

        $dentalStatusVal = null;

        if (!empty($dental_status)) {
            if (!in_array($dental_status, ['healthy', 'needs_attention', 'with_cavities', 'referred'], true)) {
                $errorMessage = "Invalid dental status.";
            } else {
                $dentalStatusVal = $dental_status;
            }
        }

        if (empty($errorMessage)) {

            try {

                // Verify ownership
                $stmt = $conn->prepare("
                    SELECT hr.id
                    FROM health_records hr
                    INNER JOIN children c ON c.id = hr.child_id
                    INNER JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
                    INNER JOIN class cl ON cl.id = en.class_id
                    WHERE hr.id = ? AND cl.teacher_id = ?
                    LIMIT 1
                ");
                $stmt->bind_param("ii", $id, $teacherId);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 0) {
                    $stmt->close();
                    throw new Exception("Access denied.");
                }
                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE health_records
                    SET weight = ?, height = ?, status = ?, remarks = ?,
                        dental_status = ?, dental_findings = ?, dental_treatment = ?,
                        recorded_date = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ddssssssi",
                    $weightVal,
                    $heightVal,
                    $status,
                    $remarks,
                    $dentalStatusVal,
                    $dental_findings,
                    $dental_treatment,
                    $recorded_date,
                    $id
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['status']  = "success";
                $_SESSION['message'] = "Health record updated.";
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
            <h2 class="fw-bold fs-4 mb-0">Edit Health Record</h2>
            <small class="text-muted">Update record details</small>
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

                <!-- GROWTH SECTION -->
                <h6 class="fw-bold text-primary text-uppercase small mb-3">
                    <i class="bi bi-activity me-1"></i> Growth Monitoring
                </h6>

                <div class="row g-3 mb-4">

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Weight (kg)</label>
                        <input type="number" step="0.01" min="0" name="weight"
                               class="form-control" value="<?= e($weight) ?>">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Height (cm)</label>
                        <input type="number" step="0.01" min="0" name="height"
                               class="form-control" value="<?= e($height) ?>">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Nutritional Status</label>
                        <select name="status" class="form-select" required>
                            <option value="normal"      <?= $status === 'normal'      ? 'selected' : '' ?>>Normal</option>
                            <option value="underweight" <?= $status === 'underweight' ? 'selected' : '' ?>>Underweight</option>
                            <option value="stunted"     <?= $status === 'stunted'     ? 'selected' : '' ?>>Stunted</option>
                            <option value="overweight"  <?= $status === 'overweight'  ? 'selected' : '' ?>>Overweight</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Date</label>
                        <input type="date" name="recorded_date" class="form-control"
                               value="<?= e($recorded_date) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Growth Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"><?= e($remarks) ?></textarea>
                    </div>

                </div>


                <!-- DENTAL SECTION -->
                <h6 class="fw-bold text-primary text-uppercase small mb-3 pt-3 border-top">
                    <i class="bi bi-emoji-smile me-1"></i> Dental Health
                </h6>

                <div class="row g-3">

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Oral Health Status</label>
                        <select name="dental_status" class="form-select">
                            <option value="">— Not assessed —</option>
                            <option value="healthy"         <?= $dental_status === 'healthy'         ? 'selected' : '' ?>>Healthy</option>
                            <option value="needs_attention" <?= $dental_status === 'needs_attention' ? 'selected' : '' ?>>Needs Attention</option>
                            <option value="with_cavities"   <?= $dental_status === 'with_cavities'   ? 'selected' : '' ?>>With Cavities</option>
                            <option value="referred"        <?= $dental_status === 'referred'        ? 'selected' : '' ?>>Referred to Dentist</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Findings</label>
                        <textarea name="dental_findings" class="form-control" rows="2"><?= e($dental_findings) ?></textarea>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Treatment / Action</label>
                        <textarea name="dental_treatment" class="form-control" rows="2"><?= e($dental_treatment) ?></textarea>
                    </div>

                </div>


                <div class="border-top mt-4 pt-3">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Update Record
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../layout/footer.php'; ?>