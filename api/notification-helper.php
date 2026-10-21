<?php

/**
 * Create notification for a single user
 */
function createNotification($conn, $userId, $type, $title, $message = '', $link = '')
{
    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("
        INSERT INTO notification (user_id, type, title, message, link)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issss", $userId, $type, $title, $message, $link);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}


/**
 * Notify specific teacher by teacher_id
 */
function notifyTeacher($conn, $teacherId, $type, $title, $message = '', $link = '')
{
    $stmt = $conn->prepare("
        SELECT user_id FROM teacher WHERE id = ? LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        createNotification($conn, (int) $row['user_id'], $type, $title, $message, $link);
    }

    $stmt->close();
    return true;
}


/**
 * Notify all parents of a specific class
 */
function notifyParentsOfClass($conn, $classId, $type, $title, $message = '', $link = '')
{
    if ($classId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT DISTINCT c.user_id
        FROM children c
        INNER JOIN enrollment en ON en.child_id = c.id AND en.status = 'approved'
        WHERE en.class_id = ?
          AND c.user_id IS NOT NULL
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        createNotification($conn, (int) $row['user_id'], $type, $title, $message, $link);
    }

    $stmt->close();
    return true;
}


/**
 * Notify parent of a specific child
 */
function notifyParentOfChild($conn, $childId, $type, $title, $message = '', $link = '')
{
    $stmt = $conn->prepare("
        SELECT user_id FROM children WHERE id = ? AND user_id IS NOT NULL LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $childId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        createNotification($conn, (int) $row['user_id'], $type, $title, $message, $link);
    }

    $stmt->close();
    return true;
}


/**
 * Notify all teachers
 */
function notifyAllTeachers($conn, $type, $title, $message = '', $link = '')
{
    $stmt = $conn->prepare("
        SELECT user_id FROM teacher WHERE user_id IS NOT NULL
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        createNotification($conn, (int) $row['user_id'], $type, $title, $message, $link);
    }

    $stmt->close();
    return true;
}


/**
 * Notify all parents
 */
function notifyAllParents($conn, $type, $title, $message = '', $link = '')
{
    $stmt = $conn->prepare("
        SELECT id FROM users WHERE role = 'parent' AND status = 'active'
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        createNotification($conn, (int) $row['id'], $type, $title, $message, $link);
    }

    $stmt->close();
    return true;
}