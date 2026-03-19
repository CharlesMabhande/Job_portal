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
    SELECT a.application_id, a.status, a.applied_at, j.title, j.department
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.candidate_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$recentStmt->execute([$candidateId]);
$recent = $recentStmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';

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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0"><i class="bi bi-clock-history me-2" style="color: #02FFFD"></i>Recent Applications</h2>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/candidate/applications.php">
                View all <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="4" class="text-center py-4">
                            <i class="bi bi-inbox display-6 text-muted d-block mb-2"></i>
                            <span class="text-muted">No applications yet. <a href="<?php echo BASE_URL; ?>/index.php">Start browsing jobs!</a></span>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo escape($row['title']); ?></td>
                            <td class="text-muted"><?php echo escape($row['department'] ?? '-'); ?></td>
                            <td><span class="status-badge <?php echo statusBadgeClass($row['status']); ?>"><?php echo escape($row['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y', strtotime($row['applied_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
