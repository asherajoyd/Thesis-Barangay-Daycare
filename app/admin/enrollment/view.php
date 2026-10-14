<?php

$pageTitle = 'Enrollment';

require_once '../guard.php';
include '../../../api/conn.php';


function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}


function displayDate($value, string $fallback = 'Not provided'): string
{
    if (empty($value)) {
        return $fallback;
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('F d, Y', $timestamp) : $fallback;
}


function displayName(...$parts): string
{
    return trim(implode(' ', array_filter(
        array_map(static fn ($part) => trim((string) $part), $parts),
        static fn ($part) => $part !== ''
    )));
}


$enrollmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$enrollment = null;

if ($enrollmentId > 0) {

    $stmt = $conn->prepare(''
        . 'SELECT e.*, ay.academic_year '
        . 'FROM enrollment e '
        . 'LEFT JOIN academicyear ay ON ay.id = e.academic_id '
        . 'WHERE e.id = ? '
        . 'LIMIT 1'
    );

    if ($stmt) {
        $stmt->bind_param('i', $enrollmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollment = $result->fetch_assoc() ?: null;
        $stmt->close();
    }
}

if (!$enrollment) {
    $_SESSION['status'] = 'danger';
    $_SESSION['message'] = 'Enrollment record not found.';

    header('Location: index.php');
    exit;
}


$status = (int) $enrollment['status'];

$statusMeta = [
    0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split'],
    1 => ['label' => 'Approved', 'class' => 'bg-primary', 'icon' => 'bi-check-circle-fill'],
    2 => ['label' => 'Rejected', 'class' => 'bg-danger', 'icon' => 'bi-x-circle-fill'],
];

$currentStatus = $statusMeta[$status] ?? [
    'label' => 'Unknown',
    'class' => 'bg-secondary',
    'icon' => 'bi-question-circle-fill',
];

$childName = displayName(
    $enrollment['c_first_name'],
    $enrollment['c_middle_name'],
    $enrollment['c_last_name']
);

$childInitials = strtoupper(
    substr((string) $enrollment['c_first_name'], 0, 1) .
    substr((string) $enrollment['c_last_name'], 0, 1)
);

$age = null;
$birthdateTimestamp = strtotime((string) $enrollment['c_birthdate']);

if ($birthdateTimestamp) {
    $birthdate = new DateTime(date('Y-m-d', $birthdateTimestamp));
    $today = new DateTime('today');
    $age = $birthdate->diff($today)->y;
}

$fileName = basename((string) ($enrollment['file_image'] ?? ''));
$filePath = __DIR__ . '/../../../api/file-image/' . $fileName;
$fileExists = $fileName !== '' && is_file($filePath);
$fileUrl = '../../../api/file-image/' . rawurlencode($fileName);
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png'], true);
$isPdf = $fileExtension === 'pdf';

include '../layout/header.php';

?>

<div class="container-fluid px-0">

    <!-- Page actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            Back to enrollments
        </a>

        <?php if ($status === 0): ?>

            <div class="d-flex flex-wrap gap-2">

                <a
                    href="approve-enrollment.php?id=<?= urlencode($enrollment['id']) ?>"
                    class="btn btn-primary btn-sm"
                    onclick="return confirm('Approve this enrollment?');"
                >
                    <i class="bi bi-check2 me-1"></i>
                    Approve enrollment
                </a>

                <a
                    href="reject-enrollment.php?id=<?= urlencode($enrollment['id']) ?>"
                    class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Reject this enrollment? This enrollment will be moved to archive.');"
                >
                    <i class="bi bi-x-lg me-1"></i>
                    Reject
                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- Child summary -->
    <section class="card shadow-sm mb-3">

        <div class="card-body p-4">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                <div class="d-flex align-items-center gap-3">

                    <div class="icon rounded-3 flex-shrink-0">
                        <?= e($childInitials ?: 'C') ?>
                    </div>

                    <div>
                        <div class="text-primary text-uppercase fw-bold small">
                            Enrollment details
                        </div>

                        <h1 class="h4 fw-bold mb-1">
                            <?= e($childName) ?>
                        </h1>

                        <p class="text-muted small mb-0">
                            Application #<?= e($enrollment['id']) ?>
                            <span class="mx-1">•</span>
                            <?= e(ucfirst((string) $enrollment['c_gender'])) ?>

                            <?php if ($age !== null): ?>
                                <span class="mx-1">•</span>
                                <?= e($age) ?> years old
                            <?php endif; ?>
                        </p>
                    </div>

                </div>

                <span class="badge rounded-pill <?= e($currentStatus['class']) ?> px-3 py-2">
                    <i class="bi <?= e($currentStatus['icon']) ?> me-1"></i>
                    <?= e($currentStatus['label']) ?>
                </span>

            </div>

        </div>

    </section>


    <!-- Child and enrollment information -->
    <div class="row g-3">

        <div class="col-12 col-xl-8">

            <section class="card h-100">

                <div class="card-header bg-white p-4">
                    <h2 class="h6 fw-bold mb-1">
                        <i class="bi bi-person-vcard text-primary me-2"></i>
                        Child information
                    </h2>
                    <p class="text-muted small mb-0">
                        Personal information provided in the application.
                    </p>
                </div>

                <div class="card-body p-4">

                    <div class="row g-4">

                        <div class="col-12 col-md-4">
                            <div class="text-uppercase text-muted fw-bold small mb-1">First name</div>
                            <div class="fw-semibold text-break"><?= e($enrollment['c_first_name']) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-uppercase text-muted fw-bold small mb-1">Middle name</div>
                            <div class="fw-semibold text-break">
                                <?= e($enrollment['c_middle_name'] ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-uppercase text-muted fw-bold small mb-1">Last name</div>
                            <div class="fw-semibold text-break"><?= e($enrollment['c_last_name']) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-uppercase text-muted fw-bold small mb-1">Gender</div>
                            <div class="fw-semibold text-capitalize"><?= e($enrollment['c_gender']) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-uppercase text-muted fw-bold small mb-1">Date of birth</div>
                            <div class="fw-semibold"><?= e(displayDate($enrollment['c_birthdate'])) ?></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="text-uppercase text-muted fw-bold small mb-1">Place of birth</div>
                            <div class="fw-semibold text-break"><?= e($enrollment['c_place_of_birth']) ?></div>
                        </div>

                        <div class="col-12">
                            <div class="text-uppercase text-muted fw-bold small mb-1">Complete address</div>
                            <div class="fw-semibold text-break"><?= e($enrollment['c_address']) ?></div>
                        </div>

                    </div>

                </div>

            </section>

        </div>


        <div class="col-12 col-xl-4">

            <section class="card h-100">

                <div class="card-header bg-white p-4">
                    <h2 class="h6 fw-bold mb-1">
                        <i class="bi bi-clipboard2-check text-primary me-2"></i>
                        Enrollment summary
                    </h2>
                    <p class="text-muted small mb-0">
                        Application and academic-year details.
                    </p>
                </div>

                <div class="card-body p-4">

                    <dl class="row g-3 mb-0 small">

                        <dt class="col-6 text-muted fw-normal">Application ID</dt>
                        <dd class="col-6 text-end fw-bold mb-0">#<?= e($enrollment['id']) ?></dd>

                        <dt class="col-6 text-muted fw-normal">Academic year</dt>
                        <dd class="col-6 text-end fw-bold mb-0 text-break">
                            <?= e($enrollment['academic_year'] ?: 'Not assigned') ?>
                        </dd>

                        <dt class="col-6 text-muted fw-normal">Submitted</dt>
                        <dd class="col-6 text-end fw-bold mb-0">
                            <?= e(displayDate($enrollment['created_at'])) ?>
                        </dd>

                        <dt class="col-6 text-muted fw-normal">Current status</dt>
                        <dd class="col-6 text-end fw-bold mb-0">
                            <?= e($currentStatus['label']) ?>
                        </dd>

                    </dl>

                </div>

            </section>

        </div>

    </div>


    <!-- Family information -->
    <section class="card mt-3">

        <div class="card-header bg-white p-4">
            <h2 class="h6 fw-bold mb-1">
                <i class="bi bi-people text-primary me-2"></i>
                Parent and guardian information
            </h2>
            <p class="text-muted small mb-0">
                Family contacts submitted with this enrollment application.
            </p>
        </div>

        <div class="card-body p-4">

            <div class="row g-3">

                <?php
                $familyMembers = [
                    [
                        'role' => 'Father',
                        'name' => displayName(
                            $enrollment['f_first_name'],
                            $enrollment['f_middle_name'],
                            $enrollment['f_last_name']
                        ),
                        'email' => $enrollment['f_email'] ?? '',
                        'icon' => 'bi-person',
                    ],
                    [
                        'role' => 'Mother',
                        'name' => displayName(
                            $enrollment['m_first_name'],
                            $enrollment['m_middle_name'],
                            $enrollment['m_last_name']
                        ),
                        'email' => $enrollment['m_email'] ?? '',
                        'icon' => 'bi-person',
                    ],
                    [
                        'role' => 'Guardian',
                        'name' => displayName(
                            $enrollment['g_first_name'],
                            $enrollment['g_middle_name'],
                            $enrollment['g_last_name']
                        ),
                        'email' => $enrollment['g_email'] ?? '',
                        'icon' => 'bi-person-heart',
                    ],
                ];
                ?>

                <?php foreach ($familyMembers as $member): ?>

                    <div class="col-12 col-lg-4">

                        <div class="border rounded-3 bg-paper-soft h-100 p-3">
                            <div class="text-primary text-uppercase fw-bold small mb-2">
                                <i class="bi <?= e($member['icon']) ?> me-1"></i>
                                <?= e($member['role']) ?>
                            </div>

                            <div class="fw-bold text-break">
                                <?= e($member['name'] ?: 'Not provided') ?>
                            </div>

                            <div class="text-muted small text-break mt-1">
                                <?= e($member['email'] ?: 'No email provided') ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </section>


    <!-- Supporting document -->
    <section class="card mt-3">

        <div class="card-header bg-white p-4">
            <h2 class="h6 fw-bold mb-1">
                <i class="bi bi-paperclip text-primary me-2"></i>
                Supporting document
            </h2>
            <p class="text-muted small mb-0">
                Document attached to the enrollment application.
            </p>
        </div>

        <?php if ($fileExists): ?>

            <div class="card-body p-4">

                <div class="row g-4 align-items-center">

                    <div class="col-12 col-lg-4">

                        <div class="d-flex align-items-start gap-3">
                            <div class="icon rounded-3 flex-shrink-0">
                                <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' ?>"></i>
                            </div>

                            <div class="min-w-0">
                                <div class="fw-bold text-break">
                                    <?= e($fileName) ?>
                                </div>
                                <div class="text-muted small text-break mt-1">
                                    <?= e($enrollment['file_type'] ?: 'Supporting document') ?>
                                </div>

                                <a
                                    href="<?= e($fileUrl) ?>"
                                    class="btn btn-outline-primary btn-sm mt-3"
                                    target="_blank"
                                    rel="noopener"
                                    download
                                >
                                    <i class="bi bi-download me-1"></i>
                                    Download document
                                </a>
                            </div>
                        </div>

                    </div>

                    <div class="col-12 col-lg-8">

                        <div class="ratio ratio-4x3 border rounded-3 bg-light p-2">

                            <?php if ($isImage): ?>

                                <img
                                    src="<?= e($fileUrl) ?>"
                                    class="img-fluid rounded object-fit-contain"
                                    alt="Supporting document for <?= e($childName) ?>"
                                >

                            <?php elseif ($isPdf): ?>

                                <iframe
                                    src="<?= e($fileUrl) ?>"
                                    class="rounded"
                                    title="Supporting document for <?= e($childName) ?>"
                                ></iframe>

                            <?php else: ?>

                                <div class="d-flex align-items-center justify-content-center text-muted small text-center p-4">
                                    Preview is unavailable for this file type. Use the download button to open the document.
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        <?php else: ?>

            <div class="card-body text-center text-muted small py-5">
                No supporting document is available for this enrollment.
            </div>

        <?php endif; ?>

    </section>

</div>

<?php include '../layout/footer.php'; ?>
