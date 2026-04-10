<?php
/**
 * Active / closed jobs with link to applicant summary table (HR-style export).
 */
require_once __DIR__ . '/../config/config.php';
requireRole(['Management', 'SysAdmin']);

$pageTitle = 'Published Jobs — Summary Tables';
$db = getDBConnection();

$stmt = $db->prepare("
    SELECT j.job_id, j.title, j.department, j.status, j.created_at,
           (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS application_count
    FROM jobs j
    WHERE j.status IN ('Active', 'Closed', 'Pending Approval')
    ORDER BY j.status = 'Active' DESC, j.created_at DESC
");
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once BASE_PATH . '/includes/job_summary_table.php';
require_once BASE_PATH . '/includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-table me-2"></i>Published jobs</h1>
        <p>Generate the <strong>Summary table for the post of …</strong> document (per job) for HR and management review. Opens in the browser; use <strong>Download</strong> for an HTML file you can open in Microsoft Word.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/management/dashboard.php">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th class="text-end">Applications</th>
                        <th class="text-end text-nowrap">Summary table</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$jobs): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No jobs in Active, Closed, or Pending Approval.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($jobs as $j): ?>
                        <?php $jid = (int)$j['job_id']; ?>
                        <tr>
                            <td class="fw-semibold"><?php echo escape($j['title']); ?></td>
                            <td class="text-muted"><?php echo escape($j['department'] ?? '—'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($j['status']); ?></span></td>
                            <td class="text-end"><?php echo (int)$j['application_count']; ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo escape(jobSummaryTablePageUrl($jid, false)); ?>" target="_blank" rel="noopener noreferrer" title="Open summary table">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                                <a class="btn btn-sm btn-primary" href="<?php echo escape(jobSummaryTablePageUrl($jid, true)); ?>" title="Download HTML for Word">
                                    <i class="bi bi-download me-1"></i> Download
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
