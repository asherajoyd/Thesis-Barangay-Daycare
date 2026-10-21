<?php

$pageTitle = "Add Health Record";

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
// GET TEACHER'S STUDENTS
// ==========================================================

$students = [];

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.first_name,
        c.middle_name,
        c.last_name,
        c.birthdate,
        cl.name AS class_name,
        s.name AS session_name
    FROM children c
    INNER JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
    INNER JOIN class cl ON cl.id = en.class_id
    LEFT JOIN session s ON s.id = cl.session_id
    WHERE cl.teacher_id = ?
    ORDER BY c.last_name ASC, c.first_name ASC
");

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$stmt->close();


// ==========================================================
// FORM STATE
// ==========================================================

$child_id        = 0;
$weight          = "";
$height          = "";
$status          = "normal";
$remarks         = "";
$recorded_date   = date('Y-m-d');

$dental_status    = "";
$dental_findings  = "";
$dental_treatment = "";

$errorMessage = "";


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $child_id         = (int) ($_POST['child_id'] ?? 0);
    $weight           = trim($_POST['weight'] ?? '');
    $height           = trim($_POST['height'] ?? '');
    $status           = trim($_POST['status'] ?? 'normal');
    $remarks          = trim($_POST['remarks'] ?? '');
    $recorded_date    = trim($_POST['recorded_date'] ?? '');

    $dental_status    = trim($_POST['dental_status'] ?? '');
    $dental_findings  = trim($_POST['dental_findings'] ?? '');
    $dental_treatment = trim($_POST['dental_treatment'] ?? '');

    // Validate
    if ($child_id <= 0) {
        $errorMessage = "Please select a child.";
    } elseif (empty($recorded_date)) {
        $errorMessage = "Recorded date is required.";
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

                $stmt = $conn->prepare("
                    INSERT INTO health_records (
                        child_id, weight, height, status, remarks,
                        dental_status, dental_findings, dental_treatment,
                        recorded_date, recorded_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iddssssssi",
                    $child_id,
                    $weightVal,
                    $heightVal,
                    $status,
                    $remarks,
                    $dentalStatusVal,
                    $dental_findings,
                    $dental_treatment,
                    $recorded_date,
                    $teacherId
                );

                if (!$stmt->execute()) {
                    throw new Exception($stmt->error);
                }

                $stmt->close();

                // ==========================================================
                // NOTIFY PARENT
                // ==========================================================

                require_once '../../../api/notification-helper.php';

                notifyParentOfChild(
                    $conn,
                    $child_id,
                    'health_record',
                    'New Health Record',
                    'A new health record has been added for your child.',
                    '../health-records/'
                );

                $_SESSION['status']  = "success";
                $_SESSION['message'] = "Health record added successfully.";

                header("Location: index.php");
                exit;

            } catch (Exception $ex) {
                $errorMessage = "Failed to add record: " . $ex->getMessage();
            }
        }
    }
}


include '../layout/header.php';

?>

<div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div class="min-w-0">
            <h2 class="fw-bold fs-4 mb-0">Add Health Record</h2>
            <small class="text-muted">Record a child's growth and dental health</small>
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

            <form action="create.php" method="POST" autocomplete="off">

                <!-- GROWTH SECTION -->
                <h6 class="fw-bold text-primary text-uppercase small mb-3">
                    <i class="bi bi-activity me-1"></i> Growth Monitoring
                </h6>

                <div class="row g-3 mb-4">

                    <div class="col-12">
                        <label class="form-label small mb-1">Child <span class="text-danger">*</span></label>
                        <select name="child_id" id="child_id" class="form-select" required onchange="autoCalc()">
                            <option value="">— Select child —</option>
                            <?php foreach ($students as $s): ?>
                                <?php
                                $name = trim($s['first_name'] . ' ' . $s['middle_name'] . ' ' . $s['last_name']);
                                ?>
                                <option value="<?= (int) $s['id'] ?>"
                                        data-birthdate="<?= e($s['birthdate']) ?>"
                                    <?= $child_id === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= e($name) ?> (<?= e($s['class_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Weight (kg)</label>
                        <input type="number" step="0.01" min="0" name="weight" id="weight"
                               class="form-control" value="<?= e($weight) ?>"
                               placeholder="e.g. 14.5" oninput="autoCalc()">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Height (cm)</label>
                        <input type="number" step="0.01" min="0" name="height" id="height"
                               class="form-control" value="<?= e($height) ?>"
                               placeholder="e.g. 98.0" oninput="autoCalc()">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">
                            Nutritional Status <span class="text-danger">*</span>
                            <span class="text-muted fw-normal" style="font-size: 0.75rem;">(auto)</span>
                        </label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="normal"      <?= $status === 'normal'      ? 'selected' : '' ?>>Normal</option>
                            <option value="underweight" <?= $status === 'underweight' ? 'selected' : '' ?>>Underweight</option>
                            <option value="stunted"     <?= $status === 'stunted'     ? 'selected' : '' ?>>Stunted</option>
                            <option value="overweight"  <?= $status === 'overweight'  ? 'selected' : '' ?>>Overweight</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Date Recorded <span class="text-danger">*</span></label>
                        <input type="date" name="recorded_date" class="form-control"
                               value="<?= e($recorded_date) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Growth Remarks (optional)</label>
                        <textarea name="remarks" class="form-control" rows="2"
                                  placeholder="e.g. observed weight gain since last checkup"><?= e($remarks) ?></textarea>
                    </div>

                </div>


                <!-- DENTAL SECTION -->
                <h6 class="fw-bold text-primary text-uppercase small mb-3 pt-3 border-top">
                    <i class="bi bi-emoji-smile me-1"></i>
                    Dental Health
                    <span class="text-muted fw-normal text-lowercase" style="font-size: 0.75rem;">(optional)</span>
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
                        <textarea name="dental_findings" class="form-control" rows="2"
                                  placeholder="e.g. may tooth decay sa upper molar"><?= e($dental_findings) ?></textarea>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Treatment / Action</label>
                        <textarea name="dental_treatment" class="form-control" rows="2"
                                  placeholder="e.g. refer to dentist, brushing reminder"><?= e($dental_treatment) ?></textarea>
                    </div>

                </div>


                <div class="border-top mt-4 pt-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save Record
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

</div>


<script>
function autoCalc() {
    const childSelect = document.getElementById('child_id');
    const weight = parseFloat(document.getElementById('weight').value);
    const height = parseFloat(document.getElementById('height').value);
    const statusEl = document.getElementById('status');

    if (!childSelect.value || isNaN(weight) || isNaN(height)) return;

    const option = childSelect.options[childSelect.selectedIndex];
    const dobStr = option.getAttribute('data-birthdate');

    if (!dobStr) return;

    const dob = new Date(dobStr);
    const now = new Date();
    const months = (now.getFullYear() - dob.getFullYear()) * 12 + (now.getMonth() - dob.getMonth());

    if (months <= 0) return;

    const normalWeight = (months / 12) * 2 + 8;
    const normalHeight = 70 + (months * 0.6);

    let calc = "normal";

    if (weight < normalWeight * 0.75) {
        calc = "underweight";
    } else if (weight > normalWeight * 1.20) {
        calc = "overweight";
    }

    if (height < normalHeight * 0.85 && calc === "normal") {
        calc = "stunted";
    }

    statusEl.value = calc;
}
</script>

<?php include '../layout/footer.php'; ?>