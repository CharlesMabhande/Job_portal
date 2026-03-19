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

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-gear-wide-connected me-2"></i>System Administration</h1>
        <p>Manage users, roles, system settings, and audit trails.</p>
    </div>
    <div class="page-actions d-flex gap-2">
        <a class="btn btn-light btn-sm text-primary fw-bold" href="<?php echo BASE_URL; ?>/admin/users.php">
            <i class="bi bi-people me-1"></i> Manage Users
        </a>
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/admin/settings.php">
            <i class="bi bi-sliders me-1"></i> Settings
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent blue"></div>
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?php echo $users; ?></div>
            <div class="stat-label">Users</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent green"></div>
            <div class="stat-icon green"><i class="bi bi-briefcase-fill"></i></div>
            <div class="stat-value"><?php echo $jobs; ?></div>
            <div class="stat-label">Jobs</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent amber"></div>
            <div class="stat-icon amber"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div class="stat-value"><?php echo $apps; ?></div>
            <div class="stat-label">Applications</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent purple"></div>
            <div class="stat-icon purple"><i class="bi bi-journal-text"></i></div>
            <div class="stat-value"><?php echo $logs; ?></div>
            <div class="stat-label">Audit Logs</div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
