<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$success = '';
$error   = '';

// Update commission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_commission'])) {
    $percentage = floatval($_POST['percentage']);
    if ($percentage < 0 || $percentage > 50) {
        $error = 'Commission must be between 0% and 50%!';
    } else {
        mysqli_query($conn, "UPDATE commission_settings SET percentage = $percentage WHERE id = 1");
        $success = 'Commission rate updated successfully!';
    }
}

// Fetch current commission
$comm_stmt = mysqli_query($conn, "SELECT * FROM commission_settings WHERE id = 1");
$commission = mysqli_fetch_assoc($comm_stmt);

// Commission stats
$total_comm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission_amount) as t FROM commissions"))['t'] ?? 0;
$pending_comm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission_amount) as t FROM commissions WHERE status='pending'"))['t'] ?? 0;
$paid_comm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission_amount) as t FROM commissions WHERE status='paid'"))['t'] ?? 0;

// Commission transactions
$transactions = mysqli_query($conn, "
    SELECT c.*, s.shop_name, o.created_at as order_date,
           u.name as owner_name
    FROM commissions c
    JOIN shops s ON c.shop_id = s.id
    JOIN orders o ON c.order_id = o.id
    JOIN users u ON s.owner_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 20
");
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i class="fas fa-percent me-2" style="color:var(--primary);"></i>
            Commission Management
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

    <div class="row g-4">

        <!-- Commission Settings -->
        <div class="col-lg-4">

            <!-- Current Rate -->
            <div class="fw-card mb-4 text-center"
                style="background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;">
                <i class="fas fa-percent fa-3x mb-3 opacity-75"></i>
                <h1 class="fw-bold"><?php echo $commission['percentage']; ?>%</h1>
                <p class="mb-0 opacity-75">Current Commission Rate</p>
            </div>

            <!-- Update Form -->
            <div class="fw-card mb-4">
                <h6 class="fw-bold mb-3">Update Commission Rate</h6>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Commission % (0-50)</label>
                        <div class="input-group">
                            <input type="number" name="percentage"
                                class="form-control"
                                value="<?php echo $commission['percentage']; ?>"
                                min="0" max="50" step="0.5" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">
                            Example: 10% of LKR 2000 = LKR 200 commission
                        </small>
                    </div>
                    <button type="submit" name="update_commission"
                        class="btn btn-primary-custom w-100">
                        <i class="fas fa-save me-2"></i>Update Rate
                    </button>
                </form>
            </div>

            <!-- How it works -->
            <div class="fw-card">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-info-circle me-2" style="color:var(--primary);"></i>
                    How Commission Works
                </h6>
                <div class="p-3 rounded mb-2" style="background:#f8f9ff;">
                    <small class="text-muted d-block">Customer Pays</small>
                    <strong>LKR 2,000</strong>
                </div>
                <div class="text-center my-2">
                    <i class="fas fa-arrow-down text-muted"></i>
                </div>
                <div class="p-3 rounded mb-2" style="background:#fff3cd;">
                    <small class="text-muted d-block">FindyWear Commission (<?php echo $commission['percentage']; ?>%)</small>
                    <strong class="text-warning">
                        LKR <?php echo number_format(2000 * $commission['percentage'] / 100, 2); ?>
                    </strong>
                </div>
                <div class="text-center my-2">
                    <i class="fas fa-arrow-down text-muted"></i>
                </div>
                <div class="p-3 rounded" style="background:#d4edda;">
                    <small class="text-muted d-block">Shop Owner Gets</small>
                    <strong class="text-success">
                        LKR <?php echo number_format(2000 - (2000 * $commission['percentage'] / 100), 2); ?>
                    </strong>
                </div>
            </div>

        </div>

        <!-- Stats & Transactions -->
        <div class="col-lg-8">

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">
                            LKR <?php echo number_format($total_comm, 0); ?>
                        </div>
                        <div class="stat-label">Total Commission</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="border-color:var(--warning);">
                        <div class="stat-number" style="color:var(--warning);">
                            LKR <?php echo number_format($pending_comm, 0); ?>
                        </div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="border-color:var(--success);">
                        <div class="stat-number" style="color:var(--success);">
                            LKR <?php echo number_format($paid_comm, 0); ?>
                        </div>
                        <div class="stat-label">Collected</div>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="fw-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-history me-2" style="color:var(--primary);"></i>
                    Commission Transactions
                </h5>

                <?php if (mysqli_num_rows($transactions) === 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                        <p class="text-muted">No transactions yet!</p>
                        <small class="text-muted">
                            Commission will be recorded when orders are placed.
                        </small>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Shop</th>
                                <th>Total</th>
                                <th>Rate</th>
                                <th>Commission</th>
                                <th>Shop Gets</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($t = mysqli_fetch_assoc($transactions)): ?>
                            <tr>
                                <td><strong>#<?php echo $t['order_id']; ?></strong></td>
                                <td>
                                    <small class="fw-bold">
                                        <?php echo htmlspecialchars($t['shop_name']); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($t['owner_name']); ?>
                                    </small>
                                </td>
                                <td>LKR <?php echo number_format($t['total_amount'], 0); ?></td>
                                <td><?php echo $t['commission_rate']; ?>%</td>
                                <td class="text-warning fw-bold">
                                    LKR <?php echo number_format($t['commission_amount'], 0); ?>
                                </td>
                                <td class="text-success fw-bold">
                                    LKR <?php echo number_format($t['shop_amount'], 0); ?>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'pending'): ?>
                                        <span class="badge-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="badge-delivered">Paid</span>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>