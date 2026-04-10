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

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-bar-chart-line me-2"></i>Management Dashboard</h1>
        <p>Approve job postings and oversee hiring decisions.</p>
    </div>
    <div class="page-actions d-flex flex-wrap gap-2">
        <a class="btn btn-light btn-sm text-primary fw-bold" href="<?php echo BASE_URL; ?>/management/jobs.php">
            <i class="bi bi-check-circle me-1"></i> Review Approvals
        </a>
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/management/published-jobs.php">
            <i class="bi bi-table me-1"></i> Job summary tables
        </a>
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/hr/applications.php">
            <i class="bi bi-people me-1"></i> Applications
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-4">
        <div class="stat-card-rich">
            <div class="stat-accent amber"></div>
            <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?php echo $pending; ?></div>
            <div class="stat-label">Pending Approvals</div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card-rich">
            <div class="stat-accent green"></div>
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value"><?php echo $active; ?></div>
            <div class="stat-label">Active Jobs</div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="stat-card-rich">
            <div class="stat-accent blue"></div>
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?php echo $apps; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
