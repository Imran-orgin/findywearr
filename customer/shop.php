<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

// Shop ID check
if (!isset($_GET['id'])) {
    header('Location: /findywearce/customer/home.php');
    exit();
}

$shop_id = intval($_GET['id']);

// Shop details fetch
$shop_stmt = mysqli_prepare($conn, "SELECT s.*, u.name as owner_name FROM shops s JOIN users u ON s.owner_id = u.id WHERE s.id = ? AND s.status = 'active'");
mysqli_stmt_bind_param($shop_stmt, "i", $shop_id);
mysqli_stmt_execute($shop_stmt);
$shop_result = mysqli_stmt_get_result($shop_stmt);
$shop = mysqli_fetch_assoc($shop_result);

if (!$shop) {
    header('Location: /findywearce/customer/home.php');
    exit();
}

// Products fetch
$prod_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE shop_id = ? AND status = 'available' ORDER BY created_at DESC");
mysqli_stmt_bind_param($prod_stmt, "i", $shop_id);
mysqli_stmt_execute($prod_stmt);
$products = mysqli_stmt_get_result($prod_stmt);

// Cart add handle
$cart_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);
    $customer_id = $_SESSION['user_id'];

    // Already in cart check
    $check = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($check, "ii", $customer_id, $product_id);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    $existing = mysqli_fetch_assoc($check_result);

    if ($existing) {
        // Update quantity
        $new_qty = $existing['quantity'] + $quantity;
        $update  = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
        mysqli_stmt_bind_param($update, "ii", $new_qty, $existing['id']);
        mysqli_stmt_execute($update);
    } else {
        // Insert new
        $insert = mysqli_prepare($conn, "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insert, "iii", $customer_id, $product_id, $quantity);
        mysqli_stmt_execute($insert);
    }
    $cart_message = 'success';
}
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Back Button -->
    <a href="/findywearce/customer/home.php" class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i>Back to Shops
    </a>

    <!-- Shop Info -->
    <div class="fw-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <img src="/findywearce/public/images/<?php echo $shop['shop_image']; ?>"
                    onerror="this.src='https://via.placeholder.com/100/667eea/white?text=Shop'"
                    class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">
            </div>
            <div class="col-md-7">
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($shop['shop_name']); ?></h3>
                <p class="text-muted mb-1">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($shop['address']); ?>
                </p>
                <p class="text-muted mb-0">
                    <i class="fas fa-phone me-1"></i>
                    <?php echo htmlspecialchars($shop['phone'] ?? 'N/A'); ?>
                </p>
            </div>
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                <a href="/findywearce/customer/cart.php"
                    class="btn btn-primary-custom">
                    <i class="fas fa-shopping-cart me-2"></i>View Cart
                </a>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <?php if ($cart_message === 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        Item added to cart successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Products -->
    <h5 class="fw-bold mb-4">
        <i class="fas fa-tshirt me-2" style="color:var(--primary);"></i>
        Products
    </h5>

    <?php if (mysqli_num_rows($products) === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No products available yet</h5>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($product = mysqli_fetch_assoc($products)): ?>
            <div class="col-md-3 col-sm-6">
                <div class="product-card">
                    <img src="/findywearce/public/images/products/<?php echo $product['image']; ?>"
                        onerror="this.src='https://via.placeholder.com/300x220/667eea/white?text=<?php echo urlencode($product['name']); ?>'"
                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h6>
                        <p class="text-muted small mb-2">
                            <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                        </p>

                        <div class="d-flex gap-2 mb-2">
                            <?php if ($product['size']): ?>
                            <span class="badge bg-light text-dark">
                                Size: <?php echo $product['size']; ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($product['color']): ?>
                            <span class="badge bg-light text-dark">
                                <?php echo $product['color']; ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="price">
                                LKR <?php echo number_format($product['price'], 2); ?>
                            </span>
                            <small class="text-muted">
                                Stock: <?php echo $product['stock']; ?>
                            </small>
                        </div>

                        <!-- Add to Cart Form -->
                        <form method="POST">
                            <input type="hidden" name="product_id"
                                value="<?php echo $product['id']; ?>">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">Qty</span>
                                <input type="number" name="quantity"
                                    class="form-control" value="1"
                                    min="1" max="<?php echo $product['stock']; ?>">
                            </div>
                            <button type="submit" name="add_to_cart"
                                class="btn btn-primary-custom w-100 btn-sm"
                                <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus me-1"></i>
                                <?php echo $product['stock'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                            </button>
                        </form>

                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Shop Reviews -->
<?php
$reviews = mysqli_prepare($conn, "
    SELECT r.*, u.name as customer_name
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    WHERE r.shop_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($reviews, "i", $shop_id);
mysqli_stmt_execute($reviews);
$shop_reviews = mysqli_stmt_get_result($reviews);
$avg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM reviews WHERE shop_id = $shop_id"))['avg'];
?>

<?php if (mysqli_num_rows($shop_reviews) > 0): ?>
<div class="container pb-4">
    <div class="fw-card">
        <h5 class="fw-bold mb-2">
            <i class="fas fa-star me-2" style="color:#f39c12;"></i>
            Reviews
            <small class="text-muted fs-6">
                (<?php echo number_format($avg, 1); ?>/5)
            </small>
        </h5>
        <?php while ($rev = mysqli_fetch_assoc($shop_reviews)): ?>
        <div class="border-bottom pb-3 mb-3">
            <div class="d-flex justify-content-between">
                <strong class="small">
                    <?php echo htmlspecialchars($rev['customer_name']); ?>
                </strong>
                <small class="text-muted">
                    <?php echo date('d M Y', strtotime($rev['created_at'])); ?>
                </small>
            </div>
            <div>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star"
                    style="color:<?php echo $i <= $rev['rating'] ? '#f39c12' : '#ddd'; ?>;font-size:0.8rem;"></i>
                <?php endfor; ?>
            </div>
            <?php if ($rev['comment']): ?>
            <p class="text-muted small mb-0 mt-1">
                "<?php echo htmlspecialchars($rev['comment']); ?>"
            </p>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>