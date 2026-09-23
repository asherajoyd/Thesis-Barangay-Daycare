<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';

switch ($role) {

    case 'admin':
        header('Location: admin/dashboard/');
        exit;

    case 'teacher':
        header('Location: teacher/index.php');
        exit;

    case 'parent':
        header('Location: parent/index.php');
        exit;

    default:
        session_unset();
        session_destroy();

        header('Location: ../login.php');
        exit;
}