<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

// Stats
// Total users
$users_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role = 'customer'");
$total_customers = mysqli_fetch_assoc($users_stmt)['t'];

// Total shop owners
$owners_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role = 'shop_owner'");
$total_owners = mysqli_fetch_assoc($owners_stmt)['t'];

// Total shops
$shops_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM shops");
$total_shops = mysqli_fetch_assoc($shops_stmt)['t'];

// Pending shops
$pending_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM shops WHERE status = 'pending'");
$pending_shops = mysqli_fetch_assoc($pending_stmt)['t'];

// Total orders
$orders_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM orders");
$total_orders = mysqli_fetch_assoc($orders_stmt)['t'];

// Total revenue
$revenue_stmt = mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE order_status = 'delivered'");
$total_revenue = mysqli_fetch_assoc($revenue_stmt)['t'] ?? 0;

// Total products
$products_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM products");
$total_products = mysqli_fetch_assoc($products_stmt)['t'];

// Commission rate
$comm_result = mysqli_query($conn, "SELECT percentage FROM commission_settings WHERE id = 1");
$comm_rate   = mysqli_fetch_assoc($comm_result)['percentage'] ?? 10;

// Total returns
$returns_stmt = mysqli_query($conn, "SELECT COUNT(*) as t FROM returns WHERE status = 'pending'");
$pending_returns = mysqli_fetch_assoc($returns_stmt)['t'];

// Recent orders
$recent_orders = mysqli_query($conn, "
    SELECT o.*, u.name as customer_name, s.shop_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    JOIN shops s ON o.shop_id = s.id
    ORDER BY o.created_at DESC
    LIMIT 8
");

// Recent shops
$recent_shops = mysqli_query($conn, "
    SELECT s.*, u.name as owner_name
    FROM shops s
    JOIN users u ON s.owner_id = u.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Admin Header -->
    <div class="fw-card mb-4"
        style="background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="fw-bold mb-1">
                    <i class="fas fa-cog me-2"></i>Admin Dashboard
                </h4>
                <p class="mb-0 opacity-75">
                    FindyWear System Overview
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <small class="opacity-75">
                    <i class="fas fa-clock me-1"></i>
                    <?php echo date('d M Y, h:i A'); ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number"><?php echo $total_customers; ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:var(--warning);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--warning);">
                            <?php echo $total_shops; ?>
                        </div>
                        <div class="stat-label">Total Shops</div>
                    </div>
                    <i class="fas fa-store" style="color:var(--warning);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:var(--success);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--success);">
                            <?php echo $total_orders; ?>
                        </div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <i class="fas fa-shopping-bag" style="color:var(--success);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:var(--danger);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--danger);">
                            LKR <?php echo number_format($total_revenue, 0); ?>
                        </div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <i class="fas fa-dollar-sign" style="color:var(--danger);"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a href="/findywearce/admin/shops.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-store fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Manage Shops</p>
                <?php if ($pending_shops > 0): ?>
                <span class="badge bg-danger"><?php echo $pending_shops; ?> pending</span>
                <?php endif; ?>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/findywearce/admin/users.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-users fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Manage Users</p>
                <small class="text-muted"><?php echo $total_customers + $total_owners; ?> users</small>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/findywearce/admin/orders.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-list fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">All Orders</p>
                <small class="text-muted"><?php echo $total_orders; ?> orders</small>
            </a>
        </div>
        <div class="col-md-3 col-6">
    <a href="/findywearce/admin/commission.php"
        class="fw-card text-center d-block text-decoration-none">
        <i class="fas fa-percent fa-2x mb-2" style="color:var(--primary);"></i>
        <p class="fw-bold mb-0">Commission</p>
        <small class="text-muted">LKR <?php echo number_format($total_revenue * ($comm_rate/100), 0); ?></small>
    </a>
</div>
        <div class="col-md-3 col-6">
            <a href="/findywearce/admin/returns.php"
                class="fw-card text-center d-block text-decoration-none">
                <i class="fas fa-undo fa-2x mb-2" style="color:var(--primary);"></i>
                <p class="fw-bold mb-0">Returns</p>
                <?php if ($pending_returns > 0): ?>
                <span class="badge bg-danger"><?php echo $pending_returns; ?> pending</span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Recent Orders -->
        <div class="col-lg-7">
            <div class="fw-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-clock me-2" style="color:var(--primary);"></i>
                        Recent Orders
                    </h5>
                    <a href="/findywearce/admin/orders.php"
                        class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Shop</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td><strong><?php echo $order['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['shop_name']); ?></td>
                                <td>LKR <?php echo number_format($order['total_amount'], 0); ?></td>
                                <td>
                                    <?php
                                    $badge = match($order['order_status']) {
                                        'pending'  => 'badge-pending',
                                        'accepted','preparing','out_for_delivery' => 'badge-accepted',
                                        'delivered' => 'badge-delivered',
                                        'cancelled' => 'badge-cancelled',
                                        default    => 'badge-pending'
                                    };
                                    ?>
                                    <span class="<?php echo $badge; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Shops -->
        <div class="col-lg-5">
            <div class="fw-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-store me-2" style="color:var(--primary);"></i>
                        Recent Shops
                    </h5>
                    <a href="/findywearce/admin/shops.php"
                        class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php while ($shop = mysqli_fetch_assoc($recent_shops)): ?>
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:45px;height:45px;
                        background:linear-gradient(135deg,var(--primary),var(--secondary));">
                        <i class="fas fa-store text-white"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="fw-bold mb-0 small">
                            <?php echo htmlspecialchars($shop['shop_name']); ?>
                        </p>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($shop['owner_name']); ?>
                        </small>
                    </div>
                    <span class="badge bg-<?php echo $shop['status'] === 'active' ? 'success' : ($shop['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <?php echo ucfirst($shop['status'] ?? 'unknown'); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>