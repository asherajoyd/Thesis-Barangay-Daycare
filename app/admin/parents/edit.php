<?php

$pageTitle = "Edit Parent";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// GET IDS
// ==========================================================

$userId  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$childId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT);

if (!$userId && isset($_POST['user_id'])) {
    $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
}

if (!$childId && isset($_POST['child_id'])) {
    $childId = filter_var($_POST['child_id'], FILTER_VALIDATE_INT);
}


$parent = null;
$child = null;
$familyMembers = [];
$errors = [];


// ==========================================================
// CSRF TOKEN
// ==========================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// ==========================================================
// GET DATA
// ==========================================================

if ($userId && $userId > 0 && $childId && $childId > 0) {

    // User (parent account)
    $stmt = $conn->prepare("
        SELECT id, first_name, middle_name, last_name, email, contact, status
        FROM users
        WHERE id = ? AND role = 'parent'
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $parent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Child
    $stmt = $conn->prepare("
        SELECT *
        FROM children
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $childId, $userId);
    $stmt->execute();
    $child = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Family members
    if ($child) {
        $stmt = $conn->prepare("
            SELECT *
            FROM parent_info
            WHERE child_id = ?
            ORDER BY is_primary DESC, id ASC
        ");
        $stmt->bind_param("i", $childId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $familyMembers[$row['relationship']] = $row;
        }

        $stmt->close();
    }
}


// ==========================================================
// FORM VALUES
// ==========================================================

$form = [
    'account_first_name'  => $parent['first_name'] ?? '',
    'account_middle_name' => $parent['middle_name'] ?? '',
    'account_last_name'   => $parent['last_name'] ?? '',
    'account_email'       => $parent['email'] ?? '',
    'account_contact'     => $parent['contact'] ?? '',
    'account_status'      => $parent['status'] ?? 'active',

    'child_first_name'    => $child['first_name'] ?? '',
    'child_middle_name'   => $child['middle_name'] ?? '',
    'child_last_name'     => $child['last_name'] ?? '',
    'child_gender'        => $child['gender'] ?? '',
    'child_birthdate'     => $child['birthdate'] ?? '',
    'child_place_of_birth'=> $child['place_of_birth'] ?? '',
    'child_address'       => $child['address'] ?? '',

    'f_first_name'        => $familyMembers['father']['first_name'] ?? '',
    'f_middle_name'       => $familyMembers['father']['middle_name'] ?? '',
    'f_last_name'         => $familyMembers['father']['last_name'] ?? '',

    'm_first_name'        => $familyMembers['mother']['first_name'] ?? '',
    'm_middle_name'       => $familyMembers['mother']['middle_name'] ?? '',
    'm_last_name'         => $familyMembers['mother']['last_name'] ?? '',

    'g_first_name'        => $familyMembers['guardian']['first_name'] ?? '',
    'g_middle_name'       => $familyMembers['guardian']['middle_name'] ?? '',
    'g_last_name'         => $familyMembers['guardian']['last_name'] ?? '',
];


// ==========================================================
// PROCESS FORM
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $parent && $child) {

    // CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = "Invalid form request. Please refresh the page and try again.";
    }

    // Collect
    foreach (array_keys($form) as $field) {
        $value = $_POST[$field] ?? '';
        $form[$field] = is_string($value) ? trim($value) : '';
    }

    // Validate parent
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

    // Validate child
    if ($form['child_first_name'] === '') {
        $errors[] = "Child first name is required.";
    }
    if ($form['child_last_name'] === '') {
        $errors[] = "Child last name is required.";
    }
    if (!in_array($form['child_gender'], ['male', 'female'], true)) {
        $errors[] = "Please select a valid child gender.";
    }
    if ($form['child_birthdate'] !== '') {
        $ts = strtotime($form['child_birthdate']);
        if ($ts === false || date('Y-m-d', $ts) !== $form['child_birthdate']) {
            $errors[] = "Please enter a valid child birthdate.";
        }
    }

    // Duplicate email check
    if (
        empty($errors) &&
        filter_var($form['account_email'], FILTER_VALIDATE_EMAIL)
    ) {
        $stmt = $conn->prepare("
            SELECT id FROM users
            WHERE email = ? AND id != ?
            LIMIT 1
        ");
        $stmt->bind_param("si", $form['account_email'], $userId);
        $stmt->execute();
        $dup = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($dup) {
            $errors[] = "This email address is already being used by another account.";
        }
    }


    // ==========================================================
    // UPDATE
    // ==========================================================

    if (empty($errors)) {

        try {
            $conn->begin_transaction();

            // 1. users
            $stmt = $conn->prepare("
                UPDATE users
                SET first_name = ?, middle_name = ?, last_name = ?,
                    email = ?, contact = ?, status = ?
                WHERE id = ? AND role = 'parent'
            ");
            $stmt->bind_param(
                "ssssssi",
                $form['account_first_name'],
                $form['account_middle_name'],
                $form['account_last_name'],
                $form['account_email'],
                $form['account_contact'],
                $form['account_status'],
                $userId
            );
            $stmt->execute();
            $stmt->close();


            // 2. children
            $stmt = $conn->prepare("
                UPDATE children
                SET first_name = ?, middle_name = ?, last_name = ?,
                    gender = ?, birthdate = NULLIF(?, ''),
                    place_of_birth = ?, address = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param(
                "sssssssii",
                $form['child_first_name'],
                $form['child_middle_name'],
                $form['child_last_name'],
                $form['child_gender'],
                $form['child_birthdate'],
                $form['child_place_of_birth'],
                $form['child_address'],
                $childId,
                $userId
            );
            $stmt->execute();
            $stmt->close();


            // 3. parent_info (upsert)
            $relations = [
                'father'   => ['f_first_name', 'f_middle_name', 'f_last_name'],
                'mother'   => ['m_first_name', 'm_middle_name', 'm_last_name'],
                'guardian' => ['g_first_name', 'g_middle_name', 'g_last_name'],
            ];

            foreach ($relations as $relationship => $keys) {

                [$fn, $mn, $ln] = $keys;

                $firstName  = $form[$fn];
                $middleName = $form[$mn];
                $lastName   = $form[$ln];

                $existingId = $familyMembers[$relationship]['id'] ?? null;

                // Skip kung walang laman at walang existing
                if (
                    $firstName === '' &&
                    $middleName === '' &&
                    $lastName === '' &&
                    !$existingId
                ) {
                    continue;
                }

                if ($existingId) {

                    // UPDATE existing
                    $stmt = $conn->prepare("
                        UPDATE parent_info
                        SET first_name = ?, middle_name = ?, last_name = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param(
                        "sssi",
                        $firstName,
                        $middleName,
                        $lastName,
                        $existingId
                    );
                    $stmt->execute();
                    $stmt->close();

                } else {

                    // INSERT new
                    $isPrimary = ($relationship === 'guardian') ? 1 : 0;

                    $stmt = $conn->prepare("
                        INSERT INTO parent_info
                            (child_id, user_id, first_name, middle_name, last_name, relationship, is_primary)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param(
                        "iissssi",
                        $childId,
                        $userId,
                        $firstName,
                        $middleName,
                        $lastName,
                        $relationship,
                        $isPrimary
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();

            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'Parent and family information updated successfully.';

            header("Location: index.php");
            exit;

        } catch (Throwable $exception) {

            $conn->rollback();
            error_log("Parent update error: " . $exception->getMessage());
            $errors[] = "Unable to update the parent information. Please try again.";
        }
    }
}


include '../layout/header.php';

?>

<section class="parents-page">

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="fw-bold fs-4 mb-1">Edit Parent</h2>
            <p class="text-muted small mb-0">Update parent account and family information</p>
        </div>
        <a href="<?= e($baseUrl) ?>parents/" class="btn btn-light border btn-sm flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back</span>
        </a>
    </div>

    <?php if (!$parent || !$child): ?>

        <div class="card border rounded-3">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted"></i>
                <h5 class="fw-bold mt-3">Parent Not Found</h5>
                <p class="text-muted small mb-3">The parent record does not exist or the ID is invalid.</p>
                <a href="<?= e($baseUrl) ?>parents/" class="btn btn-primary btn-sm">Return to Parents</a>
            </div>
        </div>

    <?php else: ?>

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
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>


        <form method="POST" action="edit.php?id=<?= (int) $userId ?>&child_id=<?= (int) $childId ?>">

            <input type="hidden" name="user_id" value="<?= (int) $userId ?>">
            <input type="hidden" name="child_id" value="<?= (int) $childId ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

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
                                    <label class="form-label small fw-semibold">
                                        First Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="account_first_name"
                                           value="<?= e($form['account_first_name']) ?>" maxlength="100" required>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Middle Name</label>
                                    <input type="text" class="form-control" name="account_middle_name"
                                           value="<?= e($form['account_middle_name']) ?>" maxlength="100">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">
                                        Last Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="account_last_name"
                                           value="<?= e($form['account_last_name']) ?>" maxlength="100" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" name="account_email"
                                           value="<?= e($form['account_email']) ?>" maxlength="255" required>
                                    <div class="form-text">This is the parent's login email.</div>
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-semibold">Contact Number</label>
                                    <input type="text" class="form-control" name="account_contact"
                                           value="<?= e($form['account_contact']) ?>" maxlength="20">
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-semibold">Status</label>
                                    <select class="form-select" name="account_status">
                                        <option value="active" <?= $form['account_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $form['account_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
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
                                    <label class="form-label small fw-semibold">
                                        First Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="child_first_name"
                                           value="<?= e($form['child_first_name']) ?>" maxlength="100" required>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Middle Name</label>
                                    <input type="text" class="form-control" name="child_middle_name"
                                           value="<?= e($form['child_middle_name']) ?>" maxlength="100">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">
                                        Last Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="child_last_name"
                                           value="<?= e($form['child_last_name']) ?>" maxlength="100" required>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">
                                        Gender <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="child_gender" required>
                                        <option value="">Select gender</option>
                                        <option value="male" <?= $form['child_gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= $form['child_gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Birthdate</label>
                                    <input type="date" class="form-control" name="child_birthdate"
                                           value="<?= e($form['child_birthdate'] ? substr($form['child_birthdate'], 0, 10) : '') ?>">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Place of Birth</label>
                                    <input type="text" class="form-control" name="child_place_of_birth"
                                           value="<?= e($form['child_place_of_birth']) ?>" maxlength="255">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Complete Address</label>
                                    <textarea class="form-control" name="child_address"
                                              rows="3" maxlength="1000"><?= e($form['child_address']) ?></textarea>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                <!-- FATHER -->
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
                                <label class="form-label small fw-semibold">First Name</label>
                                <input type="text" class="form-control" name="f_first_name"
                                       value="<?= e($form['f_first_name']) ?>" maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Middle Name</label>
                                <input type="text" class="form-control" name="f_middle_name"
                                       value="<?= e($form['f_middle_name']) ?>" maxlength="100">
                            </div>

                            <div>
                                <label class="form-label small fw-semibold">Last Name</label>
                                <input type="text" class="form-control" name="f_last_name"
                                       value="<?= e($form['f_last_name']) ?>" maxlength="100">
                            </div>

                        </div>
                    </div>
                </div>


                <!-- MOTHER -->
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
                                <label class="form-label small fw-semibold">First Name</label>
                                <input type="text" class="form-control" name="m_first_name"
                                       value="<?= e($form['m_first_name']) ?>" maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Middle Name</label>
                                <input type="text" class="form-control" name="m_middle_name"
                                       value="<?= e($form['m_middle_name']) ?>" maxlength="100">
                            </div>

                            <div>
                                <label class="form-label small fw-semibold">Last Name</label>
                                <input type="text" class="form-control" name="m_last_name"
                                       value="<?= e($form['m_last_name']) ?>" maxlength="100">
                            </div>

                        </div>
                    </div>
                </div>


                <!-- GUARDIAN -->
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
                                    <label class="form-label small fw-semibold">First Name</label>
                                    <input type="text" class="form-control" name="g_first_name"
                                           value="<?= e($form['g_first_name']) ?>" maxlength="100">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Middle Name</label>
                                    <input type="text" class="form-control" name="g_middle_name"
                                           value="<?= e($form['g_middle_name']) ?>" maxlength="100">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold">Last Name</label>
                                    <input type="text" class="form-control" name="g_last_name"
                                           value="<?= e($form['g_last_name']) ?>" maxlength="100">
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                <!-- ACTIONS -->
                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php include '../layout/footer.php'; ?>