<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['SysAdmin']);

$pageTitle = 'Audit Logs';
$db = getDBConnection();

$stmt = $db->prepare("
    SELECT l.log_id, l.action, l.table_name, l.record_id, l.ip_address, l.created_at,
           u.email
    FROM audit_logs l
    LEFT JOIN users u ON l.user_id = u.user_id
    ORDER BY l.created_at DESC
    LIMIT 300
");
$stmt->execute();
$logs = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex jp-page-toolbar justify-content-between align-items-center mb-3">
    <div class="min-w-0">
        <h1 class="h3 mb-0">Audit Logs</h1>
        <div class="text-muted small">System activity trail for security oversight.</div>
    </div>
    <a class="btn btn-outline-secondary flex-shrink-0" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record</th>
                        <th>User</th>
                        <th>IP</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$logs): ?>
                        <tr><td colspan="7" class="text-muted">No logs.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="text-muted"><?php echo (int)$l['log_id']; ?></td>
                            <td class="fw-semibold"><?php echo escape($l['action']); ?></td>
                            <td class="text-muted"><?php echo escape($l['table_name'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape($l['record_id'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape($l['email'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape($l['ip_address'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y g:i A', strtotime($l['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

