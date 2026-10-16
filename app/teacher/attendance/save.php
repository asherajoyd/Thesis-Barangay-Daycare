<?php

require_once '../guard.php';
include "../../../api/conn.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}


// ==========================================================
// CHECK TEACHER ID
// ==========================================================

if (empty($_SESSION['teacher_id'])) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Teacher profile not found.";

    header("Location: ../dashboard/index.php");
    exit();
}

$teacherId = (int) $_SESSION['teacher_id'];


// ==========================================================
// COLLECT DATA
// ==========================================================

$classId    = (int) ($_POST['class_id'] ?? 0);
$academicId = (int) ($_POST['academic_id'] ?? 0);
$date       = trim($_POST['date'] ?? '');
$statuses   = $_POST['status'] ?? [];
$remarks    = $_POST['remarks'] ?? [];


// ==========================================================
// VALIDATION
// ==========================================================

if ($classId <= 0 || empty($date) || !is_array($statuses)) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Invalid request.";

    header("Location: index.php");
    exit();
}


if (strtotime($date) === false) {

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Invalid date.";

    header("Location: index.php");
    exit();
}


// ==========================================================
// VERIFY CLASS BELONGS TO TEACHER
// ==========================================================

$verifyStmt = $conn->prepare("
    SELECT id FROM class
    WHERE id = ? AND teacher_id = ?
    LIMIT 1
");

$verifyStmt->bind_param("ii", $classId, $teacherId);
$verifyStmt->execute();
$verifyStmt->store_result();

if ($verifyStmt->num_rows === 0) {

    $verifyStmt->close();

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "You are not allowed to record attendance for this class.";

    header("Location: index.php");
    exit();
}

$verifyStmt->close();


$allowedStatuses = ['present', 'absent', 'late', 'excused'];
$recordedBy      = (int) ($_SESSION['user_id'] ?? 0);


// ==========================================================
// SAVE ATTENDANCE (UPSERT)
// ==========================================================

$savedCount   = 0;
$updatedCount = 0;
$errorCount   = 0;


try {

    $conn->begin_transaction();


    $upsertStmt = $conn->prepare("
        INSERT INTO attendance
            (enrollment_id, class_id, date, status, remarks, recorded_by)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            remarks = VALUES(remarks),
            recorded_by = VALUES(recorded_by)
    ");


    $checkStmt = $conn->prepare("
        SELECT id FROM enrollment
        WHERE id = ?
          AND class_id = ?
          AND status = 'approved'
        LIMIT 1
    ");


    foreach ($statuses as $enrollmentId => $status) {

        $enrollmentId = (int) $enrollmentId;
        $status       = trim($status);
        $remark       = trim($remarks[$enrollmentId] ?? '');

        if ($enrollmentId <= 0) {
            continue;
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $errorCount++;
            continue;
        }

        $checkStmt->bind_param("ii", $enrollmentId, $classId);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
            $errorCount++;
            continue;
        }

        $checkStmt->free_result();

        $upsertStmt->bind_param(
            "iisssi",
            $enrollmentId,
            $classId,
            $date,
            $status,
            $remark,
            $recordedBy
        );

        $upsertStmt->execute();

        if ($upsertStmt->affected_rows === 1) {
            $savedCount++;
        } elseif ($upsertStmt->affected_rows === 2) {
            $updatedCount++;
        }
    }


    $upsertStmt->close();
    $checkStmt->close();

    $conn->commit();


    $parts = [];

    if ($savedCount > 0)   $parts[] = "$savedCount saved";
    if ($updatedCount > 0) $parts[] = "$updatedCount updated";
    if ($errorCount > 0)   $parts[] = "$errorCount skipped";

    $message = !empty($parts) ? implode(', ', $parts) . "." : "No changes.";

    $_SESSION['status']  = "success";
    $_SESSION['message'] = "Attendance: " . $message;


} catch (Exception $e) {

    $conn->rollback();

    $_SESSION['status']  = "danger";
    $_SESSION['message'] = "Failed to save attendance: " . $e->getMessage();
}


header("Location: index.php?class_id=" . $classId . "&date=" . urlencode($date) . "&academic_id=" . $academicId);
exit();