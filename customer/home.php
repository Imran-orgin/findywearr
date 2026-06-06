<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /findywearce/pages/login.php');
    exit();
}
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Welcome -->
    <div class="row mb-4 mt-3">
        <div class="col-12">
            <h4 class="fw-bold">
                <i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i>
                Hi, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋
            </h4>
            <p class="text-muted" id="subText">Finding shops near you...</p>
        </div>
    </div>

    <!-- Location Alert -->
    <div id="locationAlert" class="alert alert-info">
        <i class="fas fa-spinner fa-spin me-2"></i>
        Detecting your location...
    </div>

    <!-- Search & Filter -->
    <div class="row mb-4" id="searchSection" style="display:none!important;">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control"
                    placeholder="Search shops...">
            </div>
        </div>
        <div class="col-md-3 mt-2 mt-md-0">
            <select class="form-control" id="categoryFilter">
                <option value="">All Categories</option>
                <option value="mens">Men's Wear</option>
                <option value="womens">Women's Wear</option>
                <option value="kids">Kids Wear</option>
                <option value="traditional">Traditional</option>
            </select>
        </div>
        <div class="col-md-3 mt-2 mt-md-0">
            <div class="fw-card text-center py-2">
                <strong id="shopCount" style="color: var(--primary);">0</strong>
                <small class="text-muted"> shops within 5km</small>
            </div>
        </div>
    </div>

    <!-- Shops Grid -->
    <div class="row g-4" id="shopsGrid">
        <!-- Shops load here via JS -->
    </div>

    <!-- No Shops Found -->
    <div id="noShops" style="display:none;">
        <div class="text-center py-5">
            <i class="fas fa-store-slash fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No shops found within 5km</h5>
            <p class="text-muted">Try expanding your search area</p>
        </div>
    </div>

</div>

<script>
let allShops = [];
let userLat = null;
let userLng = null;

// Get user location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(position) {
            userLat = position.coords.latitude;
            userLng = position.coords.longitude;
            loadNearbyShops(userLat, userLng);
        },
        function(error) {
            // Location denied - use default (Colombo)
            userLat = 6.9271;
            userLng = 79.8612;
            document.getElementById('locationAlert').innerHTML =
                '<i class="fas fa-exclamation-triangle me-2"></i>Location access denied. Showing shops in Colombo area.';
            document.getElementById('locationAlert').className = 'alert alert-warning';
            loadNearbyShops(userLat, userLng);
        }
    );
} else {
    document.getElementById('locationAlert').innerHTML =
        '<i class="fas fa-exclamation-circle me-2"></i>Location not supported by your browser.';
    document.getElementById('locationAlert').className = 'alert alert-danger';
}

// Load shops from API
function loadNearbyShops(lat, lng) {
    fetch(`/findywearce/api/location.php?lat=${lat}&lng=${lng}`)
        .then(res => res.json())
        .then(data => {
            allShops = data.shops || [];
            document.getElementById('locationAlert').style.display = 'none';
            document.getElementById('subText').textContent = 'Shops near your location ✅';
            document.getElementById('subText').textContent = 'Shops near your location';
            document.getElementById('searchSection').style.display = 'flex!important';
            document.getElementById('searchSection').removeAttribute('style');
            document.getElementById('shopCount').textContent = allShops.length;
            renderShops(allShops);
        })
        .catch(err => {
            document.getElementById('locationAlert').innerHTML =
                '<i class="fas fa-exclamation-circle me-2"></i>Error loading shops. Please refresh.';
            document.getElementById('locationAlert').className = 'alert alert-danger';
        });
}

// Render shops
function renderShops(shops) {
    const grid = document.getElementById('shopsGrid');
    const noShops = document.getElementById('noShops');

    if (shops.length === 0) {
        grid.innerHTML = '';
        noShops.style.display = 'block';
        return;
    }

    noShops.style.display = 'none';
    grid.innerHTML = shops.map(shop => `
        <div class="col-md-4 col-sm-6 shop-item">
            <div class="shop-card" onclick="window.location='/findywearce/customer/shop.php?id=${shop.id}'">
                <img src="/findywearce/public/images/${shop.shop_image}"
                    onerror="this.src='https://placehold.co/400x200/667eea/white?text=${encodeURIComponent(shop.shop_name)}'"
                    alt="${shop.shop_name}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0">${shop.shop_name}</h5>
                        <span class="distance-badge">
                            <i class="fas fa-map-marker-alt me-1"></i>${shop.distance} km
                        </span>
                    </div>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-map-pin me-1"></i>${shop.address}
                    </p>
                    <p class="text-muted small mb-3">${shop.description || 'Local fashion shop'}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-phone me-1"></i>${shop.phone || 'N/A'}
                        </small>
                        <span class="btn btn-sm btn-primary-custom">
                            View Shop <i class="fas fa-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
    filterShops();
});

document.getElementById('categoryFilter').addEventListener('change', function() {
    filterShops();
});

function filterShops() {
    const search   = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value.toLowerCase();

    const filtered = allShops.filter(shop => {
        const matchSearch   = shop.shop_name.toLowerCase().includes(search) ||
                             (shop.address && shop.address.toLowerCase().includes(search));
        const matchCategory = !category ||
                             (shop.description && shop.description.toLowerCase().includes(category));
        return matchSearch && matchCategory;
    });

    document.getElementById('shopCount').textContent = filtered.length;
    renderShops(filtered);
}
</script>

<?php include '../includes/footer.php'; ?>