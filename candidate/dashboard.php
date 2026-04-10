<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['Candidate']);

$pageTitle = 'Candidate Dashboard';
$db = getDBConnection();

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare("SELECT candidate_id FROM candidates WHERE user_id = ?");
$stmt->execute([$userId]);
$candidate = $stmt->fetch();
$candidateId = (int)($candidate['candidate_id'] ?? 0);

$countsStmt = $db->prepare("
    SELECT
      SUM(status = 'Pending') AS pending,
      SUM(status = 'Under Review') AS under_review,
      SUM(status = 'Shortlisted') AS shortlisted,
      SUM(status = 'Rejected') AS rejected
    FROM applications
    WHERE candidate_id = ?
");
$countsStmt->execute([$candidateId]);
$counts = $countsStmt->fetch() ?: [];

$recentStmt = $db->prepare("
    SELECT a.application_id, a.application_ref, a.status, a.applied_at, j.title, j.department
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.candidate_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$recentStmt->execute([$candidateId]);
$recent = $recentStmt->fetchAll();

$openSql = "status = 'Active' AND " . sqlJobApplicationOpen();
$sidebarStmt = $db->query("
    SELECT job_id, title, department, job_type, vacancy_scope, application_deadline
    FROM jobs
    WHERE {$openSql}
    ORDER BY created_at DESC
    LIMIT 8
");
$sidebarJobs = $sidebarStmt ? $sidebarStmt->fetchAll() : [];

require_once BASE_PATH . '/includes/header.php';

function candidateSidebarDaysLeft($deadline) {
    if (empty($deadline)) {
        return null;
    }
    $now = new DateTime();
    $end = new DateTime($deadline);
    if ($now > $end) {
        return -1;
    }
    return (int)$now->diff($end)->days;
}

function candidateJobTypeBadgeClass($type) {
    $map = [
        'Full-time' => 'badge-fulltime',
        'Part-time' => 'badge-parttime',
        'Contract' => 'badge-contract',
        'Internship' => 'badge-internship',
    ];
    return $map[$type] ?? 'badge-fulltime';
}

function statusBadgeClass($status) {
    $map = [
        'Pending' => 'status-pending',
        'Under Review' => 'status-under-review',
        'Shortlisted' => 'status-shortlisted',
        'Interview Scheduled' => 'status-interview',
        'Rejected' => 'status-rejected',
        'Offer Extended' => 'status-offer',
        'Accepted' => 'status-accepted',
        'Withdrawn' => 'status-withdrawn',
    ];
    return $map[$status] ?? 'status-pending';
}
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-hand-wave me-2"></i>Welcome, <?php echo escape($_SESSION['first_name']); ?></h1>
        <p>Track your applications and discover new career opportunities.</p>
    </div>
    <div class="page-actions d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/index.php">
            <i class="bi bi-search me-1"></i> Browse Jobs
        </a>
        <a class="btn btn-light btn-sm text-primary fw-bold" href="<?php echo BASE_URL; ?>/candidate/profile.php">
            <i class="bi bi-person-gear me-1"></i> Update Profile
        </a>
    </div>
</div>

<div class="row g-4 align-items-start mb-4">
    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card-rich">
                    <div class="stat-accent amber"></div>
                    <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-value"><?php echo (int)($counts['pending'] ?? 0); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card-rich">
                    <div class="stat-accent blue"></div>
                    <div class="stat-icon blue"><i class="bi bi-eye"></i></div>
                    <div class="stat-value"><?php echo (int)($counts['under_review'] ?? 0); ?></div>
                    <div class="stat-label">Under Review</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card-rich">
                    <div class="stat-accent green"></div>
                    <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-value"><?php echo (int)($counts['shortlisted'] ?? 0); ?></div>
                    <div class="stat-label">Shortlisted</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card-rich">
                    <div class="stat-accent red"></div>
                    <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
                    <div class="stat-value"><?php echo (int)($counts['rejected'] ?? 0); ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <div class="card animate-in">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-stretch align-items-sm-center mb-3">
                    <h2 class="h5 mb-0"><i class="bi bi-clock-history me-2" style="color: #c61f26"></i>Recent Applications</h2>
                    <a class="btn btn-sm btn-outline-primary flex-shrink-0" href="<?php echo BASE_URL; ?>/candidate/applications.php">
                        View all <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>App. no.</th>
                                <th>Job</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$recent): ?>
                                <tr><td colspan="5" class="text-center py-4">
                                    <i class="bi bi-inbox display-6 text-muted d-block mb-2"></i>
                                    <span class="text-muted">No applications yet. <a href="<?php echo BASE_URL; ?>/index.php">Start browsing jobs!</a></span>
                                </td></tr>
                            <?php endif; ?>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td class="text-nowrap small font-monospace fw-semibold"><?php echo escape($row['application_ref'] ?? ('#' . (int)$row['application_id'])); ?></td>
                                    <td class="fw-semibold"><?php echo escape($row['title']); ?></td>
                                    <td class="text-muted"><?php echo escape($row['department'] ?? '-'); ?></td>
                                    <td><span class="status-badge <?php echo statusBadgeClass($row['status']); ?>"><?php echo escape($row['status']); ?></span></td>
                                    <td class="text-muted"><?php echo escape(formatDateDisplay($row['applied_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="jp-dash-sidebar-stack d-flex flex-column gap-3">
            <a class="card jp-dash-profile-card animate-in" href="<?php echo BASE_URL; ?>/candidate/profile.php" aria-label="View my profile">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="jp-dash-profile-avatar" aria-hidden="true"><i class="bi bi-person-circle"></i></div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="jp-dash-profile-label text-uppercase text-muted fw-semibold mb-1">My profile</div>
                        <div class="fw-bold text-truncate"><?php echo escape(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))); ?></div>
                        <div class="small text-primary fw-semibold mt-1"><i class="bi bi-person-gear me-1"></i>View profile</div>
                    </div>
                    <i class="bi bi-chevron-right text-muted flex-shrink-0" aria-hidden="true"></i>
                </div>
            </a>
            <div class="card jp-dash-sidebar animate-in">
            <div class="jp-dash-sidebar-head">
                <i class="bi bi-briefcase-fill"></i>
                Available positions
            </div>
            <div class="jp-dash-sidebar-body">
                <?php if (!$sidebarJobs): ?>
                    <div class="jp-dash-sidebar-empty">
                        <i class="bi bi-folder2-open d-block mb-2 fs-3 opacity-50"></i>
                        No open vacancies right now.
                        <a class="d-block mt-2 small" href="<?php echo BASE_URL; ?>/index.php">Browse all jobs</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($sidebarJobs as $sj): ?>
                        <?php
                        $scopeLabel = vacancyScope($sj['vacancy_scope'] ?? null);
                        $dl = $sj['application_deadline'] ?? null;
                        $days = candidateSidebarDaysLeft($dl);
                        ?>
                        <div class="jp-sidebar-job">
                            <a class="jp-sidebar-job-title d-block" href="<?php echo BASE_URL; ?>/job.php?job_id=<?php echo (int)$sj['job_id']; ?>">
                                <?php echo escape($sj['title']); ?>
                            </a>
                            <div class="jp-sidebar-job-meta"><?php echo escape($sj['department'] ?? '—'); ?></div>
                            <div class="jp-sidebar-job-badges">
                                <span class="badge rounded-pill <?php echo candidateJobTypeBadgeClass($sj['job_type'] ?? ''); ?>"><?php echo escape($sj['job_type'] ?? '—'); ?></span>
                                <span class="badge rounded-pill <?php echo vacancyScopeBadgeClass($sj['vacancy_scope'] ?? null); ?>"><?php echo escape($scopeLabel); ?></span>
                            </div>
                            <?php if ($dl): ?>
                                <div class="jp-sidebar-job-deadline">
                                    <?php if ($days !== null && $days >= 0): ?>
                                        Closes <?php echo escape(formatDateDisplay($dl)); ?> · <?php echo (int)$days; ?> day<?php echo $days === 1 ? '' : 's'; ?> left
                                    <?php elseif ($days === -1): ?>
                                        Closed <?php echo escape(formatDateDisplay($dl)); ?>
                                    <?php else: ?>
                                        Closes <?php echo escape(formatDateDisplay($dl)); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <a class="btn btn-sm btn-outline-primary w-100" href="<?php echo BASE_URL; ?>/index.php">
                    <i class="bi bi-grid-3x3-gap me-1"></i> View all jobs
                </a>
            </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
