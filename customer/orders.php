<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];

// Fetch orders
$stmt = mysqli_prepare($conn, "
    SELECT o.*, s.shop_name, s.phone as shop_phone,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <h4 class="fw-bold mb-4">
        <i class="fas fa-box me-2" style="color:var(--primary);"></i>
        My Orders
    </h4>

    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        Order #<?php echo intval($_GET['success']); ?> placed successfully! 
        Shop will confirm within 1 hour.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($orders) === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No orders yet!</h5>
            <a href="/findywearce/customer/home.php" 
                class="btn btn-primary-custom mt-3">
                <i class="fas fa-store me-2"></i>Start Shopping
            </a>
        </div>

    <?php else: ?>
        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
        <div class="fw-card mb-4">
            <div class="row align-items-center">

                <!-- Order Info -->
                <div class="col-md-3 mb-3 mb-md-0">
                    <small class="text-muted">Order ID</small>
                    <h6 class="fw-bold mb-1">#<?php echo $order['id']; ?></h6>
                    <small class="text-muted">
                        <?php echo date('d M Y, h:i A', 
                            strtotime($order['created_at'])); ?>
                    </small>
                </div>

                <!-- Shop Info -->
                <div class="col-md-3 mb-3 mb-md-0">
                    <small class="text-muted">Shop</small>
                    <h6 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($order['shop_name']); ?>
                    </h6>
                    <small class="text-muted">
                        <?php echo $order['item_count']; ?> item(s)
                    </small>
                </div>

                <!-- Amount -->
                <div class="col-md-2 mb-3 mb-md-0">
                    <small class="text-muted">Total</small>
                    <h6 class="fw-bold mb-1" style="color:var(--primary);">
                        LKR <?php echo number_format($order['total_amount'], 2); ?>
                    </h6>
                    <small class="text-muted">
                        <?php echo strtoupper($order['payment_type']); ?>
                    </small>
                </div>

                <!-- Status -->
                <div class="col-md-2 mb-3 mb-md-0">
                    <?php
                    $status = $order['order_status'];
                    $badge  = match($status) {
                        'pending'          => 'badge-pending',
                        'accepted'         => 'badge-accepted',
                        'preparing'        => 'badge-accepted',
                        'out_for_delivery' => 'badge-accepted',
                        'delivered'        => 'badge-delivered',
                        'cancelled'        => 'badge-cancelled',
                        default            => 'badge-pending'
                    };
                    $label = match($status) {
                        'pending'          => '⏳ Pending',
                        'accepted'         => '✅ Accepted',
                        'preparing'        => '📦 Preparing',
                        'out_for_delivery' => '🚗 On the Way',
                        'delivered'        => '✅ Delivered',
                        'cancelled'        => '❌ Cancelled',
                        default            => $status
                    };
                    ?>
                    <span class="<?php echo $badge; ?>">
                        <?php echo $label; ?>
                    </span>
                </div>

                <!-- Actions -->
                <div class="col-md-2 text-md-end">
                    <a href="/findywearce/customer/track.php?id=<?php echo $order['id']; ?>"
                        class="btn btn-sm btn-primary-custom mb-1 d-block">
                        <i class="fas fa-map-marker-alt me-1"></i>Track
                    </a>
                    <?php if ($order['order_status'] === 'delivered'): ?>
<a href="/findywearce/customer/reviews.php"
    class="btn btn-sm btn-warning d-block mb-1 fw-bold">
    <i class="fas fa-star me-1"></i>Review
</a>
<a href="/findywearce/customer/return.php?id=<?php echo $order['id']; ?>"
    class="btn btn-sm btn-outline-danger d-block">
    <i class="fas fa-undo me-1"></i>Return
</a>
<?php endif; ?>
                </div>

            </div>

            <!-- Order Items Preview -->
            <?php
            $items_stmt = mysqli_prepare($conn, "
                SELECT oi.*, p.name, p.image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
            mysqli_stmt_execute($items_stmt);
            $items = mysqli_stmt_get_result($items_stmt);
            ?>
            <div class="mt-3 pt-3 border-top d-flex gap-2 flex-wrap">
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <div class="d-flex align-items-center gap-2 me-3">
                    <img src="/findywearce/public/images/products/<?php echo $item['image']; ?>"
                        onerror="this.src='https://via.placeholder.com/40/667eea/white?text=Item'"
                        class="rounded"
                        style="width:40px;height:40px;object-fit:cover;">
                    <div>
                        <small class="fw-bold d-block">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </small>
                        <small class="text-muted">
                            x<?php echo $item['quantity']; ?> • 
                            LKR <?php echo number_format($item['price'], 2); ?>
                        </small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

        </div>
        <?php endwhile; ?>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>