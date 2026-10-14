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
    0 => ['label' => 'Pending', 'class' => 'pending', 'icon' => 'bi-hourglass-split'],
    1 => ['label' => 'Approved', 'class' => 'approved', 'icon' => 'bi-check-circle-fill'],
    2 => ['label' => 'Rejected', 'class' => 'rejected', 'icon' => 'bi-x-circle-fill'],
];

$currentStatus = $statusMeta[$status] ?? [
    'label' => 'Unknown',
    'class' => 'unknown',
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

<style>

    .enrollment-detail {
        max-width: 1280px;
        margin: 0 auto;
    }

    .enrollment-detail .detail-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .enrollment-detail .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--color-text-secondary);
        font-size: 13px;
        font-weight: 600;
    }

    .enrollment-detail .back-link:hover {
        color: var(--color-primary);
    }

    .enrollment-detail .detail-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .enrollment-detail .profile-card {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #f0f7f1 100%);
    }

    .enrollment-detail .profile-card::after {
        position: absolute;
        right: -55px;
        bottom: -80px;
        width: 220px;
        height: 220px;
        border: 1px solid rgba(22, 101, 52, 0.08);
        border-radius: 50%;
        content: '';
    }

    .enrollment-detail .profile-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 26px;
    }

    .enrollment-detail .profile-identity {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .enrollment-detail .profile-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 68px;
        height: 68px;
        flex: 0 0 68px;
        border-radius: 18px;
        background: var(--color-primary);
        color: #ffffff;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    .enrollment-detail .eyebrow {
        margin-bottom: 5px;
        color: var(--color-primary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .enrollment-detail .profile-name {
        margin: 0;
        color: var(--color-text);
        font-size: clamp(1.25rem, 2vw, 1.7rem);
        font-weight: 700;
    }

    .enrollment-detail .profile-subtitle {
        margin: 5px 0 0;
        color: var(--color-text-muted);
        font-size: 13px;
    }

    .enrollment-detail .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .enrollment-detail .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }

    .enrollment-detail .status-badge.approved {
        background: #dcfce7;
        color: #166534;
    }

    .enrollment-detail .status-badge.rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .enrollment-detail .status-badge.unknown {
        background: var(--color-paper-soft);
        color: var(--color-text-secondary);
    }

    .enrollment-detail .detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
        gap: 18px;
        margin-top: 18px;
    }

    .enrollment-detail .detail-card {
        height: 100%;
        border: 1px solid var(--color-border);
        border-radius: 16px;
        background: var(--color-paper);
        overflow: hidden;
    }

    .enrollment-detail .detail-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 20px 22px 16px;
        border-bottom: 1px solid var(--color-border);
    }

    .enrollment-detail .detail-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: var(--color-text);
        font-size: 15px;
        font-weight: 700;
    }

    .enrollment-detail .detail-card-title i {
        color: var(--color-primary);
        font-size: 18px;
    }

    .enrollment-detail .detail-card-description {
        margin: 5px 0 0 28px;
        color: var(--color-text-muted);
        font-size: 12px;
    }

    .enrollment-detail .detail-card-body {
        padding: 22px;
    }

    .enrollment-detail .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px 24px;
    }

    .enrollment-detail .info-item.full-width {
        grid-column: 1 / -1;
    }

    .enrollment-detail .info-label {
        display: block;
        margin-bottom: 5px;
        color: var(--color-text-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .enrollment-detail .info-value {
        color: var(--color-text);
        font-size: 14px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .enrollment-detail .info-value.muted {
        color: var(--color-text-muted);
        font-weight: 500;
    }

    .enrollment-detail .summary-list {
        display: grid;
        gap: 16px;
        margin: 0;
    }

    .enrollment-detail .summary-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--color-border);
    }

    .enrollment-detail .summary-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .enrollment-detail .summary-row dt,
    .enrollment-detail .summary-row dd {
        margin: 0;
        font-size: 13px;
    }

    .enrollment-detail .summary-row dt {
        color: var(--color-text-muted);
    }

    .enrollment-detail .summary-row dd {
        color: var(--color-text);
        font-weight: 700;
        text-align: right;
    }

    .enrollment-detail .family-section,
    .enrollment-detail .document-section {
        margin-top: 18px;
    }

    .enrollment-detail .family-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        padding: 18px 22px 22px;
    }

    .enrollment-detail .family-card {
        padding: 18px;
        border: 1px solid var(--color-border);
        border-radius: 12px;
        background: var(--color-background);
    }

    .enrollment-detail .family-role {
        margin-bottom: 12px;
        color: var(--color-primary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .enrollment-detail .family-name {
        margin-bottom: 6px;
        color: var(--color-text);
        font-size: 15px;
        font-weight: 700;
    }

    .enrollment-detail .family-email {
        color: var(--color-text-muted);
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .enrollment-detail .document-layout {
        display: grid;
        grid-template-columns: minmax(220px, 0.7fr) minmax(0, 1.3fr);
        gap: 22px;
        padding: 22px;
    }

    .enrollment-detail .document-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
    }

    .enrollment-detail .document-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        margin-bottom: 14px;
        border-radius: 14px;
        background: rgba(22, 101, 52, 0.1);
        color: var(--color-primary);
        font-size: 25px;
    }

    .enrollment-detail .document-name {
        margin-bottom: 6px;
        color: var(--color-text);
        font-size: 14px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .enrollment-detail .document-type {
        margin-bottom: 18px;
        color: var(--color-text-muted);
        font-size: 12px;
    }

    .enrollment-detail .document-preview {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        padding: 12px;
        border: 1px solid var(--color-border);
        border-radius: 12px;
        background: var(--color-background);
    }

    .enrollment-detail .document-preview img {
        display: block;
        max-width: 100%;
        max-height: 330px;
        border-radius: 8px;
        object-fit: contain;
    }

    .enrollment-detail .document-preview iframe {
        width: 100%;
        height: 330px;
        border: 0;
        border-radius: 8px;
        background: #ffffff;
    }

    .enrollment-detail .empty-document {
        padding: 28px;
        color: var(--color-text-muted);
        font-size: 13px;
        text-align: center;
    }

    @media (max-width: 900px) {
        .enrollment-detail .detail-grid,
        .enrollment-detail .document-layout {
            grid-template-columns: 1fr;
        }

        .enrollment-detail .family-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .enrollment-detail .detail-topbar,
        .enrollment-detail .profile-content {
            align-items: flex-start;
            flex-direction: column;
        }

        .enrollment-detail .detail-actions {
            justify-content: flex-start;
        }

        .enrollment-detail .profile-content,
        .enrollment-detail .detail-card-body,
        .enrollment-detail .document-layout {
            padding: 18px;
        }

        .enrollment-detail .detail-card-header,
        .enrollment-detail .family-grid {
            padding-right: 18px;
            padding-left: 18px;
        }

        .enrollment-detail .info-grid {
            grid-template-columns: 1fr;
        }

        .enrollment-detail .info-item.full-width {
            grid-column: auto;
        }
    }

</style>

<div class="enrollment-detail">

    <div class="detail-topbar">

        <a href="index.php" class="back-link">
            <i class="bi bi-arrow-left"></i>
            Back to enrollments
        </a>

        <div class="detail-actions">

            <?php if ($status === 0): ?>

                <a
                    href="approve-enrollment.php?id=<?= urlencode($enrollment['id']) ?>"
                    class="btn btn-success btn-sm"
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

            <?php endif; ?>

        </div>

    </div>


    <section class="card profile-card border-0">

        <div class="profile-content">

            <div class="profile-identity">

                <div class="profile-avatar">
                    <?= e($childInitials ?: 'C') ?>
                </div>

                <div>
                    <div class="eyebrow">Enrollment details</div>
                    <h1 class="profile-name"><?= e($childName) ?></h1>
                    <p class="profile-subtitle">
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

            <span class="status-badge <?= e($currentStatus['class']) ?>">
                <i class="bi <?= e($currentStatus['icon']) ?>"></i>
                <?= e($currentStatus['label']) ?>
            </span>

        </div>

    </section>


    <div class="detail-grid">

        <section class="detail-card">

            <div class="detail-card-header">
                <div>
                    <h2 class="detail-card-title">
                        <i class="bi bi-person-vcard"></i>
                        Child information
                    </h2>
                    <p class="detail-card-description">
                        Personal information provided in the application.
                    </p>
                </div>
            </div>

            <div class="detail-card-body">

                <div class="info-grid">

                    <div class="info-item">
                        <span class="info-label">First name</span>
                        <div class="info-value"><?= e($enrollment['c_first_name']) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Middle name</span>
                        <div class="info-value <?= empty($enrollment['c_middle_name']) ? 'muted' : '' ?>">
                            <?= e($enrollment['c_middle_name'] ?: 'Not provided') ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Last name</span>
                        <div class="info-value"><?= e($enrollment['c_last_name']) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Gender</span>
                        <div class="info-value text-capitalize"><?= e($enrollment['c_gender']) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Date of birth</span>
                        <div class="info-value"><?= e(displayDate($enrollment['c_birthdate'])) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Place of birth</span>
                        <div class="info-value"><?= e($enrollment['c_place_of_birth']) ?></div>
                    </div>

                    <div class="info-item full-width">
                        <span class="info-label">Complete address</span>
                        <div class="info-value"><?= e($enrollment['c_address']) ?></div>
                    </div>

                </div>

            </div>

        </section>


        <section class="detail-card">

            <div class="detail-card-header">
                <div>
                    <h2 class="detail-card-title">
                        <i class="bi bi-clipboard2-check"></i>
                        Enrollment summary
                    </h2>
                    <p class="detail-card-description">
                        Application and academic-year details.
                    </p>
                </div>
            </div>

            <div class="detail-card-body">

                <dl class="summary-list">

                    <div class="summary-row">
                        <dt>Application ID</dt>
                        <dd>#<?= e($enrollment['id']) ?></dd>
                    </div>

                    <div class="summary-row">
                        <dt>Academic year</dt>
                        <dd><?= e($enrollment['academic_year'] ?: 'Not assigned') ?></dd>
                    </div>

                    <div class="summary-row">
                        <dt>Submitted</dt>
                        <dd><?= e(displayDate($enrollment['created_at'])) ?></dd>
                    </div>

                    <div class="summary-row">
                        <dt>Current status</dt>
                        <dd><?= e($currentStatus['label']) ?></dd>
                    </div>

                </dl>

            </div>

        </section>

    </div>


    <section class="detail-card family-section">

        <div class="detail-card-header">
            <div>
                <h2 class="detail-card-title">
                    <i class="bi bi-people"></i>
                    Parent and guardian information
                </h2>
                <p class="detail-card-description">
                    Family contacts submitted with this enrollment application.
                </p>
            </div>
        </div>

        <div class="family-grid">

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

                <article class="family-card">
                    <div class="family-role">
                        <i class="bi <?= e($member['icon']) ?> me-1"></i>
                        <?= e($member['role']) ?>
                    </div>
                    <div class="family-name">
                        <?= e($member['name'] ?: 'Not provided') ?>
                    </div>
                    <div class="family-email">
                        <?= e($member['email'] ?: 'No email provided') ?>
                    </div>
                </article>

            <?php endforeach; ?>

        </div>

    </section>


    <section class="detail-card document-section">

        <div class="detail-card-header">
            <div>
                <h2 class="detail-card-title">
                    <i class="bi bi-paperclip"></i>
                    Supporting document
                </h2>
                <p class="detail-card-description">
                    Document attached to the enrollment application.
                </p>
            </div>
        </div>

        <?php if ($fileExists): ?>

            <div class="document-layout">

                <div class="document-meta">
                    <div class="document-icon">
                        <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' ?>"></i>
                    </div>

                    <div class="document-name"><?= e($fileName) ?></div>
                    <div class="document-type">
                        <?= e($enrollment['file_type'] ?: 'Supporting document') ?>
                    </div>

                    <a
                        href="<?= e($fileUrl) ?>"
                        class="btn btn-outline-primary btn-sm"
                        target="_blank"
                        rel="noopener"
                        download
                    >
                        <i class="bi bi-download me-1"></i>
                        Download document
                    </a>
                </div>

                <div class="document-preview">

                    <?php if ($isImage): ?>

                        <img
                            src="<?= e($fileUrl) ?>"
                            alt="Supporting document for <?= e($childName) ?>"
                        >

                    <?php elseif ($isPdf): ?>

                        <iframe
                            src="<?= e($fileUrl) ?>"
                            title="Supporting document for <?= e($childName) ?>"
                        ></iframe>

                    <?php else: ?>

                        <div class="empty-document">
                            Preview is unavailable for this file type. Use the download button to open the document.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php else: ?>

            <div class="empty-document">
                No supporting document is available for this enrollment.
            </div>

        <?php endif; ?>

    </section>

</div>

<?php include '../layout/footer.php'; ?>
