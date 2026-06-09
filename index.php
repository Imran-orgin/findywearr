<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'customer')
        header('Location: /findywearce/customer/home.php');
    elseif ($_SESSION['role'] === 'shop_owner')
        header('Location: /findywearce/shop-owner/dashboard.php');
    elseif ($_SESSION['role'] === 'admin')
        header('Location: /findywearce/admin/dashboard.php');
    exit();
}
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section" style="padding: 100px 0 80px;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="badge bg-warning text-dark fw-bold px-3 py-2 mb-3 rounded-pill">
                    🇱🇰 Sri Lanka's #1 Hyperlocal Fashion App
                </div>
                <h1 class="mb-4" style="font-size:3.5rem;">
                    Find Fashion
                    <span style="color:#f093fb;">Near You!</span>
                    👗
                </h1>
                <p class="mb-4 fs-5" style="opacity:0.9;">
                    Discover dress shops within <strong>5km</strong>,
                    order online and get delivery within <strong>1 day!</strong>
                    Support local businesses in your area.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="/findywearce/pages/register.php"
                        class="btn btn-warning fw-bold px-4 py-3 rounded-pill">
                        <i class="fas fa-user-plus me-2"></i>Get Started Free
                    </a>
                    <a href="/findywearce/pages/login.php"
                        class="btn btn-outline-light px-4 py-3 rounded-pill">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                </div>

                <!-- Stats -->
                <div class="row g-3 mt-4">
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3"
                            style="background:rgba(255,255,255,0.15);">
                            <h3 class="fw-bold mb-0">5km</h3>
                            <small style="opacity:0.8;">Radius</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3"
                            style="background:rgba(255,255,255,0.15);">
                            <h3 class="fw-bold mb-0">1 Day</h3>
                            <small style="opacity:0.8;">Delivery</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3"
                            style="background:rgba(255,255,255,0.15);">
                            <h3 class="fw-bold mb-0">24hr</h3>
                            <small style="opacity:0.8;">Returns</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 text-center mt-5 mt-lg-0">
                <div style="position:relative;">
                    <div style="width:300px;height:300px;background:rgba(255,255,255,0.1);
                        border-radius:50%;margin:0 auto;display:flex;
                        align-items:center;justify-content:center;">
                        <i class="fas fa-tshirt"
                            style="font-size:150px;opacity:0.3;"></i>
                    </div>
                    <!-- Floating cards -->
                    <div class="fw-card position-absolute"
                        style="top:20px;right:30px;padding:12px 16px;
                        animation:float 3s ease-in-out infinite;">
                        <small class="fw-bold">📍 1.2 km away</small>
                        <p class="mb-0 small text-muted">Trinco Fashion</p>
                    </div>
                    <div class="fw-card position-absolute"
                        style="bottom:20px;left:20px;padding:12px 16px;
                        animation:float 3s ease-in-out infinite 1.5s;">
                        <small class="fw-bold">✅ Delivered!</small>
                        <p class="mb-0 small text-muted">Order #247</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it Works -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How FindyWear Works?</h2>
            <p class="text-muted">Simple 3 steps to get your fashion delivered!</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="fw-card h-100">
                    <div class="rounded-circle d-flex align-items-center
                        justify-content-center mx-auto mb-4"
                        style="width:80px;height:80px;
                        background:linear-gradient(135deg,var(--primary),var(--secondary));">
                        <i class="fas fa-map-marker-alt fa-2x text-white"></i>
                    </div>
                    <div class="badge bg-primary rounded-pill mb-2">Step 1</div>
                    <h5 class="fw-bold">Find Nearby Shops</h5>
                    <p class="text-muted">
                        Allow location access and see all dress shops
                        within 5km of your location instantly!
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="fw-card h-100">
                    <div class="rounded-circle d-flex align-items-center
                        justify-content-center mx-auto mb-4"
                        style="width:80px;height:80px;
                        background:linear-gradient(135deg,#f093fb,#f5576c);">
                        <i class="fas fa-shopping-cart fa-2x text-white"></i>
                    </div>
                    <div class="badge bg-danger rounded-pill mb-2">Step 2</div>
                    <h5 class="fw-bold">Order Online</h5>
                    <p class="text-muted">
                        Browse products, add to cart and place your
                        order with COD or online payment!
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="fw-card h-100">
                    <div class="rounded-circle d-flex align-items-center
                        justify-content-center mx-auto mb-4"
                        style="width:80px;height:80px;
                        background:linear-gradient(135deg,#4facfe,#00f2fe);">
                        <i class="fas fa-shipping-fast fa-2x text-white"></i>
                    </div>
                    <div class="badge bg-info rounded-pill mb-2">Step 3</div>
                    <h5 class="fw-bold">Get 1-Day Delivery</h5>
                    <p class="text-muted">
                        Your order gets delivered within 1 day
                        since the shop is nearby!
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5" style="background:var(--light-bg);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Choose FindyWear?</h2>
            <p class="text-muted">The smartest way to shop local fashion!</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="fw-card h-100">
                    <i class="fas fa-map-marker-alt fa-3x mb-3"
                        style="color:var(--primary);"></i>
                    <h5 class="fw-bold">Hyperlocal Shopping</h5>
                    <p class="text-muted">
                        Only see shops within 5km using GPS technology.
                        No more ordering from far away shops!
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card h-100">
                    <i class="fas fa-bolt fa-3x mb-3"
                        style="color:#f39c12;"></i>
                    <h5 class="fw-bold">1-Day Delivery</h5>
                    <p class="text-muted">
                        Since shops are nearby, get your fashion
                        delivered within 1 day guaranteed!
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card h-100">
                    <i class="fas fa-undo fa-3x mb-3"
                        style="color:#e74c3c;"></i>
                    <h5 class="fw-bold">Easy Returns</h5>
                    <p class="text-muted">
                        Not satisfied? Return within 24 hours
                        and get instant refund!
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card h-100">
                    <i class="fas fa-shield-alt fa-3x mb-3"
                        style="color:#2ecc71;"></i>
                    <h5 class="fw-bold">Secure Payments</h5>
                    <p class="text-muted">
                        Pay with Cash on Delivery or secure
                        online payment via PayHere!
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card h-100">
                    <i class="fas fa-store fa-3x mb-3"
                        style="color:#9b59b6;"></i>
                    <h5 class="fw-bold">Support Local Shops</h5>
                    <p class="text-muted">
                        Help local dress shop owners grow their
                        business online easily!
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-card h-100">
                    <i class="fas fa-robot fa-3x mb-3"
                        style="color:#667eea;"></i>
                    <h5 class="fw-bold">AI Assistant</h5>
                    <p class="text-muted">
                        Get instant help from our AI chatbot
                        for orders, returns and more!
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- For Shop Owners CTA -->
<section class="py-5"
    style="background:linear-gradient(135deg,var(--primary),var(--secondary));">
    <div class="container text-center text-white">
        <h2 class="fw-bold mb-3">Are You a Shop Owner? 🏪</h2>
        <p class="fs-5 mb-4" style="opacity:0.9;">
            Join FindyWear and reach customers within 5km!
            Manage your products, orders and stock easily.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/findywearce/pages/register.php"
                class="btn btn-warning fw-bold px-5 py-3 rounded-pill">
                <i class="fas fa-store me-2"></i>Register Your Shop
            </a>
            <a href="/findywearce/pages/login.php"
                class="btn btn-outline-light px-5 py-3 rounded-pill">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="row g-4 pb-4">
            <div class="col-md-4">
                <h5>
                    <i class="fas fa-tshirt me-2"></i>FindyWear
                </h5>
                <p style="color:rgba(255,255,255,0.6);">
                    Sri Lanka's hyperlocal fashion e-commerce platform.
                    Connecting customers with nearby dress shops.
                </p>
            </div>
            <div class="col-md-2">
                <h5>Customer</h5>
                <ul class="list-unstyled">
                    <li><a href="/findywearce/pages/register.php">Register</a></li>
                    <li><a href="/findywearce/pages/login.php">Login</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h5>Shop Owner</h5>
                <ul class="list-unstyled">
                    <li><a href="/findywearce/pages/register.php">Join Us</a></li>
                    <li><a href="/findywearce/pages/login.php">Dashboard</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact</h5>
                <p style="color:rgba(255,255,255,0.6);">
                    <i class="fas fa-envelope me-2"></i>
                    support@findywear.lk<br>
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Trincomalee, Sri Lanka
                </p>
            </div>
        </div>
        <hr style="border-color:rgba(255,255,255,0.1);">
        <div class="text-center" style="color:rgba(255,255,255,0.5);">
            <small>© 2026 FindyWear. All rights reserved.</small>
        </div>
    </div>
</footer>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-10px); }
}
</style>

<?php include 'includes/footer.php'; ?>