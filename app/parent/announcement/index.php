<?php

$pageTitle = "Announcement";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET ANNOUNCEMENTS FOR PARENTS
// ==========================================================

$announcements = [];

$stmt = $conn->prepare("
    SELECT
        a.*,
        u.first_name AS creator_first_name,
        u.last_name AS creator_last_name
    FROM announcement a
    LEFT JOIN users u ON u.id = a.created_by
    WHERE a.status = 'published'
      AND a.audience IN ('all', 'parent')
    ORDER BY
        FIELD(a.priority, 'urgent', 'important', 'normal'),
        a.created_at DESC,
        a.id DESC
");

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();


include '../layout/header.php';

?>

<div class="announcement-page">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">Updates and announcements from the daycare</p>
        </div>

    </div>


    <?php if (!empty($announcements)): ?>

        <div class="d-flex flex-column gap-3">

            <?php foreach ($announcements as $a): ?>

                <?php
                $priorityMeta = [
                    'normal'    => ['label' => 'Normal',    'class' => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle', 'icon' => 'bi-info-circle'],
                    'important' => ['label' => 'Important', 'class' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',       'icon' => 'bi-exclamation-circle'],
                    'urgent'    => ['label' => 'Urgent',    'class' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',          'icon' => 'bi-exclamation-triangle'],
                ];

                $p = $priorityMeta[$a['priority']] ?? $priorityMeta['normal'];

                $creatorName = trim(
                    ($a['creator_first_name'] ?? '') . ' ' .
                    ($a['creator_last_name'] ?? '')
                );
                ?>

                <div class="card">

                    <div class="card-body p-4">

                        <!-- Priority + Title -->
                        <div class="d-flex align-items-start gap-3 mb-3">

                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 border <?= e($p['class']) ?>"
                                 style="width: 48px; height: 48px; font-size: 1.2rem;">
                                <i class="bi <?= e($p['icon']) ?>"></i>
                            </div>

                            <div class="flex-grow-1 min-w-0">

                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="fw-bold mb-0 text-break">
                                        <?= e($a['title']) ?>
                                    </h5>

                                    <span class="badge border <?= e($p['class']) ?>">
                                        <?= e($p['label']) ?>
                                    </span>
                                </div>

                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= e(date('F d, Y', strtotime($a['created_at']))) ?>
                                    <?php if ($creatorName): ?>
                                        <span class="mx-1">•</span>
                                        <i class="bi bi-person me-1"></i>
                                        <?= e($creatorName) ?>
                                    <?php endif; ?>
                                </small>

                            </div>

                        </div>

                        <!-- Content -->
                        <div class="text-break" style="white-space: pre-line;">
                            <?= e($a['content']) ?>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-megaphone fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Announcements</h6>
                <p class="text-muted small mb-0">
                    Walang announcements sa ngayon. Check back later.
                </p>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php include '../layout/footer.php'; ?>