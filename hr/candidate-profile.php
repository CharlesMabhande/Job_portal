<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'Management', 'SysAdmin']);
require_once BASE_PATH . '/includes/staff_candidate_profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_candidate') {
    requireCSRFToken();
    $delCandId = (int)($_POST['candidate_id'] ?? 0);
    if ($delCandId < 1 || !staffCanViewCandidateProfile($delCandId)) {
        redirect('/hr/applications.php', 'Invalid request.', 'error');
    }
    $profDel = getCandidateProfileByCandidateId($delCandId);
    if (!$profDel) {
        redirect('/hr/applications.php', 'Candidate not found.', 'error');
    }
    if (($_SESSION['role_name'] ?? '') !== 'SysAdmin') {
        redirect('/hr/candidate-profile.php?candidate_id=' . $delCandId, 'Only system administrators can delete candidate accounts.', 'error');
    }
    $result = adminDeleteCandidateUser((int)$profDel['user_id'], (int)$_SESSION['user_id']);
    if (!empty($result['success'])) {
        redirect('/admin/users.php', $result['message'] ?? 'Candidate account removed.', 'success');
    }
    redirect('/hr/candidate-profile.php?candidate_id=' . $delCandId, $result['message'] ?? 'Could not delete account.', 'error');
}

$candidateId = (int)($_GET['candidate_id'] ?? 0);

if ($candidateId < 1) {
    redirect('/hr/applications.php', 'Invalid candidate.', 'error');
}

if (!staffCanViewCandidateProfile($candidateId)) {
    redirect('/hr/applications.php', 'You do not have access to this profile.', 'error');
}

$profile = getCandidateProfileByCandidateId($candidateId);
if (!$profile) {
    redirect('/hr/applications.php', 'Candidate not found.', 'error');
}

$references = [];
try {
    $references = getCandidateReferences($candidateId);
} catch (Throwable $e) {
    $references = [];
}

$export = (string)($_GET['export'] ?? '');
if ($export === 'pdf') {
    $inline = isset($_GET['inline']) && (string)$_GET['inline'] === '1';
    try {
        staffStreamCandidateProfilePdf($profile, $references, !$inline);
    } catch (Throwable $e) {
        redirect('/hr/candidate-profile.php?candidate_id=' . $candidateId, $e->getMessage(), 'error');
    }
    exit;
}

if ($export === 'print') {
    header('Content-Type: text/html; charset=UTF-8');
    echo staffBuildCandidateProfileHtml($profile, $references, 'print');
    exit;
}

$pageTitle = 'Candidate profile — ' . staffCandidateDisplayName($profile);
$backHref = BASE_URL . '/hr/applications.php';
$backLabel = 'Applications';
$isSysAdmin = (($_SESSION['role_name'] ?? '') === 'SysAdmin');

function staffCandidateDocUrl(int $cid, string $doc, bool $download): string {
    $q = ['candidate_id' => $cid, 'doc' => $doc];
    if ($download) {
        $q['download'] = '1';
    }

    return BASE_URL . '/hr/candidate-document.php?' . http_build_query($q);
}

require_once BASE_PATH . '/includes/header.php';

$dob = !empty($profile['date_of_birth']) ? formatDateDisplay($profile['date_of_birth']) : null;
$ageYears = ageFromDateOfBirth($profile['date_of_birth'] ?? null);
$hasCv = !empty($profile['cv_path']);
$hasCert = !empty($profile['certificates_path']);
$pqStaff = staffCandidateQualificationRows($profile['professional_qualifications'] ?? null);
$olStaff = staffCandidateQualificationRows($profile['o_level_qualifications'] ?? null);
$alStaff = staffCandidateQualificationRows($profile['a_level_qualifications'] ?? null);
$ocStaff = staffCandidateQualificationRows($profile['other_certifications'] ?? null);
$wxStaff = staffCandidateWorkRows($profile['experience'] ?? null);
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-person-vcard me-2"></i><?php echo escape(staffCandidateDisplayName($profile)); ?></h1>
        <p class="mb-0">Read-only profile · Candidate ID <?php echo (int)$candidateId; ?></p>
    </div>
    <div class="page-actions d-flex flex-wrap gap-2">
        <a class="btn btn-light btn-sm text-primary fw-semibold" href="<?php echo escape(BASE_URL . '/hr/candidate-profile.php?candidate_id=' . $candidateId . '&export=pdf'); ?>" title="Download PDF summary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <a class="btn btn-outline-light btn-sm" href="<?php echo escape(BASE_URL . '/hr/candidate-profile.php?candidate_id=' . $candidateId . '&export=print'); ?>" target="_blank" rel="noopener noreferrer" title="Print or save as PDF in browser">
            <i class="bi bi-printer me-1"></i> Print view
        </a>
        <a class="btn btn-outline-light btn-sm" href="<?php echo escape($backHref); ?>">
            <i class="bi bi-arrow-left me-1"></i> <?php echo escape($backLabel); ?>
        </a>
    </div>
</div>

<div class="alert alert-info small mb-4">
    <i class="bi bi-info-circle me-1"></i>
    This page is <strong>view only</strong> for HR and Management. Documents open in a new tab or download; they are not editable here.
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card animate-in h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>Account</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8"><?php echo escape(staffCandidateDisplayName($profile)); ?></dd>
                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8"><a href="mailto:<?php echo escape($profile['email'] ?? ''); ?>"><?php echo escape($profile['email'] ?? '—'); ?></a></dd>
                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8"><?php echo escape(trim((string)($profile['phone'] ?? '')) !== '' ? (string)$profile['phone'] : '—'); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card animate-in h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Personal details</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Date of birth</dt>
                    <dd class="col-sm-8"><?php echo $dob ? escape($dob) : '—'; ?></dd>
                    <dt class="col-sm-4 text-muted">Age</dt>
                    <dd class="col-sm-8"><?php echo $ageYears !== null ? (int)$ageYears : '—'; ?></dd>
                    <dt class="col-sm-4 text-muted">Gender</dt>
                    <dd class="col-sm-8"><?php echo escape(trim((string)($profile['gender'] ?? '')) !== '' ? (string)$profile['gender'] : '—'); ?></dd>
                    <dt class="col-sm-4 text-muted">Country</dt>
                    <dd class="col-sm-8"><?php echo escape(trim((string)($profile['country'] ?? '')) !== '' ? (string)$profile['country'] : '—'); ?></dd>
                    <dt class="col-sm-4 text-muted">Address</dt>
                    <dd class="col-sm-8"><?php $a = trim((string)($profile['address'] ?? '')); echo $a !== '' ? nl2br(escape($a)) : '—'; ?></dd>
                    <dt class="col-sm-4 text-muted">City</dt>
                    <dd class="col-sm-8"><?php echo escape(trim((string)($profile['city'] ?? '')) !== '' ? (string)$profile['city'] : '—'); ?></dd>
                    <dt class="col-sm-4 text-muted">State / province</dt>
                    <dd class="col-sm-8"><?php echo escape(trim((string)($profile['state'] ?? '')) !== '' ? (string)$profile['state'] : '—'); ?></dd>
                    <dt class="col-sm-4 text-muted">Postal code</dt>
                    <dd class="col-sm-8"><?php echo escape(trim((string)($profile['postal_code'] ?? '')) !== '' ? (string)$profile['postal_code'] : '—'); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card animate-in mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-award me-2 text-primary"></i>Professional qualifications</h2>
        <?php if (!$pqStaff): ?>
            <p class="text-muted small mb-0">None listed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Institution</th><th>Qualification</th><th>Grade</th><th>Year</th></tr></thead>
                    <tbody>
                        <?php foreach ($pqStaff as $r): ?>
                            <tr>
                                <td><?php echo escape((string)($r['institution'] ?? '—')); ?></td>
                                <td><?php echo escape((string)($r['title'] ?? '—')); ?></td>
                                <td><?php echo escape(trim((string)($r['grade'] ?? '')) !== '' ? (string)$r['grade'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['year'] ?? '')) !== '' ? (string)$r['year'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card animate-in mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-journal-bookmark me-2 text-primary"></i>O-Level</h2>
        <?php if (!$olStaff): ?>
            <p class="text-muted small mb-0">None listed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>School</th><th>Subject</th><th>Grade</th><th>Month</th><th>Year</th><th>Examining board</th></tr></thead>
                    <tbody>
                        <?php foreach ($olStaff as $r): ?>
                            <tr>
                                <td><?php echo escape((string)($r['institution'] ?? '—')); ?></td>
                                <td><?php echo escape((string)($r['title'] ?? '—')); ?></td>
                                <td><?php echo escape(trim((string)($r['grade'] ?? '')) !== '' ? (string)$r['grade'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['month'] ?? '')) !== '' ? (string)$r['month'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['year'] ?? '')) !== '' ? (string)$r['year'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['examining_board'] ?? '')) !== '' ? (string)$r['examining_board'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card animate-in mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-journal-richtext me-2 text-primary"></i>A-Level</h2>
        <?php if (!$alStaff): ?>
            <p class="text-muted small mb-0">None listed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>School / college</th><th>Subject</th><th>Grade</th><th>Month</th><th>Year</th><th>Examining board</th></tr></thead>
                    <tbody>
                        <?php foreach ($alStaff as $r): ?>
                            <tr>
                                <td><?php echo escape((string)($r['institution'] ?? '—')); ?></td>
                                <td><?php echo escape((string)($r['title'] ?? '—')); ?></td>
                                <td><?php echo escape(trim((string)($r['grade'] ?? '')) !== '' ? (string)$r['grade'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['month'] ?? '')) !== '' ? (string)$r['month'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['year'] ?? '')) !== '' ? (string)$r['year'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['examining_board'] ?? '')) !== '' ? (string)$r['examining_board'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card animate-in mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-patch-check me-2 text-primary"></i>Other certifications</h2>
        <?php if (!$ocStaff): ?>
            <p class="text-muted small mb-0">None listed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Provider</th><th>Certificate / course</th><th>Grade</th><th>Year</th></tr></thead>
                    <tbody>
                        <?php foreach ($ocStaff as $r): ?>
                            <tr>
                                <td><?php echo escape((string)($r['institution'] ?? '—')); ?></td>
                                <td><?php echo escape((string)($r['title'] ?? '—')); ?></td>
                                <td><?php echo escape(trim((string)($r['grade'] ?? '')) !== '' ? (string)$r['grade'] : '—'); ?></td>
                                <td><?php echo escape(trim((string)($r['year'] ?? '')) !== '' ? (string)$r['year'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card animate-in mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-briefcase me-2 text-primary"></i>Work experience</h2>
        <?php if (!$wxStaff): ?>
            <p class="text-muted small mb-0">None listed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Employer</th><th>Title</th><th>Start</th><th>End</th><th>Current</th><th>Summary</th></tr></thead>
                    <tbody>
                        <?php foreach ($wxStaff as $wx): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo escape((string)($wx['employer'] ?? '—')); ?></td>
                                <td><?php echo escape((string)($wx['job_title'] ?? '—')); ?></td>
                                <td><?php echo escape(trim((string)($wx['start'] ?? '')) !== '' ? (string)$wx['start'] : '—'); ?></td>
                                <td><?php echo !empty($wx['current']) ? '—' : escape(trim((string)($wx['end'] ?? '')) !== '' ? (string)$wx['end'] : '—'); ?></td>
                                <td><?php echo !empty($wx['current']) ? 'Yes' : 'No'; ?></td>
                                <td class="small"><?php $s = trim((string)($wx['description'] ?? '')); echo $s !== '' ? nl2br(escape($s)) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card animate-in mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-file-earmark-text me-2 text-warning"></i>Documents</h2>
        <p class="text-muted small mb-3">Files stored on the candidate profile (used for applications).</p>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-2">CV</div>
                    <?php if ($hasCv): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo escape(staffCandidateDocUrl($candidateId, 'cv', false)); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-eye me-1"></i>View</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo escape(staffCandidateDocUrl($candidateId, 'cv', true)); ?>"><i class="bi bi-download me-1"></i>Download</a>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small">No file on record.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-2">Qualifications / certificates</div>
                    <?php if ($hasCert): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo escape(staffCandidateDocUrl($candidateId, 'certs', false)); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-eye me-1"></i>View</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo escape(staffCandidateDocUrl($candidateId, 'certs', true)); ?>"><i class="bi bi-download me-1"></i>Download</a>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small">No file on record.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card animate-in mt-4 mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-people me-2 text-secondary"></i>References</h2>
        <?php if (!$references): ?>
            <p class="text-muted small mb-0">No references provided.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>#</th><th>Name</th><th>Title / relationship</th><th>Organisation</th><th>Email</th><th>Phone</th></tr></thead>
                    <tbody>
                        <?php foreach ($references as $i => $ref): ?>
                            <tr>
                                <td><?php echo (int)$i + 1; ?></td>
                                <td class="fw-semibold"><?php echo escape((string)($ref['full_name'] ?? '')); ?></td>
                                <td><?php echo escape((string)($ref['job_title'] ?? '—')); ?></td>
                                <td><?php echo escape((string)($ref['organisation'] ?? '—')); ?></td>
                                <td><?php $em = trim((string)($ref['email'] ?? '')); echo $em !== '' ? '<a href="mailto:' . escape($em) . '">' . escape($em) . '</a>' : '—'; ?></td>
                                <td><?php echo escape((string)($ref['phone'] ?? '—')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isSysAdmin): ?>
    <div class="card border-danger animate-in mb-4">
        <div class="card-body">
            <h2 class="h6 text-danger mb-2"><i class="bi bi-exclamation-octagon me-1"></i> System administrator</h2>
            <p class="small text-muted mb-3">
                Permanently delete this candidate’s account: their login, profile, applications, interviews, uploaded CV and certificates, and references.
                Application counts on job postings are adjusted. <strong>This cannot be undone.</strong>
            </p>
            <form method="post" onsubmit="return confirm('Permanently delete this candidate account and all related data? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                <input type="hidden" name="action" value="delete_candidate">
                <input type="hidden" name="candidate_id" value="<?php echo (int)$candidateId; ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Delete candidate account
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
