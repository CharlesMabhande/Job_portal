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

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3">Create Candidate Account</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                    <div class="col-12 col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autocomplete="email">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Register</button>
                        <span class="ms-2 small text-muted">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

