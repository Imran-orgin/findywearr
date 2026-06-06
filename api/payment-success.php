<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$order_id = intval($_GET['order_id'] ?? 0);

// Update payment status
$upd = mysqli_prepare($conn, "UPDATE payments SET status = 'success' WHERE order_id = ?");
mysqli_stmt_bind_param($upd, "i", $order_id);
mysqli_stmt_execute($upd);

// Update order payment status
$upd2 = mysqli_prepare($conn, "UPDATE orders SET payment_status = 'paid' WHERE id = ?");
mysqli_stmt_bind_param($upd2, "i", $order_id);
mysqli_stmt_execute($upd2);

header('Location: /findywearce/customer/orders.php?success=' . $order_id . '&paid=1');
exit();
?>