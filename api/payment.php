<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$order_id    = intval($_GET['order_id'] ?? 0);
$customer_id = $_SESSION['user_id'];

// Fetch order
$stmt = mysqli_prepare($conn, "
    SELECT o.*, s.shop_name, u.name, u.email, u.phone
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ? AND o.customer_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: /findywearce/customer/orders.php');
    exit();
}

// PayHere Sandbox Credentials
// Real-ஆ use பண்ண payhere.lk-ல register பண்ணி real credentials வாங்கணும்!
$merchant_id     = "1221149";  // PayHere Sandbox Merchant ID
$merchant_secret = "MzYwMzgxMzI2MzIyMzIyMzMxMzIzMjMxMjYyNjM="; // Sandbox Secret

// Order hash generate
$amount_formatted = number_format($order['total_amount'], 2, '.', '');
$currency         = "LKR";
$hash             = strtoupper(
    md5(
        $merchant_id .
        $order_id .
        $amount_formatted .
        $currency .
        strtoupper(md5($merchant_secret))
    )
);

// Split name
$name_parts = explode(' ', $order['name'], 2);
$first_name = $name_parts[0];
$last_name  = $name_parts[1] ?? '';
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="fw-card text-center">

                <!-- Header -->
                <div class="mb-4">
                    <i class="fas fa-credit-card fa-3x mb-3"
                        style="color:var(--primary);"></i>
                    <h4 class="fw-bold">Secure Payment</h4>
                    <p class="text-muted">Order #<?php echo $order_id; ?></p>
                </div>

                <!-- Order Summary -->
                <div class="p-3 rounded mb-4" 
                    style="background:linear-gradient(135deg,
                    rgba(102,126,234,0.1),rgba(118,75,162,0.1));">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Shop</span>
                        <span class="fw-bold">
                            <?php echo htmlspecialchars($order['shop_name']); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Method</span>
                        <span class="badge bg-success">Online - PayHere</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">Total Amount</span>
                        <span class="fw-bold fs-4" style="color:var(--primary);">
                            LKR <?php echo number_format($order['total_amount'], 2); ?>
                        </span>
                    </div>
                </div>

                <!-- PayHere Form -->
                <form method="post" 
                    action="https://sandbox.payhere.lk/pay/checkout">
                    
                    <!-- PayHere Required Fields -->
                    <input type="hidden" name="merchant_id" 
                        value="<?php echo $merchant_id; ?>">
                    <input type="hidden" name="return_url" 
                        value="http://localhost/findywearce/api/payment-success.php?order_id=<?php echo $order_id; ?>">
                    <input type="hidden" name="cancel_url" 
                        value="http://localhost/findywearce/api/payment-cancel.php?order_id=<?php echo $order_id; ?>">
                    <input type="hidden" name="notify_url" 
                        value="http://localhost/findywearce/api/payment-notify.php">
                    
                    <input type="hidden" name="order_id" 
                        value="<?php echo $order_id; ?>">
                    <input type="hidden" name="items" 
                        value="FindyWear Order #<?php echo $order_id; ?>">
                    <input type="hidden" name="currency" value="LKR">
                    <input type="hidden" name="amount" 
                        value="<?php echo $amount_formatted; ?>">
                    <input type="hidden" name="hash" value="<?php echo $hash; ?>">
                    
                    <!-- Customer Info -->
                    <input type="hidden" name="first_name" 
                        value="<?php echo htmlspecialchars($first_name); ?>">
                    <input type="hidden" name="last_name" 
                        value="<?php echo htmlspecialchars($last_name); ?>">
                    <input type="hidden" name="email" 
                        value="<?php echo htmlspecialchars($order['email']); ?>">
                    <input type="hidden" name="phone" 
                        value="<?php echo htmlspecialchars($order['phone'] ?? '0000000000'); ?>">
                    <input type="hidden" name="address" 
                        value="<?php echo htmlspecialchars($order['delivery_address']); ?>">
                    <input type="hidden" name="city" value="Colombo">
                    <input type="hidden" name="country" value="Sri Lanka">

                    <button type="submit" 
                        class="btn btn-primary-custom w-100 py-3 mb-3">
                        <i class="fas fa-lock me-2"></i>
                        Pay LKR <?php echo number_format($order['total_amount'], 2); ?> Now
                    </button>

                </form>

                <!-- Cancel -->
                <a href="/findywearce/customer/orders.php"
                    class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times me-2"></i>Cancel Payment
                </a>

                <!-- Security Badge -->
                <div class="mt-4 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1 text-success"></i>
                        Secured by PayHere Payment Gateway
                    </small>
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>
                        256-bit SSL Encrypted
                    </small>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>