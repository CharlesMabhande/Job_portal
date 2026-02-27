<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['Management', 'SysAdmin']);

$pageTitle = 'Management Dashboard';
$db = getDBConnection();

$pending = (int)($db->query("SELECT COUNT(*) AS c FROM jobs WHERE status = 'Pending Approval'")->fetch()['c'] ?? 0);
$active  = (int)($db->query("SELECT COUNT(*) AS c FROM jobs WHERE status = 'Active'")->fetch()['c'] ?? 0);
$apps    = (int)($db->query("SELECT COUNT(*) AS c FROM applications")->fetch()['c'] ?? 0);

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Management Dashboard</h1>
        <div class="text-muted">Approve job postings and oversee hiring decisions.</div>
    </div>
    <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/management/jobs.php">Review Approvals</a>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-4"><div class="card"><div class="card-body">
        <div class="text-muted small">Pending Approvals</div><div class="h4 mb-0"><?php echo $pending; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-4"><div class="card"><div class="card-body">
        <div class="text-muted small">Active Jobs</div><div class="h4 mb-0"><?php echo $active; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-4"><div class="card"><div class="card-body">
        <div class="text-muted small">Total Applications</div><div class="h4 mb-0"><?php echo $apps; ?></div>
    </div></div></div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

