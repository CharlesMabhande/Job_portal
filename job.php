<?php
require_once __DIR__ . '/config/config.php';

$db = getDBConnection();
$jobId = (int)($_GET['job_id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM jobs WHERE job_id = ? AND status = 'Active'");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    redirect('/index.php', 'Job not found or not available.', 'error');
}

$pageTitle = $job['title'] . ' - University Job Portal';

// Handle candidate apply (server-side) for responsiveness + simplicity.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    requireRole(['Candidate']);

    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT candidate_id, cv_path FROM candidates WHERE user_id = ?");
    $stmt->execute([$userId]);
    $candidate = $stmt->fetch();
    if (!$candidate) {
        redirect('/candidate/profile.php', 'Please complete your candidate profile first.', 'error');
    }

    // Prevent duplicate applications
    $stmt = $db->prepare("SELECT application_id FROM applications WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([(int)$jobId, (int)$candidate['candidate_id']]);
    if ($stmt->fetch()) {
        redirect('/candidate/applications.php', 'You have already applied for this job.', 'error');
    }

    // Basic capacity check
    if ((int)$job['max_applications'] > 0 && (int)$job['current_applications'] >= (int)$job['max_applications']) {
        redirect('/index.php', 'This job is no longer accepting applications.', 'error');
    }

    // Validate required fields
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    if ($coverLetter === '') {
        redirect('/job.php?job_id=' . (int)$jobId, 'Cover letter is required.', 'error');
    }

    // CV must be provided (either default on profile or upload here)
    $cvPath = $candidate['cv_path'] ?: null;
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['cv'], CV_DIR, 'cv');
        if (!$upload['success']) {
            redirect('/job.php?job_id=' . (int)$jobId, $upload['message'] ?? 'CV upload failed', 'error');
        }
        $cvPath = 'cv/' . $upload['filename'];

        // Store candidate default CV path
        $stmt = $db->prepare("UPDATE candidates SET cv_path = ? WHERE user_id = ?");
        $stmt->execute([$cvPath, $userId]);
    }
    if (!$cvPath) {
        redirect('/job.php?job_id=' . (int)$jobId, 'Please upload your CV before applying.', 'error');
    }

    // Qualifications / certificates document (required)
    if (!isset($_FILES['certificates']) || $_FILES['certificates']['error'] !== UPLOAD_ERR_OK) {
        redirect('/job.php?job_id=' . (int)$jobId, 'Please upload a single document containing all your certificates.', 'error');
    }
    $certUpload = uploadFile($_FILES['certificates'], DOCS_DIR, 'certs');
    if (!$certUpload['success']) {
        redirect('/job.php?job_id=' . (int)$jobId, $certUpload['message'] ?? 'Certificates upload failed', 'error');
    }
    $certificatesPath = 'documents/' . $certUpload['filename'];

    $stmt = $db->prepare("INSERT INTO applications (job_id, candidate_id, cover_letter, cv_path, certificates_path, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([(int)$jobId, (int)$candidate['candidate_id'], $coverLetter, $cvPath, $certificatesPath]);
    $applicationId = (int)$db->lastInsertId();

    $stmt = $db->prepare("UPDATE jobs SET current_applications = current_applications + 1 WHERE job_id = ?");
    $stmt->execute([(int)$jobId]);

    createNotification($userId, 'application_submitted', 'Application Submitted', "Your application for {$job['title']} has been submitted.", $applicationId, 'application');
    sendApplicationConfirmation($_SESSION['email'], $_SESSION['first_name'], $job['title']);
    logAudit('application_created', 'applications', $applicationId);

    redirect('/candidate/applications.php', 'Application submitted successfully.', 'success');
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h1 class="h3 mb-1"><?php echo escape($job['title']); ?></h1>
                        <div class="text-muted">
                            <?php echo escape($job['department'] ?: ''); ?>
                            <?php if (!empty($job['location'])): ?> • <?php echo escape($job['location']); ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="badge bg-secondary"><?php echo escape($job['job_type']); ?></span>
                </div>

                <hr>

                <h2 class="h6">Description</h2>
                <div class="mb-3"><?php echo nl2br(escape($job['description'])); ?></div>

                <?php if (!empty($job['requirements'])): ?>
                    <h2 class="h6">Requirements</h2>
                    <div class="mb-3"><?php echo nl2br(escape($job['requirements'])); ?></div>
                <?php endif; ?>

                <?php if (!empty($job['qualifications'])): ?>
                    <h2 class="h6">Qualifications</h2>
                    <div class="mb-0"><?php echo nl2br(escape($job['qualifications'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h2 class="h5">Apply</h2>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-warning mb-0">
                        Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> or <a href="<?php echo BASE_URL; ?>/register.php">register</a> to apply.
                    </div>
                <?php elseif ((int)($_SESSION['role_id'] ?? 0) !== 1): ?>
                    <div class="alert alert-info mb-0">Only Candidates can apply for jobs.</div>
                <?php else: ?>
                    <form method="post" enctype="multipart/form-data" class="vstack gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                        <div>
                            <label class="form-label">Cover Letter</label>
                            <textarea class="form-control" name="cover_letter" rows="6" placeholder="Write a brief cover letter..." required></textarea>
                        </div>

                        <div>
                            <label class="form-label">Upload CV</label>
                            <input class="form-control" type="file" name="cv" accept=".pdf,.doc,.docx" required>
                            <div class="form-text">Allowed: PDF, DOC, DOCX • Max 5MB. Upload your CV.</div>
                        </div>

                        <div>
                            <label class="form-label">Upload Qualifications (All Certificates)</label>
                            <input class="form-control" type="file" name="certificates" accept=".pdf,.doc,.docx" required>
                            <div class="form-text">Upload a single document that contains all your qualifications/certificates.</div>
                        </div>

                        <button class="btn btn-primary" type="submit">Submit Application</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body small text-muted">
                Posted: <?php echo escape(date('M j, Y', strtotime($job['created_at']))); ?><br>
                <?php if (!empty($job['application_deadline'])): ?>
                    Deadline: <?php echo escape(date('M j, Y', strtotime($job['application_deadline']))); ?><br>
                <?php endif; ?>
                Status: Active
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

