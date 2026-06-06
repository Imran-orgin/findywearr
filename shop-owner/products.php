<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    header('Location: /findywearce/pages/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];

// Shop fetch
$shop_stmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE owner_id = ?");
mysqli_stmt_bind_param($shop_stmt, "i", $owner_id);
mysqli_stmt_execute($shop_stmt);
$shop = mysqli_fetch_assoc(mysqli_stmt_get_result($shop_stmt));

if (!$shop) {
    header('Location: /findywearce/shop-owner/setup-shop.php');
    exit();
}

$shop_id = $shop['id'];
$error   = '';
$success = '';

// Delete product
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $del    = mysqli_prepare($conn, "DELETE FROM products WHERE id = ? AND shop_id = ?");
    mysqli_stmt_bind_param($del, "ii", $del_id, $shop_id);
    mysqli_stmt_execute($del);
    header('Location: /findywearce/shop-owner/products.php?deleted=1');
    exit();
}

// Add / Edit product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $category    = trim($_POST['category']);
    $size        = trim($_POST['size']);
    $color       = trim($_POST['color']);
    $edit_id     = intval($_POST['edit_id'] ?? 0);

    // Image upload
    $image = 'default-product.png';
    if (!empty($_FILES['image']['name'])) {
        $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image      = 'prod_' . time() . '.' . $ext;
        $upload_dir = '../public/images/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
    }

    if (empty($name) || $price <= 0) {
        $error = 'Please fill required fields!';
    } elseif ($edit_id > 0) {
        // Edit
        if (!empty($_FILES['image']['name'])) {
            $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, description=?, price=?, stock=?, category=?, size=?, color=?, image=? WHERE id=? AND shop_id=?");
            mysqli_stmt_bind_param($stmt, "ssdissssii", $name, $description, $price, $stock, $category, $size, $color, $image, $edit_id, $shop_id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, description=?, price=?, stock=?, category=?, size=?, color=? WHERE id=? AND shop_id=?");
            mysqli_stmt_bind_param($stmt, "ssdisssii", $name, $description, $price, $stock, $category, $size, $color, $edit_id, $shop_id);
        }
        mysqli_stmt_execute($stmt);
        $success = 'Product updated successfully!';
    } else {
        // Add new
        $stmt = mysqli_prepare($conn, "INSERT INTO products (shop_id, name, description, price, stock, category, size, color, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issdissss", $shop_id, $name, $description, $price, $stock, $category, $size, $color, $image);
        mysqli_stmt_execute($stmt);
        $success = 'Product added successfully!';
    }
}

// Edit fetch
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id   = intval($_GET['edit']);
    $edit_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND shop_id = ?");
    mysqli_stmt_bind_param($edit_stmt, "ii", $edit_id, $shop_id);
    mysqli_stmt_execute($edit_stmt);
    $edit_product = mysqli_fetch_assoc(mysqli_stmt_get_result($edit_stmt));
}

// Fetch all products
$prod_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE shop_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($prod_stmt, "i", $shop_id);
mysqli_stmt_execute($prod_stmt);
$products = mysqli_stmt_get_result($prod_stmt);
?>
<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <div class="row g-4">

        <!-- Add / Edit Form -->
        <div class="col-lg-4">
            <div class="fw-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-<?php echo $edit_product ? 'edit' : 'plus'; ?> me-2"
                        style="color:var(--primary);"></i>
                    <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
                </h5>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-trash me-2"></i>Product deleted!
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id"
                        value="<?php echo $edit_product ? $edit_product['id'] : 0; ?>">

                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>"
                            placeholder="e.g. Blue Shirt" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"
                            rows="2" placeholder="Product description..."><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Price (LKR) *</label>
                            <input type="number" name="price" class="form-control"
                                value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>"
                                placeholder="0.00" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stock *</label>
                            <input type="number" name="stock" class="form-control"
                                value="<?php echo $edit_product ? $edit_product['stock'] : ''; ?>"
                                placeholder="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control">
                            <option value="">Select Category</option>
                            <?php
                            $cats = ['mens' => "Men's Wear", 'womens' => "Women's Wear",
                                     'kids' => "Kids Wear", 'traditional' => "Traditional"];
                            foreach ($cats as $val => $label):
                                $sel = ($edit_product && $edit_product['category'] == $val) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $sel; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Size</label>
                            <input type="text" name="size" class="form-control"
                                value="<?php echo $edit_product ? htmlspecialchars($edit_product['size']) : ''; ?>"
                                placeholder="S, M, L, XL">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" class="form-control"
                                value="<?php echo $edit_product ? htmlspecialchars($edit_product['color']) : ''; ?>"
                                placeholder="Red, Blue...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="image" class="form-control"
                            accept="image/*">
                        <?php if ($edit_product && $edit_product['image'] !== 'default-product.png'): ?>
                        <small class="text-muted">Current: <?php echo $edit_product['image']; ?></small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100">
                        <i class="fas fa-save me-2"></i>
                        <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                    </button>

                    <?php if ($edit_product): ?>
                    <a href="/findywearce/shop-owner/products.php"
                        class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Products List -->
        <div class="col-lg-8">
            <div class="fw-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-tshirt me-2" style="color:var(--primary);"></i>
                        My Products
                    </h5>
                    <a href="/findywearce/shop-owner/dashboard.php"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                </div>

                <?php if (mysqli_num_rows($products) === 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No products yet! Add your first product.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/findywearce/public/images/products/<?php echo $product['image']; ?>"
                                                onerror="this.src='https://placehold.co/40x40/667eea/white?text=P'"
                                                class="rounded me-2"
                                                style="width:40px;height:40px;object-fit:cover;">
                                            <div>
                                                <p class="fw-bold mb-0 small">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <?php echo $product['size'] ? 'Size: '.$product['size'] : ''; ?>
                                                    <?php echo $product['color'] ? '• '.$product['color'] : ''; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>LKR <?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="fw-bold <?php echo $product['stock'] < 5 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['status'] === 'available'): ?>
                                            <span class="badge-delivered">Available</span>
                                        <?php else: ?>
                                            <span class="badge-cancelled">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $product['id']; ?>"
                                            class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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