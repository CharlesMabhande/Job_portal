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
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Welcome, <?php echo escape($_SESSION['first_name']); ?></h1>
        <div class="text-muted">Track your applications and apply for new jobs.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/index.php">Browse Jobs</a>
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/candidate/profile.php">Update Profile</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Pending</div>
            <div class="h4 mb-0"><?php echo (int)($counts['pending'] ?? 0); ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Under Review</div>
            <div class="h4 mb-0"><?php echo (int)($counts['under_review'] ?? 0); ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Shortlisted</div>
            <div class="h4 mb-0"><?php echo (int)($counts['shortlisted'] ?? 0); ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Rejected</div>
            <div class="h4 mb-0"><?php echo (int)($counts['rejected'] ?? 0); ?></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Recent Applications</h2>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo BASE_URL; ?>/candidate/applications.php">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
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
                        <tr><td colspan="4" class="text-muted">No applications yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?php echo escape($row['title']); ?></td>
                            <td><?php echo escape($row['department'] ?? ''); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($row['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y', strtotime($row['applied_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

