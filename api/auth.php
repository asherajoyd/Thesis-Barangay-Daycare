<?php

session_start();

require_once('conn.php');


// ==========================================================
// ONLY ALLOW POST REQUESTS
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


// ==========================================================
// GET FORM DATA
// ==========================================================

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


// ==========================================================
// VALIDATE INPUT
// ==========================================================

if ($email === '' || $password === '') {

    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required.'
    ]);

    exit;
}


// ==========================================================
// FIND USER
// ==========================================================

$sql = "
    SELECT
        id,
        first_name,
        middle_name,
        last_name,
        email,
        password,
        role,
        status
    FROM users
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'message' => 'Database error.'
    ]);

    exit;
}


$stmt->bind_param('s', $email);

$stmt->execute();

$result = $stmt->get_result();


// ==========================================================
// USER DOES NOT EXIST
// ==========================================================

if ($result->num_rows === 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password.'
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$user = $result->fetch_assoc();


// ==========================================================
// VERIFY PASSWORD
// ==========================================================

if (!password_verify($password, $user['password'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password.'
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// ==========================================================
// CHECK ACCOUNT STATUS
// ==========================================================

if ($user['status'] !== 'active') {

    echo json_encode([
        'success' => false,
        'message' => 'Your account is inactive. Please contact the administrator.'
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// ==========================================================
// REGENERATE SESSION ID
// ==========================================================

session_regenerate_id(true);


// ==========================================================
// STORE USER INFORMATION
// ==========================================================

$_SESSION['user_id']     = (int) $user['id'];
$_SESSION['first_name']  = $user['first_name'];
$_SESSION['middle_name'] = $user['middle_name'];
$_SESSION['last_name']   = $user['last_name'];
$_SESSION['email']       = $user['email'];
$_SESSION['role']        = $user['role'];

$_SESSION['fullname'] = trim(
    $user['first_name'] . ' ' .
    $user['middle_name'] . ' ' .
    $user['last_name']
);


// ==========================================================
// IF TEACHER, ADD teacher_id TO SESSION
// ==========================================================

if ($user['role'] === 'teacher') {

    $teacherStmt = $conn->prepare("
        SELECT id FROM teacher WHERE user_id = ? LIMIT 1
    ");

    if ($teacherStmt) {

        $teacherStmt->bind_param("i", $user['id']);
        $teacherStmt->execute();
        $teacherResult = $teacherStmt->get_result();

        if ($teacherResult->num_rows > 0) {
            $teacherRow = $teacherResult->fetch_assoc();
            $_SESSION['teacher_id'] = (int) $teacherRow['id'];
        }

        $teacherStmt->close();
    }
}


// ==========================================================
// IF PARENT, ADD parent-related IDs TO SESSION (optional)
// ==========================================================

if ($user['role'] === 'parent') {

    $childStmt = $conn->prepare("
        SELECT id FROM children WHERE user_id = ? LIMIT 1
    ");

    if ($childStmt) {

        $childStmt->bind_param("i", $user['id']);
        $childStmt->execute();
        $childResult = $childStmt->get_result();

        if ($childResult->num_rows > 0) {
            $childRow = $childResult->fetch_assoc();
            $_SESSION['child_id'] = (int) $childRow['id'];
        }

        $childStmt->close();
    }
}


// ==========================================================
// SUCCESSFUL LOGIN
// ==========================================================

echo json_encode([
    'success'  => true,
    'message'  => 'Login successful.',
    'redirect' => 'app/index.php'
]);


$stmt->close();
$conn->close();