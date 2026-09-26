<?php 
    $pageTitle = "Parents";

    require_once '../guard.php';
    include '../layout/header.php';
    include "../../../api/conn.php";
?>

<?php
$sql = "SELECT 
            c.id,
            e.id AS enrollment_id,
            e.g_first_name,
            e.g_middle_name,
            e.g_last_name,
            e.g_email,
            e.c_first_name,
            e.c_middle_name,
            e.c_last_name
        FROM children c
        LEFT JOIN enrollment e 
            ON c.enrollment_id = e.id
        ORDER BY c.id DESC";

$result = $conn->query($sql);
?>

<div>

    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?php echo ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">

            <?php echo htmlspecialchars($_SESSION['message']); ?>

            <button 
                type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
            </button>

        </div>

        <?php
        unset($_SESSION['status']);
        unset($_SESSION['message']);
        ?>

    <?php endif; ?>


    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="fw-bold fs-4">Manage Parents</h2>
    </div>


    <div class="card p-0">

        <div class="table-responsive">

            <table class="table mb-0 data-table   text-nowrap">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guardian Name</th>
                        <th>Guardian Email</th>
                        <th>Child Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($row['id']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        trim(
                                            $row['g_first_name'] . ' ' .
                                            $row['g_middle_name'] . ' ' .
                                            $row['g_last_name']
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row['g_email']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        trim(
                                            $row['c_first_name'] . ' ' .
                                            $row['c_middle_name'] . ' ' .
                                            $row['c_last_name']
                                        )
                                    );
                                    ?>
                                </td>

                                <td>

                                    <a 
                                        href="view-child.php?id=<?php echo urlencode($row['id']); ?>"
                                        class="btn btn-primary btn-sm">
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4 bg-light">
                                No children available.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include '../layout/footer.php'; ?>
