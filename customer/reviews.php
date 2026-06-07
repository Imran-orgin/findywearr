<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];
$success     = '';
$error       = '';

// Submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $shop_id  = intval($_POST['shop_id']);
    $order_id = intval($_POST['order_id']);
    $rating   = intval($_POST['rating']);
    $comment  = trim($_POST['comment']);

    // Already reviewed check
    $check = mysqli_prepare($conn, "SELECT id FROM reviews WHERE customer_id = ? AND order_id = ?");
    mysqli_stmt_bind_param($check, "ii", $customer_id, $order_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $error = 'You have already reviewed this order!';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating!';
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO reviews (customer_id, shop_id, order_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iiiis",
            $customer_id, $shop_id, $order_id, $rating, $comment);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Review submitted successfully!';
        } else {
            $error = 'Failed to submit review!';
        }
    }
}

// Fetch delivered orders (eligible for review)
$orders_stmt = mysqli_prepare($conn, "
    SELECT o.*, s.shop_name, s.id as shop_id,
           (SELECT id FROM reviews WHERE order_id = o.id 
            AND customer_id = ?) as reviewed
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    WHERE o.customer_id = ? AND o.order_status = 'delivered'
    ORDER BY o.created_at DESC
");
mysqli_stmt_bind_param($orders_stmt, "ii", $customer_id, $customer_id);
mysqli_stmt_execute($orders_stmt);
$orders = mysqli_stmt_get_result($orders_stmt);

// Fetch my reviews
$reviews_stmt = mysqli_prepare($conn, "
    SELECT r.*, s.shop_name
    FROM reviews r
    JOIN shops s ON r.shop_id = s.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
mysqli_stmt_bind_param($reviews_stmt, "i", $customer_id);
mysqli_stmt_execute($reviews_stmt);
$my_reviews = mysqli_stmt_get_result($reviews_stmt);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <h4 class="fw-bold mb-4">
        <i class="fas fa-star me-2" style="color:var(--primary);"></i>
        Reviews
    </h4>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Write Review -->
        <div class="col-lg-5">
            <div class="fw-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-edit me-2" style="color:var(--primary);"></i>
                    Write a Review
                </h5>

                <?php if (mysqli_num_rows($orders) === 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No delivered orders to review!</p>
                        <a href="/findywearce/customer/home.php"
                            class="btn btn-primary-custom btn-sm">
                            Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                <form method="POST">
                    <!-- Select Order -->
                    <div class="mb-3">
                        <label class="form-label">Select Order *</label>
                        <select name="order_id" id="orderSelect"
                            class="form-control"
                            onchange="updateShop(this)"
                            required>
                            <option value="">-- Select Order --</option>
                            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                            <?php if (!$order['reviewed']): ?>
                            <option value="<?php echo $order['id']; ?>"
                                data-shop="<?php echo $order['shop_id']; ?>"
                                data-shopname="<?php echo htmlspecialchars($order['shop_name']); ?>">
                                Order #<?php echo $order['id']; ?> -
                                <?php echo htmlspecialchars($order['shop_name']); ?>
                                (LKR <?php echo number_format($order['total_amount'], 0); ?>)
                            </option>
                            <?php endif; ?>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <input type="hidden" name="shop_id" id="shopId">

                    <!-- Star Rating -->
                    <div class="mb-3">
                        <label class="form-label">Rating *</label>
                        <div class="d-flex gap-2" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star fa-2x"
                                style="cursor:pointer;color:#ddd;"
                                onclick="setRating(<?php echo $i; ?>)"
                                onmouseover="hoverRating(<?php echo $i; ?>)"
                                onmouseout="resetHover()"
                                id="star<?php echo $i; ?>">
                            </i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0">
                        <small class="text-muted" id="ratingText">Click to rate</small>
                    </div>

                    <!-- Comment -->
                    <div class="mb-4">
                        <label class="form-label">Comment</label>
                        <textarea name="comment" class="form-control"
                            rows="3"
                            placeholder="Share your experience..."></textarea>
                    </div>

                    <button type="submit" name="submit_review"
                        class="btn btn-primary-custom w-100">
                        <i class="fas fa-star me-2"></i>Submit Review
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Reviews -->
        <div class="col-lg-7">
            <div class="fw-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-history me-2" style="color:var(--primary);"></i>
                    My Reviews
                </h5>

                <?php if (mysqli_num_rows($my_reviews) === 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No reviews yet!</p>
                    </div>
                <?php else: ?>
                    <?php while ($review = mysqli_fetch_assoc($my_reviews)): ?>
                    <div class="p-3 rounded mb-3" style="background:#f8f9ff;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="fw-bold mb-0">
                                <?php echo htmlspecialchars($review['shop_name']); ?>
                            </h6>
                            <small class="text-muted">
                                <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                            </small>
                        </div>

                        <!-- Stars -->
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star"
                                style="color:<?php echo $i <= $review['rating'] ? '#f39c12' : '#ddd'; ?>;font-size:0.9rem;"></i>
                            <?php endfor; ?>
                            <small class="text-muted ms-1">
                                <?php echo $review['rating']; ?>/5
                            </small>
                        </div>

                        <?php if ($review['comment']): ?>
                        <p class="text-muted small mb-0">
                            "<?php echo htmlspecialchars($review['comment']); ?>"
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
let selectedRating = 0;

function setRating(rating) {
    selectedRating = rating;
    document.getElementById('ratingInput').value = rating;
    updateStars(rating);
    const texts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    document.getElementById('ratingText').textContent = texts[rating] + ' (' + rating + '/5)';
    document.getElementById('ratingText').style.color = '#f39c12';
}

function hoverRating(rating) {
    updateStars(rating);
}

function resetHover() {
    updateStars(selectedRating);
}

function updateStars(rating) {
    for (let i = 1; i <= 5; i++) {
        document.getElementById('star' + i).style.color = i <= rating ? '#f39c12' : '#ddd';
    }
}

function updateShop(select) {
    const option = select.options[select.selectedIndex];
    document.getElementById('shopId').value = option.dataset.shop || '';
}
</script>

<?php include '../includes/footer.php'; ?>