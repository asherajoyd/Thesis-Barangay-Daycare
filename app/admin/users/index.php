<?php

$pageTitle = "Users";

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// HELPER
// ==========================================================

function e($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// ==========================================================
// CSRF TOKEN
// ==========================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================================
// GET ROLE FILTER
// ==========================================================

$allowedRoles = ['parent', 'teacher'];

$filterRole = strtolower(trim($_GET['role'] ?? ''));

if (!in_array($filterRole, $allowedRoles, true)) {
    $filterRole = '';
}

// ==========================================================
// GET SEARCH QUERY
// ==========================================================

$search = trim($_GET['search'] ?? '');

// ==========================================================
// GET USERS
// ==========================================================

$users = [];

$sql = "
    SELECT
        id,
        first_name,
        middle_name,
        last_name,
        email,
        role
    FROM users
    WHERE role IN ('parent', 'teacher')
";

$params = [];
$types = '';

if ($filterRole !== '') {
    $sql .= " AND role = ?";
    $types .= 's';
    $params[] = $filterRole;
}

if ($search !== '') {
    $sql .= "
        AND (
            first_name LIKE ?
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR email LIKE ?
            OR CAST(id AS CHAR) LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $types .= 'sssss';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

$sql .= "
    ORDER BY
        CASE role
            WHEN 'parent' THEN 1
            WHEN 'teacher' THEN 2
            ELSE 3
        END,
        id DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();

// ==========================================================
// GET ROLE COUNTS
// ==========================================================

$roleCounts = [
    'parent' => 0,
    'teacher' => 0
];

$result = $conn->query("
    SELECT role, COUNT(*) AS total
    FROM users
    WHERE role IN ('parent', 'teacher')
    GROUP BY role
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roleCounts[$row['role']] = (int) $row['total'];
    }
}

// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<div class="users-page">

    <!-- ======================================================
         ALERTS
         ====================================================== -->

    <?php if (!empty($_SESSION['message'])): ?>

        <div
            class="alert alert-<?= ($_SESSION['status'] ?? '') === 'success'
                ? 'success'
                : 'danger' ?> alert-dismissible fade show fw-semibold"
            role="alert">

            <?= e($_SESSION['message']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close">
            </button>

        </div>

        <?php
        unset($_SESSION['status'], $_SESSION['message']);
        ?>

    <?php endif; ?>


    <!-- ======================================================
         PAGE HEADER
         ====================================================== -->

    <div class="d-flex align-items-center justify-content-between mb-3">

        <div>
            <h2 class="fw-bold fs-4 mb-1">
                <?= e($pageTitle) ?>
            </h2>

            <p class="text-muted small mb-0">
                Manage and view parent and teacher accounts
            </p>
        </div>

    </div>


    <!-- ======================================================
         SUMMARY CARDS
         ====================================================== -->

    <div class="row g-3 mb-3">

        <!-- PARENT ACCOUNTS -->

        <div class="col-12 col-md-6">

            <div class="card border rounded-3 h-100">

                <div class="card-body d-flex align-items-center gap-3 p-3">

                    <div
                        class="rounded-3 bg-paper-soft text-primary
                               d-flex align-items-center justify-content-center
                               flex-shrink-0"
                        style="width: 48px; height: 48px;">

                        <i class="bi bi-people fs-4"></i>

                    </div>

                    <div>

                        <div class="text-muted small">
                            Parent Accounts
                        </div>

                        <div class="fw-bold fs-4">
                            <?= $roleCounts['parent'] ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- TEACHER ACCOUNTS -->

        <div class="col-12 col-md-6">

            <div class="card border rounded-3 h-100">

                <div class="card-body d-flex align-items-center gap-3 p-3">

                    <div
                        class="rounded-3 bg-paper-soft text-primary
                               d-flex align-items-center justify-content-center
                               flex-shrink-0"
                        style="width: 48px; height: 48px;">

                        <i class="bi bi-person-workspace fs-4"></i>

                    </div>

                    <div>

                        <div class="text-muted small">
                            Teacher Accounts
                        </div>

                        <div class="fw-bold fs-4">
                            <?= $roleCounts['teacher'] ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ======================================================
         SEARCH AND FILTER
         ====================================================== -->

    <div class="card mb-3">

        <div class="card-body">

            <form method="GET" action="">

                <div class="row g-3 align-items-end">

                    <!-- SEARCH -->

                    <div class="col-12 col-md-6">

                        <label
                            for="search"
                            class="form-label small fw-semibold">

                            <i class="bi bi-search me-1"></i>
                            Search Users

                        </label>

                        <input
                            type="search"
                            name="search"
                            id="search"
                            class="form-control form-control-sm"
                            placeholder="Search name, email, or user ID..."
                            value="<?= e($search) ?>">

                    </div>


                    <!-- ROLE FILTER -->

                    <div class="col-12 col-md-4">

                        <label
                            for="role"
                            class="form-label small fw-semibold">

                            <i class="bi bi-funnel me-1"></i>
                            Filter by Role

                        </label>

                        <select
                            name="role"
                            id="role"
                            class="form-select form-select-sm">

                            <option
                                value=""
                                <?= $filterRole === '' ? 'selected' : '' ?>>

                                All Users

                            </option>

                            <option
                                value="parent"
                                <?= $filterRole === 'parent' ? 'selected' : '' ?>>

                                Parents

                            </option>

                            <option
                                value="teacher"
                                <?= $filterRole === 'teacher' ? 'selected' : '' ?>>

                                Teachers

                            </option>

                        </select>

                    </div>


                    <!-- BUTTONS -->

                    <div class="col-12 col-md-2">

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary btn-sm flex-grow-1">

                                <i class="bi bi-search me-1"></i>
                                Search

                            </button>

                            <a
                                href="index.php"
                                class="btn btn-light border btn-sm"
                                title="Reset Filters">

                                <i class="bi bi-arrow-counterclockwise"></i>

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
         USERS TABLE
         ====================================================== -->

    <div class="card p-0">

        <!-- CARD HEADER -->

        <div class="card-header bg-paper border-bottom p-3">

            <div class="d-flex align-items-center justify-content-between gap-2">

                <h6 class="fw-bold mb-0">

                    <i class="bi bi-person-vcard text-primary me-2"></i>
                    User Accounts

                </h6>

                <span class="badge bg-paper-soft text-secondary border">

                    <?= count($users) ?>
                    record<?= count($users) !== 1 ? 's' : '' ?>

                </span>

            </div>

        </div>


        <!-- TABLE -->

        <div class="table-responsive">

            <table class="table mb-0 data-table text-nowrap">

                <thead>

                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($users)): ?>

                        <?php foreach ($users as $row): ?>

                            <?php
                            $fullName = trim(
                                ($row['first_name'] ?? '') . ' ' .
                                ($row['middle_name'] ?? '') . ' ' .
                                ($row['last_name'] ?? '')
                            );

                            $initial = strtoupper(
                                substr($row['first_name'] ?? '', 0, 1)
                            );
                            ?>

                            <tr>

                                <!-- USER ID -->

                                <td>
                                    <span class="fw-semibold">
                                        <?= (int) $row['id'] ?>
                                    </span>
                                </td>


                                <!-- FULL NAME -->

                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <div
                                            class="rounded-circle bg-paper-soft text-primary
                                                   d-flex align-items-center justify-content-center
                                                   fw-bold flex-shrink-0"
                                            style="width: 36px; height: 36px;">

                                            <?= e($initial) ?>

                                        </div>

                                        <span class="fw-semibold">
                                            <?= e($fullName) ?>
                                        </span>

                                    </div>

                                </td>


                                <!-- EMAIL -->

                                <td>
                                    <span class="text-break">
                                        <?= e($row['email']) ?>
                                    </span>
                                </td>


                                <!-- ROLE -->

                                <td>

                                    <?php if ($row['role'] === 'parent'): ?>

                                        <span class="badge bg-paper-soft text-secondary border">
                                            <i class="bi bi-people me-1"></i>
                                            Parent
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-paper-soft text-secondary border">
                                            <i class="bi bi-person-workspace me-1"></i>
                                            Teacher
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm reset-password-btn"
                                        data-user-id="<?= (int) $row['id'] ?>"
                                        data-user-name="<?= e($fullName) ?>"
                                        data-user-email="<?= e($row['email']) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#resetPasswordModal">

                                        <i class="bi bi-key me-1"></i>
                                        Reset Password

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="5" class="text-center py-5">

                                <i class="bi bi-person-vcard fs-2 text-muted"></i>

                                <h6 class="fw-semibold mt-2">
                                    No Users Found
                                </h6>

                                <p class="text-muted small mb-0">
                                    Try changing your search keyword
                                    or selected role.
                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- ==========================================================
     RESET PASSWORD MODAL
     ========================================================== -->

<div
    class="modal fade"
    id="resetPasswordModal"
    tabindex="-1"
    aria-labelledby="resetPasswordModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 rounded-4">

            <!-- MODAL HEADER -->

            <div class="modal-header bg-paper border-bottom p-3">

                <div>

                    <h5
                        class="modal-title fw-bold"
                        id="resetPasswordModalLabel">

                        <i class="bi bi-shield-lock text-primary me-2"></i>
                        Reset Password

                    </h5>

                    <p class="text-muted small mb-0 mt-1">
                        Set a new password for this user account.
                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>


            <!-- MODAL FORM -->

            <form
                action="reset-password.php"
                method="POST"
                id="resetPasswordForm">

                <div class="modal-body p-4">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token']) ?>">

                    <input
                        type="hidden"
                        name="user_id"
                        id="reset_user_id">


                    <!-- SELECTED USER -->

                    <div class="bg-paper-soft border rounded-3 p-3 mb-4">

                        <div class="d-flex align-items-center gap-3">

                            <div
                                class="rounded-circle bg-paper text-primary
                                       d-flex align-items-center justify-content-center
                                       flex-shrink-0"
                                style="width: 44px; height: 44px;">

                                <i class="bi bi-person fs-4"></i>

                            </div>

                            <div class="min-w-0">

                                <div
                                    class="fw-bold text-break"
                                    id="reset_user_name">

                                    Selected User

                                </div>

                                <div
                                    class="text-muted small text-break"
                                    id="reset_user_email">
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- NEW PASSWORD -->

                    <div class="mb-3">

                        <label
                            for="new_password"
                            class="form-label fw-semibold small">

                            New Password

                        </label>

                        <div class="input-group">

                            <input
                                type="password"
                                class="form-control"
                                id="new_password"
                                name="new_password"
                                placeholder="Enter new password"
                                minlength="8"
                                maxlength="72"
                                autocomplete="new-password"
                                required>

                            <button
                                class="btn btn-light border"
                                type="button"
                                id="toggleNewPassword"
                                aria-label="Show or hide new password">

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                        <div class="form-text">
                            Use at least 8 characters.
                        </div>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="mb-2">

                        <label
                            for="confirm_password"
                            class="form-label fw-semibold small">

                            Confirm New Password

                        </label>

                        <div class="input-group">

                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Re-enter new password"
                                minlength="8"
                                maxlength="72"
                                autocomplete="new-password"
                                required>

                            <button
                                class="btn btn-light border"
                                type="button"
                                id="toggleConfirmPassword"
                                aria-label="Show or hide confirmation password">

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                        <div
                            class="text-danger small mt-2 d-none"
                            id="passwordMismatch">

                            Passwords do not match.

                        </div>

                    </div>

                </div>


                <!-- MODAL FOOTER -->

                <div class="modal-footer border-top p-3">

                    <button
                        type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="savePasswordBtn">

                        <i class="bi bi-check2-circle me-1"></i>
                        Update Password

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ==========================================================
     MODAL JAVASCRIPT
     ========================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('resetPasswordModal');
    const form = document.getElementById('resetPasswordForm');

    const userId = document.getElementById('reset_user_id');
    const userName = document.getElementById('reset_user_name');
    const userEmail = document.getElementById('reset_user_email');

    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    const mismatch = document.getElementById('passwordMismatch');
    const saveButton = document.getElementById('savePasswordBtn');

    // ======================================================
    // POPULATE MODAL
    // ======================================================

    document.querySelectorAll('.reset-password-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            form.reset();

            userId.value = this.dataset.userId;
            userName.textContent = this.dataset.userName;
            userEmail.textContent = this.dataset.userEmail;

            mismatch.classList.add('d-none');

            newPassword.classList.remove('is-invalid');
            confirmPassword.classList.remove('is-invalid');

            saveButton.disabled = false;

        });

    });

    // ======================================================
    // SHOW / HIDE PASSWORD
    // ======================================================

    function togglePassword(inputId, buttonId) {

        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        const icon = button.querySelector('i');

        button.addEventListener('click', function () {

            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';

            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);

        });

    }

    togglePassword('new_password', 'toggleNewPassword');
    togglePassword('confirm_password', 'toggleConfirmPassword');

    // ======================================================
    // VALIDATE PASSWORD MATCH
    // ======================================================

    function checkPasswordMatch() {

        const hasMismatch =
            confirmPassword.value !== '' &&
            newPassword.value !== confirmPassword.value;

        mismatch.classList.toggle('d-none', !hasMismatch);

        confirmPassword.classList.toggle('is-invalid', hasMismatch);

        return !hasMismatch;
    }

    newPassword.addEventListener('input', function () {

        newPassword.classList.remove('is-invalid');
        checkPasswordMatch();

    });

    confirmPassword.addEventListener('input', checkPasswordMatch);

    // ======================================================
    // VALIDATE BEFORE SUBMIT
    // ======================================================

    form.addEventListener('submit', function (event) {

        newPassword.classList.remove('is-invalid');

        if (newPassword.value.length < 8) {

            event.preventDefault();

            newPassword.classList.add('is-invalid');
            newPassword.focus();

            return;

        }

        if (!checkPasswordMatch()) {

            event.preventDefault();
            confirmPassword.focus();

            return;

        }

        saveButton.disabled = true;

        saveButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span>' +
            'Updating...';

    });

    // ======================================================
    // RESET MODAL WHEN CLOSED
    // ======================================================

    modal.addEventListener('hidden.bs.modal', function () {

        form.reset();

        mismatch.classList.add('d-none');

        newPassword.classList.remove('is-invalid');
        confirmPassword.classList.remove('is-invalid');

        saveButton.disabled = false;

        saveButton.innerHTML =
            '<i class="bi bi-check2-circle me-1"></i>' +
            'Update Password';

        // Restore hidden password visibility states.
        newPassword.type = 'password';
        confirmPassword.type = 'password';

        document.querySelector('#toggleNewPassword i')
            .className = 'bi bi-eye';

        document.querySelector('#toggleConfirmPassword i')
            .className = 'bi bi-eye';

    });

});
</script>

<?php include '../layout/footer.php'; ?>