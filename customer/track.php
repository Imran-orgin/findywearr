<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: /findywearce/customer/orders.php');
    exit();
}

$order_id = intval($_GET['id']);

// Fetch order details
$stmt = mysqli_prepare($conn, "
    SELECT o.*, s.shop_name, s.address as shop_address, 
           s.phone as shop_phone, s.latitude as shop_lat,
           s.longitude as shop_lng
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    WHERE o.id = ? AND o.customer_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: /findywearce/customer/orders.php');
    exit();
}

// Fetch tracking history
$track_stmt = mysqli_prepare($conn, "
    SELECT * FROM order_tracking 
    WHERE order_id = ? 
    ORDER BY updated_at ASC
");
mysqli_stmt_bind_param($track_stmt, "i", $order_id);
mysqli_stmt_execute($track_stmt);
$tracking = mysqli_stmt_get_result($track_stmt);
$track_history = [];
while ($row = mysqli_fetch_assoc($tracking)) {
    $track_history[] = $row;
}

// Fetch order items
$items_stmt = mysqli_prepare($conn, "
    SELECT oi.*, p.name, p.image, p.price as unit_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_stmt_get_result($items_stmt);

// Status steps
$all_steps = [
    'pending'          => ['icon' => 'fa-clock',            'label' => 'Order Placed'],
    'accepted'         => ['icon' => 'fa-check-circle',     'label' => 'Order Accepted'],
    'preparing'        => ['icon' => 'fa-box',              'label' => 'Preparing'],
    'out_for_delivery' => ['icon' => 'fa-motorcycle',       'label' => 'Out for Delivery'],
    'delivered'        => ['icon' => 'fa-home',             'label' => 'Delivered'],
];

$current_status = $order['order_status'];
$step_keys      = array_keys($all_steps);
$current_index  = array_search($current_status, $step_keys);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Back -->
    <a href="/findywearce/customer/orders.php" 
        class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i>Back to Orders
    </a>

    <div class="row g-4">

        <!-- Left: Tracking -->
        <div class="col-lg-7">

            <!-- Order Header -->
            <div class="fw-card mb-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold mb-1">Order #<?php echo $order['id']; ?></h5>
                        <small class="text-muted">
                            Placed on <?php echo date('d M Y, h:i A', 
                                strtotime($order['created_at'])); ?>
                        </small>
                    </div>
                    <?php if ($current_status === 'cancelled'): ?>
                        <span class="badge-cancelled">❌ Cancelled</span>
                    <?php else: ?>
                        <span class="badge-accepted">
                            🚀 Expected: Within 1 Day
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Progress Steps -->
            <?php if ($current_status !== 'cancelled'): ?>
            <div class="fw-card mb-4">
                <h6 class="fw-bold mb-4">Order Progress</h6>
                <div class="d-flex justify-content-between align-items-center 
                            position-relative mb-2">
                    <!-- Progress Line -->
                    <div style="position:absolute; top:20px; left:10%; 
                                right:10%; height:3px; background:#e9ecef; z-index:0;">
                        <div style="height:100%; background:linear-gradient(
                            90deg, var(--primary), var(--secondary));
                            width:<?php echo min(($current_index / 
                            (count($all_steps)-1)) * 100, 100); ?>%;
                            transition: width 0.5s ease;">
                        </div>
                    </div>

                    <?php foreach ($all_steps as $key => $step): 
                        $idx       = array_search($key, $step_keys);
                        $completed = $idx < $current_index;
                        $active    = $idx === $current_index;
                    ?>
                    <div class="text-center" style="z-index:1; flex:1;">
                        <div class="mx-auto d-flex align-items-center 
                                    justify-content-center rounded-circle mb-2"
                            style="width:40px; height:40px;
                                background:<?php echo ($completed || $active) ? 
                                    'linear-gradient(135deg,var(--primary),var(--secondary))' : 
                                    '#e9ecef'; ?>;
                                color:<?php echo ($completed || $active) ? 
                                    'white' : '#aaa'; ?>;">
                            <i class="fas <?php echo $step['icon']; ?> 
                                fa-sm"></i>
                        </div>
                        <small style="font-size:0.7rem; 
                            color:<?php echo ($completed || $active) ? 
                                'var(--primary)' : '#aaa'; ?>;
                            font-weight:<?php echo $active ? '700' : '400'; ?>;">
                            <?php echo $step['label']; ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tracking Timeline -->
            <div class="fw-card mb-4">
                <h6 class="fw-bold mb-4">Tracking History</h6>
                <div class="tracking-timeline">
                    <?php foreach (array_reverse($track_history) as $track): ?>
                    <div class="tracking-step completed">
                        <div class="step-dot"></div>
                        <div class="ms-2">
                            <p class="fw-bold mb-0">
                                <?php echo ucwords(str_replace('_', ' ', 
                                    $track['status'])); ?>
                            </p>
                            <small class="text-muted">
                                <?php echo $track['description']; ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <?php echo date('d M Y, h:i A', 
                                    strtotime($track['updated_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Right: Order Details -->
        <div class="col-lg-5">

            <!-- Shop Info -->
            <div class="fw-card mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-store me-2" style="color:var(--primary);"></i>
                    Shop Details
                </h6>
                <p class="fw-bold mb-1">
                    <?php echo htmlspecialchars($order['shop_name']); ?>
                </p>
                <p class="text-muted small mb-1">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($order['shop_address']); ?>
                </p>
                <p class="text-muted small mb-0">
                    <i class="fas fa-phone me-1"></i>
                    <?php echo htmlspecialchars($order['shop_phone'] ?? 'N/A'); ?>
                </p>
            </div>

            <!-- Delivery Address -->
            <div class="fw-card mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-map-pin me-2" style="color:var(--primary);"></i>
                    Delivery Address
                </h6>
                <p class="text-muted mb-0">
                    <?php echo htmlspecialchars($order['delivery_address']); ?>
                </p>
            </div>

            <!-- Order Items -->
            <div class="fw-card mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-box me-2" style="color:var(--primary);"></i>
                    Order Items
                </h6>
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <div class="d-flex align-items-center mb-3">
                    <img src="/findywearce/public/images/products/
                        <?php echo $item['image']; ?>"
                        onerror="this.src='https://via.placeholder.com/50/667eea/white?text=Item'"
                        class="rounded me-3"
                        style="width:50px;height:50px;object-fit:cover;">
                    <div class="flex-grow-1">
                        <p class="fw-bold mb-0 small">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </p>
                        <small class="text-muted">
                            x<?php echo $item['quantity']; ?> × 
                            LKR <?php echo number_format($item['unit_price'], 2); ?>
                        </small>
                    </div>
                    <span class="fw-bold small">
                        LKR <?php echo number_format(
                            $item['unit_price'] * $item['quantity'], 2); ?>
                    </span>
                </div>
                <?php endwhile; ?>

                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Total</span>
                    <span class="fw-bold" style="color:var(--primary);">
                        LKR <?php echo number_format($order['total_amount'], 2); ?>
                    </span>
                </div>
            </div>

            <!-- Return Button -->
            <?php if ($order['order_status'] === 'delivered'): ?>
            <a href="/findywearce/customer/return.php?id=<?php echo $order['id']; ?>"
                class="btn btn-outline-danger w-100">
                <i class="fas fa-undo me-2"></i>Request Return
            </a>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>