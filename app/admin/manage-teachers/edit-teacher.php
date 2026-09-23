<?php
require_once '../guard.php';
include "../../../api/conn.php";

    $id = "";
    $first_name = "";
    $middle_name = "";
    $last_name = "";
    $email = "";

    $errorMessage = "";

    // GET: Get teacher data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        if (!isset($_GET['id'])) {
            header("Location: index.php");
            exit();
        }

        $id = $_GET['id'];

        $sql = "SELECT * FROM teacher WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $teacher = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$teacher) {
            header("Location: index.php");
            exit();
        }

        $first_name = $teacher['first_name'];
        $middle_name = $teacher['middle_name'];
        $last_name = $teacher['last_name'];
        $email = $teacher['email'];
    }

    // POST: Update teacher
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $id = $_POST['id'];
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($first_name) || empty($middle_name) || empty($last_name) || empty($email)) {

            $errorMessage = "All fields are required";

        } else {

            // Get user_id
            $sql = "SELECT user_id FROM teacher WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $teacher = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $user_id = $teacher['user_id'];

            // Update teacher
            $sql = "UPDATE teacher 
                    SET first_name = ?, middle_name = ?, last_name = ?, email = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssi",
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $id
            );

            if ($stmt->execute()) {

                $stmt->close();

                // Update users
                $sql = "UPDATE users
                        SET first_name = ?, middle_name = ?, last_name = ?, email = ?
                        WHERE id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssi",
                    $first_name,
                    $middle_name,
                    $last_name,
                    $email,
                    $user_id
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['status'] = "success";
                $_SESSION['message'] = "Teacher Updated Successfully!";

                header("Location: index.php");
                exit();

            } else {

                $errorMessage = "Failed to update teacher";
                $stmt->close();
            }
        }
    }


    $pageTitle = "Edit Teacher";
    include '../layout/header.php';
?>

<div>
    <div class="card shadow-sm">
        <div class="card-body p-4">

            <h2 class="fw-bold fs-3">Edit Teacher</h2>

            <?php if (!empty($errorMessage)): ?>

                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </strong>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Close">
                    </button>
                </div>

            <?php endif; ?>

            <form action="edit-teacher.php" method="POST">

                <input type="hidden"
                       name="id"
                       value="<?php echo htmlspecialchars($id); ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label small">First Name</label>

                        <input type="text"
                               name="first_name"
                               class="form-control"
                               value="<?php echo htmlspecialchars($first_name); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Middle Name</label>

                        <input type="text"
                               name="middle_name"
                               class="form-control"
                               value="<?php echo htmlspecialchars($middle_name); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Last Name</label>

                        <input type="text"
                               name="last_name"
                               class="form-control"
                               value="<?php echo htmlspecialchars($last_name); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Email</label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="name@example.com"
                               value="<?php echo htmlspecialchars($email); ?>"
                               required>
                    </div>

                    <div class="col-12 mt-4">

                        <button type="submit" class="btn btn-primary">
                            Update
                        </button>

                        <a href="index.php" class="btn btn-secondary">
                            Cancel
                        </a>

                    </div>

                </div>

            </form>

        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>