<?php

require_once '../guard.php';
include "../../../api/conn.php";


// ==========================================================
// HELPER
// ==========================================================

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// FORM STATE
// ==========================================================

$id          = 0;
$first_name  = "";
$middle_name = "";
$last_name   = "";
$email       = "";

$errorMessage = "";


// ==========================================================
// HANDLE GET — load teacher
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        header("Location: index.php");
        exit;
    }


    $stmt = $conn->prepare("
        SELECT *
        FROM teacher
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $teacher = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$teacher) {
        header("Location: index.php");
        exit;
    }


    $first_name  = $teacher['first_name'];
    $middle_name = $teacher['middle_name'];
    $last_name   = $teacher['last_name'];
    $email       = $teacher['email'];
}


// ==========================================================
// HANDLE POST — update teacher
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id          = (int) ($_POST['id'] ?? 0);
    $first_name  = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');


    // ----------------------------------------------
    // Validation
    // ----------------------------------------------

    if (
        $id <= 0 ||
        empty($first_name) ||
        empty($middle_name) ||
        empty($last_name) ||
        empty($email)
    ) {

        $errorMessage = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } else {

        // ----------------------------------------------
        // Check teacher exists
        // ----------------------------------------------

        $stmt = $conn->prepare("
            SELECT user_id
            FROM teacher
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $teacher = $stmt->get_result()->fetch_assoc();

        $stmt->close();


        if (!$teacher) {

            $errorMessage = "Teacher not found.";

        } else {

            $user_id = (int) $teacher['user_id'];


            // ----------------------------------------------
            // Check duplicate email (excluding this user)
            // ----------------------------------------------

            $check = $conn->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
                LIMIT 1
            ");

            $check->bind_param("si", $email, $user_id);
            $check->execute();
            $check->store_result();


            if ($check->num_rows > 0) {

                $check->close();

                $errorMessage =
                    "Another user is already using this email.";

            } else {

                $check->close();


                // ----------------------------------------------
                // Update (with transaction)
                // ----------------------------------------------

                try {

                    $conn->begin_transaction();


                    // Update teacher
                    $stmt = $conn->prepare("
                        UPDATE teacher
                        SET
                            first_name = ?,
                            middle_name = ?,
                            last_name = ?,
                            email = ?
                        WHERE id = ?
                    ");

                    $stmt->bind_param(
                        "ssssi",
                        $first_name,
                        $middle_name,
                        $last_name,
                        $email,
                        $id
                    );

                    $stmt->execute();
                    $stmt->close();


                    // Update users
                    $stmt = $conn->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            middle_name = ?,
                            last_name = ?,
                            email = ?
                        WHERE id = ?
                    ");

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


                    $conn->commit();


                    $_SESSION['status']  = "success";
                    $_SESSION['message'] =
                        "Teacher updated successfully.";

                    header("Location: index.php");
                    exit;


                } catch (Exception $e) {

                    $conn->rollback();

                    $errorMessage =
                        "Failed to update teacher. Please try again.";
                }
            }
        }
    }
}


// ==========================================================
// PAGE
// ==========================================================

$pageTitle = "Edit Teacher";

include '../layout/header.php';

?>


<div>

    <div class="card shadow-sm">

        <div class="card-body p-4">

            <h2 class="fw-bold fs-4 mb-3">
                Edit Teacher
            </h2>


            <?php if (!empty($errorMessage)): ?>

                <div
                    class="alert alert-warning alert-dismissible fade show"
                    role="alert">

                    <strong><?= e($errorMessage) ?></strong>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close">
                    </button>

                </div>

            <?php endif; ?>


            <form action="edit-teacher.php" method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $id ?>">


                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label small">
                            First Name
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            class="form-control"
                            value="<?= e($first_name) ?>"
                            required>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label small">
                            Middle Name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            class="form-control"
                            value="<?= e($middle_name) ?>"
                            required>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label small">
                            Last Name
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            class="form-control"
                            value="<?= e($last_name) ?>"
                            required>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label small">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="name@example.com"
                            value="<?= e($email) ?>"
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