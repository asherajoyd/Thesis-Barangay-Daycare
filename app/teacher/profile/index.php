<?php

$pageTitle = "My Profile";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


if (empty($_SESSION['teacher_id'])) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";
    header("Location: ../dashboard/");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];
$userId    = (int) ($_SESSION['user_id'] ?? 0);


// ==========================================================
// GET TEACHER INFO
// ==========================================================

$stmt = $conn->prepare("
    SELECT
        u.id, u.first_name, u.middle_name, u.last_name,
        u.email, u.contact, u.role, u.status, u.created_at,
        t.id AS teacher_id
    FROM users u
    LEFT JOIN teacher t ON t.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Profile not found.";
    header("Location: ../dashboard/");
    exit();
}


// ==========================================================
// GET TEACHER'S CLASSES
// ==========================================================

$classes = [];

if (!empty($user['teacher_id'])) {
    $stmt = $conn->prepare("
        SELECT cl.id, cl.name, s.name AS session_name,
               s.start_time, s.end_time, ay.academic_year
        FROM class cl
        LEFT JOIN session s ON s.id = cl.session_id
        LEFT JOIN academic_year ay ON ay.id = cl.academic_id
        WHERE cl.teacher_id = ? AND cl.status = 'active'
        ORDER BY s.start_time ASC, cl.name ASC
    ");
    $stmt->bind_param("i", $user['teacher_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt->close();
}


// ==========================================================
// HANDLE PASSWORD CHANGE
// ==========================================================

$passwordError   = "";
$passwordSuccess = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordError = "All password fields are required.";
    } elseif (strlen($newPassword) < 6) {
        $passwordError = "New password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = "New password and confirmation do not match.";
    } else {

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            $passwordError = "Current password is incorrect.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $newHash, $userId);
            $stmt->execute();
            $stmt->close();
            $passwordSuccess = "Password changed successfully.";
        }
    }
}


$fullName = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
$initial  = strtoupper(substr($user['first_name'], 0, 1));


include '../layout/header.php';

?>

<div class="profile-page">

    <div class="mb-3">
        <h2 class="fw-bold fs-4 mb-1"><?= e($pageTitle) ?></h2>
        <p class="text-muted small mb-0">View your profile and change password</p>
    </div>


    <div class="row g-3">

        <!-- LEFT: PROFILE INFO -->
        <div class="col-12 col-lg-4">

            <div class="card">

                <!-- AVATAR + NAME (compact) -->
                <div class="card-body p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width: 56px; height: 56px; font-size: 1.3rem;">
                            <?= e($initial) ?>
                        </div>

                        <div class="min-w-0">
                            <div class="fw-bold text-break"><?= e($fullName) ?></div>
                            <small class="text-muted"><?= e(ucfirst($user['role'])) ?></small>
                        </div>

                    </div>

                </div>

                <!-- INFO LIST -->
                <div class="card-body border-top p-3">

                    <dl class="row g-2 small mb-0">

                        <dt class="col-5 text-muted fw-normal">Email</dt>
                        <dd class="col-7 text-end mb-0 text-break">
                            <small><?= e($user['email']) ?></small>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Contact</dt>
                        <dd class="col-7 text-end mb-0">
                            <small><?= e($user['contact'] ?: '—') ?></small>
                        </dd>

                        <?php if (!empty($user['employee_no'])): ?>
                            <dt class="col-5 text-muted fw-normal">Employee No</dt>
                            <dd class="col-7 text-end mb-0">
                                <small><?= e($user['employee_no']) ?></small>
                            </dd>
                        <?php endif; ?>

                        <dt class="col-5 text-muted fw-normal">Status</dt>
                        <dd class="col-7 text-end mb-0">
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Member Since</dt>
                        <dd class="col-7 text-end mb-0">
                            <small><?= e(date('M d, Y', strtotime($user['created_at']))) ?></small>
                        </dd>

                    </dl>

                </div>

            </div>

        </div>


        <!-- RIGHT: MAIN CONTENT -->
        <div class="col-12 col-lg-8">

            <!-- MY CLASSES -->
            <?php if (!empty($classes)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-paper border-bottom p-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-door-open text-primary me-2"></i>
                            My Classes
                        </h6>
                    </div>
                    <div class="card-body p-3">

                        <div class="d-flex flex-column gap-2">

                            <?php foreach ($classes as $c): ?>
                                <div class="border rounded-3 p-2">

                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="fw-semibold small text-break">
                                            <?= e($c['name']) ?>
                                        </div>
                                        <?php if (!empty($c['academic_year'])): ?>
                                            <span class="badge bg-paper-soft text-secondary border" style="font-size: 0.7rem;">
                                                <?= e($c['academic_year']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= e($c['session_name']) ?>
                                        (<?= e(date('h:i A', strtotime($c['start_time']))) ?>
                                        –
                                        <?= e(date('h:i A', strtotime($c['end_time']))) ?>)
                                    </small>

                                </div>
                            <?php endforeach; ?>

                        </div>

                    </div>
                </div>
            <?php endif; ?>


            <!-- CHANGE PASSWORD -->
            <div class="card">
                <div class="card-header bg-paper border-bottom p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-shield-lock text-primary me-2"></i>
                        Change Password
                    </h6>
                </div>
                <div class="card-body p-3">

                    <?php if (!empty($passwordError)): ?>
                        <div class="alert alert-danger py-2 px-3 small mb-3">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <?= e($passwordError) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($passwordSuccess)): ?>
                        <div class="alert alert-success py-2 px-3 small mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= e($passwordSuccess) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">

                        <input type="hidden" name="change_password" value="1">

                        <div class="row g-2">

                            <div class="col-12">
                                <label class="form-label small mb-1">
                                    Current Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="current_password" class="form-control form-control-sm"
                                       placeholder="Enter current password" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="new_password" class="form-control form-control-sm"
                                       placeholder="At least 6 characters" minlength="6" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">
                                    Confirm Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="confirm_password" class="form-control form-control-sm"
                                       placeholder="Re-enter new password" minlength="6" required>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-check2-circle me-1"></i>
                                Update Password
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>

    </div>

</div>

<?php include '../layout/footer.php'; ?>