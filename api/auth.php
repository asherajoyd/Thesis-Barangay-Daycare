<?php

session_start();

require_once('conn.php');


// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


// Get form data
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


// Validate input
if ($email === '' || $password === '') {

    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required.'
    ]);

    exit;
}


// Find user
$sql = "
    SELECT
        id,
        first_name,
        middle_name,
        last_name,
        email,
        password,
        role
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


// User does not exist
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


// Verify password
if (!password_verify($password, $user['password'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password.'
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// Regenerate session ID
session_regenerate_id(true);


// Store user information
$_SESSION['user_id'] = $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['middle_name'] = $user['middle_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

$_SESSION['fullname'] =
    trim(
        $user['first_name'] . ' ' .
        $user['middle_name'] . ' ' .
        $user['last_name']
    );



// Successful login
echo json_encode([
    'success' => true,
    'message' => 'Login successful.',
    'redirect' => 'app/index.php'
]);


$stmt->close();
$conn->close();