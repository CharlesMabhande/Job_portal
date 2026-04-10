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

    $genderPosted = normalizeCandidateGender($_POST['gender'] ?? '');
    if ($genderPosted === null) {
        $error = 'Please select your gender (Male, Female, or Other).';
    }

    $qmaxPost = candidateProfileQualificationSlotsMax();
    $wmaxPost = candidateProfileWorkSlotsMax();
    $data = [
        'date_of_birth' => sanitize($_POST['date_of_birth'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'state' => sanitize($_POST['state'] ?? ''),
        'country' => sanitize($_POST['country'] ?? ''),
        'postal_code' => sanitize($_POST['postal_code'] ?? ''),
        'professional_qualifications' => encodeQualificationRowsFromPost(['pq_inst', 'pq_title', 'pq_grade', 'pq_year'], $qmaxPost),
        'o_level_qualifications' => encodeQualificationRowsFromPost(['ol_inst', 'ol_subject', 'ol_grade', 'ol_year'], $qmaxPost, ['month' => 'ol_month', 'examining_board' => 'ol_board']),
        'a_level_qualifications' => encodeQualificationRowsFromPost(['al_inst', 'al_subject', 'al_grade', 'al_year'], $qmaxPost, ['month' => 'al_month', 'examining_board' => 'al_board']),
        'other_certifications' => encodeQualificationRowsFromPost(['oc_inst', 'oc_title', 'oc_grade', 'oc_year'], $qmaxPost),
        'experience' => encodeWorkExperienceFromPost($wmaxPost),
    ];
    if (!$error) {
        $data['gender'] = $genderPosted;
    }

    $update = ['success' => false];
    if (!$error) {
        $update = updateCandidateProfile($userId, $data);
    }
    if (!$error) {
        if (!$update['success']) {
            $error = $update['message'] ?? 'Failed to update profile';
        } else {
            $refRows = [];
            for ($ri = 0; $ri < candidateReferencesMax(); $ri++) {
                $refRows[] = [
                    'full_name' => trim(strip_tags((string)($_POST['ref_name'][$ri] ?? ''))),
                    'job_title' => trim(strip_tags((string)($_POST['ref_title'][$ri] ?? ''))),
                    'organisation' => trim(strip_tags((string)($_POST['ref_org'][$ri] ?? ''))),
                    'email' => trim(strip_tags((string)($_POST['ref_email'][$ri] ?? ''))),
                    'phone' => trim(strip_tags((string)($_POST['ref_phone'][$ri] ?? ''))),
                ];
            }
            $profileForRef = getCandidateProfile($userId);
            $refErr = $profileForRef ? saveCandidateReferences((int)$profileForRef['candidate_id'], $refRows) : 'Profile not found.';
            if ($refErr) {
                $error = $refErr;
            }
        }
    }

    if (!$error && ($update['success'] ?? false)) {
        if (isset($_FILES['cv'])) {
            $cvErr = (int) ($_FILES['cv']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($cvErr === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['cv'], CV_DIR, 'cv');
                if (!$upload['success']) {
                    $error = $upload['message'] ?? 'CV upload failed';
                } else {
                    $cvRel = 'cv/' . $upload['filename'];
                    $stmt = $db->prepare("UPDATE candidates SET cv_path = ? WHERE user_id = ?");
                    $stmt->execute([$cvRel, $userId]);
                }
            } elseif ($cvErr !== UPLOAD_ERR_NO_FILE) {
                $error = fileUploadErrorMessage($cvErr) ?? 'CV upload failed.';
            }
        }

        if (!$error && isset($_FILES['certificates'])) {
            $certErr = (int) ($_FILES['certificates']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($certErr === UPLOAD_ERR_OK) {
                $certUpload = uploadFile($_FILES['certificates'], DOCS_DIR, 'certs');
                if (!$certUpload['success']) {
                    $error = $certUpload['message'] ?? 'Certificates upload failed';
                } else {
                    $certRel = 'documents/' . $certUpload['filename'];
                    $stmt = $db->prepare("UPDATE candidates SET certificates_path = ? WHERE user_id = ?");
                    $stmt->execute([$certRel, $userId]);
                }
            } elseif ($certErr !== UPLOAD_ERR_NO_FILE) {
                $error = fileUploadErrorMessage($certErr) ?? 'Certificates upload failed.';
            }
        }

        if (!$error) {
            $stmt = $db->prepare("SELECT cv_path, certificates_path FROM candidates WHERE user_id = ?");
            $stmt->execute([$userId]);
            $docCheck = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($docCheck['cv_path']) || empty($docCheck['certificates_path'])) {
                $error = 'Upload both documents: one CV file and one combined qualifications file (each ' . maxUploadSizeLabel() . ' or less). They are stored on your profile and reused for every application.';
            } else {
                $stmt = $db->prepare("UPDATE candidates SET profile_completed = 1 WHERE user_id = ?");
                $stmt->execute([$userId]);
                redirect('/candidate/profile.php', 'Profile updated.', 'success');
            }
        }
    }

    $profile = getCandidateProfile($userId);
}

$candidateId = (int)$profile['candidate_id'];
$references = array_slice(getCandidateReferences($candidateId), 0, candidateReferencesMax());
$refSlots = [];
$emptyRef = ['full_name' => '', 'job_title' => '', 'organisation' => '', 'email' => '', 'phone' => ''];
for ($i = 0; $i < candidateReferencesMax(); $i++) {
    $refSlots[$i] = $emptyRef;
    if (isset($references[$i]) && is_array($references[$i])) {
        foreach (['full_name', 'job_title', 'organisation', 'email', 'phone'] as $rk) {
            $refSlots[$i][$rk] = (string)($references[$i][$rk] ?? '');
        }
    }
}

$qmax = candidateProfileQualificationSlotsMax();
$wmax = candidateProfileWorkSlotsMax();
$emptyQual = ['institution' => '', 'title' => '', 'grade' => '', 'year' => '', 'month' => '', 'examining_board' => ''];
$padQualSlots = static function (?string $json, int $max) use ($emptyQual): array {
    $rows = decodeCandidateProfileJsonList($json);
    $out = [];
    for ($i = 0; $i < $max; $i++) {
        $out[$i] = $emptyQual;
        if (isset($rows[$i]) && is_array($rows[$i])) {
            foreach (['institution', 'title', 'grade', 'year', 'month', 'examining_board'] as $k) {
                $out[$i][$k] = (string)($rows[$i][$k] ?? '');
            }
        }
    }

    return $out;
};
$pqSlots = $padQualSlots($profile['professional_qualifications'] ?? null, $qmax);
$olSlots = $padQualSlots($profile['o_level_qualifications'] ?? null, $qmax);
$alSlots = $padQualSlots($profile['a_level_qualifications'] ?? null, $qmax);
$ocSlots = $padQualSlots($profile['other_certifications'] ?? null, $qmax);

$emptyWx = ['employer' => '', 'job_title' => '', 'start' => '', 'end' => '', 'current' => false, 'description' => ''];
$wxRaw = decodeCandidateProfileJsonList($profile['experience'] ?? null);
$wxSlots = [];
for ($i = 0; $i < $wmax; $i++) {
    $wxSlots[$i] = $emptyWx;
    if (isset($wxRaw[$i]) && is_array($wxRaw[$i])) {
        $wxSlots[$i]['employer'] = (string)($wxRaw[$i]['employer'] ?? '');
        $wxSlots[$i]['job_title'] = (string)($wxRaw[$i]['job_title'] ?? '');
        $wxSlots[$i]['start'] = (string)($wxRaw[$i]['start'] ?? '');
        $wxSlots[$i]['end'] = (string)($wxRaw[$i]['end'] ?? '');
        $wxSlots[$i]['current'] = !empty($wxRaw[$i]['current']);
        $wxSlots[$i]['description'] = (string)($wxRaw[$i]['description'] ?? '');
    }
}

$ageYears = ageFromDateOfBirth($profile['date_of_birth'] ?? null);
$pqView = decodeCandidateProfileJsonList($profile['professional_qualifications'] ?? null);
$olView = decodeCandidateProfileJsonList($profile['o_level_qualifications'] ?? null);
$alView = decodeCandidateProfileJsonList($profile['a_level_qualifications'] ?? null);
$ocView = decodeCandidateProfileJsonList($profile['other_certifications'] ?? null);
$wxView = decodeCandidateProfileJsonList($profile['experience'] ?? null);

$qualRowHasContent = static function ($r): bool {
    if (!is_array($r)) {
        return false;
    }
    foreach (['institution', 'title', 'grade', 'year', 'month', 'examining_board'] as $k) {
        if (trim((string)($r[$k] ?? '')) !== '') {
            return true;
        }
    }

    return false;
};

$wxRowHasContent = static function ($r): bool {
    if (!is_array($r)) {
        return false;
    }
    if (!empty($r['current'])) {
        return true;
    }
    foreach (['employer', 'job_title', 'start', 'end', 'description'] as $k) {
        if (trim((string)($r[$k] ?? '')) !== '') {
            return true;
        }
    }

    return false;
};

$repeatInitialCount = static function (array $slots, int $max, callable $rowHasContent): int {
    $last = -1;
    for ($i = 0; $i < $max; $i++) {
        if (!isset($slots[$i])) {
            continue;
        }
        if ($rowHasContent($slots[$i])) {
            $last = $i;
        }
    }

    return min($max, max(1, $last + 1));
};

$pqInitial = $repeatInitialCount($pqSlots, $qmax, $qualRowHasContent);
$olInitial = $repeatInitialCount($olSlots, $qmax, $qualRowHasContent);
$alInitial = $repeatInitialCount($alSlots, $qmax, $qualRowHasContent);
$ocInitial = $repeatInitialCount($ocSlots, $qmax, $qualRowHasContent);
$wxInitial = $repeatInitialCount($wxSlots, $wmax, $wxRowHasContent);

$refRowHasContent = static function (array $r): bool {
    foreach (['full_name', 'job_title', 'organisation', 'email', 'phone'] as $k) {
        if (trim((string)($r[$k] ?? '')) !== '') {
            return true;
        }
    }

    return false;
};

$refMax = candidateReferencesMax();
$lastRefIdx = -1;
for ($ri = 0; $ri < $refMax; $ri++) {
    if ($refRowHasContent($refSlots[$ri])) {
        $lastRefIdx = $ri;
    }
}
$refInitial = min($refMax, max(1, $lastRefIdx + 1));

$initialEditMode = $error !== null || (isset($_GET['edit']) && $_GET['edit'] === '1');

require_once BASE_PATH . '/includes/header.php';

$viewVal = function (?string $v): string {
    $t = trim((string)$v);
    return $t !== '' ? escape($t) : '<span class="text-muted fst-italic">Not provided</span>';
};

require __DIR__ . '/profile_view_edit.php';
