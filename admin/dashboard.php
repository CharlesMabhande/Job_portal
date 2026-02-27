<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['SysAdmin']);

$pageTitle = 'SysAdmin Dashboard';
$db = getDBConnection();

$users = (int)($db->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'] ?? 0);
$jobs  = (int)($db->query("SELECT COUNT(*) AS c FROM jobs")->fetch()['c'] ?? 0);
$apps  = (int)($db->query("SELECT COUNT(*) AS c FROM applications")->fetch()['c'] ?? 0);
$logs  = (int)($db->query("SELECT COUNT(*) AS c FROM audit_logs")->fetch()['c'] ?? 0);

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">System Administration</h1>
        <div class="text-muted">Manage users, roles, settings, and audit trails.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/admin/users.php">Manage Users</a>
        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/admin/settings.php">Settings</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Users</div><div class="h4 mb-0"><?php echo $users; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Jobs</div><div class="h4 mb-0"><?php echo $jobs; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Applications</div><div class="h4 mb-0"><?php echo $apps; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Audit Logs</div><div class="h4 mb-0"><?php echo $logs; ?></div>
    </div></div></div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

