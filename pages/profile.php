<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = trim($_POST['name']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name)) {
        $error = 'Name cannot be empty!';
    } else {
        // Profile image upload
        $image_update = "";
        if (!empty($_FILES['profile_image']['name'])) {
            $ext       = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $img_name  = 'profile_' . $user_id . '.' . $ext;
            $upload_dir = '../public/images/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $img_name);
            $image_update = ", profile_image = '$img_name'";
        }

        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, address=? $image_update WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $address, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Update failed! Try again.';
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    // Fetch current password
    $pwd_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($pwd_stmt, "i", $user_id);
    mysqli_stmt_execute($pwd_stmt);
    $pwd_result = mysqli_fetch_assoc(mysqli_stmt_get_result($pwd_stmt));

    if (!password_verify($current, $pwd_result['password'])) {
        $error = 'Current password is incorrect!';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match!';
    } elseif (strlen($new) < 6) {
        $error = 'Password must be at least 6 characters!';
    } else {
        $hashed   = password_hash($new, PASSWORD_DEFAULT);
        $upd_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd_stmt, "si", $hashed, $user_id);
        mysqli_stmt_execute($upd_stmt);
        $success = 'Password changed successfully!';
    }
}

// Fetch user
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Stats
$order_count  = 0;
$review_count = 0;
$shop_name    = '';

if ($user['role'] === 'customer') {
    $order_count  = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as t FROM orders WHERE customer_id = $user_id"))['t'];
    $review_count = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as t FROM reviews WHERE customer_id = $user_id"))['t'];
} elseif ($user['role'] === 'shop_owner') {
    $shop = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT shop_name FROM shops WHERE owner_id = $user_id"));
    $shop_name = $shop['shop_name'] ?? '';
}
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="row g-4">

        <!-- Left: Profile Card -->
        <div class="col-lg-4">
            <div class="fw-card text-center mb-4">

                <!-- Profile Image -->
                <div class="position-relative d-inline-block mb-3">
                    <img src="/findywearce/public/images/profiles/<?php echo $user['profile_image']; ?>"
                        onerror="this.src='https://placehold.co/100/667eea/white?text=<?php echo strtoupper(substr($user['name'],0,1)); ?>'"
                        class="rounded-circle"
                        style="width:100px;height:100px;object-fit:cover;
                        border:4px solid var(--primary);">
                </div>

                <h5 class="fw-bold mb-1">
                    <?php echo htmlspecialchars($user['name']); ?>
                </h5>
                <span class="badge bg-<?php echo $user['role']==='customer' ? 'primary' : ($user['role']==='shop_owner' ? 'warning text-dark' : 'danger'); ?> mb-3">
                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                </span>

                <p class="text-muted small mb-1">
                    <i class="fas fa-envelope me-1"></i>
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <p class="text-muted small mb-1">
                    <i class="fas fa-phone me-1"></i>
                    <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?>
                </p>
                <p class="text-muted small mb-3">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($user['address'] ?? 'Not set'); ?>
                </p>

                <!-- Stats -->
                <?php if ($user['role'] === 'customer'): ?>
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:#f8f9ff;">
                            <div class="fw-bold" style="color:var(--primary);">
                                <?php echo $order_count; ?>
                            </div>
                            <small class="text-muted">Orders</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:#f8f9ff;">
                            <div class="fw-bold" style="color:var(--primary);">
                                <?php echo $review_count; ?>
                            </div>
                            <small class="text-muted">Reviews</small>
                        </div>
                    </div>
                </div>
                <?php elseif ($user['role'] === 'shop_owner'): ?>
                <div class="p-2 rounded mt-2" style="background:#f8f9ff;">
                    <small class="text-muted">Shop</small>
                    <div class="fw-bold" style="color:var(--primary);">
                        <?php echo htmlspecialchars($shop_name); ?>
                    </div>
                </div>
                <?php endif; ?>

                <p class="text-muted small mt-3 mb-0">
                    <i class="fas fa-calendar me-1"></i>
                    Joined <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                </p>
            </div>

            <!-- Quick Links -->
            <div class="fw-card">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <?php if ($user['role'] === 'customer'): ?>
                <a href="/findywearce/customer/orders.php"
                    class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-box me-2"></i>My Orders
                </a>
                <a href="/findywearce/customer/home.php"
                    class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-store me-2"></i>Browse Shops
                </a>
                <?php elseif ($user['role'] === 'shop_owner'): ?>
                <a href="/findywearce/shop-owner/dashboard.php"
                    class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-chart-bar me-2"></i>Dashboard
                </a>
                <a href="/findywearce/shop-owner/products.php"
                    class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-tshirt me-2"></i>My Products
                </a>
                <?php endif; ?>
                <a href="/findywearce/pages/logout.php"
                    class="btn btn-outline-danger w-100">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Right: Edit Forms -->
        <div class="col-lg-8">

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

            <!-- Edit Profile -->
            <div class="fw-card mb-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-edit me-2" style="color:var(--primary);"></i>
                    Edit Profile
                </h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control"
                                value="<?php echo htmlspecialchars($user['name']); ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control"
                                rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Profile Image</label>
                            <input type="file" name="profile_image"
                                class="form-control" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" name="update_profile"
                        class="btn btn-primary-custom mt-3">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="fw-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-lock me-2" style="color:var(--primary);"></i>
                    Change Password
                </h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password"
                            class="form-control" placeholder="Enter current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password"
                            class="form-control" placeholder="Min 6 characters">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password"
                            class="form-control" placeholder="Re-enter new password">
                    </div>
                    <button type="submit" name="change_password"
                        class="btn btn-warning fw-bold">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>