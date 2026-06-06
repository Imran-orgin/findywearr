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

// Order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id  = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];

    $allowed = ['accepted', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        // Update order status
        $upd = mysqli_prepare($conn, "UPDATE orders SET order_status = ? WHERE id = ? AND shop_id = ?");
        mysqli_stmt_bind_param($upd, "sii", $new_status, $order_id, $shop_id);
        mysqli_stmt_execute($upd);

        // Add tracking
        $desc = match($new_status) {
            'accepted'         => 'Order accepted by shop',
            'preparing'        => 'Order is being prepared',
            'out_for_delivery' => 'Order is out for delivery',
            'delivered'        => 'Order delivered successfully',
            'cancelled'        => 'Order cancelled by shop',
            default            => 'Status updated'
        };

        $track = mysqli_prepare($conn, "INSERT INTO order_tracking (order_id, status, description) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($track, "iss", $order_id, $new_status, $desc);
        mysqli_stmt_execute($track);

        // Notify customer
        $order_info = mysqli_prepare($conn, "SELECT customer_id FROM orders WHERE id = ?");
        mysqli_stmt_bind_param($order_info, "i", $order_id);
        mysqli_stmt_execute($order_info);
        $order_data = mysqli_fetch_assoc(mysqli_stmt_get_result($order_info));

        $notif_msg  = "Your order #" . $order_id . " status: " . ucfirst(str_replace('_', ' ', $new_status));
        $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($notif_stmt, "is", $order_data['customer_id'], $notif_msg);
        mysqli_stmt_execute($notif_stmt);

        $success = 'Order #' . $order_id . ' status updated!';
    }
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = "o.shop_id = ?";
if ($filter !== 'all') {
    $where .= " AND o.order_status = '$filter'";
}

// Fetch orders
$stmt = mysqli_prepare($conn, "
    SELECT o.*, u.name as customer_name, u.phone as customer_phone,
           u.email as customer_email,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    WHERE $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $shop_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-list me-2" style="color:var(--primary);"></i>
            Manage Orders
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

    <!-- Filter Tabs -->
    <div class="fw-card mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <?php
            $filters = [
                'all'              => ['All Orders', 'secondary'],
                'pending'          => ['Pending', 'warning'],
                'accepted'         => ['Accepted', 'primary'],
                'preparing'        => ['Preparing', 'info'],
                'out_for_delivery' => ['Out for Delivery', 'primary'],
                'delivered'        => ['Delivered', 'success'],
                'cancelled'        => ['Cancelled', 'danger'],
            ];
            foreach ($filters as $key => [$label, $color]):
            ?>
            <a href="?filter=<?php echo $key; ?>"
                class="btn btn-sm btn-<?php echo $filter === $key ? $color : 'outline-'.$color; ?>">
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Orders -->
    <?php if (mysqli_num_rows($orders) === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No orders found!</h5>
        </div>
    <?php else: ?>
        <?php while ($order = mysqli_fetch_assoc($orders)): ?>

        <!-- Fetch order items -->
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

        <div class="fw-card mb-4">
            <div class="row align-items-start">

                <!-- Order Info -->
                <div class="col-md-4 mb-3">
                    <h6 class="fw-bold mb-1">
                        Order #<?php echo $order['id']; ?>
                    </h6>
                    <small class="text-muted d-block mb-2">
                        <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                    </small>

                    <!-- Customer -->
                    <div class="p-2 rounded mb-2" style="background:#f8f9ff;">
                        <small class="fw-bold d-block">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($order['customer_name']); ?>
                        </small>
                        <small class="text-muted">
                            <?php echo $order['customer_phone']; ?>
                        </small>
                    </div>

                    <!-- Delivery Address -->
                    <small class="text-muted">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?php echo htmlspecialchars($order['delivery_address']); ?>
                    </small>
                </div>

                <!-- Items -->
                <div class="col-md-4 mb-3">
                    <small class="text-muted fw-bold d-block mb-2">
                        ITEMS (<?php echo $order['item_count']; ?>)
                    </small>
                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
                    <div class="d-flex align-items-center mb-2">
                        <img src="/findywearce/public/images/products/<?php echo $item['image']; ?>"
                            onerror="this.src='https://placehold.co/35x35/667eea/white?text=P'"
                            class="rounded me-2"
                            style="width:35px;height:35px;object-fit:cover;">
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

                    <div class="fw-bold mt-2" style="color:var(--primary);">
                        Total: LKR <?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                    <small class="badge bg-secondary">
                        <?php echo strtoupper($order['payment_type']); ?>
                    </small>
                </div>

                <!-- Status & Action -->
                <div class="col-md-4">
                    <?php
                    $badge = match($order['order_status']) {
                        'pending'          => 'badge-pending',
                        'accepted'         => 'badge-accepted',
                        'preparing'        => 'badge-accepted',
                        'out_for_delivery' => 'badge-accepted',
                        'delivered'        => 'badge-delivered',
                        'cancelled'        => 'badge-cancelled',
                        default            => 'badge-pending'
                    };
                    ?>
                    <span class="<?php echo $badge; ?> d-block mb-3 text-center">
                        <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                    </span>

                    <?php if (!in_array($order['order_status'], ['delivered', 'cancelled'])): ?>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="new_status" class="form-control mb-2">
                            <?php
                            $next_statuses = match($order['order_status']) {
                                'pending'          => ['accepted' => 'Accept Order', 'cancelled' => 'Cancel Order'],
                                'accepted'         => ['preparing' => 'Start Preparing'],
                                'preparing'        => ['out_for_delivery' => 'Out for Delivery'],
                                'out_for_delivery' => ['delivered' => 'Mark Delivered'],
                                default            => []
                            };
                            foreach ($next_statuses as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status"
                            class="btn btn-primary-custom w-100">
                            <i class="fas fa-check me-2"></i>Update Status
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <p class="small">Order Completed</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>