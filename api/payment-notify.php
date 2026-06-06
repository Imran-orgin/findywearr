<?php
require_once '../config/database.php';

$merchant_id     = "1221149";
$merchant_secret = "MzYwMzgxMzI2MzIyMzIyMzMxMzIzMjMxMjYyNjM=";

$order_id        = $_POST['order_id'] ?? 0;
$status_code     = $_POST['status_code'] ?? '';
$amount          = $_POST['amount'] ?? 0;
$currency        = $_POST['currency'] ?? '';
$md5sig          = $_POST['md5sig'] ?? '';

// Verify hash
$local_md5sig = strtoupper(
    md5(
        $merchant_id .
        $order_id .
        number_format($amount, 2, '.', '') .
        $currency .
        $status_code .
        strtoupper(md5($merchant_secret))
    )
);

if ($local_md5sig === $md5sig && $status_code == 2) {
    // Payment successful
    $upd = mysqli_prepare($conn, "UPDATE payments SET status='success' WHERE order_id=?");
    mysqli_stmt_bind_param($upd, "i", $order_id);
    mysqli_stmt_execute($upd);

    $upd2 = mysqli_prepare($conn, "UPDATE orders SET payment_status='paid' WHERE id=?");
    mysqli_stmt_bind_param($upd2, "i", $order_id);
    mysqli_stmt_execute($upd2);

    // Commission mark as paid
    $upd3 = mysqli_prepare($conn, "UPDATE commissions SET status='paid' WHERE order_id=?");
    mysqli_stmt_bind_param($upd3, "i", $order_id);
    mysqli_stmt_execute($upd3);
}

http_response_code(200);
?>