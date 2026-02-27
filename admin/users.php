<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['SysAdmin']);

$pageTitle = 'User Management';
$db = getDBConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    $action = $_POST['action'] ?? 'update_user';

    if ($action === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            $error = 'Use the Change Password page to change your own password.';
        } elseif (empty($newPassword) || strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            $result = adminResetUserPassword($userId, $newPassword);
            if ($result['success']) {
                redirect('/admin/users.php', 'Password reset successfully.', 'success');
            } else {
                $error = $result['message'] ?? 'Failed to reset password.';
            }
        }
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $roleId = (int)($_POST['role_id'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            $error = 'You cannot change your own role/status.';
        } else {
            $stmt = $db->prepare("UPDATE users SET role_id = ?, is_active = ? WHERE user_id = ?");
            $stmt->execute([$roleId, $isActive, $userId]);
            logAudit('user_updated_by_sysadmin', 'users', $userId, null, ['role_id' => $roleId, 'is_active' => $isActive]);
            redirect('/admin/users.php', 'User updated.', 'success');
        }
    }
}

$roles = $db->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll();
$users = $db->query("
    SELECT u.user_id, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.role_id, r.role_name, u.created_at
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
    LIMIT 300
")->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Users</h1>
        <div class="text-muted">Assign roles and enable/disable accounts.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo escape($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Active</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo escape($u['first_name'] . ' ' . $u['last_name']); ?></td>
                            <td class="text-muted"><?php echo escape($u['email']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($u['role_name']); ?></span></td>
                            <td><?php echo (int)$u['is_active'] === 1 ? 'Yes' : 'No'; ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#e<?php echo (int)$u['user_id']; ?>">Edit</button>
                            </td>
                        </tr>
                        <tr class="collapse" id="e<?php echo (int)$u['user_id']; ?>">
                            <td colspan="5">
                                <form method="post" class="row g-2 align-items-end mb-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">

                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">Role</label>
                                        <select class="form-select form-select-sm" name="role_id">
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?php echo (int)$r['role_id']; ?>" <?php echo (int)$u['role_id'] === (int)$r['role_id'] ? 'selected' : ''; ?>>
                                                    <?php echo escape($r['role_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label small">Active</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="a<?php echo (int)$u['user_id']; ?>" <?php echo (int)$u['is_active'] === 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label small" for="a<?php echo (int)$u['user_id']; ?>">Account enabled</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5 text-md-end">
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                    </div>
                                </form>

                                <form method="post" class="row g-2 align-items-end">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">

                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">New Password</label>
                                        <input type="password" class="form-control form-control-sm" name="new_password" minlength="8" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">Confirm Password</label>
                                        <input type="password" class="form-control form-control-sm" name="confirm_password" minlength="8" required>
                                    </div>
                                    <div class="col-12 col-md-4 text-md-end">
                                        <label class="form-label small d-block">&nbsp;</label>
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Reset Password</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

