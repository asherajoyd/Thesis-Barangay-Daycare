<?php

$pageTitle = "Attendance";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// CHECK TEACHER ID IN SESSION
// ==========================================================

if (empty($_SESSION['teacher_id'])) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found. Please contact the administrator.";

    header("Location: ../dashboard/index.php");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// GET CURRENT ACTIVE ACADEMIC YEAR
// ==========================================================

$currentYear = null;

$result = $conn->query("
    SELECT id, academic_year, status
    FROM academic_year
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $currentYear = $result->fetch_assoc();
}


// ==========================================================
// DETERMINE FILTERS
// ==========================================================

$filterAcademicId = isset($_GET['academic_id'])
    ? (int) $_GET['academic_id']
    : 0;

if ($filterAcademicId <= 0 && $currentYear) {
    $filterAcademicId = (int) $currentYear['id'];
}

$filterClassId = isset($_GET['class_id'])
    ? (int) $_GET['class_id']
    : 0;

$filterDate = isset($_GET['date']) && $_GET['date'] !== ''
    ? $_GET['date']
    : date('Y-m-d');


// ==========================================================
// GET TEACHER'S CLASSES (filtered by teacher_id)
// ==========================================================

$classes = [];

if ($filterAcademicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            cl.id,
            cl.name,
            cl.session_id,
            s.name AS session_name,
            s.start_time,
            s.end_time,
            (SELECT COUNT(*)
             FROM enrollment e
             WHERE e.class_id = cl.id
               AND e.status = 'approved') AS enrolled_count
        FROM class cl
        LEFT JOIN session s ON s.id = cl.session_id
        WHERE cl.academic_id = ?
          AND cl.status = 'active'
          AND cl.teacher_id = ?
        ORDER BY s.start_time ASC, cl.name ASC
    ");

    $stmt->bind_param("ii", $filterAcademicId, $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    $stmt->close();
}


// ==========================================================
// GET STUDENTS + EXISTING ATTENDANCE
// ==========================================================

$students = [];

if ($filterClassId > 0 && $filterDate) {

    // Verify class belongs to this teacher
    $verifyStmt = $conn->prepare("
        SELECT id FROM class
        WHERE id = ? AND teacher_id = ?
        LIMIT 1
    ");

    $verifyStmt->bind_param("ii", $filterClassId, $teacherId);
    $verifyStmt->execute();
    $verifyStmt->store_result();

    if ($verifyStmt->num_rows === 0) {
        $verifyStmt->close();
        header("Location: index.php?academic_id=" . $filterAcademicId);
        exit();
    }

    $verifyStmt->close();


    // Get students
    $stmt = $conn->prepare("
        SELECT
            e.id AS enrollment_id,
            e.class_id,

            c.id AS child_id,
            c.first_name,
            c.middle_name,
            c.last_name,
            c.gender,
            c.birthdate,

            a.id AS attendance_id,
            a.status AS attendance_status,
            a.remarks AS attendance_remarks

        FROM enrollment e
        INNER JOIN children c ON c.id = e.child_id
        LEFT JOIN attendance a
            ON a.enrollment_id = e.id
            AND a.date = ?

        WHERE e.class_id = ?
          AND e.status = 'approved'

        ORDER BY c.last_name ASC, c.first_name ASC
    ");

    $stmt->bind_param("si", $filterDate, $filterClassId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}


// ==========================================================
// GET SELECTED CLASS INFO
// ==========================================================

$selectedClass = null;

foreach ($classes as $c) {
    if ((int) $c['id'] === $filterClassId) {
        $selectedClass = $c;
        break;
    }
}


include '../layout/header.php';

?>

<div class="attendance-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : (($_SESSION['status'] ?? '') === 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">Record daily attendance for your classes</p>
        </div>

        <a href="history.php?class_id=<?= (int) $filterClassId ?>&academic_id=<?= (int) $filterAcademicId ?>"
           class="btn btn-outline-primary">
            <i class="bi bi-clock-history me-1"></i>
            History
        </a>

    </div>


    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET">

                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Academic Year</label>
                        <select name="academic_id" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                            <?php
                            $yearsResult = $conn->query("
                                SELECT id, academic_year, status
                                FROM academic_year
                                ORDER BY status DESC, start_date DESC, id DESC
                            ");
                            while ($y = $yearsResult->fetch_assoc()):
                            ?>
                                <option value="<?= (int) $y['id'] ?>"
                                    <?= $filterAcademicId === (int) $y['id'] ? 'selected' : '' ?>>
                                    <?= e($y['academic_year']) ?>
                                    <?= $y['status'] === 'active' ? ' (Active)' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Class</label>
                        <select name="class_id" class="form-select form-select-sm" required>
                            <option value="">— Select class —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $filterClassId === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                    (<?= e($c['session_name']) ?>)
                                    [<?= (int) $c['enrolled_count'] ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Date</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="<?= e($filterDate) ?>">
                    </div>

                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>


    <?php if (empty($classes)): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2">No Classes Assigned</h6>
                <p class="text-muted small mb-0">
                    Wala kang naka-assign na class sa academic year na ito.
                    Please contact the administrator.
                </p>
            </div>
        </div>

    <?php elseif ($selectedClass && !empty($students)): ?>

        <!-- CLASS INFO + BULK ACTIONS -->
        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                    <div>
                        <div class="text-primary text-uppercase fw-bold small">Recording Attendance</div>
                        <div class="fw-bold fs-5">
                            <?= e($selectedClass['name']) ?>
                            <small class="text-muted fw-normal">
                                (<?= e($selectedClass['session_name']) ?>)
                            </small>
                        </div>
                        <small class="text-muted">
                            <?= e(date('F d, Y', strtotime($filterDate))) ?>
                            •
                            <?= e(date('h:i A', strtotime($selectedClass['start_time']))) ?>
                            –
                            <?= e(date('h:i A', strtotime($selectedClass['end_time']))) ?>
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="markAllPresentBtn"
                                class="btn btn-outline-success btn-sm">
                            <i class="bi bi-check2-all me-1"></i>
                            Mark All Present
                        </button>
                    </div>

                </div>

            </div>
        </div>


        <!-- ATTENDANCE FORM -->
        <form method="POST" action="save.php" id="attendanceForm">

            <input type="hidden" name="class_id" value="<?= (int) $filterClassId ?>">
            <input type="hidden" name="date" value="<?= e($filterDate) ?>">
            <input type="hidden" name="academic_id" value="<?= (int) $filterAcademicId ?>">

            <div class="card p-0">

                <div class="card-header bg-paper border-bottom p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-people text-primary me-2"></i>
                            Students
                        </h6>
                        <span class="badge bg-paper-soft text-secondary border">
                            <?= count($students) ?> student<?= count($students) !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>

                <div class="table-responsive">

                    <table class="table mb-0 align-middle">

                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Student</th>
                                <th class="text-center" style="width: 70px;">
                                    <span class="badge bg-success">P</span>
                                </th>
                                <th class="text-center" style="width: 70px;">
                                    <span class="badge bg-danger">A</span>
                                </th>
                                <th class="text-center" style="width: 70px;">
                                    <span class="badge bg-warning text-dark">L</span>
                                </th>
                                <th class="text-center" style="width: 70px;">
                                    <span class="badge bg-info">E</span>
                                </th>
                                <th style="min-width: 200px;">Remarks</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($students as $i => $s): ?>

                                <?php
                                $studentName = trim(
                                    $s['first_name'] . ' ' .
                                    $s['middle_name'] . ' ' .
                                    $s['last_name']
                                );

                                $currentStatus = $s['attendance_status'] ?? 'present';
                                ?>

                                <tr>

                                    <td><?= $i + 1 ?></td>

                                    <td>
                                        <div class="fw-semibold"><?= e($studentName) ?></div>
                                        <small class="text-muted text-capitalize">
                                            <?= e($s['gender']) ?>
                                            <?php if (!empty($s['birthdate'])): ?>
                                                • <?= e(date('M d, Y', strtotime($s['birthdate']))) ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>

                                    <td class="text-center">
                                        <input type="radio"
                                               name="status[<?= (int) $s['enrollment_id'] ?>]"
                                               value="present"
                                               class="form-check-input attendance-radio"
                                               <?= $currentStatus === 'present' ? 'checked' : '' ?>>
                                    </td>

                                    <td class="text-center">
                                        <input type="radio"
                                               name="status[<?= (int) $s['enrollment_id'] ?>]"
                                               value="absent"
                                               class="form-check-input attendance-radio"
                                               <?= $currentStatus === 'absent' ? 'checked' : '' ?>>
                                    </td>

                                    <td class="text-center">
                                        <input type="radio"
                                               name="status[<?= (int) $s['enrollment_id'] ?>]"
                                               value="late"
                                               class="form-check-input attendance-radio"
                                               <?= $currentStatus === 'late' ? 'checked' : '' ?>>
                                    </td>

                                    <td class="text-center">
                                        <input type="radio"
                                               name="status[<?= (int) $s['enrollment_id'] ?>]"
                                               value="excused"
                                               class="form-check-input attendance-radio"
                                               <?= $currentStatus === 'excused' ? 'checked' : '' ?>>
                                    </td>

                                    <td>
                                        <input type="text"
                                               name="remarks[<?= (int) $s['enrollment_id'] ?>]"
                                               class="form-control form-control-sm"
                                               placeholder="Optional"
                                               maxlength="255"
                                               value="<?= e($s['attendance_remarks'] ?? '') ?>">
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div class="card-footer bg-white border-top p-3">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save Attendance
                        </button>
                    </div>
                </div>

            </div>

        </form>

    <?php elseif ($selectedClass): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2">No Students in This Class</h6>
                <p class="text-muted small mb-0">
                    Wala pang naka-assign na bata sa class na ito.
                </p>
            </div>
        </div>

    <?php else: ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-check fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2">Select a Class</h6>
                <p class="text-muted small mb-0">
                    Pumili ng class at date para mag-record ng attendance.
                </p>
            </div>
        </div>

    <?php endif; ?>

</div>


<script>
(function () {
    const btn = document.getElementById('markAllPresentBtn');

    if (!btn) return;

    btn.addEventListener('click', function () {
        const radios = document.querySelectorAll('input.attendance-radio[value="present"]');
        radios.forEach(r => r.checked = true);
    });
})();
</script>

<?php include '../layout/footer.php'; ?>