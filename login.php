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

$currentUser = getCurrentUser();
$flash = getFlashMessage();
$csrf = generateCSRFToken();
$appCssVersion = @filemtime(BASE_PATH . '/assets/css/app.css') ?: time();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/app.css?v=<?php echo (int)$appCssVersion; ?>" rel="stylesheet">
</head>
<body style="padding-top:0 !important;">
<div class="login-split">
    <!-- Left: Branding -->
    <div class="login-branding">
        <div>
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="fa-solid fa-briefcase fs-2"></i>
                <span class="fw-bold fs-5">University Job Portal</span>
            </div>
            <h2>Welcome back</h2>
            <p>Login to track your applications, manage your profile, and discover new career opportunities.</p>

            <ul class="brand-features">
                <li>Track your application status in real-time</li>
                <li>Get notified about new job openings</li>
                <li>Manage your profile and documents</li>
                <li>Schedule and prepare for interviews</li>
            </ul>
        </div>
    </div>

    <!-- Right: Login Form -->
    <div class="login-form-side">
        <div class="login-form-card">
            <div class="text-center mb-4 d-md-none">
                <i class="fa-solid fa-briefcase fs-1" style="color: #2e37a4;"></i>
                <h5 class="mt-2" style="color: #2e37a4;">University Job Portal</h5>
            </div>

            <h1>Sign In</h1>
            <p class="text-muted mb-4">Enter your credentials to access your account.</p>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo escape($flash['type'] === 'error' ? 'danger' : $flash['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo escape($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                <div>
                    <label class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
                    </div>
                </div>

                <div>
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login Now
                </button>

                <div class="text-center">
                    <span class="text-muted">Don't have an account?</span>
                    <a href="<?php echo BASE_URL; ?>/register.php" class="fw-semibold">Register</a>
                </div>

                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>/index.php" class="small text-muted">
                        <i class="fa-solid fa-arrow-left me-1"></i> Return to home page
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
