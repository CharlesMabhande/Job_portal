<?php
require_once __DIR__ . '/config/config.php';

$db = getDBConnection();
$jobId = (int)($_GET['job_id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM jobs WHERE job_id = ? AND status = 'Active' AND " . sqlJobApplicationOpen());
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    redirect('/index.php', 'Job not found, closed, or the application deadline has passed.', 'error');
}

$pageTitle = $job['title'] . ' - Lupane State University Job Portal';
$fullWidth = true;

// Handle candidate apply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    requireRole(['Candidate']);

    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT candidate_id, cv_path, certificates_path FROM candidates WHERE user_id = ?");
    $stmt->execute([$userId]);
    $candidate = $stmt->fetch();
    if (!$candidate) {
        redirect('/candidate/profile.php', 'Please complete your candidate profile first.', 'error');
    }

    $stmt = $db->prepare("SELECT application_id FROM applications WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([(int)$jobId, (int)$candidate['candidate_id']]);
    if ($stmt->fetch()) {
        redirect('/candidate/applications.php', 'You have already applied for this job.', 'error');
    }

    if ((int)$job['max_applications'] > 0 && (int)$job['current_applications'] >= (int)$job['max_applications']) {
        redirect('/index.php', 'This job is no longer accepting applications.', 'error');
    }

    if (jobApplicationDeadlinePassed($job)) {
        redirect('/index.php', 'The application deadline for this job has passed.', 'error');
    }

    $coverLetter = trim($_POST['cover_letter'] ?? '');
    if ($coverLetter === '') {
        redirect('/job.php?job_id=' . (int)$jobId, 'Cover letter is required.', 'error');
    }

    /* Documents come only from the candidate profile (uploaded/updated on My Profile). */
    $cvPath = !empty($candidate['cv_path']) ? trim($candidate['cv_path']) : '';
    $certificatesPath = !empty($candidate['certificates_path']) ? trim($candidate['certificates_path']) : '';
    if ($cvPath === '' || $certificatesPath === '') {
        redirect('/candidate/profile.php', 'Add your CV and qualifications document on your profile before applying. They will be used for every application.', 'error');
    }

    $stmt = $db->prepare("INSERT INTO applications (job_id, candidate_id, cover_letter, cv_path, certificates_path, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([(int)$jobId, (int)$candidate['candidate_id'], $coverLetter, $cvPath, $certificatesPath]);
    $applicationId = (int)$db->lastInsertId();
    $applicationRef = assignApplicationReference($db, $applicationId);

    $stmt = $db->prepare("UPDATE jobs SET current_applications = current_applications + 1 WHERE job_id = ?");
    $stmt->execute([(int)$jobId]);

    createNotification($userId, 'application_submitted', 'Application Submitted', "Your application for {$job['title']} has been submitted. Reference: {$applicationRef}.", $applicationId, 'application');
    sendApplicationConfirmation($_SESSION['email'], $_SESSION['first_name'], $job['title'], $applicationRef);
    logAudit('application_created', 'applications', $applicationId);

    redirect('/candidate/applications.php', 'Application submitted successfully. Your application number is ' . $applicationRef . '.', 'success');
}

// Compute deadline info
$daysLeft = null;
if (!empty($job['application_deadline'])) {
    $now = new DateTime();
    $end = new DateTime($job['application_deadline']);
    $diff = $now->diff($end);
    $daysLeft = $now > $end ? -1 : (int)$diff->days;
}

// Type badge class
$typeBadges = [
    'Full-time' => 'badge-fulltime',
    'Part-time' => 'badge-parttime',
    'Contract' => 'badge-contract',
    'Internship' => 'badge-internship',
];
$badgeClass = $typeBadges[$job['job_type']] ?? 'badge-fulltime';
$vScope = vacancyScope($job['vacancy_scope'] ?? 'External');

$candidateApplyDocs = null;
if (isset($_SESSION['user_id']) && (int)($_SESSION['role_id'] ?? 0) === 1) {
    $stmt = $db->prepare("SELECT cv_path, certificates_path FROM candidates WHERE user_id = ?");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $candidateApplyDocs = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

require_once BASE_PATH . '/includes/header.php';
?>

<!-- Job Detail Header -->
<section class="job-detail-header">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color: rgba(255,255,255,.5);">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" style="color: rgba(255,255,255,.7);">Jobs</a></li>
                <li class="breadcrumb-item active" style="color: rgba(255,255,255,.9);" aria-current="page"><?php echo escape($job['title']); ?></li>
            </ol>
        </nav>

        <h1><?php echo escape($job['title']); ?></h1>

        <div class="job-meta-header mt-3">
            <?php if (!empty($job['department'])): ?>
                <span><i class="bi bi-building"></i> <?php echo escape($job['department']); ?></span>
            <?php endif; ?>
            <?php if (!empty($job['location'])): ?>
                <span><i class="bi bi-geo-alt"></i> <?php echo escape($job['location']); ?></span>
            <?php endif; ?>
            <span class="badge-type <?php echo $badgeClass; ?>"><?php echo escape($job['job_type']); ?></span>
            <span class="badge-type <?php echo vacancyScopeBadgeClass($vScope); ?>"><?php echo escape($vScope); ?> vacancy</span>
            <?php if (!empty($job['max_applications']) && (int)$job['max_applications'] > 1): ?>
                <span><i class="bi bi-people"></i> <?php echo (int)$job['max_applications']; ?> Vacancies</span>
            <?php else: ?>
                <span><i class="bi bi-person"></i> 1 Vacancy</span>
            <?php endif; ?>
            <?php if ($daysLeft !== null && $daysLeft >= 0 && !empty($job['application_deadline'])): ?>
                <span class="badge-deadline job-deadline-badge <?php echo $daysLeft <= 5 ? 'urgent' : ''; ?>">
                    <span class="d-block"><i class="bi bi-clock"></i> <?php echo $daysLeft; ?> day<?php echo $daysLeft !== 1 ? 's' : ''; ?> left</span>
                    <span class="d-block deadline-date-line">Closes <?php echo escape(formatDateDisplay($job['application_deadline'])); ?></span>
                </span>
            <?php elseif ($daysLeft === -1): ?>
                <span class="badge-deadline urgent"><i class="bi bi-clock"></i> Deadline passed</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Job Detail Body -->
<section class="job-detail-body" style="background: #f3f3f4;">
    <div class="container">
        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="job-detail-card">
                    <!-- Summary -->
                    <div class="card-section">
                        <h3><i class="bi bi-file-text"></i> Job Summary</h3>
                        <p style="color: #333; line-height: 1.7; margin: 0;">
                            <?php echo nl2br(escape($job['description'])); ?>
                        </p>
                    </div>

                    <?php if (!empty($job['qualifications'])): ?>
                    <div class="card-section">
                        <h3><i class="bi bi-mortarboard"></i> Qualifications &amp; Requirements</h3>
                        <div style="color: #333; line-height: 1.8;">
                            <?php echo nl2br(escape($job['qualifications'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($job['requirements'])): ?>
                    <div class="card-section">
                        <h3><i class="bi bi-list-check"></i> Key Responsibilities</h3>
                        <div style="color: #333; line-height: 1.8;">
                            <?php echo nl2br(escape($job['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Job Info Card -->
                <div class="job-sidebar-card mb-4">
                    <div class="sidebar-header">
                        <h4><i class="bi bi-info-circle me-2"></i>Job Details</h4>
                    </div>
                    <div class="sidebar-body">
                        <?php if (!empty($job['department'])): ?>
                        <div class="job-info-row">
                            <span class="info-label">Department</span>
                            <span class="info-value"><?php echo escape($job['department']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="job-info-row">
                            <span class="info-label">Job Type</span>
                            <span class="info-value"><?php echo escape($job['job_type']); ?></span>
                        </div>
                        <div class="job-info-row">
                            <span class="info-label">Vacancy</span>
                            <span class="info-value"><span class="badge-type <?php echo vacancyScopeBadgeClass($vScope); ?>"><?php echo escape($vScope); ?></span></span>
                        </div>
                        <?php if (!empty($job['location'])): ?>
                        <div class="job-info-row">
                            <span class="info-label">Location</span>
                            <span class="info-value"><?php echo escape($job['location']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="job-info-row">
                            <span class="info-label">Vacancies</span>
                            <span class="info-value"><?php echo !empty($job['max_applications']) ? (int)$job['max_applications'] : 1; ?> Position<?php echo (!empty($job['max_applications']) && (int)$job['max_applications'] > 1) ? 's' : ''; ?></span>
                        </div>
                        <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                        <div class="job-info-row">
                            <span class="info-label">Salary Range</span>
                            <span class="info-value">
                                <?php
                                if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                    echo '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']);
                                } elseif (!empty($job['salary_min'])) {
                                    echo 'From $' . number_format($job['salary_min']);
                                } else {
                                    echo 'Up to $' . number_format($job['salary_max']);
                                }
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="job-info-row">
                            <span class="info-label">Published</span>
                            <span class="info-value"><?php echo escape(formatDateDisplay($job['created_at'])); ?></span>
                        </div>
                        <?php if (!empty($job['application_deadline'])): ?>
                        <div class="job-info-row">
                            <span class="info-label">Deadline</span>
                            <span class="info-value"><?php echo escape(formatDateDisplay($job['application_deadline'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="job-info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value"><span class="job-status-badge">OPEN</span></span>
                        </div>
                    </div>
                </div>

                <!-- Apply Card -->
                <div class="job-sidebar-card">
                    <div class="sidebar-header" style="background: #c61f26; border-bottom-color: #c61f26;">
                        <h4 style="color: #fff; margin: 0;"><i class="bi bi-send me-2"></i>Apply Now</h4>
                    </div>
                    <div class="sidebar-body">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-lock display-6 text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-3">Sign in or create an account to apply for this position.</p>
                                <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-person-plus me-1"></i> Register to Apply
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                                </a>
                            </div>
                        <?php elseif ((int)($_SESSION['role_id'] ?? 0) !== 1): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-1"></i> Only Candidates can apply for jobs.
                            </div>
                        <?php else: ?>
                            <form method="post" class="vstack gap-3">
                                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

                                <div>
                                    <label class="form-label fw-semibold">Cover Letter <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="cover_letter" rows="5" placeholder="Example: Dear Hiring Committee, I am writing to apply for [role]… (2–4 short paragraphs: motivation, relevant experience, availability, closing)." required title="Plain text; avoid pasting from Word with odd formatting"></textarea>
                                </div>

                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="fw-semibold mb-2"><i class="bi bi-paperclip me-1"></i> Documents from your profile</div>
                                    <p class="small text-muted mb-2">CV and certificates are not uploaded here. They are taken from <strong>My Profile</strong> and attached to this application automatically.</p>
                                    <?php
                                    $hasCv = $candidateApplyDocs && !empty($candidateApplyDocs['cv_path']);
                                    $hasCert = $candidateApplyDocs && !empty($candidateApplyDocs['certificates_path']);
                                    ?>
                                    <?php if ($hasCv && $hasCert): ?>
                                        <ul class="small mb-0 ps-3">
                                            <li>CV: <a href="<?php echo escape(candidateMyDocumentUrl('cv')); ?>" target="_blank" rel="noopener noreferrer">View current file</a></li>
                                            <li>Certificates: <a href="<?php echo escape(candidateMyDocumentUrl('certs')); ?>" target="_blank" rel="noopener noreferrer">View current file</a></li>
                                        </ul>
                                    <?php else: ?>
                                        <div class="alert alert-warning py-2 px-3 small mb-0">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            Upload your CV and qualifications on <a href="<?php echo BASE_URL; ?>/candidate/profile.php" class="alert-link">My Profile</a> before you can submit.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-primary w-100" type="submit" <?php echo (!$hasCv || !$hasCert) ? 'disabled' : ''; ?>>
                                    <i class="bi bi-send me-1"></i> Submit Application
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
