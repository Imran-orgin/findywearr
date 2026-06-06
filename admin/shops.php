<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$success = '';

// Shop status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop'])) {
    $shop_id   = intval($_POST['shop_id']);
    $new_status = $_POST['new_status'];
    $allowed   = ['active', 'inactive', 'pending'];

    if (in_array($new_status, $allowed)) {
        $upd = mysqli_prepare($conn, "UPDATE shops SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $new_status, $shop_id);
        mysqli_stmt_execute($upd);
        $success = 'Shop status updated successfully!';
    }
}

// Delete shop
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $del    = mysqli_prepare($conn, "DELETE FROM shops WHERE id = ?");
    mysqli_stmt_bind_param($del, "i", $del_id);
    mysqli_stmt_execute($del);
    header('Location: /findywearce/admin/shops.php?deleted=1');
    exit();
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE s.status = '$filter'" : "";

// Fetch shops
$shops = mysqli_query($conn, "
    SELECT s.*, u.name as owner_name, u.email as owner_email,
           u.phone as owner_phone,
           COUNT(DISTINCT p.id) as product_count,
           COUNT(DISTINCT o.id) as order_count
    FROM shops s
    JOIN users u ON s.owner_id = u.id
    LEFT JOIN products p ON s.id = p.shop_id
    LEFT JOIN orders o ON s.id = o.shop_id
    $where
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-store me-2" style="color:var(--primary);"></i>
            Manage Shops
        </h4>
        <a href="/findywearce/admin/dashboard.php"
            class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-info">
        <i class="fas fa-trash me-2"></i>Shop deleted successfully!
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="fw-card mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <?php
            $filters = [
                'all'      => ['All Shops', 'secondary'],
                'active'   => ['Active', 'success'],
                'pending'  => ['Pending', 'warning'],
                'inactive' => ['Inactive', 'danger'],
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

    <!-- Shops -->
    <?php if (mysqli_num_rows($shops) === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-store-slash fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No shops found!</h5>
        </div>
    <?php else: ?>
        <?php while ($shop = mysqli_fetch_assoc($shops)): ?>
        <div class="fw-card mb-4">
            <div class="row align-items-center">

                <!-- Shop Info -->
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle d-flex align-items-center
                                    justify-content-center me-3"
                            style="width:50px;height:50px;
                            background:linear-gradient(135deg,
                            var(--primary),var(--secondary));">
                            <i class="fas fa-store text-white"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">
                                <?php echo htmlspecialchars($shop['shop_name']); ?>
                            </h6>
                            <span class="badge bg-<?php echo $shop['status'] === 'active' ? 'success' : ($shop['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($shop['status']); ?>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted d-block">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?php echo htmlspecialchars($shop['address']); ?>
                    </small>
                    <small class="text-muted d-block">
                        <i class="fas fa-phone me-1"></i>
                        <?php echo htmlspecialchars($shop['phone'] ?? 'N/A'); ?>
                    </small>
                </div>

                <!-- Owner Info -->
                <div class="col-md-3 mb-3 mb-md-0">
                    <small class="text-muted fw-bold d-block mb-1">OWNER</small>
                    <p class="fw-bold mb-0 small">
                        <?php echo htmlspecialchars($shop['owner_name']); ?>
                    </p>
                    <small class="text-muted d-block">
                        <?php echo htmlspecialchars($shop['owner_email']); ?>
                    </small>
                    <small class="text-muted">
                        <?php echo htmlspecialchars($shop['owner_phone'] ?? 'N/A'); ?>
                    </small>

                    <div class="d-flex gap-3 mt-2">
                        <div class="text-center">
                            <div class="fw-bold" style="color:var(--primary);">
                                <?php echo $shop['product_count']; ?>
                            </div>
                            <small class="text-muted">Products</small>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold" style="color:var(--success);">
                                <?php echo $shop['order_count']; ?>
                            </div>
                            <small class="text-muted">Orders</small>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="col-md-2 mb-3 mb-md-0">
                    <small class="text-muted fw-bold d-block mb-1">LOCATION</small>
                    <small class="text-muted d-block">
                        Lat: <?php echo $shop['latitude']; ?>
                    </small>
                    <small class="text-muted d-block">
                        Lng: <?php echo $shop['longitude']; ?>
                    </small>
                    <small class="text-muted d-block mt-1">
                        <?php echo date('d M Y', strtotime($shop['created_at'])); ?>
                    </small>
                </div>

                <!-- Actions -->
                <div class="col-md-3">
                    <form method="POST" class="mb-2">
                        <input type="hidden" name="shop_id"
                            value="<?php echo $shop['id']; ?>">
                        <select name="new_status" class="form-control form-control-sm mb-2">
                            <option value="active" <?php echo $shop['status']==='active' ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="pending" <?php echo $shop['status']==='pending' ? 'selected' : ''; ?>>
                                Pending
                            </option>
                            <option value="inactive" <?php echo $shop['status']==='inactive' ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                        </select>
                        <button type="submit" name="update_shop"
                            class="btn btn-sm btn-primary-custom w-100 mb-1">
                            <i class="fas fa-save me-1"></i>Update Status
                        </button>
                    </form>
                    <a href="?delete=<?php echo $shop['id']; ?>"
                        class="btn btn-sm btn-outline-danger w-100"
                        onclick="return confirm('Delete this shop? This cannot be undone!')">
                        <i class="fas fa-trash me-1"></i>Delete Shop
                    </a>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>