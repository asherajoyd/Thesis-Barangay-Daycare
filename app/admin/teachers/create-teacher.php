<?php

require_once '../guard.php';
include "../../../api/conn.php";


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
$contact     = "";

$errorMessage   = "";


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name  = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');

    // Validation
    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email)
    ) {

        $errorMessage = "First name, last name, and email are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } else {

        // Check duplicate email
        $check = $conn->prepare("
            SELECT id FROM users
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

            // Get active academic year
            $academic_id = null;

            $academicQuery = $conn->query("
                SELECT id FROM academic_year
                WHERE status = 'active'
                ORDER BY id DESC
                LIMIT 1
            ");

            if ($academicQuery && $academicQuery->num_rows > 0) {
                $academic_id = (int) $academicQuery->fetch_assoc()['id'];
            }

            $default_password = password_hash("123", PASSWORD_DEFAULT);
            $role = "teacher";
            $userStatus = "active";

            try {

                $conn->begin_transaction();

                // 1. Insert user
                $stmt_user = $conn->prepare("
                    INSERT INTO users
                    (first_name, middle_name, last_name, email, password, contact, role, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt_user->bind_param(
                    "ssssssss",
                    $first_name,
                    $middle_name,
                    $last_name,
                    $email,
                    $default_password,
                    $contact,
                    $role,
                    $userStatus
                );

                $stmt_user->execute();
                $user_id = $stmt_user->insert_id;
                $stmt_user->close();

                // 2. Insert teacher
                $stmt_teacher = $conn->prepare("
                    INSERT INTO teacher
                    (first_name, middle_name, last_name, email, contact, user_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt_teacher->bind_param(
                    "sssssi",
                    $first_name,
                    $middle_name,
                    $last_name,
                    $email,
                    $contact,
                    $user_id
                );

                $stmt_teacher->execute();
                $teacher_id = $stmt_teacher->insert_id;
                $stmt_teacher->close();

                // 3. Insert teacher_assignment (kung may active academic year)
                if ($academic_id) {

                    $stmt_assign = $conn->prepare("
                        INSERT INTO teacher_assignment
                        (teacher_id, academic_id)
                        VALUES (?, ?)
                    ");

                    $stmt_assign->bind_param(
                        "ii",
                        $teacher_id,
                        $academic_id
                    );

                    $stmt_assign->execute();
                    $stmt_assign->close();
                }

                $conn->commit();

                $_SESSION['status'] = "success";
                $_SESSION['message'] =
                    "Teacher added successfully. Default password: 123";

                header("Location: index.php");
                exit;

            } catch (Exception $e) {

                $conn->rollback();
                $errorMessage = "Failed to add teacher. Please try again.";
            }
        }
    }
}


$pageTitle = "Add Teacher";

include '../layout/header.php';

?>


<style>
    @media (max-width: 575.98px) {
        .teacher-form-card { border-radius: 0.75rem; }
        .teacher-header { align-items: flex-start !important; }
        .teacher-header .btn { white-space: nowrap; }
        .teacher-actions { flex-direction: column; }
        .teacher-actions .btn { width: 100%; }
    }
</style>


<div>

    <!-- Header -->
    <div class="teacher-header d-flex align-items-center justify-content-between gap-2 mb-3">

        <div class="min-w-0">
            <h2 class="fw-bold fs-4 mb-0">Add Teacher</h2>
            <small class="text-muted">Create a new teacher account</small>
        </div>

        <a href="<?= $baseUrl ?>teachers/" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>

    </div>


    <!-- Error -->
    <?php if (!empty($errorMessage)): ?>

        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= e($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php endif; ?>


    <!-- Form Card -->
    <div class="card teacher-form-card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">

            <form action="create-teacher.php" method="POST" autocomplete="off">

                <div class="row g-3">

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control"
                               placeholder="First name" value="<?= e($first_name) ?>"
                               autocomplete="given-name" required>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small mb-1">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control"
                               placeholder="Middle name" value="<?= e($middle_name) ?>"
                               autocomplete="additional-name">
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control"
                               placeholder="Last name" value="<?= e($last_name) ?>"
                               autocomplete="family-name" required>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small mb-1">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               placeholder="name@example.com" value="<?= e($email) ?>"
                               autocomplete="email" required>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small mb-1">Contact Number</label>
                        <input type="text" name="contact" class="form-control"
                               placeholder="09XX XXX XXXX" value="<?= e($contact) ?>"
                               maxlength="20">
                    </div>

                </div>


                <!-- Footer -->
                <div class="border-top mt-4 pt-3">

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">

                        <small class="text-muted fw-semibold">
                            <i class="bi bi-info-circle me-1"></i>
                            Default password: <code class="fw-bold">123</code>
                        </small>

                        <div class="teacher-actions d-flex gap-2">

                            <a href="<?= $baseUrl ?>teachers/" class="btn btn-light border">Cancel</a>

                            <button type="submit" class="btn btn-primary">Save Teacher</button>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    </div>

</div>


<?php include '../layout/footer.php'; ?>