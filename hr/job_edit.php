<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

$db = getDBConnection();
$jobId = (int)($_GET['job_id'] ?? 0);
$editing = $jobId > 0;

$job = null;
if ($editing) {
    $stmt = $db->prepare("SELECT * FROM jobs WHERE job_id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    if (!$job) {
        redirect('/hr/jobs.php', 'Job not found.', 'error');
    }
}

$pageTitle = $editing ? 'Edit Job' : 'Create Job';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();

    $data = [
        'title' => sanitize($_POST['title'] ?? ''),
        'department' => sanitize($_POST['department'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'requirements' => $_POST['requirements'] ?? '',
        'qualifications' => $_POST['qualifications'] ?? '',
        'location' => sanitize($_POST['location'] ?? ''),
        'job_type' => sanitize($_POST['job_type'] ?? 'Full-time'),
        'salary_min' => $_POST['salary_min'] !== '' ? $_POST['salary_min'] : null,
        'salary_max' => $_POST['salary_max'] !== '' ? $_POST['salary_max'] : null,
        'application_deadline' => $_POST['application_deadline'] !== '' ? $_POST['application_deadline'] : null,
        'max_applications' => (int)($_POST['max_applications'] ?? 0),
        'status' => sanitize($_POST['status'] ?? 'Draft'),
    ];

    $errors = validateInput($data, ['title' => 'required', 'description' => 'required']);
    if ($errors) {
        $error = implode(' ', array_values($errors));
    } else {
        if ($editing) {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "{$k} = ?";
                $values[] = $v;
            }
            $values[] = $jobId;
            $stmt = $db->prepare("UPDATE jobs SET " . implode(', ', $fields) . " WHERE job_id = ?");
            $stmt->execute($values);
            logAudit('job_updated', 'jobs', $jobId);
            redirect('/hr/jobs.php', 'Job updated.', 'success');
        } else {
            $data['posted_by'] = (int)$_SESSION['user_id'];
            $cols = implode(', ', array_keys($data));
            $qs = '?' . str_repeat(', ?', count($data) - 1);
            $stmt = $db->prepare("INSERT INTO jobs ({$cols}) VALUES ({$qs})");
            $stmt->execute(array_values($data));
            $newId = (int)$db->lastInsertId();
            logAudit('job_created', 'jobs', $newId);
            redirect('/hr/jobs.php', 'Job created.', 'success');
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0"><?php echo escape($pageTitle); ?></h1>
        <div class="text-muted">Draft jobs can be submitted for approval.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/hr/jobs.php">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo escape($error); ?></div>
<?php endif; ?>

<form method="post" class="card">
    <div class="card-body">
        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" required value="<?php echo escape($job['title'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Department</label>
                <input class="form-control" name="department" value="<?php echo escape($job['department'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Location</label>
                <input class="form-control" name="location" value="<?php echo escape($job['location'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Job Type</label>
                <select class="form-select" name="job_type">
                    <?php
                    $types = ['Full-time','Part-time','Contract','Internship'];
                    $cur = $job['job_type'] ?? 'Full-time';
                    foreach ($types as $t) {
                        echo '<option value="' . escape($t) . '"' . ($cur === $t ? ' selected' : '') . '>' . escape($t) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Salary Min</label>
                <input class="form-control" name="salary_min" type="number" step="0.01" value="<?php echo escape($job['salary_min'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Salary Max</label>
                <input class="form-control" name="salary_max" type="number" step="0.01" value="<?php echo escape($job['salary_max'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Application Deadline</label>
                <input class="form-control" name="application_deadline" type="date" value="<?php echo escape($job['application_deadline'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Max Applications (0 = unlimited)</label>
                <input class="form-control" name="max_applications" type="number" value="<?php echo escape($job['max_applications'] ?? 0); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="5" required><?php echo escape($job['description'] ?? ''); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Requirements</label>
                <textarea class="form-control" name="requirements" rows="4"><?php echo escape($job['requirements'] ?? ''); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Qualifications</label>
                <textarea class="form-control" name="qualifications" rows="4"><?php echo escape($job['qualifications'] ?? ''); ?></textarea>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Status</label>
                <?php $curStatus = $job['status'] ?? 'Draft'; ?>
                <select class="form-select" name="status">
                    <?php foreach (['Draft','Pending Approval','Active','Closed','Cancelled'] as $s): ?>
                        <option value="<?php echo escape($s); ?>" <?php echo $curStatus === $s ? 'selected' : ''; ?>>
                            <?php echo escape($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Typically: Draft → Pending Approval → Active</div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white">
        <button class="btn btn-primary" type="submit"><?php echo $editing ? 'Save Changes' : 'Create Job'; ?></button>
        <a class="btn btn-outline-secondary ms-2" href="<?php echo BASE_URL; ?>/hr/jobs.php">Cancel</a>
    </div>
</form>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

