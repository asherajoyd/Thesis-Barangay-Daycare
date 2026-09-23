<?php 
    $pageTitle = "Manage Teacher";
    require_once '../guard.php';
    include '../layout/header.php';
    include "../../../api/conn.php";
?>

<?php
$sql = "SELECT * FROM teacher";
$result = $conn->query($sql);
?>

<div>
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="fw-semibold alert alert-<?php echo $_SESSION['status'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <?php
        unset($_SESSION['status']);
        unset($_SESSION['message']);
        ?>
    <?php endif; ?>
    <div class="d-flex align-items-center justify-content-between">
        <h2 class="fw-bold fs-3">Manage Teacher</h2>
            <a href="create-teacher.php" class="btn btn-primary mb-3">Add Teacher</a>

    </div>

    <div class="card p-0">
       <table class="table mb-0 data-table table-striped">
           <thead>
               <tr>
                   <th>ID</th>
                   <th>First Name</th>
                   <th>Middle Name</th>
                   <th>Last Name</th>
                   <th>Email</th>
                   <th>Actions</th>
               </tr>
           </thead>
           <tbody>
               <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo htmlspecialchars($row["id"]); ?></td>
                            <td><?php echo htmlspecialchars($row["first_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["middle_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["last_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["email"]); ?></td>
                            <td>
                                <a href="edit-teacher.php?id=<?php echo urlencode($row["id"]); ?>"
                                class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <a href="delete-teacher.php?id=<?php echo urlencode($row["id"]); ?>"
                                class="btn btn-danger btn-sm">
                                    Delete
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center text-muted py-4 bg-light">
                            No data available.
                        </td>
                    </tr>

                <?php endif; ?>
           </tbody>
       </table>
   </div>
</div>

<?php include '../layout/footer.php'; ?>