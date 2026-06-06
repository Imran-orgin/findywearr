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
$error   = '';

// Stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    foreach ($_POST['stocks'] as $product_id => $new_stock) {
        $product_id = intval($product_id);
        $new_stock  = intval($new_stock);
        $status     = $new_stock > 0 ? 'available' : 'out_of_stock';

        $upd = mysqli_prepare($conn, "UPDATE products SET stock = ?, status = ? WHERE id = ? AND shop_id = ?");
        mysqli_stmt_bind_param($upd, "isii", $new_stock, $status, $product_id, $shop_id);
        mysqli_stmt_execute($upd);
    }
    $success = 'Stock updated successfully!';
}

// Fetch products
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE shop_id = ? ORDER BY stock ASC");
mysqli_stmt_bind_param($stmt, "i", $shop_id);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

// Count stats
$total_stmt    = mysqli_prepare($conn, "SELECT COUNT(*) as t FROM products WHERE shop_id = ?");
mysqli_stmt_bind_param($total_stmt, "i", $shop_id);
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['t'];

$low_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as t FROM products WHERE shop_id = ? AND stock < 5 AND stock > 0");
mysqli_stmt_bind_param($low_stmt, "i", $shop_id);
mysqli_stmt_execute($low_stmt);
$low = mysqli_fetch_assoc(mysqli_stmt_get_result($low_stmt))['t'];

$out_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as t FROM products WHERE shop_id = ? AND stock = 0");
mysqli_stmt_bind_param($out_stmt, "i", $shop_id);
mysqli_stmt_execute($out_stmt);
$out = mysqli_fetch_assoc(mysqli_stmt_get_result($out_stmt))['t'];
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-boxes me-2" style="color:var(--primary);"></i>
            Stock Management
        </h4>
        <a href="/findywearce/shop-owner/dashboard.php"
            class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <!-- Stock Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number"><?php echo $total; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <i class="fas fa-tshirt"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-color:var(--warning);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--warning);">
                            <?php echo $low; ?>
                        </div>
                        <div class="stat-label">Low Stock (< 5)</div>
                    </div>
                    <i class="fas fa-exclamation-triangle" 
                        style="color:var(--warning);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-color:var(--danger);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-number" style="color:var(--danger);">
                            <?php echo $out; ?>
                        </div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                    <i class="fas fa-times-circle" style="color:var(--danger);"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
    <?php endif; ?>

    <!-- Stock Table -->
    <div class="fw-card">
        <h5 class="fw-bold mb-4">
            <i class="fas fa-edit me-2" style="color:var(--primary);"></i>
            Update Stock
        </h5>

        <?php if (mysqli_num_rows($products) === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <p class="text-muted">No products found!</p>
                <a href="/findywearce/shop-owner/products.php"
                    class="btn btn-primary-custom">Add Products</a>
            </div>
        <?php else: ?>
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                            <th>New Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <tr class="<?php echo $product['stock'] == 0 ? 'table-danger' : ($product['stock'] < 5 ? 'table-warning' : ''); ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="/findywearce/public/images/products/<?php echo $product['image']; ?>"
                                        onerror="this.src='https://placehold.co/40x40/667eea/white?text=P'"
                                        class="rounded me-2"
                                        style="width:40px;height:40px;object-fit:cover;">
                                    <div>
                                        <p class="fw-bold mb-0 small">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo $product['size'] ? 'Size: '.$product['size'] : ''; ?>
                                            <?php echo $product['color'] ? '• '.$product['color'] : ''; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <small class="badge bg-light text-dark">
                                    <?php echo ucfirst($product['category'] ?? 'N/A'); ?>
                                </small>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo $product['stock'] == 0 ? 'text-danger' : ($product['stock'] < 5 ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['stock'] == 0): ?>
                                    <span class="badge-cancelled">Out of Stock</span>
                                <?php elseif ($product['stock'] < 5): ?>
                                    <span class="badge-pending">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge-delivered">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td style="width:150px;">
                                <input type="number"
                                    name="stocks[<?php echo $product['id']; ?>]"
                                    class="form-control form-control-sm"
                                    value="<?php echo $product['stock']; ?>"
                                    min="0">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button type="submit" name="update_stock"
                    class="btn btn-primary-custom px-5">
                    <i class="fas fa-save me-2"></i>Save Stock
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>