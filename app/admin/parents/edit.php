<?php

$pageTitle = "Edit Parent";

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// HELPER
// ==========================================================

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ==========================================================
// GET PARENT ID
// ==========================================================

$parentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$parentId && isset($_POST['parent_id'])) {
    $parentId = filter_var(
        $_POST['parent_id'],
        FILTER_VALIDATE_INT
    );
}

$parent = null;
$errors = [];

// ==========================================================
// CSRF TOKEN
// ==========================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================================
// GET PARENT INFORMATION
// ==========================================================

function getParent($conn, $parentId)
{
    $stmt = $conn->prepare("
        SELECT
            p.id AS parent_id,
            p.user_id,
            p.enrollment_id,

            u.first_name AS account_first_name,
            u.middle_name AS account_middle_name,
            u.last_name AS account_last_name,
            u.email AS account_email,

            en.c_first_name,
            en.c_middle_name,
            en.c_last_name,
            en.c_gender,
            en.c_birthdate,
            en.c_address,
            en.c_place_of_birth,

            en.f_first_name,
            en.f_middle_name,
            en.f_last_name,
            en.f_email,

            en.m_first_name,
            en.m_middle_name,
            en.m_last_name,
            en.m_email,

            en.g_first_name,
            en.g_middle_name,
            en.g_last_name,
            en.g_email,

            en.status AS enrollment_status,
            en.academic_id,
            ay.academic_year

        FROM `parent` p

        INNER JOIN users u
            ON u.id = p.user_id

        INNER JOIN enrollment en
            ON en.id = p.enrollment_id

        LEFT JOIN academicyear ay
            ON ay.id = en.academic_id

        WHERE p.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $parentId);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

if ($parentId && $parentId > 0) {
    $parent = getParent($conn, $parentId);
}

// ==========================================================
// DEFAULT FORM VALUES
// ==========================================================

$form = $parent ?: [];

$fields = [
    'account_first_name',
    'account_middle_name',
    'account_last_name',
    'account_email',

    'c_first_name',
    'c_middle_name',
    'c_last_name',
    'c_gender',
    'c_birthdate',
    'c_address',
    'c_place_of_birth',

    'f_first_name',
    'f_middle_name',
    'f_last_name',
    'f_email',

    'm_first_name',
    'm_middle_name',
    'm_last_name',
    'm_email',

    'g_first_name',
    'g_middle_name',
    'g_last_name',
    'g_email'
];

foreach ($fields as $field) {
    $form[$field] = $form[$field] ?? '';
}

// ==========================================================
// PROCESS FORM SUBMISSION
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $parent) {

    // ------------------------------------------------------
    // CSRF VALIDATION
    // ------------------------------------------------------

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = "Invalid form request. Please refresh the page and try again.";
    }

    // ------------------------------------------------------
    // COLLECT INPUTS
    // ------------------------------------------------------

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';

        $form[$field] = is_string($value)
            ? trim($value)
            : '';
    }

    // ------------------------------------------------------
    // VALIDATE PARENT ACCOUNT
    // ------------------------------------------------------

    if ($form['account_first_name'] === '') {
        $errors[] = "Parent first name is required.";
    }

    if ($form['account_last_name'] === '') {
        $errors[] = "Parent last name is required.";
    }

    if (
        $form['account_email'] === '' ||
        !filter_var($form['account_email'], FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = "Please enter a valid parent email address.";
    }

    // ------------------------------------------------------
    // VALIDATE CHILD INFORMATION
    // ------------------------------------------------------

    if ($form['c_first_name'] === '') {
        $errors[] = "Child first name is required.";
    }

    if ($form['c_last_name'] === '') {
        $errors[] = "Child last name is required.";
    }

    if (!in_array($form['c_gender'], ['male', 'female'], true)) {
        $errors[] = "Please select a valid child gender.";
    }

    if ($form['c_birthdate'] !== '') {
        $birthTimestamp = strtotime($form['c_birthdate']);

        if (
            $birthTimestamp === false ||
            date('Y-m-d', $birthTimestamp) !== $form['c_birthdate']
        ) {
            $errors[] = "Please enter a valid child birthdate.";
        }
    }

    // ------------------------------------------------------
    // CHECK DUPLICATE EMAIL
    // ------------------------------------------------------

    if (
        empty($errors) &&
        filter_var($form['account_email'], FILTER_VALIDATE_EMAIL)
    ) {
        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
              AND id != ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "si",
            $form['account_email'],
            $parent['user_id']
        );

        $stmt->execute();
        $duplicate = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($duplicate) {
            $errors[] = "This email address is already being used by another account.";
        }
    }

    // ------------------------------------------------------
    // UPDATE DATABASE
    // ------------------------------------------------------

    if (empty($errors)) {

        try {
            $conn->begin_transaction();

            // Update the parent account in users.
            $stmt = $conn->prepare("
                UPDATE users
                SET
                    first_name = ?,
                    middle_name = ?,
                    last_name = ?,
                    email = ?
                WHERE id = ?
                  AND role = 'parent'
            ");

            $stmt->bind_param(
                "ssssi",
                $form['account_first_name'],
                $form['account_middle_name'],
                $form['account_last_name'],
                $form['account_email'],
                $parent['user_id']
            );

            $stmt->execute();
            $stmt->close();

            // Update child and family information in enrollment.
            $stmt = $conn->prepare("
                UPDATE enrollment
                SET
                    c_first_name = ?,
                    c_middle_name = ?,
                    c_last_name = ?,
                    c_gender = ?,
                    c_birthdate = NULLIF(?, ''),
                    c_address = ?,
                    c_place_of_birth = ?,

                    f_first_name = ?,
                    f_middle_name = ?,
                    f_last_name = ?,
                    f_email = ?,

                    m_first_name = ?,
                    m_middle_name = ?,
                    m_last_name = ?,
                    m_email = ?,

                    g_first_name = ?,
                    g_middle_name = ?,
                    g_last_name = ?,
                    g_email = ?

                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssssssssssssssssssi",
                $form['c_first_name'],
                $form['c_middle_name'],
                $form['c_last_name'],
                $form['c_gender'],
                $form['c_birthdate'],
                $form['c_address'],
                $form['c_place_of_birth'],

                $form['f_first_name'],
                $form['f_middle_name'],
                $form['f_last_name'],
                $form['f_email'],

                $form['m_first_name'],
                $form['m_middle_name'],
                $form['m_last_name'],
                $form['m_email'],

                $form['g_first_name'],
                $form['g_middle_name'],
                $form['g_last_name'],
                $form['g_email'],

                $parent['enrollment_id']
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'Parent and family information updated successfully.';

            header("Location: view.php?id=" . (int) $parentId);
            exit;

        } catch (Throwable $exception) {

            $conn->rollback();

            error_log("Parent update error: " . $exception->getMessage());

            $errors[] = "Unable to update the parent information. Please check the submitted data and try again.";
        }
    }
}

// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<section class="parents-page">

    <!-- PAGE HEADER -->

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">

        <div>
            <h2 class="fw-bold fs-4 mb-1">Edit Parent</h2>

            <p class="text-muted small mb-0">
                Update parent account and family information
            </p>
        </div>

        <a
            href="<?= e($baseUrl) ?>parents/"
            class="btn btn-light border btn-sm flex-shrink-0">

            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>

        </a>

    </div>

    <?php if (!$parent): ?>

        <!-- PARENT NOT FOUND -->

        <div class="card border rounded-3">

            <div class="card-body text-center py-5">

                <i class="bi bi-person-exclamation fs-1 text-muted"></i>

                <h5 class="fw-bold mt-3">Parent Not Found</h5>

                <p class="text-muted small mb-3">
                    The parent record does not exist or the ID is invalid.
                </p>

                <a
                    href="<?= e($baseUrl) ?>parents/"
                    class="btn btn-primary btn-sm">

                    Return to Parents

                </a>

            </div>

        </div>

    <?php else: ?>

        <!-- VALIDATION ERRORS -->

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger alert-dismissible fade show" role="alert">

                <div class="fw-semibold mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Please check the following:
                </div>

                <ul class="mb-0 ps-3">

                    <?php foreach ($errors as $error): ?>

                        <li><?= e($error) ?></li>

                    <?php endforeach; ?>

                </ul>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>

            </div>

        <?php endif; ?>


        <!-- PROFILE SUMMARY -->

        <div class="card border rounded-3 mb-4">

            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="rounded-3 bg-paper-soft text-primary d-flex
                               align-items-center justify-content-center
                               fw-bold fs-3 flex-shrink-0"
                        style="width: 60px; height: 60px;">

                        <?= e(strtoupper(substr(
                            $form['account_first_name'] ?: '?',
                            0,
                            1
                        ))) ?>

                    </div>

                    <div class="flex-grow-1 min-w-0">

                        <span class="text-muted small">
                            EDITING PARENT ACCOUNT
                        </span>

                        <h4 class="fw-bold fs-5 mb-1 mt-1">
                            <?= e(trim(
                                $form['account_first_name'] . ' ' .
                                $form['account_middle_name'] . ' ' .
                                $form['account_last_name']
                            )) ?>
                        </h4>

                        <div class="text-muted small text-break">
                            <i class="bi bi-envelope me-1"></i>
                            <?= e($form['account_email']) ?>
                        </div>

                    </div>

                    <span class="badge bg-paper-soft text-secondary border">
                        Parent #<?= (int) $parent['parent_id'] ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- EDIT FORM -->

        <form method="POST" action="edit.php?id=<?= (int) $parentId ?>">

            <input
                type="hidden"
                name="parent_id"
                value="<?= (int) $parentId ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>">


            <div class="row g-3">

                <!-- PARENT ACCOUNT -->

                <div class="col-12">

                    <div class="card border rounded-3">

                        <div class="card-header bg-paper border-bottom p-3">

                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-person-vcard text-primary me-2"></i>
                                Parent Account Information
                            </h6>

                        </div>

                        <div class="card-body p-4">

                            <div class="row g-3">

                                <div class="col-12 col-md-4">

                                    <label for="account_first_name" class="form-label small fw-semibold">
                                        First Name <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="account_first_name"
                                        name="account_first_name"
                                        value="<?= e($form['account_first_name']) ?>"
                                        maxlength="100"
                                        required>

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="account_middle_name" class="form-label small fw-semibold">
                                        Middle Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="account_middle_name"
                                        name="account_middle_name"
                                        value="<?= e($form['account_middle_name']) ?>"
                                        maxlength="100">

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="account_last_name" class="form-label small fw-semibold">
                                        Last Name <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="account_last_name"
                                        name="account_last_name"
                                        value="<?= e($form['account_last_name']) ?>"
                                        maxlength="100"
                                        required>

                                </div>

                                <div class="col-12 col-md-6">

                                    <label for="account_email" class="form-label small fw-semibold">
                                        Email Address <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="account_email"
                                        name="account_email"
                                        value="<?= e($form['account_email']) ?>"
                                        maxlength="255"
                                        required>

                                    <div class="form-text">
                                        This is the parent's login email.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CHILD INFORMATION -->

                <div class="col-12">

                    <div class="card border rounded-3">

                        <div class="card-header bg-paper border-bottom p-3">

                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-person-hearts text-primary me-2"></i>
                                Child Information
                            </h6>

                        </div>

                        <div class="card-body p-4">

                            <div class="row g-3">

                                <div class="col-12 col-md-4">

                                    <label for="c_first_name" class="form-label small fw-semibold">
                                        First Name <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="c_first_name"
                                        name="c_first_name"
                                        value="<?= e($form['c_first_name']) ?>"
                                        maxlength="100"
                                        required>

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="c_middle_name" class="form-label small fw-semibold">
                                        Middle Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="c_middle_name"
                                        name="c_middle_name"
                                        value="<?= e($form['c_middle_name']) ?>"
                                        maxlength="100">

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="c_last_name" class="form-label small fw-semibold">
                                        Last Name <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="c_last_name"
                                        name="c_last_name"
                                        value="<?= e($form['c_last_name']) ?>"
                                        maxlength="100"
                                        required>

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="c_gender" class="form-label small fw-semibold">
                                        Gender <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-select"
                                        id="c_gender"
                                        name="c_gender"
                                        required>

                                        <option value="">Select gender</option>

                                        <option
                                            value="male"
                                            <?= $form['c_gender'] === 'male' ? 'selected' : '' ?>>
                                            Male
                                        </option>

                                        <option
                                            value="female"
                                            <?= $form['c_gender'] === 'female' ? 'selected' : '' ?>>
                                            Female
                                        </option>

                                    </select>

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="c_birthdate" class="form-label small fw-semibold">
                                        Birthdate
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="c_birthdate"
                                        name="c_birthdate"
                                        value="<?= e(
                                            $form['c_birthdate'] !== ''
                                                ? substr($form['c_birthdate'], 0, 10)
                                                : ''
                                        ) ?>">

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="c_place_of_birth" class="form-label small fw-semibold">
                                        Place of Birth
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="c_place_of_birth"
                                        name="c_place_of_birth"
                                        value="<?= e($form['c_place_of_birth']) ?>"
                                        maxlength="255">

                                </div>

                                <div class="col-12">

                                    <label for="c_address" class="form-label small fw-semibold">
                                        Complete Address
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="c_address"
                                        name="c_address"
                                        rows="3"
                                        maxlength="1000"><?= e($form['c_address']) ?></textarea>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- FATHER INFORMATION -->

                <div class="col-12 col-lg-6">

                    <div class="card border rounded-3 h-100">

                        <div class="card-header bg-paper border-bottom p-3">

                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-person text-primary me-2"></i>
                                Father's Information
                            </h6>

                        </div>

                        <div class="card-body p-4">

                            <div class="mb-3">

                                <label for="f_first_name" class="form-label small fw-semibold">
                                    First Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="f_first_name"
                                    name="f_first_name"
                                    value="<?= e($form['f_first_name']) ?>"
                                    maxlength="100">

                            </div>

                            <div class="mb-3">

                                <label for="f_middle_name" class="form-label small fw-semibold">
                                    Middle Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="f_middle_name"
                                    name="f_middle_name"
                                    value="<?= e($form['f_middle_name']) ?>"
                                    maxlength="100">

                            </div>

                            <div class="mb-3">

                                <label for="f_last_name" class="form-label small fw-semibold">
                                    Last Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="f_last_name"
                                    name="f_last_name"
                                    value="<?= e($form['f_last_name']) ?>"
                                    maxlength="100">

                            </div>

                            <div>

                                <label for="f_email" class="form-label small fw-semibold">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    class="form-control"
                                    id="f_email"
                                    name="f_email"
                                    value="<?= e($form['f_email']) ?>"
                                    maxlength="255">

                            </div>

                        </div>

                    </div>

                </div>


                <!-- MOTHER INFORMATION -->

                <div class="col-12 col-lg-6">

                    <div class="card border rounded-3 h-100">

                        <div class="card-header bg-paper border-bottom p-3">

                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-person text-primary me-2"></i>
                                Mother's Information
                            </h6>

                        </div>

                        <div class="card-body p-4">

                            <div class="mb-3">

                                <label for="m_first_name" class="form-label small fw-semibold">
                                    First Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="m_first_name"
                                    name="m_first_name"
                                    value="<?= e($form['m_first_name']) ?>"
                                    maxlength="100">

                            </div>

                            <div class="mb-3">

                                <label for="m_middle_name" class="form-label small fw-semibold">
                                    Middle Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="m_middle_name"
                                    name="m_middle_name"
                                    value="<?= e($form['m_middle_name']) ?>"
                                    maxlength="100">

                            </div>

                            <div class="mb-3">

                                <label for="m_last_name" class="form-label small fw-semibold">
                                    Last Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="m_last_name"
                                    name="m_last_name"
                                    value="<?= e($form['m_last_name']) ?>"
                                    maxlength="100">

                            </div>

                            <div>

                                <label for="m_email" class="form-label small fw-semibold">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    class="form-control"
                                    id="m_email"
                                    name="m_email"
                                    value="<?= e($form['m_email']) ?>"
                                    maxlength="255">

                            </div>

                        </div>

                    </div>

                </div>


                <!-- GUARDIAN INFORMATION -->

                <div class="col-12">

                    <div class="card border rounded-3">

                        <div class="card-header bg-paper border-bottom p-3">

                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-person-badge text-primary me-2"></i>
                                Guardian Information
                            </h6>

                        </div>

                        <div class="card-body p-4">

                            <div class="row g-3">

                                <div class="col-12 col-md-4">

                                    <label for="g_first_name" class="form-label small fw-semibold">
                                        First Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="g_first_name"
                                        name="g_first_name"
                                        value="<?= e($form['g_first_name']) ?>"
                                        maxlength="100">

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="g_middle_name" class="form-label small fw-semibold">
                                        Middle Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="g_middle_name"
                                        name="g_middle_name"
                                        value="<?= e($form['g_middle_name']) ?>"
                                        maxlength="100">

                                </div>

                                <div class="col-12 col-md-4">

                                    <label for="g_last_name" class="form-label small fw-semibold">
                                        Last Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="g_last_name"
                                        name="g_last_name"
                                        value="<?= e($form['g_last_name']) ?>"
                                        maxlength="100">

                                </div>

                                <div class="col-12 col-md-6">

                                    <label for="g_email" class="form-label small fw-semibold">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="g_email"
                                        name="g_email"
                                        value="<?= e($form['g_email']) ?>"
                                        maxlength="255">

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ENROLLMENT REFERENCE -->

                <div class="col-12">

                    <div class="card border rounded-3">

                        <div class="card-header bg-paper border-bottom p-3">

                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-journal-check text-primary me-2"></i>
                                Enrollment Reference
                            </h6>

                        </div>

                        <div class="card-body p-4">

                            <div class="row g-3">

                                <div class="col-12 col-md-6">

                                    <label class="text-muted small d-block mb-1">
                                        Enrollment ID
                                    </label>

                                    <div class="fw-semibold">
                                        #<?= (int) $parent['enrollment_id'] ?>
                                    </div>

                                </div>

                                <div class="col-12 col-md-6">

                                    <label class="text-muted small d-block mb-1">
                                        Academic Year
                                    </label>

                                    <div class="fw-semibold">
                                        <?= e($parent['academic_year'] ?: 'Not assigned') ?>
                                    </div>

                                </div>

                            </div>

                            <p class="text-muted small mt-3 mb-0">
                                Enrollment status, academic year, and uploaded
                                enrollment documents are not changed by this form.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- FORM ACTIONS -->

                <div class="col-12">

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">

                        <a
                            href="view.php?id=<?= (int) $parentId ?>"
                            class="btn btn-light border">

                            <i class="bi bi-x-lg me-1"></i>
                            Cancel

                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="bi bi-check2-circle me-1"></i>
                            Save Changes

                        </button>

                    </div>

                </div>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>