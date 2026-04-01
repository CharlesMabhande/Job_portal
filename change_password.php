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
        <div class="card animate-in" style="border: none; box-shadow: 0 8px 30px rgba(0,0,0,.08); border-radius: 16px; overflow: hidden;">
            <div style="background: #c61f26; padding: 1.75rem 1.5rem; text-align: center;">
                <i class="bi bi-shield-lock-fill" style="font-size: 2.5rem; color: rgba(255,255,255,.9);"></i>
                <h1 class="h4 mt-2 mb-1" style="color: #fff;">Change Password</h1>
                <p style="color: rgba(255,255,255,.7); font-size: .9rem; margin: 0;">Keep your account secure with a strong password.</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?php echo escape($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> <?php echo escape($success); ?></div>
                <?php endif; ?>

                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                    <div>
                        <label class="form-label fw-semibold">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                            <input type="password" name="current_password" class="form-control" required autocomplete="current-password" placeholder="Your existing login password" title="The password you use to sign in today">
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password" placeholder="e.g. NewStr0ng!Pass (min 8 characters)" title="At least 8 characters; different from current password">
                        </div>
                        <div class="form-text">Use a mix of letters, numbers, and symbols.</div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password" placeholder="Re-type the new password exactly" title="Must match new password">
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg" type="submit">
                        <i class="bi bi-check-lg me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
