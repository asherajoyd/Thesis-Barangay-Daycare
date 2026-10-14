<?php
$pageTitle = "Activities";

require_once __DIR__ . '/../../guard.php';
require_once __DIR__ . '/../../../api/conn.php';

// Get the logged-in parent's user ID.
// This assumes children.user_id stores the parent's user ID.
$parentUserId = (int) ($_SESSION['user_id'] ?? 0);

$assessments = [];
$errorMessage = '';

// Only fetch records belonging to this parent's children.
if ($parentUserId > 0) {
    $sql = "
        SELECT
            aa.id,
            aa.title,
            aa.activity_type,
            aa.remarks,
            aa.assessment_date,
            aa.file_path,
            c.id AS child_id,
            e.c_first_name,
            e.c_middle_name,
            e.c_last_name
        FROM activity_assessments aa
        INNER JOIN children c
            ON c.id = aa.child_id
        LEFT JOIN enrollment e
            ON e.id = c.enrollment_id
        WHERE c.user_id = ?
        ORDER BY aa.assessment_date DESC, aa.id DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $parentUserId);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $assessments[] = $row;
            }
        } else {
            $errorMessage = "Unable to load activities right now.";
        }

        $stmt->close();
    } else {
        $errorMessage = "Unable to load activities right now.";
    }
}

function activityEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

include __DIR__ . '/../../layout/header.php';

?>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>
    .parent-activities {
        font-family: 'DM Sans', sans-serif;
        padding: 12px 0 30px;
        color: #17221c;
    }

    .parent-activities .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .parent-activities .page-subtitle {
        color: #6f7b74;
        font-size: .9rem;
        margin-bottom: 24px;
    }

    .activity-summary {
        background: #fff;
        border: 1px solid #e1e7e3;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 22px;
    }

    .activity-summary .summary-number {
        color: #176b3a;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .activity-card {
        background: #fff;
        border: 1px solid #e1e7e3;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 16px;
        box-shadow: 0 2px 6px rgba(20, 50, 30, .03);
    }

    .activity-card h3 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
    }

    .activity-badge {
        display: inline-block;
        background: #e7f0eb;
        color: #176b3a;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
    }

    .activity-meta {
        color: #6f7b74;
        font-size: .85rem;
        margin-top: 10px;
    }

    .activity-remarks {
        background: #f7faf8;
        border-radius: 8px;
        padding: 12px;
        margin-top: 15px;
        font-size: .9rem;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .activity-file {
        display: inline-block;
        margin-top: 15px;
        padding: 8px 12px;
        border: 1px solid #176b3a;
        color: #176b3a;
        border-radius: 8px;
        text-decoration: none;
        font-size: .85rem;
        font-weight: 600;
    }

    .activity-file:hover {
        background: #e7f0eb;
        color: #0f542c;
    }

    .activity-empty {
        background: #fff;
        border: 1px dashed #cbd8cf;
        border-radius: 14px;
        padding: 45px 20px;
        text-align: center;
        color: #6f7b74;
    }

    .activity-empty i {
        font-size: 2.5rem;
        color: #176b3a;
    }
</style>

<div class="parent-activities">

    <h2 class="page-title">
        <i class="bi bi-journal-text" style="color:#176b3a"></i>
        Activities
    </h2>

    <p class="page-subtitle">
        View your child's activities, assessment notes, and uploaded files.
    </p>

    <?php if ($errorMessage !== ''): ?>

        <div class="alert alert-danger">
            <?= activityEscape($errorMessage) ?>
        </div>

    <?php else: ?>

        <div class="activity-summary">
            <div class="text-muted small">Available Activity Records</div>
            <div class="summary-number"><?= count($assessments) ?></div>
            <div class="text-muted small">Activities recorded by the teacher</div>
        </div>

        <?php if (!empty($assessments)): ?>

            <?php foreach ($assessments as $row): ?>

                <?php
                    $studentName = trim(
                        ($row['c_first_name'] ?? '') . ' ' .
                        ($row['c_middle_name'] ?? '') . ' ' .
                        ($row['c_last_name'] ?? '')
                    );

                    if ($studentName === '') {
                        $studentName = 'Child #' . (int)$row['child_id'];
                    }

                    $filePath = trim($row['file_path'] ?? '');

                    // Files in the supplied teacher code are stored
                    // relative to the thesis project root.
                    $fileUrl = '';

                    if (
                        $filePath !== '' &&
                        !str_contains($filePath, '..') &&
                        !str_contains($filePath, '\\') &&
                        !preg_match('/^[a-z][a-z0-9+.-]*:/i', $filePath) &&
                        !str_starts_with($filePath, '/')
                    ) {
                        $fileUrl = '/ashera/thesis/' . $filePath;
                    }
                ?>

                <article class="activity-card">

                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">

                        <div>
                            <h3><?= activityEscape($row['title'] ?: 'Untitled Activity') ?></h3>

                            <div class="mt-2">
                                <span class="activity-badge">
                                    <?= activityEscape($row['activity_type'] ?: 'Activity') ?>
                                </span>
                            </div>
                        </div>

                        <span class="text-muted small">
                            #<?= (int)$row['id'] ?>
                        </span>

                    </div>

                    <div class="activity-meta">
                        <div>
                            <i class="bi bi-person"></i>
                            Child:
                            <strong><?= activityEscape($studentName) ?></strong>
                        </div>

                        <div class="mt-1">
                            <i class="bi bi-calendar3"></i>
                            Date:
                            <?php if (!empty($row['assessment_date'])): ?>
                                <?= activityEscape(date(
                                    'F d, Y',
                                    strtotime($row['assessment_date'])
                                )) ?>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (trim($row['remarks'] ?? '') !== ''): ?>
                        <div class="activity-remarks">
                            <strong>Teacher's Remarks</strong>
                            <div><?= activityEscape($row['remarks']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($fileUrl !== ''): ?>
                        <a
                            class="activity-file"
                            href="<?= activityEscape($fileUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="bi bi-paperclip"></i>
                            View Attachment
                        </a>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="activity-empty">
                <i class="bi bi-journal-x d-block mb-3"></i>

                <h5 class="fw-bold text-dark">No Activities Yet</h5>

                <p class="mb-0">
                    Activity records for your child will appear here
                    when the teacher adds them.
                </p>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../../layout/footer.php'; ?>