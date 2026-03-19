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

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-speedometer2 me-2"></i>HR Dashboard</h1>
        <p>Manage job postings, review applications, and schedule interviews.</p>
    </div>
    <div class="page-actions d-flex gap-2">
        <a class="btn btn-light btn-sm text-primary fw-bold" href="<?php echo BASE_URL; ?>/hr/job_edit.php">
            <i class="bi bi-plus-circle me-1"></i> Create Job
        </a>
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/hr/applications.php">
            <i class="bi bi-file-earmark-text me-1"></i> Review Applications
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent blue"></div>
            <div class="stat-icon blue"><i class="bi bi-briefcase"></i></div>
            <div class="stat-value"><?php echo $jobsCount; ?></div>
            <div class="stat-label">Total Jobs</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent amber"></div>
            <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?php echo $pendingApproval; ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent green"></div>
            <div class="stat-icon green"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?php echo $appsCount; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card-rich">
            <div class="stat-accent purple"></div>
            <div class="stat-icon purple"><i class="bi bi-clock-history"></i></div>
            <div class="stat-value"><?php echo $pendingApps; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
