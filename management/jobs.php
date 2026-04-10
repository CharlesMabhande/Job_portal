<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['Management', 'SysAdmin']);

$pageTitle = 'Job Approvals';
$db = getDBConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    $jobId = (int)($_POST['job_id'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM jobs WHERE job_id = ? AND status = 'Pending Approval'");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) {
        $error = 'Job not found or not pending approval.';
    } else {
        $stmt = $db->prepare("UPDATE jobs SET status = 'Active', approved_by = ?, approved_at = NOW() WHERE job_id = ?");
        $stmt->execute([(int)$_SESSION['user_id'], $jobId]);
        logAudit('job_approved', 'jobs', $jobId);
        redirect('/management/jobs.php', 'Job approved and activated.', 'success');
    }
}

$stmt = $db->prepare("
    SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name
    FROM jobs j
    LEFT JOIN users u ON j.posted_by = u.user_id
    WHERE j.status = 'Pending Approval'
    ORDER BY j.created_at DESC
");
$stmt->execute();
$jobs = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-check-circle me-2"></i>Job Approvals</h1>
        <p>Review and approve postings before they become visible to candidates.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/management/dashboard.php">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?php echo escape($error); ?></div>
<?php endif; ?>

<div class="card animate-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Posted By</th>
                        <th>Created</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$jobs): ?>
                        <tr><td colspan="5" class="text-center py-5">
                            <i class="bi bi-check-circle display-4 text-success d-block mb-3"></i>
                            <h6 class="text-muted">All caught up!</h6>
                            <p class="text-muted small">No pending approvals at the moment.</p>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($jobs as $j): ?>
                        <tr>
                            <td class="fw-bold"><?php echo escape($j['title']); ?></td>
                            <td class="text-muted"><?php echo escape($j['department'] ?? '-'); ?></td>
                            <td class="text-muted"><?php echo escape($j['posted_by_name'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape(formatDateDisplay($j['created_at'])); ?></td>
                            <td class="text-end">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="job_id" value="<?php echo (int)$j['job_id']; ?>">
                                    <button class="btn btn-sm btn-primary" type="submit" style="background: #c61f26; border: none;">
                                        <i class="bi bi-check-lg me-1"></i> Approve
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
