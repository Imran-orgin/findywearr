<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];

// Remove item
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $del = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($del, "ii", $remove_id, $customer_id);
    mysqli_stmt_execute($del);
    header('Location: /findywearce/customer/cart.php');
    exit();
}

// Update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $qty) {
        $cart_id = intval($cart_id);
        $qty     = intval($qty);
        if ($qty > 0) {
            $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
            mysqli_stmt_bind_param($upd, "iii", $qty, $cart_id, $customer_id);
            mysqli_stmt_execute($upd);
        }
    }
    header('Location: /findywearce/customer/cart.php');
    exit();
}

// Fetch cart items
$stmt = mysqli_prepare($conn, "
    SELECT c.id as cart_id, c.quantity, 
           p.id as product_id, p.name, p.price, p.image, p.stock, p.size, p.color,
           s.id as shop_id, s.shop_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN shops s ON p.shop_id = s.id
    WHERE c.customer_id = ?
    ORDER BY s.shop_name, c.added_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cart_items = [];
$total      = 0;
$shop_id    = null;

while ($row = mysqli_fetch_assoc($result)) {
    $cart_items[] = $row;
    $total       += $row['price'] * $row['quantity'];
    $shop_id      = $row['shop_id'];
}
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <h4 class="fw-bold mb-4">
        <i class="fas fa-shopping-cart me-2" style="color:var(--primary);"></i>
        My Cart
    </h4>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Your cart is empty!</h5>
            <a href="/findywearce/customer/home.php" class="btn btn-primary-custom mt-3">
                <i class="fas fa-store me-2"></i>Browse Shops
            </a>
        </div>

    <?php else: ?>
        <form method="POST">
        <div class="row g-4">

            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="fw-card">

                    <!-- Shop Name -->
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                        <i class="fas fa-store me-2" style="color:var(--primary);"></i>
                        <h6 class="fw-bold mb-0">
                            <?php echo htmlspecialchars($cart_items[0]['shop_name']); ?>
                        </h6>
                    </div>

                    <?php foreach ($cart_items as $item): ?>
                    <div class="row align-items-center mb-4 pb-3 border-bottom">

                        <!-- Product Image -->
                        <div class="col-3 col-md-2">
                            <img src="/findywearce/public/images/products/<?php echo $item['image']; ?>"
                                onerror="this.src='https://via.placeholder.com/80/667eea/white?text=Item'"
                                class="rounded" style="width:70px;height:70px;object-fit:cover;">
                        </div>

                        <!-- Product Info -->
                        <div class="col-5 col-md-5">
                            <h6 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h6>
                            <small class="text-muted">
                                <?php if ($item['size']): ?>
                                    Size: <?php echo $item['size']; ?> &nbsp;
                                <?php endif; ?>
                                <?php if ($item['color']): ?>
                                    Color: <?php echo $item['color']; ?>
                                <?php endif; ?>
                            </small>
                            <div class="price mt-1">
                                LKR <?php echo number_format($item['price'], 2); ?>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-2 col-md-3">
                            <input type="number"
                                name="quantities[<?php echo $item['cart_id']; ?>]"
                                class="form-control form-control-sm text-center"
                                value="<?php echo $item['quantity']; ?>"
                                min="1"
                                max="<?php echo $item['stock']; ?>">
                        </div>

                        <!-- Remove -->
                        <div class="col-2 col-md-2 text-end">
                            <a href="/findywearce/customer/cart.php?remove=<?php echo $item['cart_id']; ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Remove this item?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>

                    <!-- Update Cart Button -->
                    <button type="submit" name="update_cart"
                        class="btn btn-outline-secondary">
                        <i class="fas fa-sync me-2"></i>Update Cart
                    </button>

                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="fw-card">
                    <h5 class="fw-bold mb-4">Order Summary</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span>LKR <?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Delivery</span>
                        <span class="text-success fw-bold">FREE</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold fs-5" style="color:var(--primary);">
                            LKR <?php echo number_format($total, 2); ?>
                        </span>
                    </div>

                    <a href="/findywearce/customer/checkout.php"
                        class="btn btn-primary-custom w-100">
                        <i class="fas fa-credit-card me-2"></i>
                        Proceed to Checkout
                    </a>

                    <a href="/findywearce/customer/home.php"
                        class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-arrow-left me-2"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>

        </div>
        </form>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>