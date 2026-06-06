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

// Fetch order
$stmt = mysqli_prepare($conn, "
    SELECT o.*, s.shop_name 
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    WHERE o.id = ? AND o.customer_id = ? AND o.order_status = 'delivered'
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: /findywearce/customer/orders.php');
    exit();
}

// Check 1 day limit
$delivered_time = strtotime($order['updated_at']);
$current_time   = time();
$hours_passed   = ($current_time - $delivered_time) / 3600;

$can_return = $hours_passed <= 24;

// Check already returned
$check_stmt = mysqli_prepare($conn, "
    SELECT id FROM returns 
    WHERE order_id = ? AND customer_id = ?
");
mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);
$already_returned = mysqli_stmt_num_rows($check_stmt) > 0;

$error   = '';
$success = '';

// Submit return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_return'])) {
    $reason = trim($_POST['reason']);

    if (empty($reason)) {
        $error = 'Please provide a reason for return!';
    } elseif (!$can_return) {
        $error = 'Return period has expired! Returns only allowed within 24 hours.';
    } elseif ($already_returned) {
        $error = 'You have already submitted a return request for this order!';
    } else {
        $return_stmt = mysqli_prepare($conn, "
            INSERT INTO returns (order_id, customer_id, reason, refund_amount)
            VALUES (?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($return_stmt, "iisd",
            $order_id, $customer_id, $reason, $order['total_amount']);

        if (mysqli_stmt_execute($return_stmt)) {
            // Notify shop owner
            $shop_owner_stmt = mysqli_prepare($conn, "
                SELECT owner_id FROM shops WHERE id = ?
            ");
            mysqli_stmt_bind_param($shop_owner_stmt, "i", $order['shop_id']);
            mysqli_stmt_execute($shop_owner_stmt);
            $shop_owner = mysqli_fetch_assoc(
                mysqli_stmt_get_result($shop_owner_stmt));

            $notif_msg  = "Return request for Order #" . $order_id . 
                          " from " . $_SESSION['name'];
            $notif_stmt = mysqli_prepare($conn, "
                INSERT INTO notifications (user_id, message) VALUES (?, ?)
            ");
            mysqli_stmt_bind_param($notif_stmt, "is", 
                $shop_owner['owner_id'], $notif_msg);
            mysqli_stmt_execute($notif_stmt);

            $success = 'Return request submitted successfully!';
            $already_returned = true;
        } else {
            $error = 'Failed to submit return request. Please try again.';
        }
    }
}

// Fetch order items
$items_stmt = mysqli_prepare($conn, "
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_stmt_get_result($items_stmt);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Back -->
    <a href="/findywearce/customer/orders.php"
        class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i>Back to Orders
    </a>

    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="fw-card">

                <!-- Header -->
                <div class="text-center mb-4">
                    <i class="fas fa-undo fa-3x mb-3" 
                        style="color:var(--primary);"></i>
                    <h4 class="fw-bold">Return Request</h4>
                    <p class="text-muted">Order #<?php echo $order_id; ?> • 
                        <?php echo htmlspecialchars($order['shop_name']); ?>
                    </p>
                </div>

                <!-- Time Warning -->
                <?php if (!$can_return): ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-clock me-2"></i>
                    Return period expired! Returns only allowed within <strong>24 hours</strong> of delivery.
                </div>
                <?php elseif (!$already_returned): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-clock me-2"></i>
                    Return window: <strong>
                    <?php echo max(0, round(24 - $hours_passed)); ?> hours
                    </strong> remaining
                </div>
                <?php endif; ?>

                <!-- Success -->
                <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <br><small>Refund will be processed within 24 hours!</small>
                </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <!-- Already Returned -->
                <?php if ($already_returned && !$success): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Return request already submitted! 
                    Refund will be processed within 24 hours.
                </div>
                <?php endif; ?>

                <!-- Order Items -->
                <h6 class="fw-bold mb-3">Items in this Order</h6>
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <div class="d-flex align-items-center mb-3 p-3 
                            bg-light rounded">
                    <img src="/findywearce/public/images/products/
                        <?php echo $item['image']; ?>"
                        onerror="this.src='https://via.placeholder.com/50/667eea/white?text=Item'"
                        class="rounded me-3"
                        style="width:50px;height:50px;object-fit:cover;">
                    <div class="flex-grow-1">
                        <p class="fw-bold mb-0">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </p>
                        <small class="text-muted">
                            Qty: <?php echo $item['quantity']; ?> × 
                            LKR <?php echo number_format($item['price'], 2); ?>
                        </small>
                    </div>
                </div>
                <?php endwhile; ?>

                <hr>

                <!-- Refund Amount -->
                <div class="d-flex justify-content-between mb-4 p-3 
                            rounded" 
                    style="background:linear-gradient(135deg,
                        rgba(102,126,234,0.1),rgba(118,75,162,0.1));">
                    <span class="fw-bold">Refund Amount</span>
                    <span class="fw-bold fs-5" style="color:var(--primary);">
                        LKR <?php echo number_format($order['total_amount'], 2); ?>
                    </span>
                </div>

                <!-- Return Form -->
                <?php if ($can_return && !$already_returned): ?>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Reason for Return <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2 mb-3">
                            <?php
                            $reasons = [
                                'Wrong size',
                                'Wrong color',
                                'Damaged product',
                                'Not as described',
                                'Changed my mind',
                                'Other'
                            ];
                            foreach ($reasons as $r):
                            ?>
                            <div class="col-6">
                                <div class="role-card py-2"
                                    onclick="selectReason('<?php echo $r; ?>', this)">
                                    <small class="fw-bold"><?php echo $r; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <textarea name="reason" id="reasonText"
                            class="form-control" rows="3"
                            placeholder="Describe your reason..."
                            required></textarea>
                    </div>

                    <button type="submit" name="submit_return"
                        class="btn btn-danger w-100 py-3"
                        onclick="return confirm('Submit return request?')">
                        <i class="fas fa-undo me-2"></i>
                        Submit Return Request
                    </button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function selectReason(reason, el) {
    document.getElementById('reasonText').value = reason;
    document.querySelectorAll('.role-card').forEach(
        c => c.classList.remove('selected'));
    el.classList.add('selected');
}
</script>

<?php include '../includes/footer.php'; ?>