<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

require_once BASE_PATH . '/includes/job_summary_table.php';

$pageTitle = 'Manage Jobs';
$db = getDBConnection();

$stmt = $db->prepare("
    SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name
    FROM jobs j
    LEFT JOIN users u ON j.posted_by = u.user_id
    ORDER BY j.created_at DESC
");
$stmt->execute();
$jobs = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';

function jobStatusClass($status) {
    $map = [
        'Draft' => 'status-draft',
        'Pending Approval' => 'status-pending',
        'Active' => 'status-active',
        'Closed' => 'status-withdrawn',
        'Cancelled' => 'status-rejected',
    ];
    return $map[$status] ?? 'status-draft';
}
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-briefcase me-2"></i>Manage Jobs</h1>
        <p>Create, edit, and submit jobs for management approval.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-light btn-sm text-primary fw-bold" href="<?php echo BASE_URL; ?>/hr/job_edit.php">
            <i class="bi bi-plus-circle me-1"></i> Create Job
        </a>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Vacancy</th>
                        <th>Status</th>
                        <th>Posted By</th>
                        <th>Created</th>
                        <th class="text-end text-nowrap">Summary table</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$jobs): ?>
                        <tr><td colspan="8" class="text-center py-5">
                            <i class="bi bi-briefcase display-4 text-muted d-block mb-3"></i>
                            <h6 class="text-muted">No jobs yet</h6>
                            <p class="text-muted small">Create your first job posting to get started.</p>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($jobs as $j): ?>
                        <?php
                        $jid = (int)$j['job_id'];
                        $jst = (string)($j['status'] ?? '');
                        $canSummary = !in_array($jst, ['Draft', 'Cancelled'], true);
                        ?>
                        <tr>
                            <td class="fw-semibold"><?php echo escape($j['title']); ?></td>
                            <td class="text-muted"><?php echo escape($j['department'] ?? '-'); ?></td>
                            <td><span class="badge-type <?php echo vacancyScopeBadgeClass($j['vacancy_scope'] ?? 'External'); ?>"><?php echo escape(vacancyScope($j['vacancy_scope'] ?? 'External')); ?></span></td>
                            <td><span class="status-badge <?php echo jobStatusClass($j['status']); ?>"><?php echo escape($j['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape($j['posted_by_name'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape(formatDateDisplay($j['created_at'])); ?></td>
                            <td class="text-end text-nowrap">
                                <?php if ($canSummary): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo escape(jobSummaryTablePageUrl($jid, false)); ?>" target="_blank" rel="noopener noreferrer" title="Open summary table"><i class="bi bi-eye"></i></a>
                                    <a class="btn btn-sm btn-primary" href="<?php echo escape(jobSummaryTablePageUrl($jid, true)); ?>" title="Download HTML for Word"><i class="bi bi-download"></i></a>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/hr/job_edit.php?job_id=<?php echo $jid; ?>">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
