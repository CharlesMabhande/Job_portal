<?php
require_once __DIR__ . '/config/config.php';

requireLogin();

$pageTitle = 'Change Password';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validation
    $errors = validateInput([
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'confirm_password' => $confirmPassword
    ], [
        'current_password' => 'required',
        'new_password' => 'required|min:8',
        'confirm_password' => 'required'
    ]);

    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'New passwords do not match';
    }

    if (!empty($errors)) {
        $error = reset($errors);
    } else {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $result = changeUserPassword($userId, $currentPassword, $newPassword);

        if ($result['success']) {
            $success = 'Password updated successfully.';
        } else {
            $error = $result['message'] ?? 'Failed to change password.';
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3">Change Password</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo escape($success); ?></div>
                <?php endif; ?>

                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                    <div>
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Use at least 8 characters, with a mix of letters, numbers, and symbols.</div>
                    </div>

                    <div>
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>

                    <button class="btn btn-primary" type="submit">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

