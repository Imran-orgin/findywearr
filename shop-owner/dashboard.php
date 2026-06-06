<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];

// Shop details fetch
$shop_stmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE owner_id = ?");
mysqli_stmt_bind_param($shop_stmt, "i", $owner_id);
mysqli_stmt_execute($shop_stmt);
$shop = mysqli_fetch_assoc(mysqli_stmt_get_result($shop_stmt));

if (!$shop) {
    header('Location: /findywearce/shop-owner/setup-shop.php');
    exit();
}

$shop_id = $shop['id'];

// Stats fetch
// Total orders
$orders_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM orders WHERE shop_id = ?");
mysqli_stmt_bind_param($orders_stmt, "i", $shop_id);
mysqli_stmt_execute($orders_stmt);
$total_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($orders_stmt))['total'];

// Pending orders
$pending_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM orders WHERE shop_id = ? AND order_status = 'pending'");
mysqli_stmt_bind_param($pending_stmt, "i", $shop_id);
mysqli_stmt_execute($pending_stmt);
$pending_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($pending_stmt))['total'];

// Total revenue
$revenue_stmt = mysqli_prepare($conn, "SELECT SUM(total_amount) as total FROM orders WHERE shop_id = ? AND order_status = 'delivered'");
mysqli_stmt_bind_param($revenue_stmt, "i", $shop_id);
mysqli_stmt_execute($revenue_stmt);
$total_revenue = mysqli_fetch_assoc(mysqli_stmt_get_result($revenue_stmt))['total'] ?? 0;

// Total products
$products_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM products WHERE shop_id = ?");
mysqli_stmt_bind_param($products_stmt, "i", $shop_id);
mysqli_stmt_execute($products_stmt);
$total_products = mysqli_fetch_assoc(mysqli_stmt_get_result($products_stmt))['total'];

// Low stock products (stock < 5)
$lowstock_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM products WHERE shop_id = ? AND stock < 5");
mysqli_stmt_bind_param($lowstock_stmt, "i", $shop_id);
mysqli_stmt_execute($lowstock_stmt);
$low_stock = mysqli_fetch_assoc(mysqli_stmt_get_result($lowstock_stmt))['total'];

// Recent orders
$recent_stmt = mysqli_prepare($conn, "
    SELECT o.*, u.name as customer_name, u.phone as customer_phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.shop_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($recent_stmt, "i", $shop_id);
mysqli_stmt_execute($recent_stmt);
$recent_orders = mysqli_stmt_get_result($recent_stmt);

// Pending returns
$returns_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM returns r JOIN orders o ON r.order_id = o.id WHERE o.shop_id = ? AND r.status = 'pending'");
mysqli_stmt_bind_param($returns_stmt, "i", $shop_id);
mysqli_stmt_execute($returns_stmt);
$pending_returns = mysqli_fetch_assoc(mysqli_stmt_get_result($returns_stmt))['total'];
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Shop Header -->
    <div class="fw-card mb-4" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="fw-bold mb-1">
                    <i class="fas fa-store me-2"></i>
                    <?php echo htmlspecialchars($shop['shop_name']); ?>
                </h4>
                <p class="mb-1 opacity-75">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($shop['address']); ?>
                </p>
                <span class="badge bg-success">
                    <i class="fas fa-circle me-1"></i>Active
                </span>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/findywearce/shop-owner/products.php"
                    class="btn btn-warning fw-bold">
                    <i class="fas fa-plus me-1"></i>Add Product
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">

        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color: var(--warning);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color: var(--warning);">
                            <?php echo $pending_orders; ?>
                        </div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                    <i class="fas fa-clock" style="color: var(--warning);"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color: var(--success);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color: var(--success);">
                            <?php echo number_format($total_revenue, 0); ?>
                        </div>
                        <div class="stat-label">Revenue (LKR)</div>
                    </div>
                    <i class="fas fa-dollar-sign" style="color: var(--success);"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color: var(--danger);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color: var(--danger);">
                            <?php echo $low_stock; ?>
                        </div>
                        <div class="stat-label">Low Stock</div>
                    </div>
                    <i class="fas fa-exclamation-triangle" 
                        style="color: var(--danger);"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a href="/findywearce/shop-owner/orders.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-list fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Orders</p>
                <?php if ($pending_orders > 0): ?>
                <span class="badge bg-danger"><?php echo $pending_orders; ?> pending</span>
                <?php endif; ?>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/findywearce/shop-owner/products.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-tshirt fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Products</p>
                <small class="text-muted"><?php echo $total_products; ?> items</small>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/findywearce/shop-owner/stock.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-boxes fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Stock</p>
                <?php if ($low_stock > 0): ?>
                <span class="badge bg-warning text-dark"><?php echo $low_stock; ?> low</span>
                <?php endif; ?>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/findywearce/shop-owner/returns.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-undo fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Returns</p>
                <?php if ($pending_returns > 0): ?>
                <span class="badge bg-danger"><?php echo $pending_returns; ?> pending</span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="fw-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-clock me-2" style="color:var(--primary);"></i>
                Recent Orders
            </h5>
            <a href="/findywearce/shop-owner/orders.php"
                class="btn btn-sm btn-outline-primary">View All</a>
        </div>

        <?php if (mysqli_num_rows($recent_orders) === 0): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                <p class="text-muted">No orders yet!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo $order['customer_phone']; ?>
                                </small>
                            </td>
                            <td>LKR <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo strtoupper($order['payment_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $badge = match($order['order_status']) {
                                    'pending'          => 'badge-pending',
                                    'accepted'         => 'badge-accepted',
                                    'delivered'        => 'badge-delivered',
                                    'cancelled'        => 'badge-cancelled',
                                    default            => 'badge-pending'
                                };
                                ?>
                                <span class="<?php echo $badge; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="/findywearce/shop-owner/orders.php?id=<?php echo $order['id']; ?>"
                                    class="btn btn-sm btn-primary-custom">
                                    Manage
                                </a>
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