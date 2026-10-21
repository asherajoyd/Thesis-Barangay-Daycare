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

    $icon = 'bi-bell';
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
        case 'activity':
            $icon = 'bi-journal-text';
            $color = 'text-info';
            break;
        case 'health_record':
            $icon = 'bi-heart-pulse';
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
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}