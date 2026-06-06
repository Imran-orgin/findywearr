<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE o.order_status = '$filter'" : "";

// Fetch orders
$orders = mysqli_query($conn, "
    SELECT o.*, 
           u.name as customer_name, u.phone as customer_phone,
           s.shop_name,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    JOIN shops s ON o.shop_id = s.id
    JOIN order_items oi ON o.id = oi.order_id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

// Stats
$total_stmt     = mysqli_query($conn, "SELECT COUNT(*) as t FROM orders");
$total          = mysqli_fetch_assoc($total_stmt)['t'];

$pending_stmt   = mysqli_query($conn, "SELECT COUNT(*) as t FROM orders WHERE order_status='pending'");
$pending        = mysqli_fetch_assoc($pending_stmt)['t'];

$delivered_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM orders WHERE order_status='delivered'");
$delivered      = mysqli_fetch_assoc($delivered_stmt)['t'];

$revenue_stmt   = mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE order_status='delivered'");
$revenue        = mysqli_fetch_assoc($revenue_stmt)['t'] ?? 0;
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-list me-2" style="color:var(--primary);"></i>
            All Orders
        </h4>
        <a href="/findywearce/admin/dashboard.php"
            class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number"><?php echo $total; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:var(--warning);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--warning);">
                            <?php echo $pending; ?>
                        </div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <i class="fas fa-clock" style="color:var(--warning);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:var(--success);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--success);">
                            <?php echo $delivered; ?>
                        </div>
                        <div class="stat-label">Delivered</div>
                    </div>
                    <i class="fas fa-check-circle" style="color:var(--success);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:var(--primary);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--primary);">
                            <?php echo number_format($revenue, 0); ?>
                        </div>
                        <div class="stat-label">Revenue (LKR)</div>
                    </div>
                    <i class="fas fa-dollar-sign" style="color:var(--primary);"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="fw-card mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <?php
            $filters = [
                'all'              => ['All', 'secondary'],
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

    <!-- Orders Table -->
    <div class="fw-card">
        <?php if (mysqli_num_rows($orders) === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No orders found!</h5>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Shop</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td>
                            <strong>#<?php echo $order['id']; ?></strong>
                        </td>
                        <td>
                            <p class="fw-bold mb-0 small">
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </p>
                            <small class="text-muted">
                                <?php echo $order['customer_phone']; ?>
                            </small>
                        </td>
                        <td>
                            <small class="fw-bold">
                                <?php echo htmlspecialchars($order['shop_name']); ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?php echo $order['item_count']; ?> items
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold" style="color:var(--primary);">
                                LKR <?php echo number_format($order['total_amount'], 0); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $order['payment_type'] === 'cod' ? 'secondary' : 'success'; ?>">
                                <?php echo strtoupper($order['payment_type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $badge = match($order['order_status']) {
                                'pending'          => 'badge-pending',
                                'accepted',
                                'preparing',
                                'out_for_delivery' => 'badge-accepted',
                                'delivered'        => 'badge-delivered',
                                'cancelled'        => 'badge-cancelled',
                                default            => 'badge-pending'
                            };
                            $label = match($order['order_status']) {
                                'pending'          => '⏳ Pending',
                                'accepted'         => '✅ Accepted',
                                'preparing'        => '📦 Preparing',
                                'out_for_delivery' => '🚗 On Way',
                                'delivered'        => '✅ Delivered',
                                'cancelled'        => '❌ Cancelled',
                                default            => $order['order_status']
                            };
                            ?>
                            <span class="<?php echo $badge; ?>">
                                <?php echo $label; ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                            </small>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>