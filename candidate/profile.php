<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['Candidate']);

$pageTitle = 'My Profile';
$db = getDBConnection();
$userId = (int)$_SESSION['user_id'];

$profile = getCandidateProfile($userId);
if (!$profile) {
    redirect('/candidate/dashboard.php', 'Profile not found.', 'error');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();

    $data = [
        'date_of_birth' => sanitize($_POST['date_of_birth'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'state' => sanitize($_POST['state'] ?? ''),
        'country' => sanitize($_POST['country'] ?? ''),
        'postal_code' => sanitize($_POST['postal_code'] ?? '')
    ];

    $update = updateCandidateProfile($userId, $data);
    if (!$update['success']) {
        $error = $update['message'] ?? 'Failed to update profile';
    } else {
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['cv'], CV_DIR, 'cv');
            if (!$upload['success']) {
                $error = $upload['message'] ?? 'CV upload failed';
            } else {
                $cvRel = 'cv/' . $upload['filename'];
                $stmt = $db->prepare("UPDATE candidates SET cv_path = ?, profile_completed = 1 WHERE user_id = ?");
                $stmt->execute([$cvRel, $userId]);
            }
        }

        if (!$error && isset($_FILES['certificates']) && $_FILES['certificates']['error'] === UPLOAD_ERR_OK) {
            $certUpload = uploadFile($_FILES['certificates'], DOCS_DIR, 'certs');
            if (!$certUpload['success']) {
                $error = $certUpload['message'] ?? 'Certificates upload failed';
            } else {
                $certRel = 'documents/' . $certUpload['filename'];
                $stmt = $db->prepare("UPDATE candidates SET certificates_path = ?, profile_completed = 1 WHERE user_id = ?");
                $stmt->execute([$certRel, $userId]);
            }
        }

        if (!$error) {
            $stmt = $db->prepare("UPDATE candidates SET profile_completed = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            redirect('/candidate/profile.php', 'Profile updated.', 'success');
        }
    }

    $profile = getCandidateProfile($userId);
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-person-badge me-2"></i>My Profile</h1>
        <p>Keep your details up-to-date to strengthen your applications.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/candidate/dashboard.php">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?php echo escape($error); ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: linear-gradient(135deg, #2e37a4, #4f46e5); color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-person"></i>
                </span>
                Personal Details
            </h5>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo escape($profile['date_of_birth'] ?? ''); ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Country <span class="text-danger">*</span></label>
                    <input type="text" name="country" class="form-control" value="<?php echo escape($profile['country'] ?? ''); ?>" required placeholder="e.g. Zimbabwe">
                </div>
                <div class="col-12">
                    <label class="form-label">Street Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required placeholder="Enter your full address"><?php echo escape($profile['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control" value="<?php echo escape($profile['city'] ?? ''); ?>" required placeholder="e.g. Harare">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">State / Province <span class="text-danger">*</span></label>
                    <input type="text" name="state" class="form-control" value="<?php echo escape($profile['state'] ?? ''); ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Postal Code <span class="text-danger">*</span></label>
                    <input type="text" name="postal_code" class="form-control" value="<?php echo escape($profile['postal_code'] ?? ''); ?>" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4" style="animation-delay: .1s;">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <span style="background: linear-gradient(135deg, #059669, #14b8a6); color: #fff; width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </span>
                Documents
            </h5>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Default CV</label>
                    <input type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx">
                    <div class="form-text">
                        <?php if (!empty($profile['cv_path'])): ?>
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Current: <a href="<?php echo BASE_URL . '/uploads/' . escape($profile['cv_path']); ?>" target="_blank" rel="noreferrer">View CV</a>
                        <?php else: ?>
                            <i class="bi bi-exclamation-circle text-warning me-1"></i> No CV uploaded yet. PDF, DOC, DOCX accepted.
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Qualifications / Certificates</label>
                    <input type="file" name="certificates" class="form-control" accept=".pdf,.doc,.docx">
                    <div class="form-text">
                        <?php if (!empty($profile['certificates_path'])): ?>
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Current: <a href="<?php echo BASE_URL . '/uploads/' . escape($profile['certificates_path']); ?>" target="_blank" rel="noreferrer">View Certificates</a>
                        <?php else: ?>
                            <i class="bi bi-exclamation-circle text-warning me-1"></i> No certificates uploaded yet.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-between">
        <button class="btn btn-primary btn-lg" type="submit">
            <i class="bi bi-check-lg me-1"></i> Save Profile
        </button>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/index.php">
            <i class="bi bi-search me-1"></i> Browse Jobs
        </a>
    </div>
</form>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
