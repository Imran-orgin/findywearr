<?php
if (session_status() == PHP_SESSION_NONE){
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindyWear - Local Fashion Near You</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/findywearce/public/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNavbar">
        <div class="container">
            <!-- Logo -->
             <a class="navbar-brand fw-bold fs-4" href="/findywearce/index.php">
                <i class="fas fa-tshirt me-2"></i>FindyWear
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">

                <?php if (isset($_SESSION['user_id'])): ?>

                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="findywearce/customer/home.php">
                                <i class="fas fa-store me-1"></i>Shops
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="findywearce/customer/cart.php">
                                <i class="fas fa-shopping-cart me-1"></i>Cart
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav_link" href="/findywearce/customer/orders.php">
                                <i class="fas fa-box me-1"></i>Orders
                            </a>
                        </li>
                        <?php endif; ?>

                    <?php if ($_SESSION['role'] === 'shop_owner'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="findywearce/shop-owner/dashboard.php">
                                <i class="fas fa-chart-bar me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="findywearce/shop-owner/products.php">
                                <i class="fas fa-tshirt me-1"></i>Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="findywearce/shop-owner/orders.php">
                                <i class="fas fa-list me-1"></i>Orders
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="findywearce/admin/dashboard.php">
                                    <i class="fas fa-cogs me-1"></i>Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Notification Bell -->
                         <li class="nav-item me-2">
                            <a class="nav-link position-relative" href="#">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifCount" style="display:none;">0</span>
                            </a>
                         </li>

                         <!-- User Dropdown -->
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/findywearce/pages/profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-divider"></a></li>
                                <li><a class="dropdown-item text-danger" href="/findywearce/pages/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                          </li>

                          <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/findywearce/pages/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </a>
                          </li>
                          <li class="nav-item ms-2">
                            <a class="btn btn-warning" fw-bold px-3 href="/findywearce/pages/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                          </li>
                          <?php endif; ?>
                </ul>

            </div>
        </div>
    </nav>

    <!-- Space for fixed navbar -->
     div style="margin-top: 70px;"></div>
</body>
</html>