<?php

$pageTitle = "Archive";

require_once '../guard.php';
include '../layout/header.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT
        e.id,
        e.status,
        c.first_name,
        c.middle_name,
        c.last_name,
        c.gender,
        c.birthdate
    FROM enrollment e
    INNER JOIN children c ON c.id = e.child_id
    WHERE e.status = 'rejected'
    ORDER BY e.id DESC
";

$result = $conn->query($sql);

?>

<div>

    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?= ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <?php unset($_SESSION['status'], $_SESSION['message']); ?>

    <?php endif; ?>


    <div class="d-flex align-items-center justify-content-between">
        <h2 class="fw-bold fs-4">Archive</h2>
        <a href="<?= $baseUrl ?>enrollment/" class="btn btn-dark mb-3 py-1 px-2"
           data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Back">
            <i class="bi bi-arrow-left-square"></i>
        </a>
    </div>


    <div class="card p-0">
        <div class="table-responsive">
            <table class="table mb-0 data-table text-nowrap">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Gender</th>
                        <th>Birthdate</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td><?= e($row['id']) ?></td>

                                <td>
                                    <?= e(trim(
                                        $row['first_name'] . ' ' .
                                        $row['middle_name'] . ' ' .
                                        $row['last_name']
                                    )) ?>
                                </td>

                                <td class="text-capitalize fw-bold"><?= e($row['gender']) ?></td>

                                <td><?= date('F d, Y', strtotime($row['birthdate'])) ?></td>

                                <td>
                                    <span class="badge bg-danger">Rejected</span>
                                </td>

                                <td>
                                    <a href="restore-enrollment.php?id=<?= urlencode($row['id']) ?>"
                                       class="btn btn-success btn-sm"
                                       onclick="return confirm('Restore this enrollment?');">
                                        Restore
                                    </a>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center text-muted py-4 bg-light">
                                No enrollment data available.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>
    </div>

</div>


<?php include '../layout/footer.php'; ?>