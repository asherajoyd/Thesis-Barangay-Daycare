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

$first_name  = "";
$middle_name = "";
$last_name   = "";
$email       = "";

$errorMessage   = "";
$successMessage = "";


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name  = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');


    // ----------------------------------------------
    // Validation
    // ----------------------------------------------

    if (
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
        // Check duplicate email in users
        // ----------------------------------------------

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();


        if ($check->num_rows > 0) {

            $check->close();

            $errorMessage = "A user with this email already exists.";

        } else {

            $check->close();


            // ----------------------------------------------
            // Get active academic year
            // ----------------------------------------------

            $academic_id = null;

            $academicQuery = $conn->query("
                SELECT id
                FROM academicyear
                WHERE status = 1
                ORDER BY id DESC
                LIMIT 1
            ");

            if ($academicQuery && $academicQuery->num_rows > 0) {

                $academic_id = (int) $academicQuery
                    ->fetch_assoc()['id'];
            }


            if (!$academic_id) {

                $errorMessage =
                    "No active academic year. Please set one in Settings first.";

            } else {

                // ----------------------------------------------
                // Insert (with transaction)
                // ----------------------------------------------

                $default_password = password_hash("123", PASSWORD_DEFAULT);
                $role = "teacher";


                try {

                    $conn->begin_transaction();


                    // Insert into users
                    $stmt_user = $conn->prepare("
                        INSERT INTO users
                        (
                            first_name,
                            middle_name,
                            last_name,
                            email,
                            password,
                            role
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt_user->bind_param(
                        "ssssss",
                        $first_name,
                        $middle_name,
                        $last_name,
                        $email,
                        $default_password,
                        $role
                    );

                    $stmt_user->execute();

                    $user_id = $stmt_user->insert_id;

                    $stmt_user->close();


                    // Insert into teacher
                    $stmt_teacher = $conn->prepare("
                        INSERT INTO teacher
                        (
                            first_name,
                            middle_name,
                            last_name,
                            email,
                            user_id,
                            academic_id
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt_teacher->bind_param(
                        "ssssii",
                        $first_name,
                        $middle_name,
                        $last_name,
                        $email,
                        $user_id,
                        $academic_id
                    );

                    $stmt_teacher->execute();

                    $stmt_teacher->close();


                    $conn->commit();


                    $_SESSION['status']  = "success";
                    $_SESSION['message'] =
                        "Teacher added successfully. Default password: 123";

                    header("Location: index.php");
                    exit;


                } catch (Exception $e) {

                    $conn->rollback();

                    $errorMessage =
                        "Failed to add teacher. Please try again.";
                }
            }
        }
    }
}


// ==========================================================
// PAGE
// ==========================================================

$pageTitle = "Add Teacher";

include '../layout/header.php';

?>


<div>

    <div class="card shadow-sm">

        <div class="card-body p-4">

            <h2 class="fw-bold fs-4 mb-3">
                Add Teacher
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


            <form action="create-teacher.php" method="POST">

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
                            Save
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