<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

$pageTitle = 'Applications';
$db = getDBConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $notes = $_POST['notes'] ?? '';
    $rejectionReason = $_POST['rejection_reason'] ?? '';

    $stmt = $db->prepare("
        SELECT a.application_id, a.status AS old_status, j.title AS job_title,
               c.user_id AS candidate_user_id, u.email, u.first_name
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        JOIN candidates c ON a.candidate_id = c.candidate_id
        JOIN users u ON c.user_id = u.user_id
        WHERE a.application_id = ?
    ");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch();

    if (!$app) {
        $error = 'Application not found.';
    } else {
        $stmt = $db->prepare("
            UPDATE applications
            SET status = ?, review_notes = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE application_id = ?
        ");
        $stmt->execute([$status, $notes, $rejectionReason, (int)$_SESSION['user_id'], $applicationId]);

        createNotification((int)$app['candidate_user_id'], 'application_status_changed', 'Application Status Updated',
            "Your application for {$app['job_title']} is now: {$status}", $applicationId, 'application');
        sendStatusUpdateEmail($app['email'], $app['first_name'], $app['job_title'], $status);
        logAudit('application_status_updated', 'applications', $applicationId, ['status' => $app['old_status']], ['status' => $status]);

        redirect('/hr/applications.php', 'Application updated.', 'success');
    }
}

$statusFilter = sanitize($_GET['status'] ?? '');
$where = [];
$params = [];
if ($statusFilter !== '') {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("
    SELECT a.application_id, a.status, a.applied_at,
           j.title AS job_title, j.department,
           u.first_name, u.last_name, u.email
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN candidates c ON a.candidate_id = c.candidate_id
    JOIN users u ON c.user_id = u.user_id
    {$whereSql}
    ORDER BY a.applied_at DESC
    LIMIT 200
");
$stmt->execute($params);
$apps = $stmt->fetchAll();

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
        <h1><i class="bi bi-file-earmark-check me-2"></i>Applications</h1>
        <p>Review candidates and update application statuses.</p>
    </div>
    <div class="page-actions">
        <form method="get" class="d-flex gap-2">
            <select class="form-select form-select-sm" name="status" style="min-width: 170px; border-color: rgba(255,255,255,.3); background: rgba(255,255,255,.15); color: #fff;">
                <option value="" style="color: #333;">All statuses</option>
                <?php foreach (['Pending','Under Review','Shortlisted','Interview Scheduled','Rejected','Offer Extended','Accepted','Withdrawn'] as $s): ?>
                    <option value="<?php echo escape($s); ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?> style="color: #333;"><?php echo escape($s); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light btn-sm text-primary fw-bold" type="submit">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
        </form>
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
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$apps): ?>
                        <tr><td colspan="5" class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                            <h6 class="text-muted">No applications found</h6>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo escape($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?php echo escape($a['email']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo escape($a['job_title']); ?></div>
                                <div class="small text-muted"><?php echo escape($a['department'] ?? ''); ?></div>
                            </td>
                            <td><span class="status-badge <?php echo statusBadgeClass($a['status']); ?>"><?php echo escape($a['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y', strtotime($a['applied_at']))); ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#u<?php echo (int)$a['application_id']; ?>">
                                    <i class="bi bi-pencil-square me-1"></i> Update
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="u<?php echo (int)$a['application_id']; ?>">
                            <td colspan="5" style="background: #f4f6fb; border-radius: 12px;">
                                <form method="post" class="row g-2 align-items-end p-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="application_id" value="<?php echo (int)$a['application_id']; ?>">

                                    <div class="col-12 col-md-3">
                                        <label class="form-label small fw-bold">New Status</label>
                                        <select class="form-select form-select-sm" name="status" required>
                                            <?php foreach (['Under Review','Shortlisted','Interview Scheduled','Rejected','Offer Extended'] as $s): ?>
                                                <option value="<?php echo escape($s); ?>"><?php echo escape($s); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-bold">Notes</label>
                                        <input class="form-control form-control-sm" name="notes" placeholder="Internal notes (optional)">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label small fw-bold">Rejection Reason</label>
                                        <input class="form-control form-control-sm" name="rejection_reason" placeholder="If rejecting">
                                    </div>
                                    <div class="col-12 col-md-2 text-md-end">
                                        <button class="btn btn-sm btn-primary w-100" type="submit">
                                            <i class="bi bi-check-lg me-1"></i> Save
                                        </button>
                                    </div>
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
