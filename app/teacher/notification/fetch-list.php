<?php

require_once '../guard.php';
include "../../../api/conn.php";

header('Content-Type: application/json');


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['notifications' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, type, title, message, link, is_read, created_at
    FROM notification
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
    LIMIT 10
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {

    $icon  = 'bi-bell';
    $color = 'text-primary';

    switch ($row['type']) {
        case 'message':
            $icon = 'bi-chat-dots';
            $color = 'text-primary';
            break;
        case 'announcement':
            $icon = 'bi-megaphone';
            $color = 'text-warning';
            break;
        case 'enrollment':
            $icon = 'bi-person-check';
            $color = 'text-success';
            break;
    }

    $notifications[] = [
        'id'         => (int) $row['id'],
        'type'       => $row['type'],
        'title'      => e($row['title']),
        'message'    => e($row['message'] ?? ''),
        'link'       => $row['link'] ?? '',
        'is_read'    => (int) $row['is_read'],
        'created_at' => date('M d, h:i A', strtotime($row['created_at'])),
        'time_ago'   => timeAgo($row['created_at']),
        'icon'       => $icon,
        'color'      => $color
    ];
}

$stmt->close();

echo json_encode(['notifications' => $notifications]);


function timeAgo($datetime)
{
    $timezone = new DateTimeZone('Asia/Manila');

    $now = new DateTimeImmutable('now', $timezone);

    // Assumes created_at is stored in Philippine local time.
    $then = new DateTimeImmutable($datetime, $timezone);

    $seconds = $now->getTimestamp() - $then->getTimestamp();

    if ($seconds < 0) {
        return 'just now';
    }

    if ($seconds < 60) {
        return 'just now';
    }

    if ($seconds < 3600) {
        $minutes = (int) floor($seconds / 60);
        return $minutes . ($minutes === 1 ? ' minute ago' : ' minutes ago');
    }

    if ($seconds < 86400) {
        $hours = (int) floor($seconds / 3600);
        return $hours . ($hours === 1 ? ' hour ago' : ' hours ago');
    }

    if ($seconds < 604800) {
        $days = (int) floor($seconds / 86400);
        return $days . ($days === 1 ? ' day ago' : ' days ago');
    }

    if ($seconds < 2592000) {
        $weeks = (int) floor($seconds / 604800);
        return $weeks . ($weeks === 1 ? ' week ago' : ' weeks ago');
    }

    if ($seconds < 31536000) {
        $months = (int) floor($seconds / 2592000);
        return $months . ($months === 1 ? ' month ago' : ' months ago');
    }

    $years = (int) floor($seconds / 31536000);
    return $years . ($years === 1 ? ' year ago' : ' years ago');
}