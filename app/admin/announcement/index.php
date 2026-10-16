<?php

$pageTitle = "Announcement";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// FILTER
// ==========================================================

$filterStatus   = $_GET['status']   ?? '';
$filterAudience = $_GET['audience'] ?? '';


// ==========================================================
// GET ANNOUNCEMENTS
// ==========================================================

$announcements = [];

$sql = "
    SELECT
        a.*,
        u.first_name AS creator_first_name,
        u.last_name AS creator_last_name
    FROM announcement a
    LEFT JOIN users u ON u.id = a.created_by
    WHERE 1=1
";

$params = [];
$types  = '';

if ($filterStatus !== '' && in_array($filterStatus, ['published', 'draft'], true)) {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterAudience !== '' && in_array($filterAudience, ['all', 'parent', 'teacher'], true)) {
    $sql .= " AND a.audience = ?";
    $params[] = $filterAudience;
    $types .= 's';
}

$sql .= " ORDER BY a.created_at DESC, a.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();


include '../layout/header.php';

?>

<div class="announcement-page">

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
            <p class="text-muted small mb-0">Manage announcements for parents and teachers</p>
        </div>

        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add Announcement
        </a>

    </div>


    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET">

                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Audience</label>
                        <select name="audience" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                            <option value="">All Audience</option>
                            <option value="all" <?= $filterAudience === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="parent" <?= $filterAudience === 'parent' ? 'selected' : '' ?>>Parents</option>
                            <option value="teacher" <?= $filterAudience === 'teacher' ? 'selected' : '' ?>>Teachers</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <a href="index.php" class="btn btn-light border btn-sm w-100">
                            <i class="bi bi-x-circle me-1"></i>
                            Reset Filters
                        </a>
                    </div>

                </div>

            </form>

        </div>
    </div>


    <!-- ANNOUNCEMENTS LIST -->
    <div class="card p-0">

        <div class="card-header bg-paper border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-megaphone text-primary me-2"></i>
                    Announcements
                </h6>
                <span class="badge bg-paper-soft text-secondary border">
                    <?= count($announcements) ?> record<?= count($announcements) !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <?php if (!empty($announcements)): ?>

            <div class="card-body p-3">

                <div class="d-flex flex-column gap-2">

                    <?php foreach ($announcements as $a): ?>

                        <?php
                        $priorityMeta = [
                            'normal'    => ['label' => 'Normal',    'class' => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle', 'icon' => 'bi-info-circle'],
                            'important' => ['label' => 'Important', 'class' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',       'icon' => 'bi-exclamation-circle'],
                            'urgent'    => ['label' => 'Urgent',    'class' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',          'icon' => 'bi-exclamation-triangle'],
                        ];

                        $audienceMeta = [
                            'all'     => ['label' => 'Everyone',  'icon' => 'bi-people'],
                            'parent'  => ['label' => 'Parents',   'icon' => 'bi-person-hearts'],
                            'teacher' => ['label' => 'Teachers',  'icon' => 'bi-person-video3'],
                        ];

                        $p = $priorityMeta[$a['priority']] ?? $priorityMeta['normal'];
                        $au = $audienceMeta[$a['audience']] ?? $audienceMeta['all'];

                        $creatorName = trim(
                            ($a['creator_first_name'] ?? '') . ' ' .
                            ($a['creator_last_name'] ?? '')
                        );
                        ?>

                        <div class="border rounded-3 p-3">

                            <div class="d-flex flex-wrap align-items-start gap-3">

                                <!-- Priority Icon -->
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 border <?= e($p['class']) ?>"
                                     style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi <?= e($p['icon']) ?>"></i>
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1 min-w-0">

                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <div class="fw-bold text-break">
                                            <?= e($a['title']) ?>
                                        </div>

                                        <?php if ($a['status'] === 'draft'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                Draft
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="text-muted small mb-2 text-break" style="max-height: 4.5em; overflow: hidden;">
                                        <?= e(mb_strimwidth($a['content'], 0, 200, '...')) ?>
                                    </p>

                                    <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">

                                        <span class="badge bg-paper-soft text-secondary border">
                                            <i class="bi <?= e($au['icon']) ?> me-1"></i>
                                            <?= e($au['label']) ?>
                                        </span>

                                        <span class="badge border <?= e($p['class']) ?>">
                                            <?= e($p['label']) ?>
                                        </span>

                                        <span>
                                            <i class="bi bi-calendar me-1"></i>
                                            <?= e(date('M d, Y', strtotime($a['created_at']))) ?>
                                        </span>

                                        <?php if ($creatorName): ?>
                                            <span>
                                                <i class="bi bi-person me-1"></i>
                                                <?= e($creatorName) ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                </div>

                                <!-- Actions -->
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">

                                    <a href="edit.php?id=<?= (int) $a['id'] ?>"
                                       class="btn btn-warning btn-sm"
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a href="delete.php?id=<?= (int) $a['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this announcement?');"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php else: ?>

            <div class="card-body text-center py-5">
                <i class="bi bi-megaphone fs-2 text-muted"></i>
                <h6 class="fw-semibold mt-2 mb-1">No Announcements</h6>
                <p class="text-muted small mb-0">
                    Start by creating your first announcement.
                </p>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include '../layout/footer.php'; ?>