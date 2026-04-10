<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

require_once BASE_PATH . '/includes/job_summary_table.php';

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

    $vacancyScope = sanitize($_POST['vacancy_scope'] ?? 'External');
    if ($vacancyScope !== 'Internal' && $vacancyScope !== 'External') {
        $vacancyScope = 'External';
    }

    $data = [
        'title' => sanitize($_POST['title'] ?? ''),
        'department' => sanitize($_POST['department'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'requirements' => $_POST['requirements'] ?? '',
        'qualifications' => $_POST['qualifications'] ?? '',
        'location' => sanitize($_POST['location'] ?? ''),
        'job_type' => sanitize($_POST['job_type'] ?? 'Full-time'),
        'vacancy_scope' => $vacancyScope,
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

<?php if ($editing && $job): ?>
    <?php
    $jstEdit = (string)($job['status'] ?? '');
    $canSummaryEdit = !in_array($jstEdit, ['Draft', 'Cancelled'], true);
    ?>
    <?php if ($canSummaryEdit): ?>
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="small mb-0">
                <i class="bi bi-table me-1"></i>
                <strong>Applicant summary table</strong> — LSU format (name, age, gender, qualifications, experience, salary column, comments). Open to print or save as PDF; download HTML to open in Microsoft Word.
            </div>
            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <a class="btn btn-sm btn-outline-primary" href="<?php echo escape(jobSummaryTablePageUrl($jobId, false)); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-eye me-1"></i> View</a>
                <a class="btn btn-sm btn-primary" href="<?php echo escape(jobSummaryTablePageUrl($jobId, true)); ?>"><i class="bi bi-download me-1"></i> Download</a>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: #c61f26; color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-info-circle"></i>
                </span>
                Basic Information
            </h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                    <input class="form-control" name="title" required value="<?php echo escape($job['title'] ?? ''); ?>" placeholder="e.g. Senior Lecturer – Computer Science">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Department</label>
                    <input class="form-control" name="department" value="<?php echo escape($job['department'] ?? ''); ?>" placeholder="e.g. ICTS Department, Faculty of Science">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Location</label>
                    <input class="form-control" name="location" value="<?php echo escape($job['location'] ?? ''); ?>" placeholder="e.g. Lupane Main Campus, Bulawayo liaison office">
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
                    <label class="form-label fw-semibold">Vacancy audience</label>
                    <?php $curScope = vacancyScope($job['vacancy_scope'] ?? 'External'); ?>
                    <select class="form-select" name="vacancy_scope" title="Internal: typically for current staff; External: open to all applicants">
                        <option value="External" <?php echo $curScope === 'External' ? 'selected' : ''; ?>>External — open to the public</option>
                        <option value="Internal" <?php echo $curScope === 'Internal' ? 'selected' : ''; ?>>Internal — staff / internal applicants</option>
                    </select>
                    <div class="form-text">Shown on the job list and detail page. Does not block applications by itself.</div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Salary Min</label>
                    <input class="form-control" name="salary_min" type="number" step="0.01" value="<?php echo escape($job['salary_min'] ?? ''); ?>" placeholder="e.g. 1200.00 (USD/month)">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Salary Max</label>
                    <input class="form-control" name="salary_max" type="number" step="0.01" value="<?php echo escape($job['salary_max'] ?? ''); ?>" placeholder="e.g. 1800.00 (leave blank if negotiable)">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Application Deadline</label>
                    <?php
                    $__deadlineVal = $job['application_deadline'] ?? '';
                    if ($__deadlineVal !== '' && strlen((string)$__deadlineVal) > 10) {
                        $__deadlineVal = substr((string)$__deadlineVal, 0, 10);
                    }
                    ?>
                    <input class="form-control" name="application_deadline" type="date" value="<?php echo escape($__deadlineVal); ?>" title="Pick the last day applications are accepted">
                    <div class="form-text">Shown across the site as <strong>dd/mm/yyyy</strong>. The calendar may follow your browser; the value is saved as YYYY-MM-DD.</div>
                    <?php unset($__deadlineVal); ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Max Applications (0 = unlimited)</label>
                    <input class="form-control" name="max_applications" type="number" value="<?php echo escape($job['max_applications'] ?? 0); ?>" placeholder="e.g. 50 (use 0 for no limit)" title="0 means unlimited applications">
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4" style="animation-delay: .1s;">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: #E35D1E; color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-file-text"></i>
                </span>
                Job Details
            </h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="description" rows="5" required placeholder="Example: We seek a motivated professional to teach undergraduate courses, supervise projects, and support departmental research…"><?php echo escape($job['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Requirements</label>
                    <textarea class="form-control" name="requirements" rows="4" placeholder="Example: • Deliver lectures and tutorials  • Prepare assessments  • Attend faculty meetings  • Maintain student records"><?php echo escape($job['requirements'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Qualifications</label>
                    <textarea class="form-control" name="qualifications" rows="4" placeholder="Example: PhD or Master’s in relevant field; 3+ years teaching experience; strong communication skills; registration with professional body (if applicable)"><?php echo escape($job['qualifications'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4" style="animation-delay: .2s;">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: #c61f26; color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
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
