<?php
include "../../api/conn.php";

$first_name = "";
$middle_name = "";
$last_name = "";
$email = "";

$errorMessage = "";
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name  = trim($_POST["first_name"] ?? '');
    $middle_name = trim($_POST["middle_name"] ?? '');
    $last_name   = trim($_POST["last_name"] ?? '');
    $email       = trim($_POST["email"] ?? '');

    // Default password (123) na naka-hash at role na "teacher"
    $default_password = password_hash("123", PASSWORD_DEFAULT);
    $role = "teacher";

    // Validate inputs
    if (empty($first_name) || empty($middle_name) || empty($last_name) || empty($email)) {$errorMessage = "All fields are required";
    } else {
        // -------------------------------------------------------------
        // 1. FIRST INSERT: Save to `users` table
        // -------------------------------------------------------------
        $sql_user = "INSERT INTO users (first_name, middle_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_user =$conn->prepare($sql_user);$stmt_user->bind_param("ssssss", $first_name,$middle_name, $last_name,$email, $default_password,$role);

        if ($stmt_user->execute()) {
            // Kunin ang id na bagong gawa sa `users` table (kung kailangan)
            $user_id =$stmt_user->insert_id;
            $stmt_user->close();

            // -------------------------------------------------------------
            // 2. SECOND INSERT: Save to `teacher` table
            // -------------------------------------------------------------
            $sql_teacher = "INSERT INTO teacher (first_name, middle_name, last_name, email) VALUES (?, ?, ?, ?)";
            $stmt_teacher =$conn->prepare($sql_teacher);$stmt_teacher->bind_param("ssss", $first_name,$middle_name, $last_name,$email);

            if ($stmt_teacher->execute()) {$stmt_teacher->close();

                // Kapag parehong matagumpay, mag-redirect sa teachers list
                header("Location: teachers.php");
                exit();
            } else {
                $errorMessage = "Saved to users, but failed to save to teacher table: " . $conn->error;
            }
        } else {
            $errorMessage = "Failed to save user: " . $conn->error;
        }
    }
}
?>

<?php include 'layout/header.php'; ?>

<div>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="mb-4">Add Teacher Account</h2>

            <?php if (!empty($errorMessage)) { ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong><?php echo htmlspecialchars($errorMessage); ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <?php if (!empty($successMessage)) { ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong><?php echo htmlspecialchars($successMessage); ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <form action="create-teacher.php" method="POST">
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
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="teachers.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>