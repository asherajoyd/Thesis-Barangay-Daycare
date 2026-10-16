<?php

$pageTitle = "Parents";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function fullName($first, $middle, $last)
{
    return trim(($first ?? '') . ' ' . ($middle ?? '') . ' ' . ($last ?? ''));
}


// ==========================================================
// GET IDS
// ==========================================================

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$childId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT);

$parent = null;
$familyMembers = [];


if ($userId && $userId > 0) {

    // ==========================================================
    // GET PARENT (users) + CHILD + ENROLLMENT
    // ==========================================================

    $sql = "
        SELECT
            u.id AS user_id,
            u.first_name AS account_first_name,
            u.middle_name AS account_middle_name,
            u.last_name AS account_last_name,
            u.email AS account_email,
            u.contact AS account_contact,
            u.role AS account_role,
            u.status AS account_status,

            c.id AS child_id,
            c.first_name AS child_first_name,
            c.middle_name AS child_middle_name,
            c.last_name AS child_last_name,
            c.gender AS child_gender,
            c.birthdate AS child_birthdate,
            c.address AS child_address,
            c.place_of_birth AS child_place_of_birth,

            e.id AS enrollment_id,
            e.status AS enrollment_status,
            e.created_at,
            e.academic_id,

            ay.academic_year

        FROM users u

        INNER JOIN children c
            ON c.user_id = u.id

        INNER JOIN enrollment e
            ON e.child_id = c.id

        LEFT JOIN academic_year ay
            ON ay.id = e.academic_id

        WHERE u.id = ?
          AND u.role = 'parent'
          AND e.status = 'approved'
    ";

    if ($childId && $childId > 0) {
        $sql .= " AND c.id = ?";
    }

    $sql .= " LIMIT 1";

    $stmt = $conn->prepare($sql);

    if ($childId && $childId > 0) {
        $stmt->bind_param("ii", $userId, $childId);
    } else {
        $stmt->bind_param("i", $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $parent = $result->fetch_assoc();
    $stmt->close();


    // ==========================================================
    // GET FAMILY MEMBERS (parent_info)
    // ==========================================================

    if ($parent) {

        $stmt = $conn->prepare("
            SELECT
                p.*,
                u.email AS user_email
            FROM parent_info p
            LEFT JOIN users u ON u.id = p.user_id
            WHERE p.child_id = ?
            ORDER BY p.is_primary DESC, p.id ASC
        ");

        $stmt->bind_param("i", $parent['child_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $familyMembers[] = $row;
        }

        $stmt->close();
    }
}


include '../layout/header.php';


// ==========================================================
// PAGE DATA
// ==========================================================

$accountName = $parent
    ? fullName(
        $parent['account_first_name'],
        $parent['account_middle_name'],
        $parent['account_last_name']
    )
    : '';

$childName = $parent
    ? fullName(
        $parent['child_first_name'],
        $parent['child_middle_name'],
        $parent['child_last_name']
    )
    : '';

$initial = $parent
    ? strtoupper(substr($parent['account_first_name'], 0, 1))
    : '';

$enrollmentStatus = null;

if ($parent) {
    switch ($parent['enrollment_status']) {
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

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="fw-bold fs-4 mb-1">View Parent</h2>
            <p class="text-muted small mb-0">View parent account and family information</p>
        </div>
        <a href="<?= e($baseUrl) ?>parents/" class="btn btn-light border btn-sm flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>
    </div>

    <?php if (!$parent): ?>

        <div class="card border rounded-3">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted"></i>
                <h5 class="fw-bold mt-3">Parent Not Found</h5>
                <p class="text-muted small mb-3">The parent record does not exist or the ID is invalid.</p>
                <a href="<?= e($baseUrl) ?>parents/" class="btn btn-primary btn-sm">Return to Parents</a>
            </div>
        </div>

    <?php else: ?>

        <!-- PARENT PROFILE -->
        <div class="card border rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">

                    <div class="rounded-3 bg-paper-soft text-primary d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0"
                         style="width: 60px; height: 60px;">
                        <?= e($initial) ?>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <span class="text-muted small">PARENT ACCOUNT</span>
                        <h4 class="fw-bold fs-5 mb-1 mt-1"><?= e($accountName) ?></h4>
                        <div class="text-muted small text-break">
                            <i class="bi bi-envelope me-1"></i>
                            <?= e($parent['account_email']) ?>
                        </div>
                    </div>

                    <span class="badge bg-paper-soft text-secondary border">
                        User #<?= e($parent['user_id']) ?>
                    </span>

                </div>
            </div>
        </div>


        <div class="row g-3">

            <!-- PARENT ACCOUNT INFORMATION -->
            <div class="col-12 col-lg-6">
                <div class="card border rounded-3 h-100">
                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person-vcard text-primary me-2"></i>
                            Parent Account Information
                        </h6>
                    </div>
                    <div class="card-body row p-4">

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">First Name</label>
                            <div class="fw-semibold"><?= e($parent['account_first_name']) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Middle Name</label>
                            <div class="fw-semibold"><?= e($parent['account_middle_name'] ?: 'Not provided') ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Last Name</label>
                            <div class="fw-semibold"><?= e($parent['account_last_name']) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Contact Number</label>
                            <div class="fw-semibold"><?= e($parent['account_contact'] ?: 'Not provided') ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Email Address</label>
                            <div class="fw-semibold text-break"><?= e($parent['account_email']) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">Status</label>
                            <span class="badge <?= $parent['account_status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= e(ucfirst($parent['account_status'])) ?>
                            </span>
                        </div>

                    </div>
                </div>
            </div>


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
                            <label class="text-muted small d-block mb-1">Child's Full Name</label>
                            <div class="fw-semibold"><?= e($childName) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Gender</label>
                            <div class="fw-semibold"><?= e(ucfirst($parent['child_gender'])) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Birthdate</label>
                            <div class="fw-semibold">
                                <?= !empty($parent['child_birthdate'])
                                    ? e(date('F d, Y', strtotime($parent['child_birthdate'])))
                                    : 'Not provided' ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Place of Birth</label>
                            <div class="fw-semibold"><?= e($parent['child_place_of_birth'] ?: 'Not provided') ?></div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small d-block mb-1">Address</label>
                            <div class="fw-semibold"><?= e($parent['child_address'] ?: 'Not provided') ?></div>
                        </div>

                    </div>
                </div>
            </div>


            <!-- FAMILY INFORMATION -->
            <div class="col-12">
                <div class="card border rounded-3">
                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-people text-primary me-2"></i>
                            Family Information
                        </h6>
                    </div>
                    <div class="card-body p-4">

                        <div class="row g-3">

                            <?php if (!empty($familyMembers)): ?>

                                <?php foreach ($familyMembers as $member): ?>

                                    <?php
                                    $role = ucfirst($member['relationship']);
                                    $icon = $member['relationship'] === 'guardian'
                                        ? 'bi-person-badge'
                                        : 'bi-person';
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

                                            <div class="fw-bold text-break">
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
                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="col-12">
                                    <p class="text-muted small mb-0 text-center py-3">
                                        No family information available.
                                    </p>
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>
                </div>
            </div>


            <!-- ENROLLMENT INFORMATION -->
            <div class="col-12">
                <div class="card border rounded-3">
                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-journal-check text-primary me-2"></i>
                            Enrollment Information
                        </h6>
                    </div>
                    <div class="card-body row p-4">

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Enrollment ID</label>
                            <div class="fw-semibold">#<?= e($parent['enrollment_id']) ?></div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">Academic Year</label>
                            <div class="fw-semibold"><?= e($parent['academic_year'] ?: 'Not assigned') ?></div>
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
                                <?= !empty($parent['created_at'])
                                    ? e(date('F d, Y h:i A', strtotime($parent['created_at'])))
                                    : 'Not available' ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>