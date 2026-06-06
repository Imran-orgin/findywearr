<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$order_id = intval($_GET['order_id'] ?? 0);

// Update payment status
$upd = mysqli_prepare($conn, "UPDATE payments SET status = 'failed' WHERE order_id = ?");
mysqli_stmt_bind_param($upd, "i", $order_id);
mysqli_stmt_execute($upd);

header('Location: /findywearce/customer/orders.php?cancelled=' . $order_id);
exit();
?>