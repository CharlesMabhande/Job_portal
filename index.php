<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Jobs - University Job Portal';
$db = getDBConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$where = "status = 'Active'";
$params = [];
if ($search !== '') {
    $where .= " AND (title LIKE ? OR description LIKE ? OR department LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM jobs WHERE {$where}");
$countStmt->execute($params);
$total = (int)($countStmt->fetch()['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $limit));

$sql = "SELECT job_id, title, department, location, job_type, created_at, application_deadline, description
        FROM jobs
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$bindIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($bindIndex, $p);
    $bindIndex++;
}
$stmt->bindValue($bindIndex++, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row g-3 align-items-center mb-3">
    <div class="col-12 col-md">
        <h1 class="h3 mb-0">Open Jobs</h1>
        <div class="text-muted">Browse available university positions and apply online.</div>
    </div>
    <div class="col-12 col-md-5">
        <form method="get" class="d-flex gap-2">
            <input type="text" class="form-control" name="search" value="<?php echo escape($search); ?>" placeholder="Search jobs...">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <?php if (!$jobs): ?>
        <div class="col-12">
            <div class="alert alert-info mb-0">No jobs found.</div>
        </div>
    <?php endif; ?>

    <?php foreach ($jobs as $job): ?>
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-2">
                        <h2 class="h5 mb-1"><?php echo escape($job['title']); ?></h2>
                        <span class="badge bg-secondary"><?php echo escape($job['job_type']); ?></span>
                    </div>
                    <div class="text-muted small mb-2">
                        <?php echo escape($job['department'] ?: ''); ?>
                        <?php if (!empty($job['location'])): ?> • <?php echo escape($job['location']); ?><?php endif; ?>
                    </div>
                    <p class="mb-3 text-truncate-2"><?php echo escape($job['description']); ?></p>
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center">
                        <div class="small text-muted">
                            Posted: <?php echo escape(date('M j, Y', strtotime($job['created_at']))); ?>
                            <?php if (!empty($job['application_deadline'])): ?>
                                • Deadline: <?php echo escape(date('M j, Y', strtotime($job['application_deadline']))); ?>
                            <?php endif; ?>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="<?php echo BASE_URL; ?>/job.php?job_id=<?php echo (int)$job['job_id']; ?>">View & Apply</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>"><?php echo $p; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

