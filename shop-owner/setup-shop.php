<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];

// Already shop irukka check
$check = mysqli_prepare($conn, "SELECT id FROM shops WHERE owner_id = ?");
mysqli_stmt_bind_param($check, "i", $owner_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    header('Location: /findywearce/shop-owner/dashboard.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name   = trim($_POST['shop_name']);
    $description = trim($_POST['description']);
    $address     = trim($_POST['address']);
    $phone       = trim($_POST['phone']);
    $latitude    = floatval($_POST['latitude']);
    $longitude   = floatval($_POST['longitude']);

    if (empty($shop_name) || empty($address) || empty($latitude)) {
        $error = 'Please fill all required fields!';
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO shops (owner_id, shop_name, description, address, 
                             latitude, longitude, phone, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        mysqli_stmt_bind_param($stmt, "isssdds",
            $owner_id, $shop_name, $description, 
            $address, $latitude, $longitude, $phone);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: /findywearce/shop-owner/dashboard.php');
            exit();
        } else {
            $error = 'Failed to create shop. Try again!';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<style>
    body { margin-top: 0 !important; }
    div[style*="margin-top: 80px"] { display: none; }
</style>

<div class="auth-wrapper" style="padding: 40px 20px; min-height: 100vh;">
    <div class="auth-card" style="max-width: 550px;">

        <div class="logo">
            <i class="fas fa-store"></i>
            <h2>Setup Your Shop</h2>
            <p>Fill in your shop details to get started</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Shop Name <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-store"></i></span>
                    <input type="text" name="shop_name" class="form-control"
                        placeholder="Enter shop name" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"
                    placeholder="Describe your shop..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="text" name="phone" class="form-control"
                        placeholder="Shop phone number" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control" rows="2"
                    placeholder="Full shop address" required></textarea>
            </div>

            <!-- Location -->
            <div class="mb-3">
                <label class="form-label">Shop Location <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-outline-secondary w-100 mb-2"
                    onclick="getLocation()">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Detect My Location
                </button>
                <div id="locationStatus" class="text-muted small text-center"></div>
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 mt-2"
                id="submitBtn">
                <i class="fas fa-store me-2"></i>Create My Shop
            </button>

        </form>
    </div>
</div>

<script>
function getLocation() {
    const status = document.getElementById('locationStatus');
    status.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Detecting...';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                document.getElementById('latitude').value  = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
                status.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Location detected successfully!';
                status.className = 'text-success small text-center';
            },
            function() {
                status.innerHTML = '<i class="fas fa-exclamation-circle text-danger me-1"></i>Location access denied!';
                status.className = 'text-danger small text-center';
            }
        );
    }
}
</script>

<?php include '../includes/footer.php'; ?>