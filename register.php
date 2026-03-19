<?php
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$pageTitle = 'Register - University Job Portal';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();

    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = validateInput([
        'email' => $email,
        'password' => $password,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone,
        'confirm_password' => $confirmPassword,
    ], [
        'email' => 'required|email',
        'password' => 'required|min:8',
        'first_name' => 'required',
        'last_name' => 'required',
        'phone' => 'required',
        'confirm_password' => 'required'
    ]);

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if ($errors) {
        $error = implode(' ', array_values($errors));
    } else {
        $result = registerUser($email, $password, $firstName, $lastName, $phone, 1);
        if ($result['success']) {
            sendWelcomeEmail($email, $firstName);
            loginUser($email, $password);
            redirect('/candidate/dashboard.php', 'Account created successfully.', 'success');
        }
        $error = $result['message'] ?? 'Registration failed';
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="register-page py-3">
    <div class="register-card">
        <div class="register-header">
            <div class="mb-2">
                <i class="bi bi-person-plus-fill display-6"></i>
            </div>
            <h1>Candidate Registration</h1>
            <p>Create your account to start applying for jobs</p>
        </div>

        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-1"></i> <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2" style="border-bottom: 2px solid #e8e8e8;">
                        <span style="background: #2e37a4; color: #fff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 600;">1</span>
                        <span class="fw-semibold" style="color: #2e37a4;">Personal Information</span>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                        <input type="text" name="first_name" class="form-control" placeholder="Enter first name" required
                               value="<?php echo escape($_POST['first_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                        <input type="text" name="last_name" class="form-control" placeholder="Enter last name" required
                               value="<?php echo escape($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email"
                               value="<?php echo escape($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-text">Used for job alerts and application updates.</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="Include country code" required
                               value="<?php echo escape($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2" style="border-bottom: 2px solid #e8e8e8;">
                        <span style="background: #2e37a4; color: #fff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 600;">2</span>
                        <span class="fw-semibold" style="color: #2e37a4;">Account Security</span>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="form-text">Use a mix of letters, numbers, and symbols.</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-center justify-content-between">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="bi bi-person-plus me-1"></i> Create Account
                        </button>
                        <span class="text-muted">
                            Already have an account?
                            <a href="<?php echo BASE_URL; ?>/login.php" class="fw-semibold">Login</a>
                        </span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-center mt-2">
                        <a href="<?php echo BASE_URL; ?>/index.php" class="small text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Return to home page
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
