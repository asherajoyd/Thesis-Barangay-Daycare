<?php include 'layout/header.php'; ?>

<?php
include "../../api/conn.php";
$sql = "SELECT * FROM teacher";
$result = $conn->query($sql);
?>

<div>
<div class="d-flex justify-content-between">
    <h2 >Manage Teacher</h2>
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
               <?php while ($row = $result->fetch_assoc()) { ?>
               <tr>
                   <td><?php echo htmlspecialchars($row["id"]); ?></td>
                   <td><?php echo htmlspecialchars($row["first_name"]); ?></td>
                   <td><?php echo htmlspecialchars($row["middle_name"]); ?></td>
                   <td><?php echo htmlspecialchars($row["last_name"]); ?></td>
                   <td><?php echo htmlspecialchars($row["email"]); ?></td>
                   <td>
                       <a href="edit-teacher.php?id=<?php echo urlencode($row["id"]); ?>" class="btn btn-warning btn-sm">Edit</a>
                       <a href="delete-teacher.php?id=<?php echo urlencode($row["id"]); ?>" class="btn btn-danger btn-sm">Delete</a>
                   </td>
               </tr>
               <?php } ?>
           </tbody>
       </table>
   </div>
</div>

<?php include 'layout/footer.php'; ?>