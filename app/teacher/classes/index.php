<?php

$pageTitle = "My Classes";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// CHECK TEACHER ID
// ==========================================================

if (empty($_SESSION['teacher_id'])) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found. Please contact the administrator.";

    header("Location: ../dashboard/");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// GET CURRENT ACTIVE ACADEMIC YEAR
// ==========================================================

$currentYear = null;

$result = $conn->query("
    SELECT id, academic_year
    FROM academic_year
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $currentYear = $result->fetch_assoc();
}

$academicId = $currentYear ? (int) $currentYear['id'] : 0;


// ==========================================================
// GET TEACHER'S CLASSES
// ==========================================================

$classes = [];

if ($academicId > 0) {

    $stmt = $conn->prepare("
        SELECT
            cl.id,
            cl.name,
            cl.capacity,
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

        WHERE cl.teacher_id = ?
          AND cl.academic_id = ?
          AND cl.status = 'active'

        ORDER BY s.start_time ASC, cl.name ASC
    ");

    $stmt->bind_param("ii", $teacherId, $academicId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    $stmt->close();
}


// ==========================================================
// DETERMINE SELECTED CLASS
// ==========================================================

$selectedClassId = isset($_GET['class_id'])
    ? (int) $_GET['class_id']
    : 0;

if ($selectedClassId <= 0 && !empty($classes)) {
    $selectedClassId = (int) $classes[0]['id'];
}

$selectedClass = null;

foreach ($classes as $c) {
    if ((int) $c['id'] === $selectedClassId) {
        $selectedClass = $c;
        break;
    }
}


// ==========================================================
// GET STUDENTS FOR SELECTED CLASS
// ==========================================================

$students = [];

if ($selectedClass) {

    $stmt = $conn->prepare("
        SELECT
            e.id AS enrollment_id,

            c.id AS child_id,
            c.first_name,
            c.middle_name,
            c.last_name,
            c.gender,
            c.birthdate,

            u.first_name AS parent_first_name,
            u.last_name AS parent_last_name,
            u.email AS parent_email,
            u.contact AS parent_contact

        FROM enrollment e
        INNER JOIN children c ON c.id = e.child_id
        LEFT JOIN users u ON u.id = c.user_id

        WHERE e.class_id = ?
          AND e.status = 'approved'

        ORDER BY c.last_name ASC, c.first_name ASC
    ");

    $stmt->bind_param("i", $selectedClassId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}


include '../layout/header.php';

?>

<div class="teacher-classes-page">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">View your classes and students</p>
        </div>

    </div>


    <?php if (empty($classes)): ?>

        <!-- NO CLASSES -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Classes Assigned</h6>
                <p class="text-muted small mb-0">
                    Wala kang naka-assign na class sa academic year na ito.
                    Please contact the administrator.
                </p>
            </div>
        </div>

    <?php else: ?>

        <!-- CLASS SELECTOR -->
        <?php if (count($classes) > 1): ?>

            <div class="card mb-3">
                <div class="card-body py-3 px-4">

                    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">

                        <label for="class_id" class="text-muted small fw-bold mb-0 text-uppercase">
                            <i class="bi bi-door-open me-1"></i>
                            Select Class
                        </label>

                        <select name="class_id"
                                id="class_id"
                                class="form-select form-select-sm"
                                style="max-width: 300px;"
                                onchange="this.form.submit()">

                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $selectedClassId === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                    (<?= e($c['session_name']) ?>)
                                </option>
                            <?php endforeach; ?>

                        </select>

                    </form>

                </div>
            </div>

        <?php endif; ?>


        <?php if ($selectedClass): ?>

            <!-- CLASS INFO CARD -->
            <div class="card mb-3">

                <div class="card-body p-4">

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                        <div class="d-flex align-items-center gap-3">

                            <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 56px; height: 56px;">
                                <i class="bi bi-door-open fs-4"></i>
                            </div>

                            <div>
                                <div class="text-primary text-uppercase fw-bold small">
                                    Class Details
                                </div>
                                <h3 class="fw-bold fs-5 mb-0">
                                    <?= e($selectedClass['name']) ?>
                                </h3>
                                <small class="text-muted">
                                    <?= e($selectedClass['session_name']) ?>
                                    •
                                    <?= e(date('h:i A', strtotime($selectedClass['start_time']))) ?>
                                    –
                                    <?= e(date('h:i A', strtotime($selectedClass['end_time']))) ?>
                                    <?php if ($currentYear): ?>
                                        • AY <?= e($currentYear['academic_year']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>

                        </div>


                        <div class="text-end">

                            <?php
                            $enrolled = (int) $selectedClass['enrolled_count'];
                            $capacity = (int) $selectedClass['capacity'];
                            $isFull   = $enrolled >= $capacity;
                            ?>

                            <div class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                                Students
                            </div>

                            <div class="fs-4 fw-bold">
                                <span class="<?= $isFull ? 'text-danger' : 'text-success' ?>">
                                    <?= $enrolled ?>
                                </span>
                                <span class="text-muted">/ <?= $capacity ?></span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- STUDENTS TABLE -->
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

                <?php if (!empty($students)): ?>

                    <div class="table-responsive">

                        <table class="table mb-0 data-table text-nowrap align-middle">

                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Student</th>
                                    <th>Gender</th>
                                    <th>Birthdate</th>
                                    <th>Parent / Guardian</th>
                                    <th>Contact</th>
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

                                    $parentName = trim(
                                        ($s['parent_first_name'] ?? '') . ' ' .
                                        ($s['parent_last_name'] ?? '')
                                    );
                                    ?>

                                    <tr>

                                        <td><?= $i + 1 ?></td>

                                        <td>
                                            <div class="fw-semibold"><?= e($studentName) ?></div>
                                        </td>

                                        <td class="text-capitalize"><?= e($s['gender']) ?></td>

                                        <td>
                                            <?= !empty($s['birthdate'])
                                                ? e(date('M d, Y', strtotime($s['birthdate'])))
                                                : '—' ?>
                                        </td>

                                        <td>
                                            <?php if ($parentName): ?>
                                                <div class="fw-semibold"><?= e($parentName) ?></div>
                                                <?php if (!empty($s['parent_email'])): ?>
                                                    <small class="text-muted text-break">
                                                        <?= e($s['parent_email']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= e($s['parent_contact'] ?: '—') ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="card-body text-center py-5">
                        <i class="bi bi-people fs-2 text-muted"></i>
                        <h6 class="fw-semibold mt-2 mb-1">No Students Yet</h6>
                        <p class="text-muted small mb-0">
                            Wala pang naka-assign na bata sa class na ito.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>