<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])){
    if ($_SESSION['role'] === 'customer') header('Location: /findywearce/customer/home.php');
    elseif($_SESSION['role'] === 'shop_owner') header('Location: /findywearce/shop-owner/dashboard.php');
    elseif($_SESSION['role'] === 'admin') header('Location: /findywearce/admin/dashboard.php');
    exit();
}
include 'includes/header.php';
?>

<section class="hero-section">
    <div class="container">
         <div class="row align-items-center">
             <div class="col-lg-6">
                 <h1 class="mb-4">
                    Find Fashion <br>
                    <span style="color:#f093fb;">Near You</span>
                </h1>
                 <p class="mb-4">
                    Discover dress shops within 5km, order online and get delivery within 1 day!
                </p>
                 <a href="/findywearce/pages/register.php" class="btn btn-warning fw-bold px-4 py-3 me-3">
                    <i class="fas fa-user-plus me-2"></i>Get Started
                </a>
                    <a href="/findywearce/pages/login.php" class="btn btn-outline-light px-4 py-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
             </div>
              <div class="col-lg-6 text-center mt-4 mt-lg-0">
                <i class="fas fa-tshirt" style="font-size: 12rem; opacity: 0.3;"></i>
            </div>
         </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Why FindyWear?</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="fw-card text-center">
                    <i class="fas fa-map-marker-alt fa-3x mb-3" style="color: var(--primary)"></i>

                    <h5 class="fw-bold">5Km Nearby Shops</h5>
                    <p class="text-muted">See only shops within 5km of your location.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card text-center">
                    <i class="fas fa-shipping-fast fa-3x mb-3" style="color: var(--primary)"></i>
                    <h5 class="fw-bold">1 day Delivery</h5>
                    <p class="text-muted">Get your orders delivered within 1 day.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card text-center">
                    <i class="fas fa-undo fa-3x mb-3" style="color: var(--primary)"></i>
                    <h5 class="fw-bold">Easy Returns</h5>
                    <p class="text-muted">Return within 1 day and get instant refunds.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>