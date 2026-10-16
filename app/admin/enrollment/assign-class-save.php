<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: assign-class.php");
    exit();
}


$academicId  = (int) ($_POST['academic_id'] ?? 0);
$assignments = $_POST['assignments'] ?? [];


if ($academicId <= 0 || !is_array($assignments)) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Invalid request.";

    header("Location: assign-class.php");
    exit();
}


$savedCount    = 0;
$skippedCount  = 0;
$errors        = [];


try {

    $conn->begin_transaction();


    // Prepare statements
    $updateStmt = $conn->prepare("
        UPDATE enrollment
        SET class_id = ?
        WHERE id = ?
          AND status = 'approved'
          AND academic_id = ?
          AND class_id IS NULL
    ");


    $checkStmt = $conn->prepare("
        SELECT id FROM class
        WHERE id = ?
          AND academic_id = ?
          AND status = 'active'
        LIMIT 1
    ");


    foreach ($assignments as $enrollmentId => $classId) {

        $enrollmentId = (int) $enrollmentId;
        $classId      = (int) $classId;

        // Skip kung walang piniling class
        if ($classId <= 0) {
            $skippedCount++;
            continue;
        }

        // Verify class exists + belongs to academic year + active
        $checkStmt->bind_param("ii", $classId, $academicId);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
            $errors[] = "Enrollment #{$enrollmentId}: class not valid.";
            continue;
        }

        $checkStmt->free_result();

        // Update enrollment
        $updateStmt->bind_param("iii", $classId, $enrollmentId, $academicId);
        $updateStmt->execute();

        if ($updateStmt->affected_rows > 0) {
            $savedCount++;
        } else {
            $skippedCount++;
        }
    }


    $updateStmt->close();
    $checkStmt->close();

    $conn->commit();


    // Build message
    $msg = "{$savedCount} assignment" . ($savedCount !== 1 ? 's' : '') . " saved.";

    if ($skippedCount > 0) {
        $msg .= " {$skippedCount} skipped.";
    }

    if (!empty($errors)) {
        $msg .= " " . count($errors) . " error(s).";
        $_SESSION['status'] = "warning";
    } else {
        $_SESSION['status'] = "success";
    }

    $_SESSION['message'] = $msg;


} catch (Exception $e) {

    $conn->rollback();

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Failed to save assignments: " . $e->getMessage();
}


header("Location: assign-class.php?academic_id=" . $academicId);
exit();