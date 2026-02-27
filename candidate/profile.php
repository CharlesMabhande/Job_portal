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

    // Update basic candidate fields
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
        // CV upload (optional)
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

        // Certificates upload (optional)
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
            // Ensure profile marked as completed if no file errors
            $stmt = $db->prepare("UPDATE candidates SET profile_completed = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            redirect('/candidate/profile.php', 'Profile updated.', 'success');
        }
    }

    $profile = getCandidateProfile($userId);
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">My Profile</h1>
        <div class="text-muted">Keep your details up-to-date before applying.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/candidate/dashboard.php">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo escape($error); ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
    <div class="card-body">
        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?php echo escape($profile['date_of_birth'] ?? ''); ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" value="<?php echo escape($profile['country'] ?? ''); ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" required><?php echo escape($profile['address'] ?? ''); ?></textarea>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?php echo escape($profile['city'] ?? ''); ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-control" value="<?php echo escape($profile['state'] ?? ''); ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Postal Code</label>
                <input type="text" name="postal_code" class="form-control" value="<?php echo escape($profile['postal_code'] ?? ''); ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label">Default CV</label>
                <input type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx">
                <div class="form-text">
                    <?php if (!empty($profile['cv_path'])): ?>
                        Current: <a href="<?php echo BASE_URL . '/uploads/' . escape($profile['cv_path']); ?>" target="_blank" rel="noreferrer">View CV</a>
                    <?php else: ?>
                        No CV uploaded yet.
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Qualifications / Certificates (single document)</label>
                <input type="file" name="certificates" class="form-control" accept=".pdf,.doc,.docx">
                <div class="form-text">
                    <?php if (!empty($profile['certificates_path'])): ?>
                        Current: <a href="<?php echo BASE_URL . '/uploads/' . escape($profile['certificates_path']); ?>" target="_blank" rel="noreferrer">View Certificates Document</a>
                    <?php else: ?>
                        No certificates document uploaded yet.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex flex-column flex-sm-row gap-2 justify-content-between">
        <button class="btn btn-primary" type="submit">Save Profile</button>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/index.php">Browse Jobs</a>
    </div>
</form>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

