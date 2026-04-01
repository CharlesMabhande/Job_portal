<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['SysAdmin']);

$pageTitle = 'System Settings';
$db = getDBConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    $key = sanitize($_POST['setting_key'] ?? '');
    $value = $_POST['setting_value'] ?? '';

    if ($key === '') {
        $error = 'Invalid setting key.';
    } else {
        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?");
        $stmt->execute([$value, (int)$_SESSION['user_id'], $key]);
        logAudit('system_setting_updated', 'system_settings', null, null, ['setting_key' => $key]);
        redirect('/admin/settings.php', 'Setting updated.', 'success');
    }
}

$settings = $db->query("SELECT setting_key, setting_value, description, updated_at FROM system_settings ORDER BY setting_key")->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex jp-page-toolbar justify-content-between align-items-center mb-3">
    <div class="min-w-0">
        <h1 class="h3 mb-0">System Settings</h1>
        <div class="text-muted small">System-wide configuration (maintenance mode, file limits, etc.).</div>
    </div>
    <a class="btn btn-outline-secondary flex-shrink-0" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Back</a>
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
                        <th>Key</th>
                        <th>Value</th>
                        <th>Description</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settings as $s): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo escape($s['setting_key']); ?></td>
                            <td class="text-muted text-truncate" style="max-width: 240px;"><?php echo escape((string)$s['setting_value']); ?></td>
                            <td class="text-muted"><?php echo escape($s['description'] ?? ''); ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#s<?php echo escape($s['setting_key']); ?>">Edit</button>
                            </td>
                        </tr>
                        <tr class="collapse" id="s<?php echo escape($s['setting_key']); ?>">
                            <td colspan="4">
                                <form method="post" class="row g-2 align-items-end">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="setting_key" value="<?php echo escape($s['setting_key']); ?>">
                                    <div class="col-12 col-md-9">
                                        <label class="form-label small">Value</label>
                                        <input class="form-control" name="setting_value" value="<?php echo escape((string)$s['setting_value']); ?>" placeholder="<?php echo escape(settingValuePlaceholder($s['setting_key'])); ?>" title="<?php echo escape($s['description'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12 col-md-3 text-md-end">
                                        <button class="btn btn-primary" type="submit">Save</button>
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

