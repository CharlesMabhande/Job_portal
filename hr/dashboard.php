<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

$pageTitle = 'HR Dashboard';
$db = getDBConnection();

$jobsCount = (int)($db->query("SELECT COUNT(*) AS c FROM jobs")->fetch()['c'] ?? 0);
$appsCount = (int)($db->query("SELECT COUNT(*) AS c FROM applications")->fetch()['c'] ?? 0);
$pendingApproval = (int)($db->query("SELECT COUNT(*) AS c FROM jobs WHERE status = 'Pending Approval'")->fetch()['c'] ?? 0);
$pendingApps = (int)($db->query("SELECT COUNT(*) AS c FROM applications WHERE status IN ('Pending','Under Review')")->fetch()['c'] ?? 0);

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">HR Dashboard</h1>
        <div class="text-muted">Manage job postings, applications, interviews, and reports.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/hr/job_edit.php">Create Job</a>
        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/hr/applications.php">Review Applications</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Total Jobs</div><div class="h4 mb-0"><?php echo $jobsCount; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Pending Approval</div><div class="h4 mb-0"><?php echo $pendingApproval; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Total Applications</div><div class="h4 mb-0"><?php echo $appsCount; ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Pending Review</div><div class="h4 mb-0"><?php echo $pendingApps; ?></div>
    </div></div></div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

