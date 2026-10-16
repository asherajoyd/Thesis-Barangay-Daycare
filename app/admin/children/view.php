<?php

$pageTitle = "Children";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function fullName($first, $middle, $last)
{
    return trim(
        ($first ?? '') . ' ' .
        ($middle ?? '') . ' ' .
        ($last ?? '')
    );
}


// ==========================================================
// GET CHILD ID
// ==========================================================

$childId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$child = null;
$familyMembers = [];
$documents = [];


if ($childId && $childId > 0) {

    // ==========================================================
    // GET CHILD + ENROLLMENT + ACADEMIC YEAR
    // ==========================================================

    $stmt = $conn->prepare("
        SELECT
            c.id AS child_record_id,
            c.user_id,
            c.first_name,
            c.middle_name,
            c.last_name,
            c.gender,
            c.birthdate,
            c.place_of_birth,
            c.address,

            e.id AS enrollment_id,
            e.status AS enrollment_status,
            e.created_at,
            e.academic_id,

            ay.academic_year

        FROM children c

        INNER JOIN enrollment e
            ON e.child_id = c.id

        LEFT JOIN academic_year ay
            ON ay.id = e.academic_id

        WHERE c.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $childId);
    $stmt->execute();

    $result = $stmt->get_result();
    $child = $result->fetch_assoc();

    $stmt->close();


    // ==========================================================
    // GET FAMILY MEMBERS (parent_info)
    // ==========================================================

    if ($child) {

        $stmt = $conn->prepare("
            SELECT
                p.*,
                u.email AS user_email
            FROM parent_info p
            LEFT JOIN users u ON u.id = p.user_id
            WHERE p.child_id = ?
            ORDER BY p.is_primary DESC, p.id ASC
        ");

        $stmt->bind_param("i", $childId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $familyMembers[] = $row;
        }

        $stmt->close();


        // ==========================================================
        // GET ENROLLMENT DOCUMENTS
        // ==========================================================

        $stmt = $conn->prepare("
            SELECT *
            FROM enrollment_document
            WHERE enrollment_id = ?
            ORDER BY id ASC
        ");

        $stmt->bind_param("i", $child['enrollment_id']);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }

        $stmt->close();
    }
}


include '../layout/header.php';


// ==========================================================
// PAGE DATA
// ==========================================================

$childName = $child
    ? fullName(
        $child['first_name'],
        $child['middle_name'],
        $child['last_name']
    )
    : '';

$initial = $child
    ? strtoupper(substr($child['first_name'] ?? '', 0, 1))
    : '';

$enrollmentStatus = null;

if ($child) {

    switch ($child['enrollment_status']) {

        case 'pending':
            $enrollmentStatus = ['label' => 'Pending', 'class' => 'bg-warning text-dark'];
            break;

        case 'approved':
            $enrollmentStatus = ['label' => 'Approved', 'class' => 'bg-success'];
            break;

        case 'rejected':
            $enrollmentStatus = ['label' => 'Rejected', 'class' => 'bg-danger'];
            break;

        default:
            $enrollmentStatus = ['label' => 'Unknown', 'class' => 'bg-secondary'];
    }
}

?>

<section>

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">

        <div>
            <h2 class="fw-bold fs-4 mb-1">View Child</h2>
            <p class="text-muted small mb-0">
                View child personal, family, and enrollment information
            </p>
        </div>

        <a href="<?= e($baseUrl) ?>children/"
           class="btn btn-light border btn-sm flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>

    </div>

    <?php if (!$child): ?>

        <!-- CHILD NOT FOUND -->
        <div class="card border rounded-3">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted"></i>
                <h5 class="fw-bold mt-3">Child Not Found</h5>
                <p class="text-muted small mb-3">
                    The child record does not exist or the ID is invalid.
                </p>
                <a href="<?= e($baseUrl) ?>children/" class="btn btn-primary btn-sm">
                    Return to Children
                </a>
            </div>
        </div>

    <?php else: ?>

        <!-- CHILD PROFILE -->
        <div class="card border rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 flex-wrap">

                    <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0"
                         style="width: 60px; height: 60px;">
                        <?= e($initial) ?>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <span class="text-muted small">CHILD PROFILE</span>
                        <h4 class="fw-bold fs-5 mb-1 mt-1">
                            <?= e($childName ?: 'Unnamed Child') ?>
                        </h4>
                        <div class="text-muted small">
                            <i class="bi bi-journal-check me-1"></i>
                            Enrollment #<?= e($child['enrollment_id']) ?>
                        </div>
                    </div>

                    <span class="badge bg-paper-soft text-secondary border">
                        Child #<?= e($child['child_record_id']) ?>
                    </span>

                </div>
            </div>
        </div>


        <!-- INFORMATION CARDS -->
        <div class="row g-3">

            <!-- CHILD INFORMATION -->
            <div class="col-12 col-lg-6">
                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person-hearts text-primary me-2"></i>
                            Child Information
                        </h6>
                    </div>

                    <div class="card-body row p-4">

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">First Name</label>
                            <div class="fw-semibold"><?= e($child['first_name']) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Middle Name</label>
                            <div class="fw-semibold"><?= e($child['middle_name'] ?: 'Not provided') ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Last Name</label>
                            <div class="fw-semibold"><?= e($child['last_name']) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Gender</label>
                            <div class="fw-semibold">
                                <?= e(!empty($child['gender']) ? ucfirst($child['gender']) : 'Not provided') ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Birthdate</label>
                            <div class="fw-semibold">
                                <?php
                                if (!empty($child['birthdate'])) {
                                    $timestamp = strtotime($child['birthdate']);
                                    echo $timestamp !== false
                                        ? e(date('F d, Y', $timestamp))
                                        : 'Not available';
                                } else {
                                    echo 'Not provided';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Place of Birth</label>
                            <div class="fw-semibold"><?= e($child['place_of_birth'] ?: 'Not provided') ?></div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small d-block mb-1">Address</label>
                            <div class="fw-semibold"><?= e($child['address'] ?: 'Not provided') ?></div>
                        </div>

                    </div>
                </div>
            </div>


            <!-- FAMILY INFORMATION -->
            <div class="col-12 col-lg-6">
                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-people text-primary me-2"></i>
                            Family Information
                        </h6>
                    </div>

                    <div class="card-body p-4">

                        <?php if (!empty($familyMembers)): ?>

                            <?php foreach ($familyMembers as $member): ?>

                                <?php
                                $role = ucfirst($member['relationship']);
                                $icon = $member['relationship'] === 'guardian'
                                    ? 'bi-person-badge'
                                    : 'bi-person';
                                ?>

                                <div class="mb-4 pb-3 border-bottom">

                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi <?= e($icon) ?> text-primary"></i>
                                        <span class="fw-bold small text-uppercase">
                                            <?= e($role) ?>
                                        </span>
                                        <?php if ((int) $member['is_primary'] === 1): ?>
                                            <span class="badge bg-primary">Primary</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="fw-semibold">
                                        <?= e(fullName(
                                            $member['first_name'],
                                            $member['middle_name'],
                                            $member['last_name']
                                        ) ?: 'Not provided') ?>
                                    </div>

                                    <?php if (!empty($member['user_email'])): ?>
                                        <div class="text-muted small text-break mt-1">
                                            <?= e($member['user_email']) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <p class="text-muted small mb-0">
                                No parent or guardian information available.
                            </p>

                        <?php endif; ?>

                    </div>
                </div>
            </div>


            <!-- ENROLLMENT INFORMATION -->
            <div class="col-12">
                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-journal-check text-primary me-2"></i>
                            Enrollment Information
                        </h6>
                    </div>

                    <div class="card-body row p-4">

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Enrollment ID</label>
                            <div class="fw-semibold">#<?= e($child['enrollment_id']) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Academic Year</label>
                            <div class="fw-semibold"><?= e($child['academic_year'] ?: 'Not assigned') ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Enrollment Status</label>
                            <span class="badge <?= e($enrollmentStatus['class']) ?>">
                                <?= e($enrollmentStatus['label']) ?>
                            </span>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Date Submitted</label>
                            <div class="fw-semibold">
                                <?php
                                if (!empty($child['created_at'])) {
                                    $timestamp = strtotime($child['created_at']);
                                    echo $timestamp !== false
                                        ? e(date('F d, Y h:i A', $timestamp))
                                        : 'Not available';
                                } else {
                                    echo 'Not available';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">Child Record ID</label>
                            <div class="fw-semibold">#<?= e($child['child_record_id']) ?></div>
                        </div>

                    </div>
                </div>
            </div>


            <!-- SUPPORTING DOCUMENTS -->
            <div class="col-12">
                <div class="card border rounded-3">

                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-paperclip text-primary me-2"></i>
                            Supporting Documents
                        </h6>
                    </div>

                    <div class="card-body p-4">

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

                                                <div class="fw-semibold small text-break">
                                                    <?= e($doc['file_type']) ?>
                                                </div>

                                                <div class="text-muted small text-break mt-1">
                                                    <?= e($fileName) ?>
                                                </div>

                                                <div class="ratio ratio-4x3 border rounded-3 bg-light mt-3">
                                                    <?php if ($isImage): ?>
                                                        <img src="<?= e($fileUrl) ?>"
                                                             class="img-fluid rounded object-fit-contain"
                                                             alt="">
                                                    <?php elseif ($isPdf): ?>
                                                        <iframe src="<?= e($fileUrl) ?>" class="rounded"></iframe>
                                                    <?php else: ?>
                                                        <div class="d-flex align-items-center justify-content-center text-muted small text-center p-4">
                                                            Preview unavailable.
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <a href="<?= e($fileUrl) ?>"
                                                   class="btn btn-outline-primary btn-sm mt-3 w-100"
                                                   target="_blank" rel="noopener" download>
                                                    <i class="bi bi-download me-1"></i>
                                                    Download
                                                </a>

                                            </div>
                                        </div>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <p class="text-muted small mb-0 text-center py-3">
                                No supporting documents available.
                            </p>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>