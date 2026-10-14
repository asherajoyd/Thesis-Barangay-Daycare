<?php

$pageTitle = "Parents";

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// HELPER
// ==========================================================

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ==========================================================
// GET PARENT ID
// ==========================================================

$parentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$parent = null;

if ($parentId) {

    // ==========================================================
    // GET PARENT, USER ACCOUNT, AND ENROLLMENT INFORMATION
    // ==========================================================

    $stmt = $conn->prepare("
        SELECT
            p.id AS parent_id,
            p.user_id,
            p.enrollment_id,

            u.first_name AS account_first_name,
            u.middle_name AS account_middle_name,
            u.last_name AS account_last_name,
            u.email AS account_email,
            u.role AS account_role,

            en.id AS child_id,
            en.c_first_name,
            en.c_middle_name,
            en.c_last_name,
            en.c_gender,
            en.c_birthdate,
            en.c_address,
            en.c_place_of_birth,

            en.f_first_name,
            en.f_middle_name,
            en.f_last_name,
            en.f_email,

            en.m_first_name,
            en.m_middle_name,
            en.m_last_name,
            en.m_email,

            en.g_first_name,
            en.g_middle_name,
            en.g_last_name,
            en.g_email,

            en.status AS enrollment_status,
            en.created_at,
            en.academic_id,
            en.file_type,
            en.file_image,

            ay.academic_year

        FROM `parent` p

        INNER JOIN users u
            ON u.id = p.user_id

        INNER JOIN enrollment en
            ON en.id = p.enrollment_id

        LEFT JOIN academicyear ay
            ON ay.id = en.academic_id

        WHERE p.id = ?
        AND en.status = 1
        LIMIT 1
    ");

    $stmt->bind_param("i", $parentId);
    $stmt->execute();

    $result = $stmt->get_result();
    $parent = $result->fetch_assoc();

    $stmt->close();
}

// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

$accountName = $parent
    ? trim(
        $parent['account_first_name'] . ' ' .
        $parent['account_middle_name'] . ' ' .
        $parent['account_last_name']
    )
    : '';

$childName = $parent
    ? trim(
        $parent['c_first_name'] . ' ' .
        $parent['c_middle_name'] . ' ' .
        $parent['c_last_name']
    )
    : '';

$initial = $parent
    ? strtoupper(substr($parent['account_first_name'], 0, 1))
    : '';

$enrollmentStatus = null;

if ($parent) {
    switch ((int) $parent['enrollment_status']) {
        case 0:
            $enrollmentStatus = [
                'label' => 'Pending',
                'class' => 'bg-warning text-dark'
            ];
            break;

        case 1:
            $enrollmentStatus = [
                'label' => 'Approved',
                'class' => 'bg-success'
            ];
            break;

        case 2:
            $enrollmentStatus = [
                'label' => 'Rejected',
                'class' => 'bg-danger'
            ];
            break;

        default:
            $enrollmentStatus = [
                'label' => 'Unknown',
                'class' => 'bg-secondary'
            ];
    }
}

?>

<section>

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">

        <div>
            <h2 class="fw-bold fs-4 mb-1">View Parent</h2>
            <p class="text-muted small mb-0">
                View parent account and family information
            </p>
        </div>

        <a href="<?= e($baseUrl) ?>parents/"
           class="btn btn-light border btn-sm flex-shrink-0">

            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>

        </a>

    </div>

    <?php if (!$parent): ?>

        <!-- PARENT NOT FOUND -->
        <div class="card border rounded-3">
            <div class="card-body text-center py-5">

                <i class="bi bi-person-exclamation fs-1 text-muted"></i>

                <h5 class="fw-bold mt-3">Parent Not Found</h5>

                <p class="text-muted small mb-3">
                    The parent record does not exist or the ID is invalid.
                </p>

                <a href="<?= e($baseUrl) ?>parents/"
                   class="btn btn-primary btn-sm">

                    Return to Parents

                </a>

            </div>
        </div>

    <?php else: ?>

        <!-- ==================================================
             PARENT PROFILE
             ================================================== -->

        <div class="card border rounded-3 mb-4">

            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3">

                    <div class="rounded-3 bg-paper-soft text-primary
                                d-flex align-items-center justify-content-center
                                fw-bold fs-3 flex-shrink-0"
                         style="width: 60px; height: 60px;">

                        <?= e($initial) ?>

                    </div>

                    <div class="flex-grow-1 min-w-0">

                        <span class="text-muted small">
                            PARENT ACCOUNT
                        </span>

                        <h4 class="fw-bold fs-5 mb-1 mt-1">
                            <?= e($accountName) ?>
                        </h4>

                        <div class="text-muted small text-break">
                            <i class="bi bi-envelope me-1"></i>
                            <?= e($parent['account_email']) ?>
                        </div>

                    </div>

                    <span class="badge bg-paper-soft text-secondary border">
                        Parent #<?= e($parent['parent_id']) ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- ==================================================
             INFORMATION CARDS
             ================================================== -->

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
                            <label class="text-muted small d-block mb-1">
                                First Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($parent['account_first_name']) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Middle Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($parent['account_middle_name'] ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Last Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($parent['account_last_name']) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($parent['account_email']) ?>
                            </div>
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
                            <label class="text-muted small d-block mb-1">
                                Child's Full Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($childName) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Gender
                            </label>

                            <div class="fw-semibold">
                                <?= e(ucfirst($parent['c_gender'])) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Birthdate
                            </label>

                            <div class="fw-semibold">
                                <?php
                                if (!empty($parent['c_birthdate'])) {
                                    echo e(date(
                                        'F d, Y',
                                        strtotime($parent['c_birthdate'])
                                    ));
                                } else {
                                    echo 'Not provided';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Place of Birth
                            </label>

                            <div class="fw-semibold">
                                <?= e($parent['c_place_of_birth'] ?: 'Not provided') ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            <!-- FATHER INFORMATION -->
            <div class="col-12 col-lg-6">

                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">

                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person text-primary me-2"></i>
                            Father's Information
                        </h6>

                    </div>

                    <div class="card-body p-4">

                        <div class="mb-4">
                            <label class="text-muted small d-block mb-1">
                                Full Name
                            </label>

                            <div class="fw-semibold">
                                <?= e(trim(
                                    $parent['f_first_name'] . ' ' .
                                    $parent['f_middle_name'] . ' ' .
                                    $parent['f_last_name']
                                ) ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($parent['f_email'] ?: 'Not provided') ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            <!-- MOTHER INFORMATION -->
            <div class="col-12 col-lg-6">

                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">

                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person text-primary me-2"></i>
                            Mother's Information
                        </h6>

                    </div>

                    <div class="card-body p-4">

                        <div class="mb-4">
                            <label class="text-muted small d-block mb-1">
                                Full Name
                            </label>

                            <div class="fw-semibold">
                                <?= e(trim(
                                    $parent['m_first_name'] . ' ' .
                                    $parent['m_middle_name'] . ' ' .
                                    $parent['m_last_name']
                                ) ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($parent['m_email'] ?: 'Not provided') ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            <!-- GUARDIAN INFORMATION -->
            <div class="col-12 col-lg-6">

                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">

                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person-badge text-primary me-2"></i>
                            Guardian Information
                        </h6>

                    </div>

                    <div class="card-body p-4">

                        <div class="mb-4">
                            <label class="text-muted small d-block mb-1">
                                Full Name
                            </label>

                            <div class="fw-semibold">
                                <?= e(trim(
                                    $parent['g_first_name'] . ' ' .
                                    $parent['g_middle_name'] . ' ' .
                                    $parent['g_last_name']
                                ) ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($parent['g_email'] ?: 'Not provided') ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            <!-- ENROLLMENT INFORMATION -->
            <div class="col-12 col-lg-6">

                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom p-3">

                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-journal-check text-primary me-2"></i>
                            Enrollment Information
                        </h6>

                    </div>

                    <div class="card-body row p-4">

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Enrollment ID
                            </label>

                            <div class="fw-semibold">
                                #<?= e($parent['enrollment_id']) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Academic Year
                            </label>

                            <div class="fw-semibold">
                                <?= e($parent['academic_year'] ?: 'Not assigned') ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Enrollment Status
                            </label>

                            <span class="badge <?= e($enrollmentStatus['class']) ?>">
                                <?= e($enrollmentStatus['label']) ?>
                            </span>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Date Submitted
                            </label>

                            <div class="fw-semibold">
                                <?= !empty($parent['created_at'])
                                    ? e(date('F d, Y h:i A', strtotime($parent['created_at'])))
                                    : 'Not available' ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Parent Record ID
                            </label>

                            <div class="fw-semibold">
                                #<?= e($parent['parent_id']) ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>