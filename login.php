<?php
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$pageTitle = 'Login - University Job Portal';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginUser($email, $password);
    if ($result['success']) {
        redirect('/dashboard.php', 'Welcome back!', 'success');
    }
    $error = $result['message'] ?? 'Login failed';
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3">Login</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autocomplete="email">
                    </div>

                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>

                    <button class="btn btn-primary" type="submit">Login</button>

                    <div class="small text-muted">
                        Don’t have an account? <a href="<?php echo BASE_URL; ?>/register.php">Register</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

