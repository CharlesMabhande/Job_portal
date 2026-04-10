<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'Management', 'SysAdmin']);

$pageTitle = 'Applications';
$db = getDBConnection();
$error = null;
$roleName = (string)($_SESSION['role_name'] ?? '');
$canMutateApplications = in_array($roleName, ['HR', 'SysAdmin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canMutateApplications) {
        redirect('/hr/applications.php', 'You can view applications but cannot update or delete them.', 'error');
    }
    requireCSRFToken();
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? 'update';

    if ($action === 'delete') {
        if ($applicationId < 1) {
            $error = 'Invalid application.';
        } else {
            $stmt = $db->prepare("SELECT application_id, job_id FROM applications WHERE application_id = ?");
            $stmt->execute([$applicationId]);
            $toDelete = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$toDelete) {
                $error = 'Application not found.';
            } else {
                try {
                    $db->beginTransaction();
                    $db->prepare("DELETE FROM applications WHERE application_id = ?")->execute([$applicationId]);
                    $jobId = (int)$toDelete['job_id'];
                    $db->prepare("UPDATE jobs SET current_applications = GREATEST(0, current_applications - 1) WHERE job_id = ?")->execute([$jobId]);
                    $db->commit();
                    logAudit('application_deleted', 'applications', $applicationId, null, ['job_id' => $jobId]);
                    redirect('/hr/applications.php', 'Application deleted.', 'success');
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Application delete failed: ' . $e->getMessage());
                    $error = 'Could not delete application. Please try again.';
                }
            }
        }
    } else {
        $status = sanitize($_POST['status'] ?? '');
        $notes = $_POST['notes'] ?? '';
        $rejectionReason = $_POST['rejection_reason'] ?? '';

        $stmt = $db->prepare("
            SELECT a.application_id, a.application_ref, a.status AS old_status, j.title AS job_title,
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

            $ref = !empty($app['application_ref']) ? $app['application_ref'] : ('#' . $applicationId);
            createNotification((int)$app['candidate_user_id'], 'application_status_changed', 'Application Status Updated',
                "Application {$ref} ({$app['job_title']}) is now: {$status}", $applicationId, 'application');
            sendStatusUpdateEmail($app['email'], $app['first_name'], $app['job_title'], $status);
            logAudit('application_status_updated', 'applications', $applicationId, ['status' => $app['old_status']], ['status' => $status]);

            redirect('/hr/applications.php', 'Application updated.', 'success');
        }
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
    SELECT a.application_id, a.application_ref, a.status, a.applied_at,
           a.candidate_id,
           a.cv_path AS app_cv_path,
           a.certificates_path AS app_certificates_path,
           c.cv_path AS candidate_cv_path,
           c.certificates_path AS candidate_certificates_path,
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

/**
 * HR-only URL to view or download a document (streams with inline PDF / correct MIME).
 */
function hrViewDocumentUrl(int $applicationId, string $doc, bool $download = false): string {
    $params = ['application_id' => $applicationId, 'doc' => $doc];
    if ($download) {
        $params['download'] = '1';
    }
    return BASE_URL . '/hr/view-document.php?' . http_build_query($params);
}

/**
 * True if we have a stored path for this application (for showing document buttons).
 */
function hrHasDocumentPath(?string $path): bool {
    return $path !== null && trim($path) !== '';
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
        <h1><i class="bi bi-file-earmark-check me-2"></i>Applications</h1>
        <p><?php echo $canMutateApplications ? 'Review candidates and update application statuses.' : 'View candidates and applications (read-only).'; ?></p>
    </div>
    <div class="page-actions w-100 w-md-auto">
        <form method="get" class="d-flex flex-column flex-sm-row gap-2 align-items-stretch">
            <select class="form-select form-select-sm flex-grow-1" name="status" style="min-width: 0; max-width: 100%; border-color: rgba(255,255,255,.3); background: rgba(255,255,255,.15); color: #fff;">
                <option value="" style="color: #333;">All statuses</option>
                <?php foreach (['Pending','Under Review','Shortlisted','Interview Scheduled','Rejected','Offer Extended','Accepted','Withdrawn'] as $s): ?>
                    <option value="<?php echo escape($s); ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?> style="color: #333;"><?php echo escape($s); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light btn-sm text-primary fw-bold w-100 w-sm-auto flex-shrink-0" type="submit">
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
        <div class="table-responsive hr-applications-scroll">
            <table class="table mb-0 table-hr-applications align-middle">
                <thead>
                    <tr>
                        <th class="text-nowrap">Ref</th>
                        <th class="hr-apps-th-candidate">Candidate</th>
                        <th class="hr-apps-th-job">Job</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-nowrap">Applied</th>
                        <th class="hr-apps-th-docs">Documents</th>
                        <th class="text-nowrap hr-apps-th-profile">Profile</th>
                        <th class="text-end text-nowrap hr-apps-th-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$apps): ?>
                        <tr><td colspan="8" class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                            <h6 class="text-muted">No applications found</h6>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($apps as $a): ?>
                        <?php
                        $cvPath = !empty($a['app_cv_path']) ? $a['app_cv_path'] : ($a['candidate_cv_path'] ?? '');
                        $certPath = !empty($a['app_certificates_path']) ? $a['app_certificates_path'] : ($a['candidate_certificates_path'] ?? '');
                        $hasCv = hrHasDocumentPath($cvPath);
                        $hasCert = hrHasDocumentPath($certPath);
                        $appId = (int)$a['application_id'];
                        $candId = (int)($a['candidate_id'] ?? 0);
                        $fullName = trim($a['first_name'] . ' ' . $a['last_name']);
                        $candTitle = $fullName . ' — ' . $a['email'];
                        $dept = trim($a['department'] ?? '');
                        $jobLine = $a['job_title'] . ($dept !== '' ? ' · ' . $dept : '');
                        ?>
                        <tr class="hr-apps-row">
                            <td class="text-nowrap small font-monospace fw-semibold"><?php echo escape($a['application_ref'] ?? ('#' . $appId)); ?></td>
                            <td class="hr-apps-td-clip">
                                <div class="hr-apps-one-line text-truncate" title="<?php echo escape($candTitle); ?>">
                                    <span class="fw-bold"><?php echo escape($fullName); ?></span>
                                    <span class="text-muted small hr-apps-sep">·</span>
                                    <span class="text-muted small"><?php echo escape($a['email']); ?></span>
                                </div>
                            </td>
                            <td class="hr-apps-td-clip">
                                <div class="hr-apps-one-line text-truncate" title="<?php echo escape($jobLine); ?>">
                                    <span class="fw-semibold"><?php echo escape($a['job_title']); ?></span>
                                    <?php if ($dept !== ''): ?>
                                        <span class="text-muted small hr-apps-sep">·</span>
                                        <span class="text-muted small"><?php echo escape($dept); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-nowrap"><span class="status-badge <?php echo statusBadgeClass($a['status']); ?>"><?php echo escape($a['status']); ?></span></td>
                            <td class="text-muted text-nowrap"><?php echo escape(formatDateDisplay($a['applied_at'])); ?></td>
                            <td class="hr-apps-td-docs">
                                <div class="hr-apps-doc-strip">
                                    <?php if ($hasCv): ?>
                                        <a class="btn btn-sm btn-outline-secondary text-nowrap" href="<?php echo escape(hrViewDocumentUrl($appId, 'cv', false)); ?>" target="_blank" rel="noopener noreferrer" title="View CV">
                                            <i class="bi bi-eye"></i><span class="d-none d-lg-inline"> CV</span>
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo escape(hrViewDocumentUrl($appId, 'cv', true)); ?>" title="Download CV">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="small text-muted text-nowrap">No CV</span>
                                    <?php endif; ?>
                                    <?php if ($hasCert): ?>
                                        <a class="btn btn-sm btn-outline-secondary text-nowrap" href="<?php echo escape(hrViewDocumentUrl($appId, 'certs', false)); ?>" target="_blank" rel="noopener noreferrer" title="View certificates">
                                            <i class="bi bi-eye"></i><span class="d-none d-lg-inline"> Certs</span>
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo escape(hrViewDocumentUrl($appId, 'certs', true)); ?>" title="Download certificates">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="small text-muted text-nowrap">No certs</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <?php if ($candId > 0): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo escape(BASE_URL . '/hr/candidate-profile.php?candidate_id=' . $candId); ?>" title="View candidate profile">
                                        <i class="bi bi-person-vcard"></i><span class="d-none d-xl-inline ms-1">Profile</span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end hr-apps-td-action">
                                <?php if ($canMutateApplications): ?>
                                <div class="d-inline-flex flex-nowrap gap-1 align-items-center justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#u<?php echo (int)$a['application_id']; ?>" title="Update status">
                                        <i class="bi bi-pencil-square"></i><span class="d-none d-lg-inline ms-1">Update</span>
                                    </button>
                                    <form method="post" class="d-inline flex-shrink-0" onsubmit="return confirm('Permanently delete this application? This cannot be undone. Related interview records will also be removed.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="application_id" value="<?php echo (int)$a['application_id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit" title="Delete application">
                                            <i class="bi bi-trash"></i><span class="d-none d-lg-inline ms-1">Delete</span>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="collapse" id="u<?php echo (int)$a['application_id']; ?>">
                            <td colspan="8" style="background: #f3f3f4; border-radius: 12px;">
                                <form method="post" class="row g-2 align-items-end p-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="action" value="update">
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
                                        <input class="form-control form-control-sm" name="notes" placeholder="e.g. Strong fit; follow-up interview suggested" title="Visible to HR; optional internal notes">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label small fw-bold">Rejection Reason</label>
                                        <input class="form-control form-control-sm" name="rejection_reason" placeholder="e.g. Does not meet minimum qualification in Section X" title="Shown to candidate when status is Rejected">
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
