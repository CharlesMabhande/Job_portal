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
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Applications</h1>
        <div class="text-muted">Review candidates and update statuses.</div>
    </div>
    <form method="get" class="d-flex gap-2">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach (['Pending','Under Review','Shortlisted','Interview Scheduled','Rejected','Offer Extended','Accepted','Withdrawn'] as $s): ?>
                <option value="<?php echo escape($s); ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo escape($s); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
    </form>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo escape($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$apps): ?>
                        <tr><td colspan="5" class="text-muted">No applications found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo escape($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                <div class="small text-muted"><?php echo escape($a['email']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo escape($a['job_title']); ?></div>
                                <div class="small text-muted"><?php echo escape($a['department'] ?? ''); ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($a['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y', strtotime($a['applied_at']))); ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#u<?php echo (int)$a['application_id']; ?>">Update</button>
                            </td>
                        </tr>
                        <tr class="collapse" id="u<?php echo (int)$a['application_id']; ?>">
                            <td colspan="5">
                                <form method="post" class="row g-2 align-items-end">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="application_id" value="<?php echo (int)$a['application_id']; ?>">

                                    <div class="col-12 col-md-3">
                                        <label class="form-label small">Status</label>
                                        <select class="form-select form-select-sm" name="status" required>
                                            <?php foreach (['Under Review','Shortlisted','Interview Scheduled','Rejected','Offer Extended'] as $s): ?>
                                                <option value="<?php echo escape($s); ?>"><?php echo escape($s); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">Notes</label>
                                        <input class="form-control form-control-sm" name="notes" placeholder="Internal notes (optional)">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label small">Rejection reason</label>
                                        <input class="form-control form-control-sm" name="rejection_reason" placeholder="If rejected">
                                    </div>
                                    <div class="col-12 col-md-2 text-md-end">
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
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

