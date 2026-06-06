<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = mysqli_prepare($conn, "
    SELECT c.id as cart_id, c.quantity,
           p.id as product_id, p.name, p.price, p.image, p.stock,
           s.id as shop_id, s.shop_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN shops s ON p.shop_id = s.id
    WHERE c.customer_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result     = mysqli_stmt_get_result($stmt);
$cart_items = [];
$total      = 0;
$shop_id    = null;

while ($row = mysqli_fetch_assoc($result)) {
    $cart_items[] = $row;
    $total       += $row['price'] * $row['quantity'];
    $shop_id      = $row['shop_id'];
}

if (empty($cart_items)) {
    header('Location: /findywearce/customer/cart.php');
    exit();
}

// Fetch commission rate
$comm_result = mysqli_query($conn, "SELECT percentage FROM commission_settings WHERE id = 1");
$comm_rate   = mysqli_fetch_assoc($comm_result)['percentage'] ?? 10;

// Calculate commission
$commission_amount = $total * ($comm_rate / 100);
$shop_amount       = $total - $commission_amount;

// Fetch customer info
$user_stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $customer_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

$error = '';

// Place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $payment_type     = $_POST['payment_type'];
    $notes            = trim($_POST['notes']);

    if (empty($delivery_address)) {
        $error = 'Please enter delivery address!';
    } else {
        // Insert order
        $order_stmt = mysqli_prepare($conn, "
            INSERT INTO orders (customer_id, shop_id, total_amount, payment_type, delivery_address, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($order_stmt, "iidsss",
            $customer_id, $shop_id, $total, $payment_type, $delivery_address, $notes);

        if (mysqli_stmt_execute($order_stmt)) {
            $order_id = mysqli_insert_id($conn);

            // Insert order items + update stock
            foreach ($cart_items as $item) {
                $item_stmt = mysqli_prepare($conn, "
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($item_stmt, "iiid",
                    $order_id, $item['product_id'], $item['quantity'], $item['price']);
                mysqli_stmt_execute($item_stmt);

                // Update stock
                $stock_stmt = mysqli_prepare($conn, "
                    UPDATE products SET stock = stock - ? WHERE id = ?
                ");
                mysqli_stmt_bind_param($stock_stmt, "ii", $item['quantity'], $item['product_id']);
                mysqli_stmt_execute($stock_stmt);
            }

            // Insert commission record
            $comm_stmt = mysqli_prepare($conn, "
                INSERT INTO commissions 
                (order_id, shop_id, total_amount, commission_rate, commission_amount, shop_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($comm_stmt, "iidddd",
                $order_id, $shop_id, $total, $comm_rate, $commission_amount, $shop_amount);
            mysqli_stmt_execute($comm_stmt);

            // Insert payment record
            $pay_stmt = mysqli_prepare($conn, "
                INSERT INTO payments (order_id, amount, method, status)
                VALUES (?, ?, ?, ?)
            ");
            $pay_status = $payment_type === 'cod' ? 'pending' : 'pending';
            mysqli_stmt_bind_param($pay_stmt, "idss",
                $order_id, $total, $payment_type, $pay_status);
            mysqli_stmt_execute($pay_stmt);

            // Add tracking
            $track_stmt = mysqli_prepare($conn, "
                INSERT INTO order_tracking (order_id, status, description)
                VALUES (?, 'pending', 'Order placed successfully')
            ");
            mysqli_stmt_bind_param($track_stmt, "i", $order_id);
            mysqli_stmt_execute($track_stmt);

            // Notify shop owner
            $shop_owner_stmt = mysqli_prepare($conn, "SELECT owner_id FROM shops WHERE id = ?");
            mysqli_stmt_bind_param($shop_owner_stmt, "i", $shop_id);
            mysqli_stmt_execute($shop_owner_stmt);
            $shop_owner = mysqli_fetch_assoc(mysqli_stmt_get_result($shop_owner_stmt));

            $notif_msg  = "New order #" . $order_id . " received! Amount: LKR " . number_format($shop_amount, 2);
            $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif_stmt, "is", $shop_owner['owner_id'], $notif_msg);
            mysqli_stmt_execute($notif_stmt);

            // Clear cart
            $clear = mysqli_prepare($conn, "DELETE FROM cart WHERE customer_id = ?");
            mysqli_stmt_bind_param($clear, "i", $customer_id);
            mysqli_stmt_execute($clear);

            // Online payment redirect
            if ($payment_type === 'online') {
                header('Location: /findywearce/api/payment.php?order_id=' . $order_id);
            } else {
                header('Location: /findywearce/customer/orders.php?success=' . $order_id);
            }
            exit();
        } else {
            $error = 'Order failed! Please try again.';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <h4 class="fw-bold mb-4">
        <i class="fas fa-credit-card me-2" style="color:var(--primary);"></i>
        Checkout
    </h4>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
    <div class="row g-4">

        <!-- Delivery Details -->
        <div class="col-lg-7">
            <div class="fw-card mb-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-map-marker-alt me-2" style="color:var(--primary);"></i>
                    Delivery Details
                </h5>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control"
                        value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control"
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Delivery Address <span class="text-danger">*</span>
                    </label>
                    <textarea name="delivery_address" class="form-control"
                        rows="3" placeholder="Enter your full delivery address"
                        required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Order Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2"
                        placeholder="Any special instructions..."></textarea>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="fw-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-wallet me-2" style="color:var(--primary);"></i>
                    Payment Method
                </h5>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="role-card" id="codCard"
                            onclick="selectPayment('cod')">
                            <i class="fas fa-money-bill-wave"></i>
                            <h6>Cash on Delivery</h6>
                            <small class="text-muted">Pay when received</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="role-card" id="onlineCard"
                            onclick="selectPayment('online')">
                            <i class="fas fa-credit-card"></i>
                            <h6>Online Payment</h6>
                            <small class="text-muted">PayHere - Secure</small>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="payment_type" id="paymentType" value="cod">
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-5">
            <div class="fw-card">
                <h5 class="fw-bold mb-4">Order Summary</h5>

                <?php foreach ($cart_items as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <img src="/findywearce/public/images/products/<?php echo $item['image']; ?>"
                            onerror="this.src='https://placehold.co/40/667eea/white?text=Item'"
                            class="rounded me-2"
                            style="width:40px;height:40px;object-fit:cover;">
                        <div>
                            <small class="fw-bold">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </small>
                            <br>
                            <small class="text-muted">x<?php echo $item['quantity']; ?></small>
                        </div>
                    </div>
                    <small class="fw-bold">
                        LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </small>
                </div>
                <?php endforeach; ?>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span>LKR <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Delivery</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>

                <!-- Commission Info -->
                <div class="p-2 rounded mb-2" style="background:#f8f9ff;">
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">
                            Platform Fee (<?php echo $comm_rate; ?>%)
                        </small>
                        <small class="text-muted">
                            LKR <?php echo number_format($commission_amount, 2); ?>
                        </small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Shop Receives</small>
                        <small class="text-success fw-bold">
                            LKR <?php echo number_format($shop_amount, 2); ?>
                        </small>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">You Pay</span>
                    <span class="fw-bold fs-5" style="color:var(--primary);">
                        LKR <?php echo number_format($total, 2); ?>
                    </span>
                </div>

                <button type="submit" name="place_order"
                    class="btn btn-primary-custom w-100 py-3">
                    <i class="fas fa-check-circle me-2"></i>Place Order
                </button>

                <p class="text-center text-muted small mt-3">
                    <i class="fas fa-shield-alt me-1"></i>
                    Delivered within 1 day!
                </p>
            </div>
        </div>

    </div>
    </form>
</div>

<script>
selectPayment('cod');

function selectPayment(type) {
    document.getElementById('paymentType').value = type;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    document.getElementById(type === 'cod' ? 'codCard' : 'onlineCard').classList.add('selected');
}
</script>

<?php include '../includes/footer.php'; ?>