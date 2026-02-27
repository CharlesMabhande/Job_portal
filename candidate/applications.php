<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['Candidate']);

$pageTitle = 'My Applications';
$db = getDBConnection();

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare("SELECT candidate_id FROM candidates WHERE user_id = ?");
$stmt->execute([$userId]);
$candidateId = (int)($stmt->fetch()['candidate_id'] ?? 0);

$stmt = $db->prepare("
    SELECT a.application_id, a.status, a.applied_at, j.title, j.department, j.location
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.candidate_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$candidateId]);
$apps = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">My Applications</h1>
        <div class="text-muted">All applications you’ve submitted.</div>
    </div>
    <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/index.php">Apply to a Job</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$apps): ?>
                        <tr><td colspan="5" class="text-muted">No applications yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td><?php echo escape($a['title']); ?></td>
                            <td><?php echo escape($a['department'] ?? ''); ?></td>
                            <td><?php echo escape($a['location'] ?? ''); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($a['status']); ?></span></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y', strtotime($a['applied_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

