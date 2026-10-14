<?php

$pageTitle = "Children";

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// HELPER
// ==========================================================

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

if ($childId && $childId > 0) {

    // ==========================================================
    // GET CHILD AND ENROLLMENT INFORMATION
    // ==========================================================

    $stmt = $conn->prepare("
        SELECT
            c.id AS child_record_id,
            c.enrollment_id,
            c.user_id,

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

        FROM children c

        INNER JOIN enrollment en
            ON en.id = c.enrollment_id

        LEFT JOIN academicyear ay
            ON ay.id = en.academic_id

        WHERE c.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $childId);
    $stmt->execute();

    $result = $stmt->get_result();
    $child = $result->fetch_assoc();

    $stmt->close();
}

// ==========================================================
// PAGE DATA
// ==========================================================

include '../layout/header.php';

$childName = $child
    ? fullName(
        $child['c_first_name'],
        $child['c_middle_name'],
        $child['c_last_name']
    )
    : '';

$initial = $child
    ? strtoupper(substr($child['c_first_name'] ?? '', 0, 1))
    : '';

$enrollmentStatus = null;

if ($child) {
    switch ((int) $child['enrollment_status']) {
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

                <a href="<?= e($baseUrl) ?>children/"
                   class="btn btn-primary btn-sm">

                    Return to Children

                </a>

            </div>

        </div>

    <?php else: ?>

        <!-- ==================================================
             CHILD PROFILE
             ================================================== -->

        <div class="card border rounded-3 mb-4">

            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3 flex-wrap">

                    <div class="rounded-3 bg-paper-soft text-primary
                                d-flex align-items-center justify-content-center
                                fw-bold fs-3 flex-shrink-0"
                         style="width: 60px; height: 60px;">

                        <?= e($initial) ?>

                    </div>

                    <div class="flex-grow-1 min-w-0">

                        <span class="text-muted small">
                            CHILD PROFILE
                        </span>

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


        <!-- ==================================================
             INFORMATION CARDS
             ================================================== -->

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
                            <label class="text-muted small d-block mb-1">
                                First Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['c_first_name']) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Middle Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['c_middle_name'] ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Last Name
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['c_last_name']) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Gender
                            </label>

                            <div class="fw-semibold">
                                <?= e(
                                    !empty($child['c_gender'])
                                        ? ucfirst($child['c_gender'])
                                        : 'Not provided'
                                ) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Birthdate
                            </label>

                            <div class="fw-semibold">
                                <?php
                                if (!empty($child['c_birthdate'])) {
                                    $timestamp = strtotime($child['c_birthdate']);

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
                            <label class="text-muted small d-block mb-1">
                                Place of Birth
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['c_place_of_birth'] ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small d-block mb-1">
                                Address
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['c_address'] ?: 'Not provided') ?>
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
                                <?= e(
                                    fullName(
                                        $child['f_first_name'],
                                        $child['f_middle_name'],
                                        $child['f_last_name']
                                    ) ?: 'Not provided'
                                ) ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($child['f_email'] ?: 'Not provided') ?>
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
                                <?= e(
                                    fullName(
                                        $child['m_first_name'],
                                        $child['m_middle_name'],
                                        $child['m_last_name']
                                    ) ?: 'Not provided'
                                ) ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($child['m_email'] ?: 'Not provided') ?>
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
                                <?= e(
                                    fullName(
                                        $child['g_first_name'],
                                        $child['g_middle_name'],
                                        $child['g_last_name']
                                    ) ?: 'Not provided'
                                ) ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small d-block mb-1">
                                Email Address
                            </label>

                            <div class="fw-semibold text-break">
                                <?= e($child['g_email'] ?: 'Not provided') ?>
                            </div>
                        </div>

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
                            <label class="text-muted small d-block mb-1">
                                Enrollment ID
                            </label>

                            <div class="fw-semibold">
                                #<?= e($child['enrollment_id']) ?>
                            </div>
                        </div>

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Academic Year
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['academic_year'] ?: 'Not assigned') ?>
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

                        <div class="mb-4 col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Child Record ID
                            </label>

                            <div class="fw-semibold">
                                #<?= e($child['child_record_id']) ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">
                                Document Type
                            </label>

                            <div class="fw-semibold">
                                <?= e($child['file_type'] ?: 'Not provided') ?>
                            </div>
                        </div>

                        <?php if (!empty($child['file_image'])): ?>

                            <div class="col-12 mt-4">

                                <label class="text-muted small d-block mb-2">
                                    Uploaded Document
                                </label>

                                <div class="border rounded-3 p-3 bg-paper-soft">

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="rounded-3 bg-paper d-flex
                                                    align-items-center justify-content-center
                                                    flex-shrink-0"
                                             style="width: 48px; height: 48px;">

                                            <i class="bi bi-file-earmark-text
                                                      text-primary fs-3"></i>

                                        </div>

                                        <div class="flex-grow-1 min-w-0">

                                            <div class="fw-semibold text-break">
                                                <?= e($child['file_image']) ?>
                                            </div>

                                            <div class="text-muted small">
                                                <?= e($child['file_type'] ?: 'Enrollment document') ?>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>