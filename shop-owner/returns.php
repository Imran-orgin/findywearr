<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];

// Shop fetch
$shop_stmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE owner_id = ?");
mysqli_stmt_bind_param($shop_stmt, "i", $owner_id);
mysqli_stmt_execute($shop_stmt);
$shop = mysqli_fetch_assoc(mysqli_stmt_get_result($shop_stmt));

if (!$shop) {
    header('Location: /findywearce/shop-owner/setup-shop.php');
    exit();
}

$shop_id = $shop['id'];
$success = '';

// Handle return approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_return'])) {
    $return_id  = intval($_POST['return_id']);
    $new_status = $_POST['new_status'];
    $allowed    = ['approved', 'rejected', 'refunded'];

    if (in_array($new_status, $allowed)) {
        $upd = mysqli_prepare($conn, "UPDATE returns SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $new_status, $return_id);
        mysqli_stmt_execute($upd);

        // Get customer id
        $ret_stmt = mysqli_prepare($conn, "SELECT customer_id, order_id FROM returns WHERE id = ?");
        mysqli_stmt_bind_param($ret_stmt, "i", $return_id);
        mysqli_stmt_execute($ret_stmt);
        $ret_data = mysqli_fetch_assoc(mysqli_stmt_get_result($ret_stmt));

        // Notify customer
        $msg = match($new_status) {
            'approved' => "Your return request for Order #" . $ret_data['order_id'] . " has been approved!",
            'rejected' => "Your return request for Order #" . $ret_data['order_id'] . " has been rejected.",
            'refunded' => "Refund for Order #" . $ret_data['order_id'] . " has been processed!",
            default    => "Return status updated"
        };

        $notif = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($notif, "is", $ret_data['customer_id'], $msg);
        mysqli_stmt_execute($notif);

        $success = 'Return request updated successfully!';
    }
}

// Fetch returns
$stmt = mysqli_prepare($conn, "
    SELECT r.*, 
           u.name as customer_name, u.phone as customer_phone,
           o.total_amount, o.created_at as order_date
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON r.customer_id = u.id
    WHERE o.shop_id = ?
    ORDER BY r.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $shop_id);
mysqli_stmt_execute($stmt);
$returns = mysqli_stmt_get_result($stmt);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-undo me-2" style="color:var(--primary);"></i>
            Return Requests
        </h4>
        <a href="/findywearce/shop-owner/dashboard.php"
            class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($returns) === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-undo fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No return requests yet!</h5>
        </div>

    <?php else: ?>
        <?php while ($return = mysqli_fetch_assoc($returns)): ?>
        <div class="fw-card mb-4">
            <div class="row align-items-center">

                <!-- Return Info -->
                <div class="col-md-4 mb-3 mb-md-0">
                    <h6 class="fw-bold mb-1">
                        Return #<?php echo $return['id']; ?> 
                        <small class="text-muted">
                            (Order #<?php echo $return['order_id']; ?>)
                        </small>
                    </h6>
                    <small class="text-muted d-block mb-2">
                        <?php echo date('d M Y, h:i A', 
                            strtotime($return['created_at'])); ?>
                    </small>

                    <!-- Customer -->
                    <div class="p-2 rounded mb-2" style="background:#f8f9ff;">
                        <small class="fw-bold d-block">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($return['customer_name']); ?>
                        </small>
                        <small class="text-muted">
                            <?php echo $return['customer_phone']; ?>
                        </small>
                    </div>

                    <!-- Reason -->
                    <div class="p-2 rounded" style="background:#fff3cd;">
                        <small class="fw-bold d-block">Reason:</small>
                        <small><?php echo htmlspecialchars($return['reason']); ?></small>
                    </div>
                </div>

                <!-- Amount -->
                <div class="col-md-4 mb-3 mb-md-0 text-center">
                    <div class="fw-bold fs-4" style="color:var(--primary);">
                        LKR <?php echo number_format($return['refund_amount'], 2); ?>
                    </div>
                    <small class="text-muted">Refund Amount</small>

                    <div class="mt-3">
                        <?php
                        $badge = match($return['status']) {
                            'pending'  => 'badge-pending',
                            'approved' => 'badge-accepted',
                            'rejected' => 'badge-cancelled',
                            'refunded' => 'badge-delivered',
                            default    => 'badge-pending'
                        };
                        $label = match($return['status']) {
                            'pending'  => '⏳ Pending',
                            'approved' => '✅ Approved',
                            'rejected' => '❌ Rejected',
                            'refunded' => '💰 Refunded',
                            default    => $return['status']
                        };
                        ?>
                        <span class="<?php echo $badge; ?>">
                            <?php echo $label; ?>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="col-md-4">
                    <?php if ($return['status'] === 'pending'): ?>
                    <form method="POST">
                        <input type="hidden" name="return_id"
                            value="<?php echo $return['id']; ?>">

                        <button type="submit" name="update_return"
                            value="1"
                            onclick="document.querySelector('[name=new_status]').value='approved'"
                            class="btn btn-success w-100 mb-2">
                            <i class="fas fa-check me-2"></i>Approve Return
                        </button>

                        <button type="submit" name="update_return"
                            value="1"
                            onclick="document.querySelector('[name=new_status]').value='rejected'"
                            class="btn btn-outline-danger w-100 mb-2">
                            <i class="fas fa-times me-2"></i>Reject Return
                        </button>

                        <input type="hidden" name="new_status" value="approved">
                    </form>

                    <?php elseif ($return['status'] === 'approved'): ?>
                    <form method="POST">
                        <input type="hidden" name="return_id"
                            value="<?php echo $return['id']; ?>">
                        <input type="hidden" name="new_status" value="refunded">
                        <button type="submit" name="update_return"
                            class="btn btn-primary-custom w-100">
                            <i class="fas fa-money-bill me-2"></i>
                            Mark as Refunded
                        </button>
                    </form>

                    <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 
                            <?php echo $return['status'] === 'refunded' ? 'text-success' : 'text-danger'; ?>">
                        </i>
                        <p class="small">
                            <?php echo $return['status'] === 'refunded' ? 
                                'Refund Completed' : 'Return Rejected'; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>