<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$success = '';
$error   = '';

// Delete user
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id !== $_SESSION['user_id']) {
        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $del_id);
        mysqli_stmt_execute($del);
        header('Location: /findywearce/admin/users.php?deleted=1');
        exit();
    } else {
        $error = 'You cannot delete your own account!';
    }
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE role = '$filter'" : "";

// Fetch users
$users = mysqli_query($conn, "
    SELECT u.*,
        (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count,
        (SELECT COUNT(*) FROM shops WHERE owner_id = u.id) as shop_count
    FROM users u
    $where
    ORDER BY u.created_at DESC
");
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-users me-2" style="color:var(--primary);"></i>
            Manage Users
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

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-info">
        <i class="fas fa-trash me-2"></i>User deleted successfully!
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="fw-card mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <?php
            $filters = [
                'all'        => ['All Users', 'secondary'],
                'customer'   => ['Customers', 'primary'],
                'shop_owner' => ['Shop Owners', 'warning'],
                'admin'      => ['Admins', 'danger'],
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

    <!-- Users Table -->
    <div class="fw-card">
        <?php if (mysqli_num_rows($users) === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No users found!</h5>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Activity</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center
                                            justify-content-center me-3"
                                    style="width:40px;height:40px;
                                    background:linear-gradient(135deg,
                                    var(--primary),var(--secondary));
                                    color:white;font-weight:bold;">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="fw-bold mb-0 small">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                        <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary ms-1">You</span>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $role_badge = match($user['role']) {
                                'customer'   => ['bg-primary', 'Customer'],
                                'shop_owner' => ['bg-warning text-dark', 'Shop Owner'],
                                'admin'      => ['bg-danger', 'Admin'],
                                default      => ['bg-secondary', $user['role']]
                            };
                            ?>
                            <span class="badge <?php echo $role_badge[0]; ?>">
                                <?php echo $role_badge[1]; ?>
                            </span>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></small>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'customer'): ?>
                                <small class="text-muted">
                                    <i class="fas fa-shopping-bag me-1"></i>
                                    <?php echo $user['order_count']; ?> orders
                                </small>
                            <?php elseif ($user['role'] === 'shop_owner'): ?>
                                <small class="text-muted">
                                    <i class="fas fa-store me-1"></i>
                                    <?php echo $user['shop_count']; ?> shop(s)
                                </small>
                            <?php else: ?>
                                <small class="text-muted">System Admin</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($user['id'] !== $_SESSION['user_id']
                                && $user['role'] !== 'admin'): ?>
                            <a href="?delete=<?php echo $user['id']; ?>&filter=<?php echo $filter; ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete this user?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>