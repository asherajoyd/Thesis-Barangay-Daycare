<?php

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// REDIRECT WITH MESSAGE
// ==========================================================

function redirectUsers($status, $message)
{
    $_SESSION['status'] = $status;
    $_SESSION['message'] = $message;

    header("Location: index.php");
    exit;
}

// ==========================================================
// REQUEST METHOD
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ==========================================================
// CSRF VALIDATION
// ==========================================================

$sessionToken = $_SESSION['csrf_token'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($sessionToken) ||
    !is_string($submittedToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    redirectUsers(
        'error',
        'Invalid or expired request. Please try again.'
    );
}

// ==========================================================
// GET INPUT
// ==========================================================

$userId = filter_input(
    INPUT_POST,
    'user_id',
    FILTER_VALIDATE_INT
);

$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// ==========================================================
// VALIDATE USER ID
// ==========================================================

if (!$userId || $userId <= 0) {
    redirectUsers('error', 'Invalid user account.');
}

// ==========================================================
// VALIDATE PASSWORD INPUT
// ==========================================================

if (
    !is_string($newPassword) ||
    !is_string($confirmPassword)
) {
    redirectUsers('error', 'Invalid password input.');
}

// ==========================================================
// PASSWORD LENGTH
// PASSWORD_DEFAULT currently uses bcrypt on many PHP versions,
// which supports a maximum of 72 password bytes.
// ==========================================================

if (
    strlen($newPassword) < 8 ||
    strlen($newPassword) > 72
) {
    redirectUsers(
        'error',
        'Password must contain at least 8 characters and no more than 72 bytes.'
    );
}

// ==========================================================
// CONFIRM PASSWORD
// ==========================================================

if ($newPassword !== $confirmPassword) {
    redirectUsers('error', 'The passwords do not match.');
}

// ==========================================================
// VERIFY TARGET ACCOUNT
// Parent and teacher accounts only
// ==========================================================

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role IN ('parent', 'teacher')
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    redirectUsers(
        'error',
        'Parent or teacher account not found.'
    );
}

// ==========================================================
// HASH PASSWORD
// ==========================================================

$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

if ($hashedPassword === false) {
    redirectUsers(
        'error',
        'Unable to secure the new password.'
    );
}

// ==========================================================
// UPDATE DATABASE
// ==========================================================

$stmt = $conn->prepare("
    UPDATE users
    SET password = ?
    WHERE id = ?
      AND role IN ('parent', 'teacher')
");

$stmt->bind_param(
    "si",
    $hashedPassword,
    $userId
);

if (!$stmt->execute()) {

    $stmt->close();

    redirectUsers(
        'error',
        'A database error occurred while updating the password.'
    );

}

$affectedRows = $stmt->affected_rows;

$stmt->close();

// Affected rows may be zero if the newly generated hash happens
// to equal the existing hash. Verify the saved password instead.
if ($affectedRows === 1) {

    redirectUsers(
        'success',
        'Password successfully reset.'
    );

}

// ==========================================================
// VERIFY PASSWORD IF MYSQL REPORTS NO CHANGED ROWS
// ==========================================================

$stmt = $conn->prepare("
    SELECT password
    FROM users
    WHERE id = ?
      AND role IN ('parent', 'teacher')
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$savedUser = $result->fetch_assoc();

$stmt->close();

if (
    $savedUser &&
    password_verify($newPassword, $savedUser['password'])
) {

    redirectUsers(
        'success',
        'Password successfully reset.'
    );

}

redirectUsers(
    'error',
    'Password could not be updated. Please try again.'
);