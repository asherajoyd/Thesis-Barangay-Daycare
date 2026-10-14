<?php

$pageTitle = "Teachers";

require_once '../guard.php';
include "../../../api/conn.php";

$teacherId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$teacher = null;

if ($teacherId) {
    $stmt = $conn->prepare("
        SELECT id, first_name, middle_name, last_name, email, user_id, academic_id
        FROM teacher
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $teacherId);
    $stmt->execute();

    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();

    $stmt->close();
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

include '../layout/header.php';

$fullName = $teacher
    ? trim($teacher['first_name'] . ' ' . $teacher['middle_name'] . ' ' . $teacher['last_name'])
    : '';

$initial = $teacher
    ? strtoupper(substr($teacher['first_name'], 0, 1))
    : '';
?>

<section>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="fw-bold fs-4 mb-1">View Teacher</h2>
            <p class="text-muted small mb-0">
                View teacher account information
            </p>
        </div>

        <a href="<?= e($baseUrl) ?>teachers/"
           class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>
    </div>

    <?php if (!$teacher): ?>

        <!-- Not Found -->
        <div class="card border rounded-3">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted"></i>

                <h5 class="fw-semibold mt-3">Teacher Not Found</h5>

                <p class="text-muted small mb-3">
                    The teacher record does not exist or the ID is invalid.
                </p>

                <a href="<?= e($baseUrl) ?>teachers/"
                   class="btn btn-primary btn-sm">
                    Return to Teachers
                </a>
            </div>
        </div>

    <?php else: ?>

        <!-- Teacher Profile -->
        <div class="card border rounded-3 mb-4">
            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3">

                    <div class="rounded-3 bg-paper-soft text-primary
                                d-flex align-items-center justify-content-center
                                fw-bold fs-3 flex-shrink-0"
                         style="width: 64px; height: 64px;">
                        <?= e($initial) ?>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <span class="text-muted small">
                            TEACHER PROFILE
                        </span>

                        <h4 class="fw-bold fs-5 mb-1 mt-1">
                            <?= e($fullName) ?>
                        </h4>

                        <p class="text-muted small mb-0 text-break">
                            <i class="bi bi-envelope me-1"></i>
                            <?= e($teacher['email']) ?>
                        </p>
                    </div>

                    <span class="badge bg-paper-soft text-secondary border">
                        ID #<?= e($teacher['id']) ?>
                    </span>

                </div>

            </div>
        </div>

        <div class="row g-3">

            <!-- Personal Information -->
            <div class="col-12">
                <div class="card border rounded-3 h-100">

                    <div class="card-header bg-paper border-bottom py-3 px-4">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person-vcard text-primary me-2"></i>
                            Personal Information
                        </h6>
                    </div>

                    <div class="card-body p-4">
                        <div class="row">

                            <div class="mb-4 col-md-6">
                                <label class="text-muted small d-block mb-1">
                                    First Name
                                </label>
                                <div class="fw-semibold">
                                    <?= e($teacher['first_name']) ?>
                                </div>
                            </div>

                            <div class="mb-4 col-md-6">
                                <label class="text-muted small d-block mb-1">
                                    Middle Name
                                </label>
                                <div class="fw-semibold">
                                    <?= e($teacher['middle_name'] ?: 'Not provided') ?>
                                </div>
                            </div>

                            <div class="mb-4 col-md-6">
                                <label class="text-muted small d-block mb-1">
                                    Last Name
                                </label>
                                <div class="fw-semibold">
                                    <?= e($teacher['last_name']) ?>
                                </div>
                            </div>

                            <div class="mb-4 col-md-6">
                                <label class="text-muted small d-block mb-1">
                                    Email Address
                                </label>
                                <div class="fw-semibold text-break">
                                    <?= e($teacher['email']) ?>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>