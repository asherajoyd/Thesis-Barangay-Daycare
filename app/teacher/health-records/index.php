<?php 
    $pageTitle = "Health Records";

    require_once '../guard.php';
    include "../../../api/conn.php";

    // Handle form submission para sa pag-add ng record
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_health_record'])) {
        $child_id = (int)$_POST['child_id'];
        $weight = (float)$_POST['weight'];
        $height = (float)$_POST['height'];
        $status = trim($_POST['status']);
        $recorded_date = $_POST['recorded_date'];

        $stmt = $conn->prepare("INSERT INTO health_records (child_id, weight, height, status, recorded_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iddss", $child_id, $weight, $height, $status, $recorded_date);
        
        if ($stmt->execute()) {
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'Health record added successfully!';
        } else {
            $_SESSION['status'] = 'danger';
            $_SESSION['message'] = 'Failed to add health record.';
        }
        $stmt->close();
        header("Location: index.php");
        exit();
    }

    // Kunin ang listahan ng mga bata pati ang birthdate at gender para sa auto-status calculation
    $childrenList = [];
    $resChildren = $conn->query("
        SELECT c.id, e.c_first_name, e.c_middle_name, e.c_last_name, e.c_birthdate, e.c_gender 
        FROM children c 
        LEFT JOIN enrollment e ON c.enrollment_id = e.id
    ");
    if ($resChildren) {
        while ($row = $resChildren->fetch_assoc()) {
            $dob = !empty($row['c_birthdate']) ? new DateTime($row['c_birthdate']) : null;
            $now = new DateTime();
            $ageInMonths = 0;
            if ($dob) {
                $interval = $dob->diff($now);
                $ageInMonths = ($interval->y * 12) + $interval->m;
            }
            $row['age_in_months'] = $ageInMonths;
            $childrenList[] = $row;
        }
    }

    // Kunin ang lahat ng health records mula sa database
    $records = [];
    $resRecords = $conn->query("
        SELECT hr.*, e.c_first_name, e.c_middle_name, e.c_last_name, e.c_gender, e.c_birthdate 
        FROM health_records hr 
        LEFT JOIN children c ON hr.child_id = c.id 
        LEFT JOIN enrollment e ON c.enrollment_id = e.id 
        ORDER BY hr.id DESC
    ");
    if ($resRecords) {
        while ($row = $resRecords->fetch_assoc()) {
            $records[] = $row;
        }
    }

    include '../layout/header.php';
?>

<!-- Import DM Sans Font -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

<style>
    body, h1, h2, h3, h4, h5, h6, .table, .card, .btn, .form-control, .form-select {
        font-family: 'DM Sans', sans-serif !important;
    }
</style>

<div class="container-fluid px-0">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="fw-semibold alert alert-<?= $_SESSION['status'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['status'], $_SESSION['message']); ?>
    <?php endif; ?>

    <!-- PAGE HEADER -->
    <div class="mb-3">
        <h2 class="fw-bold fs-3 text-dark mb-0">
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
    </div>

    <!-- MAIN LAYOUT ROW -->
    <div class="row g-4">

        <!-- LEFT SIDE: STATUS / SUMMARY PANEL -->
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm" style="border-radius: 8px;">
                <div class="card-body p-3">
                    <h5 class="fw-bold text-dark fs-6 mb-3 pb-2 border-bottom">Status Summary</h5>
                    
                    <div class="d-flex align-items-center justify-content-between py-2 px-2 mb-1 rounded bg-light">
                        <span class="text-dark fw-medium small">Normal</span>
                        <span class="badge bg-success text-white px-2 py-1">
                            <?= count(array_filter($records, fn($r) => strtolower(trim($r['status'])) === 'normal')) ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 px-2 mb-1 rounded bg-light">
                        <span class="text-dark fw-medium small">Underweight</span>
                        <span class="badge bg-warning text-dark px-2 py-1">
                            <?= count(array_filter($records, fn($r) => strtolower(trim($r['status'])) === 'underweight')) ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 px-2 mb-1 rounded bg-light">
                        <span class="text-dark fw-medium small">Stunted</span>
                        <span class="badge bg-danger text-white px-2 py-1">
                            <?= count(array_filter($records, fn($r) => strtolower(trim($r['status'])) === 'stunted')) ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 px-2 mb-1 rounded bg-light">
                        <span class="text-dark fw-medium small">Overweight</span>
                        <span class="badge bg-danger text-white px-2 py-1">
                            <?= count(array_filter($records, fn($r) => strtolower(trim($r['status'])) === 'overweight')) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: FILTERS & WIDE TABLE -->
        <div class="col-12 col-lg-9">
            
            <!-- TOP FILTERS BAR -->
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
                <div class="card-body py-3 px-4">
                    <div class="row align-items-center justify-content-between g-3">
                        <div class="col-12 col-md-auto">
                            <span class="text-muted small fw-medium">Real-time records synchronized with daycare masterlist.</span>
                        </div>
                        <div class="col-12 col-md-auto">
                            <button type="button" class="btn btn-sm px-4 text-white fw-semibold" data-bs-toggle="modal" data-bs-target="#addRecordModal" style="background-color: #198754; border-color: #198754; border-radius: 6px;">
                                <i class="bi bi-plus-lg me-1"></i> Add Record
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE CONTAINER (Styled exactly like Children module) -->
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 8px;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 py-3">ID</th>
                                <th class="py-3">Child Name</th>
                                <th class="py-3">Gender</th>
                                <th class="py-3">Birthdate</th>
                                <th class="py-3">Weight (kg)</th>
                                <th class="py-3">Height (cm)</th>
                                <th class="py-3">Status</th>
                                <th class="text-end pe-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($records) > 0): ?>
                                <?php foreach ($records as $index => $row): ?>
                                    <tr>
                                        <td class="ps-4 text-dark fw-medium"><?= $index + 1 ?></td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars(trim(($row['c_first_name'] ?? '') . ' ' . ($row['c_middle_name'] ?? '') . ' ' . ($row['c_last_name'] ?? ''))) ?>
                                        </td>
                                        <td class="text-capitalize text-muted"><?= htmlspecialchars($row['c_gender'] ?? '—') ?></td>
                                        <td class="text-muted"><?= !empty($row['c_birthdate']) ? date('F d, Y', strtotime($row['c_birthdate'])) : '—' ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($row['weight']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($row['height']) ?></td>
                                        <td>
                                            <?php 
                                                $st = strtolower(trim($row['status']));
                                                $badgeClass = 'bg-secondary';
                                                if ($st === 'normal') {
                                                    $badgeClass = 'bg-success';
                                                } elseif ($st === 'underweight') {
                                                    $badgeClass = 'bg-warning text-dark';
                                                } elseif ($st === 'stunted' || $st === 'overweight') {
                                                    $badgeClass = 'bg-danger';
                                                }
                                            ?>
                                            <span class="badge <?= $badgeClass ?> px-2 py-1" style="font-weight: 500;"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm text-white px-3 fw-semibold" style="background-color: #198754; border-color: #198754; border-radius: 6px; font-size: 0.8rem;">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4 bg-light">
                                        No health records found. Click "Add Record" to create one.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ADD RECORD MODAL FORM WITH AUTO-CALCULATION -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold fs-5 text-dark" id="addRecordModalLabel">Add Health Record</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label for="child_id" class="form-label small fw-semibold">Select Child</label>
                        <select name="child_id" id="child_id" class="form-select form-select-sm shadow-none" required onchange="calculateStatus()">
                            <option value="">-- Choose Child --</option>
                            <?php foreach ($childrenList as $child): ?>
                                <option value="<?= $child['id'] ?>" data-dob="<?= $child['c_birthdate'] ?>">
                                    <?= htmlspecialchars(trim($child['c_first_name'] . ' ' . $child['c_middle_name'] . ' ' . $child['c_last_name'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="weight" class="form-label small fw-semibold">Weight (kg)</label>
                            <input type="number" step="0.01" name="weight" id="weight" class="form-control form-control-sm shadow-none" placeholder="e.g. 14.5" required oninput="calculateStatus()">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="height" class="form-label small fw-semibold">Height (cm)</label>
                            <input type="number" step="0.01" name="height" id="height" class="form-control form-control-sm shadow-none" placeholder="e.g. 98.0" required oninput="calculateStatus()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label small fw-semibold">Nutritional Status <span class="text-success fw-normal" style="font-size: 0.75rem;">(Auto-calculated)</span></label>
                        <select name="status" id="status" class="form-select form-select-sm shadow-none fw-bold text-success" required>
                            <option value="Normal">Normal</option>
                            <option value="Underweight">Underweight</option>
                            <option value="Stunted">Stunted</option>
                            <option value="Overweight">Overweight</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="recorded_date" class="form-label small fw-semibold">Date Recorded</label>
                        <input type="date" name="recorded_date" id="recorded_date" class="form-control form-control-sm shadow-none" value="<?= date('Y-m-d') ?>" required>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_health_record" class="btn btn-success btn-sm px-4 fw-semibold" style="background-color: #198754; border-color: #198754;">Save Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JAVASCRIPT FOR AUTO-CALCULATING STATUS -->
<script>
function calculateStatus() {
    const childSelect = document.getElementById('child_id');
    const weightInput = document.getElementById('weight').value;
    const heightInput = document.getElementById('height').value;
    const statusSelect = document.getElementById('status');

    if (!childSelect.value || !weightInput || !heightInput) {
        return;
    }

    const selectedOption = childSelect.options[childSelect.selectedIndex];
    const dobString = selectedOption.getAttribute('data-dob');
    
    if (!dobString) return;

    const dob = new Date(dobString);
    const today = new Date();
    let ageInMonths = (today.getFullYear() - dob.getFullYear()) * 12 + (today.getMonth() - dob.getMonth());
    
    const weight = parseFloat(weightInput);
    const height = parseFloat(heightInput);

    let calculatedStatus = "Normal";

    if (ageInMonths > 0) {
        let estimatedNormalWeight = (ageInMonths / 12) * 2 + 8;
        let estimatedNormalHeight = 70 + (ageInMonths * 0.6);

        if (weight < (estimatedNormalWeight * 0.75)) {
            calculatedStatus = "Underweight";
        } else if (weight > (estimatedNormalWeight * 1.20)) {
            calculatedStatus = "Overweight";
        }

        if (height < (estimatedNormalHeight * 0.85) && calculatedStatus === "Normal") {
            calculatedStatus = "Stunted";
        }
    }

    statusSelect.value = calculatedStatus;
}
</script>

<?php include '../layout/footer.php'; ?>