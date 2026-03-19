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

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-<?php echo $editing ? 'pencil-square' : 'plus-circle'; ?> me-2"></i><?php echo escape($pageTitle); ?></h1>
        <p><?php echo $editing ? 'Update job details and submit for approval.' : 'Draft a new job posting. Submit for approval when ready.'; ?></p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/hr/jobs.php">
            <i class="bi bi-arrow-left me-1"></i> Back to Jobs
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?php echo escape($error); ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: linear-gradient(135deg, #2e37a4, #4f46e5); color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-info-circle"></i>
                </span>
                Basic Information
            </h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                    <input class="form-control" name="title" required value="<?php echo escape($job['title'] ?? ''); ?>" placeholder="e.g. Senior Software Engineer">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Department</label>
                    <input class="form-control" name="department" value="<?php echo escape($job['department'] ?? ''); ?>" placeholder="e.g. IT Department">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Location</label>
                    <input class="form-control" name="location" value="<?php echo escape($job['location'] ?? ''); ?>" placeholder="e.g. Main Campus">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Job Type</label>
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
                    <label class="form-label fw-semibold">Salary Min</label>
                    <input class="form-control" name="salary_min" type="number" step="0.01" value="<?php echo escape($job['salary_min'] ?? ''); ?>" placeholder="0.00">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Salary Max</label>
                    <input class="form-control" name="salary_max" type="number" step="0.01" value="<?php echo escape($job['salary_max'] ?? ''); ?>" placeholder="0.00">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Application Deadline</label>
                    <input class="form-control" name="application_deadline" type="date" value="<?php echo escape($job['application_deadline'] ?? ''); ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Max Applications (0 = unlimited)</label>
                    <input class="form-control" name="max_applications" type="number" value="<?php echo escape($job['max_applications'] ?? 0); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4" style="animation-delay: .1s;">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: linear-gradient(135deg, #059669, #14b8a6); color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-file-text"></i>
                </span>
                Job Details
            </h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="description" rows="5" required placeholder="Describe the role, responsibilities, and expectations..."><?php echo escape($job['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Requirements</label>
                    <textarea class="form-control" name="requirements" rows="4" placeholder="List the key responsibilities and duties..."><?php echo escape($job['requirements'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Qualifications</label>
                    <textarea class="form-control" name="qualifications" rows="4" placeholder="List required qualifications and experience..."><?php echo escape($job['qualifications'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4" style="animation-delay: .2s;">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: linear-gradient(135deg, #f59e0b, #ec4899); color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-flag"></i>
                </span>
                Publishing
            </h5>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <?php $curStatus = $job['status'] ?? 'Draft'; ?>
                    <select class="form-select" name="status">
                        <?php foreach (['Draft','Pending Approval','Active','Closed','Cancelled'] as $s): ?>
                            <option value="<?php echo escape($s); ?>" <?php echo $curStatus === $s ? 'selected' : ''; ?>>
                                <?php echo escape($s); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Workflow: Draft &rarr; Pending Approval &rarr; Active</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3">
        <button class="btn btn-primary btn-lg" type="submit">
            <i class="bi bi-check-lg me-1"></i> <?php echo $editing ? 'Save Changes' : 'Create Job'; ?>
        </button>
        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/hr/jobs.php">Cancel</a>
    </div>
</form>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
