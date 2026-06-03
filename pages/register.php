<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in na redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'customer') header('Location: /findywearce/customer/home.php');
    elseif ($_SESSION['role'] === 'shop_owner') header('Location: /findywearce/shop-owner/dashboard.php');
    elseif ($_SESSION['role'] === 'admin') header('Location: /findywearce/admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);
    $phone    = trim($_POST['phone']);
    $role     = $_POST['role'];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields!';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif (!in_array($role, ['customer', 'shop_owner'])) {
        $error = 'Please select a valid role!';
    } else {
        // Email already exists check
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = 'Email already registered! Please login.';
        } else {
            // Insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = mysqli_prepare($conn,
                "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed, $phone, $role);

            if (mysqli_stmt_execute($stmt)) {
                header('Location: /findywearce/pages/login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed! Please try again.';
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<style>
    body { margin-top: 0 !important; }
    div[style*="margin-top: 70px"] { display: none; }
</style>

<div class="auth-wrapper" style="padding: 40px 20px;">
    <div class="auth-card" style="max-width: 520px;">

        <!-- Logo -->
        <div class="logo">
            <i class="fas fa-tshirt"></i>
            <h2>FindyWear</h2>
            <p>Create your account</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <!-- Role Selection -->
            <div class="mb-4">
                <label class="form-label">I am a...</label>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="role-card <?php echo (isset($_POST['role']) && $_POST['role']==='customer') ? 'selected' : ''; ?>"
                            onclick="selectRole('customer')">
                            <i class="fas fa-user"></i>
                            <h6>Customer</h6>
                            <small class="text-muted">Shop nearby</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="role-card <?php echo (isset($_POST['role']) && $_POST['role']==='shop_owner') ? 'selected' : ''; ?>"
                            onclick="selectRole('shop_owner')">
                            <i class="fas fa-store"></i>
                            <h6>Shop Owner</h6>
                            <small class="text-muted">Sell online</small>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="role" id="roleInput"
                    value="<?php echo isset($_POST['role']) ? $_POST['role'] : ''; ?>">
                <div id="roleError" class="text-danger small mt-1" style="display:none;">
                    Please select a role!
                </div>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" class="form-control"
                        placeholder="Enter your full name"
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                        required>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                        placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required>
                </div>
            </div>

            <!-- Phone -->
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="text" name="phone" class="form-control"
                        placeholder="Enter your phone number"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="passwordField"
                        class="form-control" placeholder="Min 6 characters" required>
                    <button class="btn btn-outline-secondary" type="button"
                        onclick="togglePassword('passwordField', 'eyeIcon1')">
                        <i class="fas fa-eye" id="eyeIcon1"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirm_password" id="confirmField"
                        class="form-control" placeholder="Re-enter password" required>
                    <button class="btn btn-outline-secondary" type="button"
                        onclick="togglePassword('confirmField', 'eyeIcon2')">
                        <i class="fas fa-eye" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 mb-3"
                onclick="return validateForm()">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>

        </form>

        <hr>

        <p class="text-center mb-0">
            Already have an account?
            <a href="/findywearce/pages/login.php" class="fw-bold"
                style="color: var(--primary);">Login here</a>
        </p>

    </div>
</div>

<script>
// Role select
function selectRole(role) {
    document.getElementById('roleInput').value = role;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('roleError').style.display = 'none';
}

// Password toggle
function togglePassword(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    if (field.type === 'password') {
        field.type    = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type    = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Form validate
function validateForm() {
    const role = document.getElementById('roleInput').value;
    if (!role) {
        document.getElementById('roleError').style.display = 'block';
        return false;
    }
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>