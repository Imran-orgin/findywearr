<?php
require_once '../config/database.php';
if(session_status() === PHP_SESSION_NONE) session_start();

//Already logged in na redirect to home
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'customer') header('Location: ../findywearce/customer/home.php');
    elseif($_SESSION['role'] === 'shop_owner') header('Location: ../findywearce/shop-owner/dashboard.php');
    elseif($_SESSION['role'] === 'admin') header('Location: ../findywearce/admin/dashboard.php');
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if(empty($email) || empty($password)){
        $error = 'Please fill in all fields!';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if($user && password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

          if ($user['role'] === 'customer')
    header('Location: /findywearce/customer/home.php');
elseif ($user['role'] === 'shop_owner')
   header('Location: /findywearce/shop-owner/dashboard.php');
elseif ($user['role'] === 'admin')
    header('Location: /findywearce/admin/dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password!';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<!-- Remove navbar margin for auth page -->
<style>
    body { margin-top: 0 !important; }
    div[style*="margin-top: 70px"] { display: none; }
</style>

<div class="auth-wrapper">
    <div class="auth-card">

     <!-- Logo -->
      <div class="logo">
        <i class="fas fa-tshirt"></i>
        <h2>FindyWearce</h2>
        <p>Login to your account</p>
      </div>

      <!-- Error / Success Messages -->
      <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
      <?php endif; ?>

      <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            Registration successful! Please login.
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control"
                     placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                     required>

                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="passwordField" 
                    class="form-control" placeholder="Enter your password" required>
                    <button class="btn btn-outline-secondary" type="button"
                    onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <hr>

        <p class="text-center mb-0">
            Don't have an account? 
            <a href="/findywearce/pages/register.php" class="fw-bold"
            style="color: var(--primary);">Register here</a>
        </p>

    </div>
</div>

<script>
function togglePassword() {
    const Field = document.getElementById('passwordField');
    const eyeIcon = document.getElementById('eyeIcon');
    if(Field.type === 'password') {
        Field.type = 'text';
        eyeIcon.className = 'fas fa-eye-slash';
    } else {
        Field.type = 'password';
        eyeIcon.className = 'fas fa-eye';
    }
}
</script>

<?php include '../includes/footer.php'; ?>