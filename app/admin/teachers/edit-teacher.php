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


<style>
    /* Small mobile improvements */
    @media (max-width: 575.98px) {

        .teacher-form-card {
            border-radius: 0.75rem;
        }

        .teacher-header {
            align-items: flex-start !important;
        }

        .teacher-header .btn {
            white-space: nowrap;
        }

        .teacher-actions {
            flex-direction: column;
        }

        .teacher-actions .btn {
            width: 100%;
        }

    }
</style>


<div>

    <!-- Header -->
    <div class="teacher-header d-flex align-items-center
                justify-content-between gap-2 mb-3">

        <div class="min-w-0">

            <h2 class="fw-bold fs-4 mb-0">
                Edit Teacher
            </h2>

            <small class="text-muted">
                Update teacher account information
            </small>

        </div>


        <a href="<?= $baseUrl ?>teachers/"
           class="btn btn-sm btn-light border">

            <i class="bi bi-arrow-left me-1"></i>

            <span class="d-none d-sm-inline">
                Back
            </span>

        </a>

    </div>


    <!-- Error -->
    <?php if (!empty($errorMessage)): ?>

        <div class="alert alert-danger alert-dismissible fade show
                    py-2 px-3 small mb-3"
             role="alert">

            <i class="bi bi-exclamation-circle me-1"></i>

            <?= e($errorMessage) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
            </button>

        </div>

    <?php endif; ?>


    <!-- Form Card -->
    <div class="card teacher-form-card border-0 shadow-sm">

        <div class="card-body p-3 p-md-4">

            <form action="edit-teacher.php"
                  method="POST"
                  autocomplete="off">

                <!-- Teacher ID -->
                <input type="hidden"
                       name="id"
                       value="<?= (int) $id ?>">


                <!-- Fields -->
                <div class="row g-3">

                    <!-- First Name -->
                    <div class="col-12 col-sm-6 col-lg-3">

                        <label class="form-label small mb-1">

                            First Name

                            <span class="text-danger">*</span>

                        </label>

                        <input type="text"
                               name="first_name"
                               class="form-control"
                               placeholder="First name"
                               value="<?= e($first_name) ?>"
                               autocomplete="given-name"
                               required>

                    </div>


                    <!-- Middle Name -->
                    <div class="col-12 col-sm-6 col-lg-3">

                        <label class="form-label small mb-1">

                            Middle Name

                            <span class="text-danger">*</span>

                        </label>

                        <input type="text"
                               name="middle_name"
                               class="form-control"
                               placeholder="Middle name"
                               value="<?= e($middle_name) ?>"
                               autocomplete="additional-name"
                               required>

                    </div>


                    <!-- Last Name -->
                    <div class="col-12 col-sm-6 col-lg-3">

                        <label class="form-label small mb-1">

                            Last Name

                            <span class="text-danger">*</span>

                        </label>

                        <input type="text"
                               name="last_name"
                               class="form-control"
                               placeholder="Last name"
                               value="<?= e($last_name) ?>"
                               autocomplete="family-name"
                               required>

                    </div>


                    <!-- Email -->
                    <div class="col-12 col-sm-6 col-lg-3">

                        <label class="form-label small mb-1">

                            Email

                            <span class="text-danger">*</span>

                        </label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="name@example.com"
                               value="<?= e($email) ?>"
                               autocomplete="email"
                               required>

                    </div>

                </div>


                <!-- Footer -->
                <div class="border-top mt-4 pt-3">

                    <div class="d-flex flex-column flex-sm-row
                                align-items-sm-center
                                justify-content-end gap-3">

                        <div class="teacher-actions d-flex gap-2">

                            <a href="<?= $baseUrl ?>teachers/"
                               class="btn btn-light border">

                                Cancel

                            </a>


                            <button type="submit"
                                    class="btn btn-primary">


                                Update Teacher

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


<?php include '../layout/footer.php'; ?>
