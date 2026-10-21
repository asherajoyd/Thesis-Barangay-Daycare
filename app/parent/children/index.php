<?php

$pageTitle = "Children";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET PARENT USER ID
// ==========================================================

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../../login.php");
    exit();
}


// ==========================================================
// GET PARENT'S CHILDREN
// ==========================================================

$children = [];

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.first_name,
        c.middle_name,
        c.last_name,
        c.gender,
        c.birthdate,
        c.place_of_birth,
        c.address,

        e.id AS enrollment_id,
        e.academic_id,
        e.class_id,
        e.status AS enrollment_status,
        e.created_at AS enrolled_at,

        cl.name AS class_name,
        s.name AS session_name,
        s.start_time,
        s.end_time,

        t.first_name AS teacher_first_name,
        t.middle_name AS teacher_middle_name,
        t.last_name AS teacher_last_name,

        ay.academic_year

    FROM children c

    LEFT JOIN enrollment e
        ON e.child_id = c.id
        AND e.status = 'approved'

    LEFT JOIN class cl
        ON cl.id = e.class_id

    LEFT JOIN session s
        ON s.id = cl.session_id

    LEFT JOIN teacher t
        ON t.id = cl.teacher_id

    LEFT JOIN academic_year ay
        ON ay.id = e.academic_id

    WHERE c.user_id = ?

    ORDER BY c.id ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

$stmt->close();


// ==========================================================
// DETERMINE SELECTED CHILD
// ==========================================================

$selectedChildId = isset($_GET['child_id'])
    ? (int) $_GET['child_id']
    : 0;

if ($selectedChildId <= 0 && !empty($children)) {
    $selectedChildId = (int) $children[0]['id'];
}

$selectedChild = null;

foreach ($children as $c) {
    if ((int) $c['id'] === $selectedChildId) {
        $selectedChild = $c;
        break;
    }
}


// ==========================================================
// GET FAMILY MEMBERS + DOCUMENTS FOR SELECTED CHILD
// ==========================================================

$familyMembers = [];
$documents     = [];

if ($selectedChild) {

    // Family
    $stmt = $conn->prepare("
        SELECT
            p.*,
            u.email AS user_email,
            u.contact AS user_contact
        FROM parent_info p
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.child_id = ?
        ORDER BY p.is_primary DESC, p.id ASC
    ");

    $stmt->bind_param("i", $selectedChild['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $familyMembers[] = $row;
    }

    $stmt->close();


    // Documents
    if (!empty($selectedChild['enrollment_id'])) {

        $stmt = $conn->prepare("
            SELECT * FROM enrollment_document
            WHERE enrollment_id = ?
            ORDER BY id ASC
        ");

        $stmt->bind_param("i", $selectedChild['enrollment_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }

        $stmt->close();
    }
}


include '../layout/header.php';

?>

<div class="children-page">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">View your child's complete profile</p>
        </div>

    </div>


    <?php if (empty($children)): ?>

        <!-- NO CHILDREN -->
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-exclamation fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Child Record Found</h6>
                <p class="text-muted small mb-0">
                    Walang naka-link na bata sa account mo.
                </p>
            </div>
        </div>

    <?php else: ?>

        <!-- CHILD SELECTOR -->
        <?php if (count($children) > 1): ?>

            <div class="card mb-3">
                <div class="card-body py-3 px-4">

                    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">

                        <label for="child_id" class="text-muted small fw-bold mb-0 text-uppercase">
                            <i class="bi bi-person-hearts me-1"></i>
                            Select Child
                        </label>

                        <select name="child_id"
                                id="child_id"
                                class="form-select form-select-sm"
                                style="max-width: 300px;"
                                onchange="this.form.submit()">

                            <?php foreach ($children as $c): ?>
                                <?php
                                $childName = trim(
                                    $c['first_name'] . ' ' .
                                    $c['middle_name'] . ' ' .
                                    $c['last_name']
                                );
                                ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= $selectedChildId === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($childName) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                    </form>

                </div>
            </div>

        <?php endif; ?>


        <?php if ($selectedChild): ?>

            <?php
            $childName = trim(
                $selectedChild['first_name'] . ' ' .
                $selectedChild['middle_name'] . ' ' .
                $selectedChild['last_name']
            );

            $initial = strtoupper(substr($selectedChild['first_name'], 0, 1));
            $hasClass = !empty($selectedChild['class_id']);
            ?>

            <!-- CHILD PROFILE -->
            <div class="card mb-3">

                <!-- Header -->
                <div class="card-body p-4">

                    <div class="d-flex align-items-center gap-3">

                        <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width: 64px; height: 64px; font-size: 1.5rem;">
                            <?= e($initial) ?>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="text-primary text-uppercase fw-bold small">
                                Child Profile
                            </div>
                            <h3 class="fw-bold fs-5 mb-1 text-break">
                                <?= e($childName) ?>
                            </h3>
                            <small class="text-muted text-capitalize">
                                <?= e($selectedChild['gender']) ?>
                                <?php if (!empty($selectedChild['birthdate'])): ?>
                                    • <?= e(date('F d, Y', strtotime($selectedChild['birthdate']))) ?>
                                <?php endif; ?>
                            </small>
                        </div>

                        <?php if ($hasClass): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0">
                                Enrolled
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle flex-shrink-0">
                                No Class
                            </span>
                        <?php endif; ?>

                    </div>

                </div>


                <!-- Class Info -->
                <?php if ($hasClass): ?>

                    <div class="card-body border-top p-4">

                        <h6 class="fw-bold text-uppercase text-primary small mb-3">
                            <i class="bi bi-door-open me-1"></i>
                            Class Information
                        </h6>

                        <div class="row g-3 small">

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Class</div>
                                <div class="fw-semibold"><?= e($selectedChild['class_name']) ?></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Session</div>
                                <div class="fw-semibold"><?= e($selectedChild['session_name']) ?></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Schedule</div>
                                <div class="fw-semibold">
                                    <?= e(date('h:i A', strtotime($selectedChild['start_time']))) ?>
                                    –
                                    <?= e(date('h:i A', strtotime($selectedChild['end_time']))) ?>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Academic Year</div>
                                <div class="fw-semibold"><?= e($selectedChild['academic_year'] ?: '—') ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Teacher</div>
                                <div class="fw-semibold">
                                    <?= e(trim(
                                        ($selectedChild['teacher_first_name'] ?? '') . ' ' .
                                        ($selectedChild['teacher_middle_name'] ?? '') . ' ' .
                                        ($selectedChild['teacher_last_name'] ?? '')
                                    ) ?: 'Not assigned') ?>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Enrolled Since</div>
                                <div class="fw-semibold">
                                    <?= !empty($selectedChild['enrolled_at'])
                                        ? e(date('F d, Y', strtotime($selectedChild['enrolled_at'])))
                                        : '—' ?>
                                </div>
                            </div>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- Personal Info -->
                <div class="card-body border-top p-4">

                    <h6 class="fw-bold text-uppercase text-primary small mb-3">
                        <i class="bi bi-person-vcard me-1"></i>
                        Personal Information
                    </h6>

                    <div class="row g-3 small">

                        <div class="col-12 col-md-4">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">First Name</div>
                            <div class="fw-semibold"><?= e($selectedChild['first_name']) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Middle Name</div>
                            <div class="fw-semibold"><?= e($selectedChild['middle_name'] ?: '—') ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Last Name</div>
                            <div class="fw-semibold"><?= e($selectedChild['last_name']) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Gender</div>
                            <div class="fw-semibold text-capitalize"><?= e($selectedChild['gender']) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Birthdate</div>
                            <div class="fw-semibold">
                                <?= !empty($selectedChild['birthdate'])
                                    ? e(date('F d, Y', strtotime($selectedChild['birthdate'])))
                                    : '—' ?>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Place of Birth</div>
                            <div class="fw-semibold"><?= e($selectedChild['place_of_birth'] ?: '—') ?></div>
                        </div>

                        <div class="col-12">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem;">Address</div>
                            <div class="fw-semibold"><?= e($selectedChild['address'] ?: '—') ?></div>
                        </div>

                    </div>

                </div>


                <!-- Family Info -->
                <div class="card-body border-top p-4">

                    <h6 class="fw-bold text-uppercase text-primary small mb-3">
                        <i class="bi bi-people me-1"></i>
                        Family Information
                    </h6>

                    <?php if (!empty($familyMembers)): ?>

                        <div class="row g-3">

                            <?php foreach ($familyMembers as $member): ?>

                                <?php
                                $role = ucfirst($member['relationship']);
                                $icon = $member['relationship'] === 'guardian'
                                    ? 'bi-person-badge'
                                    : 'bi-person';

                                $memberName = trim(
                                    ($member['first_name'] ?? '') . ' ' .
                                    ($member['middle_name'] ?? '') . ' ' .
                                    ($member['last_name'] ?? '')
                                );
                                ?>

                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="border rounded-3 bg-paper-soft h-100 p-3">

                                        <div class="text-primary text-uppercase fw-bold small mb-2">
                                            <i class="bi <?= e($icon) ?> me-1"></i>
                                            <?= e($role) ?>
                                            <?php if ((int) $member['is_primary'] === 1): ?>
                                                <span class="badge bg-primary ms-1">Primary</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="fw-semibold text-break">
                                            <?= e($memberName ?: 'Not provided') ?>
                                        </div>

                                        <?php if (!empty($member['user_email'])): ?>
                                            <div class="text-muted small text-break mt-1">
                                                <i class="bi bi-envelope me-1"></i>
                                                <?= e($member['user_email']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($member['user_contact'])): ?>
                                            <div class="text-muted small text-break">
                                                <i class="bi bi-telephone me-1"></i>
                                                <?= e($member['user_contact']) ?>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p class="text-muted small mb-0">No family information available.</p>

                    <?php endif; ?>

                </div>


                <!-- Documents -->
                <div class="card-body border-top p-4">

                    <h6 class="fw-bold text-uppercase text-primary small mb-3">
                        <i class="bi bi-paperclip me-1"></i>
                        Documents
                    </h6>

                    <?php if (!empty($documents)): ?>

                        <div class="row g-3">

                            <?php foreach ($documents as $doc): ?>

                                <?php
                                $fileName = basename((string) ($doc['file_image'] ?? ''));
                                $filePath = __DIR__ . '/../../../api/file-image/' . $fileName;
                                $fileExists = $fileName !== '' && is_file($filePath);
                                $fileUrl = '../../../api/file-image/' . rawurlencode($fileName);
                                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png'], true);
                                $isPdf = $ext === 'pdf';
                                ?>

                                <?php if ($fileExists): ?>

                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 h-100">

                                            <div class="d-flex align-items-start gap-3 mb-2">

                                                <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' ?>"></i>
                                                </div>

                                                <div class="min-w-0 flex-grow-1">
                                                    <div class="fw-semibold small text-break">
                                                        <?= e($doc['file_type']) ?>
                                                    </div>
                                                    <div class="text-muted small text-break">
                                                        <?= e($fileName) ?>
                                                    </div>
                                                </div>

                                            </div>

                                            <a href="<?= e($fileUrl) ?>"
                                               class="btn btn-outline-primary btn-sm w-100"
                                               target="_blank" rel="noopener">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                                View Document
                                            </a>

                                        </div>
                                    </div>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p class="text-muted small mb-0">No documents available.</p>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>