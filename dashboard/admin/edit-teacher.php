<?php
include "../../api/conn.php";

$id = "";
$first_name = "";
$middle_name = "";
$last_name = "";
$email = "";

$errorMessage = "";
$successMessage = "";

// 1. GET Request: Fetch existing teacher data to pre-fill the form
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET["id"])) {
        header("Location: index.php");
        exit();
    }

    $id = $_GET["id"];

    $sql = "SELECT * FROM teacher WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        header("Location: index.php");
        exit();
    }

    $first_name = $row["first_name"];
    $middle_name = $row["middle_name"];
    $last_name = $row["last_name"];
    $email = $row["email"];
    $stmt->close();
} 
// 2. POST Request: Handle updating the teacher data
else {
    $id = $_POST["id"];
    $first_name = trim($_POST["first_name"] ?? '');
    $middle_name = trim($_POST["middle_name"] ?? '');
    $last_name  = trim($_POST["last_name"] ?? '');
    $email      = trim($_POST["email"] ?? '');

    // Validate inputs
    if (empty($first_name) || empty($middle_name) || empty($last_name) || empty($email)) {
        $errorMessage = "All fields are required";
    } else {
        $sql = "UPDATE teacher SET first_name = ?, middle_name = ?, last_name = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $first_name, $middle_name, $last_name, $email, $id);

        if ($stmt->execute()) {
            header("Location: teachers.php");
            exit();
        } else {
            $errorMessage = "Failed to update teacher";
        }
        $stmt->close();
    }
}
?>

<?php include 'layout/header.php'; ?>

<div>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="mb-4">Edit Teacher</h2>

            <?php if (!empty($errorMessage)) { ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong><?php echo htmlspecialchars($errorMessage); ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <form action="edit-teacher.php" method="POST">
                <!-- Hidden input to send $id on form submit -->
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($middle_name); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>