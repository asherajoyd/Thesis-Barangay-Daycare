<?php 
    $pageTitle = "Manage Enrollment";

    require_once '../guard.php';
    include '../layout/header.php';
    include "../../../api/conn.php";
?>

<?php
$sql = "SELECT 
            id,
            c_first_name,
            c_middle_name,
            c_last_name,
            c_gender,
            c_birthdate,
            status
        FROM enrollment
        WHERE status = 0
        ORDER BY id DESC";

$result = $conn->query($sql);
?>

<div>

    <?php if (!empty($_SESSION['message'])): ?>

        <div class="fw-semibold alert alert-<?php echo ($_SESSION['status'] ?? '') === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">

            <?php echo htmlspecialchars($_SESSION['message']); ?>

            <button 
                type="button" 
                class="btn-close" 
                data-bs-dismiss="alert">
            </button>

        </div>

        <?php
        unset($_SESSION['status']);
        unset($_SESSION['message']);
        ?>

    <?php endif; ?>


    <div class="d-flex align-items-center justify-content-between">
        <h2 class="fw-bold fs-4">Manage Enrollment</h2>
        <a href="archieve.php" class="btn btn-warning mb-3 py-1 px-2" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Archive">
            <i class="bi bi-archive"></i>
        </a>
    </div>


    <div class="card p-0">

        <div class="table-responsive">

            <table class="table mb-0 data-table table-striped text-nowrap">

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

                            <?php
                            $status = (int) $row['status'];
                            ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($row['id']); ?>
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

                                <td class="text-capitalize fw-bold">
                                    <?php echo htmlspecialchars($row['c_gender']); ?>
                                </td>

                                <td>
                                    <?php echo date('F d, Y', strtotime($row['c_birthdate'])); ?>
                                </td>

                                <td>

                                    <?php if ($status === 0): ?>

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    <?php elseif ($status === 1): ?>

                                        <span class="badge bg-success">
                                            Approved
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <!-- View -->
                                    <a 
                                        href="view-enrollment.php?id=<?php echo urlencode($row['id']); ?>"
                                        class="btn btn-outline-secondary btn-sm">
                                        View
                                    </a>


                                    <?php if ($status === 0): ?>

                                        <!-- Approve -->
                                        <a 
                                            href="approve-enrollment.php?id=<?php echo urlencode($row['id']); ?>"
                                            class="btn btn-success btn-sm"
                                            onclick="return confirm('Approve this enrollment?');">
                                            Approve
                                        </a>

                                        <!-- Reject / Reject -->
                                        <a 
                                            href="reject-enrollment.php?id=<?php echo urlencode($row['id']); ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Reject this enrollment? This will permanently delete the record.');">
                                            Reject
                                        </a>

                                    <?php endif; ?>

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