<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['notifications' => [], 'count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark as read
if (isset($_POST['mark_read'])) {
    $notif_id = intval($_POST['notif_id']);
    if ($notif_id === 0) {
        // Mark all as read
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        // Mark one as read
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $notif_id, $user_id);
    }
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit();
}

// Fetch notifications
$stmt = mysqli_prepare($conn, "
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
$unread_count  = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
    if (!$row['is_read']) $unread_count++;
}

echo json_encode([
    'notifications' => $notifications,
    'count'         => $unread_count
]);
?>