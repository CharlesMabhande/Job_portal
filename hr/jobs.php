<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

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
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Jobs</h1>
        <div class="text-muted">Create, edit, and submit jobs for management approval.</div>
    </div>
    <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/hr/job_edit.php">Create Job</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Posted By</th>
                        <th>Created</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$jobs): ?>
                        <tr><td colspan="6" class="text-muted">No jobs yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($jobs as $j): ?>
                        <tr>
                            <td><?php echo escape($j['title']); ?></td>
                            <td><?php echo escape($j['department'] ?? ''); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($j['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape($j['posted_by_name'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y', strtotime($j['created_at']))); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/hr/job_edit.php?job_id=<?php echo (int)$j['job_id']; ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

