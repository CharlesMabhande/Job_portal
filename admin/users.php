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

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-people-fill me-2"></i>User Management</h1>
        <p>Assign roles, enable/disable accounts, and reset passwords.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?php echo escape($error); ?></div>
<?php endif; ?>

<div class="card animate-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo escape($u['first_name'] . ' ' . $u['last_name']); ?></div>
                                <div class="small text-muted"><?php echo escape(date('M j, Y', strtotime($u['created_at']))); ?></div>
                            </td>
                            <td class="text-muted"><?php echo escape($u['email']); ?></td>
                            <td>
                                <?php
                                $roleBadges = ['Candidate' => 'status-under-review', 'HR' => 'status-shortlisted', 'Management' => 'status-interview', 'SysAdmin' => 'status-offer'];
                                $cls = $roleBadges[$u['role_name']] ?? 'status-pending';
                                ?>
                                <span class="status-badge <?php echo $cls; ?>"><?php echo escape($u['role_name']); ?></span>
                            </td>
                            <td>
                                <?php if ((int)$u['is_active'] === 1): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-rejected">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#e<?php echo (int)$u['user_id']; ?>">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="e<?php echo (int)$u['user_id']; ?>">
                            <td colspan="5" style="background: #f3f3f4; border-radius: 12px;">
                                <div class="p-2">
                                    <form method="post" class="row g-2 align-items-end mb-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">

                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold">Role</label>
                                            <select class="form-select form-select-sm" name="role_id">
                                                <?php foreach ($roles as $r): ?>
                                                    <option value="<?php echo (int)$r['role_id']; ?>" <?php echo (int)$u['role_id'] === (int)$r['role_id'] ? 'selected' : ''; ?>>
                                                        <?php echo escape($r['role_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label class="form-label small fw-bold">Account</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="a<?php echo (int)$u['user_id']; ?>" <?php echo (int)$u['is_active'] === 1 ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="a<?php echo (int)$u['user_id']; ?>">Enabled</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-5 text-md-end">
                                            <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i> Save Role</button>
                                        </div>
                                    </form>

                                    <hr class="my-2">

                                    <form method="post" class="row g-2 align-items-end">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">

                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold">New Password</label>
                                            <input type="password" class="form-control form-control-sm" name="new_password" minlength="8" required placeholder="e.g. TempPass2026! (min 8 chars)" title="At least 8 characters; mix letters, numbers, symbols">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-bold">Confirm Password</label>
                                            <input type="password" class="form-control form-control-sm" name="confirm_password" minlength="8" required placeholder="Re-type the new password exactly" title="Must match new password">
                                        </div>
                                        <div class="col-12 col-md-4 text-md-end">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="bi bi-key me-1"></i> Reset Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
