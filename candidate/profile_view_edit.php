<?php
/**
 * Candidate profile view + edit UI (included from profile.php).
 *
 * @var array<string,mixed> $profile
 * @var callable $viewVal
 * @var list<array<string,mixed>> $references
 * @var bool $initialEditMode
 */
if (!isset($profile, $viewVal)) {
    return;
}

/** @param list<array<string,string>> $slots */
$jpQualRowFields = static function (
    string $pfx,
    array $qs,
    bool $empty,
    bool $oaExtra
): void {
    $gv = static function (string $k) use ($qs, $empty): string {
        return $empty ? '' : escape((string)($qs[$k] ?? ''));
    };
    $phInst = $pfx === 'pq' ? 'e.g. Lupane State University' : ($pfx === 'oc' ? 'e.g. training provider or board' : ($pfx === 'al' ? 'e.g. Founders High School' : 'e.g. Sizane High School'));
    $phTitle = $pfx === 'pq' ? 'e.g. BSc Computer Science' : ($pfx === 'oc' ? 'e.g. ICDL, Project Management' : 'e.g. Mathematics, English');
    $phGrade = $pfx === 'pq' ? 'e.g. 2:1, Pass with merit' : ($pfx === 'oc' ? 'e.g. Pass, Distinction' : 'e.g. A, B, 1–9');
    ?>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label"><?php echo $pfx === 'pq' ? 'School / college / university' : ($pfx === 'oc' ? 'Institution / provider' : ($pfx === 'al' ? 'School / college' : 'School')); ?></label>
            <input type="text" name="<?php echo escape($pfx); ?>_inst[]" class="form-control" value="<?php echo $gv('institution'); ?>" maxlength="255" placeholder="<?php echo escape($phInst); ?>">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label"><?php echo $pfx === 'pq' ? 'Qualification' : ($pfx === 'oc' ? 'Certificate / course name' : 'Subject'); ?></label>
            <input type="text" name="<?php echo escape($pfx === 'pq' ? 'pq_title' : ($pfx === 'oc' ? 'oc_title' : $pfx . '_subject')); ?>[]" class="form-control" value="<?php echo $gv('title'); ?>" maxlength="255" placeholder="<?php echo escape($phTitle); ?>">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label"><?php echo $pfx === 'pq' ? 'Grade / class' : ($pfx === 'oc' ? 'Grade / result' : 'Grade'); ?></label>
            <input type="text" name="<?php echo escape($pfx); ?>_grade[]" class="form-control" value="<?php echo $gv('grade'); ?>" maxlength="80" placeholder="<?php echo escape($phGrade); ?>">
        </div>
        <?php if ($oaExtra): ?>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label">Month</label>
                <?php echo candidateExamMonthSelectHtml($pfx . '_month[]', $empty ? '' : (string)($qs['month'] ?? '')); ?>
            </div>
        <?php endif; ?>
        <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label"><?php echo $pfx === 'pq' ? 'Year qualified' : 'Year'; ?></label>
            <input type="text" name="<?php echo escape($pfx); ?>_year[]" class="form-control" value="<?php echo $gv('year'); ?>" maxlength="20" placeholder="e.g. 2015 or June 2015">
        </div>
        <?php if ($oaExtra): ?>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label">Examining board</label>
                <input type="text" name="<?php echo escape($pfx); ?>_board[]" class="form-control" value="<?php echo $gv('examining_board'); ?>" maxlength="120" placeholder="e.g. ZIMSEC, Cambridge">
            </div>
        <?php endif; ?>
    </div>
    <?php
};

$jpQualTableRow = static function (
    string $pfx,
    array $qs,
    bool $empty,
    bool $oaExtra
): void {
    $gv = static function (string $k) use ($qs, $empty): string {
        return $empty ? '' : escape((string)($qs[$k] ?? ''));
    };
    $titleName = $pfx === 'pq' ? 'pq_title' : $pfx . '_subject';
    ?>
    <tr class="jp-repeat-row">
        <td>
            <div class="small text-muted mb-1">Entry <span class="jp-repeat-num">1</span></div>
            <input type="text" name="<?php echo escape($pfx); ?>_inst[]" class="form-control form-control-sm" value="<?php echo $gv('institution'); ?>" maxlength="255">
        </td>
        <td>
            <input type="text" name="<?php echo escape($titleName); ?>[]" class="form-control form-control-sm" value="<?php echo $gv('title'); ?>" maxlength="255">
        </td>
        <td>
            <input type="text" name="<?php echo escape($pfx); ?>_grade[]" class="form-control form-control-sm" value="<?php echo $gv('grade'); ?>" maxlength="80">
        </td>
        <?php if ($oaExtra): ?>
            <td><?php echo candidateExamMonthSelectHtml($pfx . '_month[]', $empty ? '' : (string)($qs['month'] ?? '')); ?></td>
        <?php endif; ?>
        <td>
            <input type="text" name="<?php echo escape($pfx); ?>_year[]" class="form-control form-control-sm" value="<?php echo $gv('year'); ?>" maxlength="20">
        </td>
        <?php if ($oaExtra): ?>
            <td>
                <input type="text" name="<?php echo escape($pfx); ?>_board[]" class="form-control form-control-sm" value="<?php echo $gv('examining_board'); ?>" maxlength="120">
            </td>
        <?php endif; ?>
        <td class="text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
        </td>
    </tr>
    <?php
};

$jpWorkTableRow = static function (array $wx, bool $empty): void {
    $val = static function (string $k) use ($wx, $empty): string {
        return $empty ? '' : escape((string)($wx[$k] ?? ''));
    };
    $isCurrent = !$empty && !empty($wx['current']);
    ?>
    <tr class="jp-repeat-row">
        <td>
            <div class="small text-muted mb-1">Role <span class="jp-repeat-num">1</span></div>
            <input type="text" name="wx_employer[]" class="form-control form-control-sm" value="<?php echo $val('employer'); ?>" maxlength="255" placeholder="Employer">
        </td>
        <td>
            <input type="text" name="wx_job_title[]" class="form-control form-control-sm" value="<?php echo $val('job_title'); ?>" maxlength="255" placeholder="Job title">
        </td>
        <td>
            <input type="text" name="wx_start[]" class="form-control form-control-sm" value="<?php echo $val('start'); ?>" maxlength="40" placeholder="e.g. Jan 2020">
        </td>
        <td>
            <input type="text" name="wx_end[]" class="form-control form-control-sm" value="<?php echo $val('end'); ?>" maxlength="40" placeholder="e.g. Dec 2023">
        </td>
        <td>
            <input type="hidden" name="wx_current[]" value="<?php echo $isCurrent ? '1' : '0'; ?>" class="jp-wx-current-hidden" autocomplete="off">
            <label class="form-check-label d-flex align-items-center gap-2 mb-0">
                <input type="checkbox" class="form-check-input jp-wx-current-cb"<?php echo $isCurrent ? ' checked' : ''; ?>>
                Current
            </label>
        </td>
        <td>
            <textarea name="wx_description[]" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="Main duties, achievements"><?php echo $val('description'); ?></textarea>
        </td>
        <td class="text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
        </td>
    </tr>
    <?php
};

$jpReferenceTableRow = static function (array $rs, bool $empty): void {
    $val = static function (string $k) use ($rs, $empty): string {
        return $empty ? '' : escape((string)($rs[$k] ?? ''));
    };
    ?>
    <tr class="jp-repeat-row">
        <td>
            <div class="small text-muted mb-1">Reference <span class="jp-repeat-num">1</span></div>
            <input type="text" name="ref_name[]" class="form-control form-control-sm" value="<?php echo $val('full_name'); ?>" maxlength="200" placeholder="Full name">
        </td>
        <td><input type="text" name="ref_title[]" class="form-control form-control-sm" value="<?php echo $val('job_title'); ?>" maxlength="200" placeholder="Job title / relationship"></td>
        <td><input type="text" name="ref_org[]" class="form-control form-control-sm" value="<?php echo $val('organisation'); ?>" maxlength="255" placeholder="Organisation"></td>
        <td><input type="email" name="ref_email[]" class="form-control form-control-sm" value="<?php echo $val('email'); ?>" maxlength="255" placeholder="name@example.com"></td>
        <td><input type="text" name="ref_phone[]" class="form-control form-control-sm" value="<?php echo $val('phone'); ?>" maxlength="50" placeholder="+263..."></td>
        <td class="text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
        </td>
    </tr>
    <?php
};
?>
<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-person-badge me-2"></i>My Profile</h1>
        <p class="mb-0">Keep your details up-to-date to strengthen your applications.</p>
        <p class="small opacity-90 mb-0 mt-1 d-none d-md-block" id="profile-mode-hint">
            <?php if ($initialEditMode): ?>
                You are editing your profile. Use <strong>View mode</strong> when you are done or to review only.
            <?php else: ?>
                You are viewing your profile. Click <strong>Edit</strong> to make changes.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions d-flex flex-wrap gap-2 align-items-center">
        <button type="button" class="btn btn-light btn-sm text-primary fw-semibold" id="btn-profile-edit" title="Edit profile"<?php echo $initialEditMode ? ' hidden' : ''; ?>><i class="bi bi-pencil-square me-1"></i> Edit</button>
        <button type="button" class="btn btn-outline-light btn-sm" id="btn-profile-view" title="Return to viewing mode"<?php echo $initialEditMode ? '' : ' hidden'; ?>><i class="bi bi-eye me-1"></i> View mode</button>
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/candidate/dashboard.php"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?php echo escape((string)$error); ?></div>
<?php endif; ?>

<div id="profile-view-panel" class="<?php echo $initialEditMode ? 'd-none' : ''; ?>">
    <div class="card animate-in mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-person-vcard"></i></span> Account
                </h5>
                <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit" title="Edit profile"><i class="bi bi-pencil-square"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <tbody>
                        <?php if (!empty($hasProfilePhotoColumn)): ?>
                            <tr>
                                <th style="width: 22%;">Profile photo</th>
                                <td colspan="3">
                                    <?php if (!empty($profile['profile_photo_path'])): ?>
                                        <img src="<?php echo escape(candidateProfilePhotoUrl((string)$profile['profile_photo_path']) ?? ''); ?>" alt="Profile photo" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #eee;">
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Full name</th>
                            <td><?php echo $viewVal(trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))); ?></td>
                            <th style="width: 18%;">Email</th>
                            <td><?php echo $viewVal($profile['email'] ?? null); ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo $viewVal($profile['phone'] ?? null); ?></td>
                            <th></th>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card animate-in mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                <h5 class="mb-0 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-person"></i></span> Personal details</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit"><i class="bi bi-pencil-square"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <tbody>
                        <tr>
                            <th style="width: 22%;">Date of birth</th>
                            <td><?php
                                $dob = $profile['date_of_birth'] ?? '';
                                if ($dob !== '') {
                                    echo escape(formatDateDisplay($dob));
                                    if (isset($ageYears) && $ageYears !== null) {
                                        echo ' <span class="text-muted">(age ' . (int)$ageYears . ')</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted fst-italic">Not provided</span>';
                                }
                            ?></td>
                            <th style="width: 18%;">Gender</th>
                            <td><?php echo $viewVal($profile['gender'] ?? null); ?></td>
                        </tr>
                        <tr>
                            <th>Country</th>
                            <td><?php echo $viewVal($profile['country'] ?? null); ?></td>
                            <th>National ID Number</th>
                            <td><?php echo !empty($hasNationalIdColumn) ? $viewVal($profile['national_id_number'] ?? null) : '<span class="text-muted fst-italic">Not provided</span>'; ?></td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td><?php echo $viewVal($profile['city'] ?? null); ?></td>
                            <th>State / province</th>
                            <td><?php echo $viewVal($profile['state'] ?? null); ?></td>
                        </tr>
                        <tr>
                            <th>Postal code</th>
                            <td><?php echo $viewVal($profile['postal_code'] ?? null); ?></td>
                            <th></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Street address</th>
                            <td colspan="3"><?php $addr = trim((string)($profile['address'] ?? '')); echo $addr !== '' ? nl2br(escape($addr)) : '<span class="text-muted fst-italic">Not provided</span>'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    $vcard = static function (string $title, string $icon, array $rows, bool $oa, string $instLabel, string $subjLabel) use ($viewVal, $qualRowHasContent): void {
        ?>
        <div class="card animate-in mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                    <h5 class="mb-0 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi <?php echo escape($icon); ?>"></i></span> <?php echo escape($title); ?></h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit"><i class="bi bi-pencil-square"></i></button>
                </div>
                <?php
                $validRows = [];
                foreach ($rows as $r) {
                    if ($qualRowHasContent($r)) {
                        $validRows[] = $r;
                    }
                }
                if (!$validRows) {
                    echo '<p class="text-muted small mb-0">None added yet.</p>';
                } else {
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo escape($instLabel); ?></th>
                                    <th><?php echo escape($subjLabel); ?></th>
                                    <th>Grade</th>
                                    <?php if ($oa): ?><th>Month</th><?php endif; ?>
                                    <th>Year</th>
                                    <?php if ($oa): ?><th>Examining board</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($validRows as $r): ?>
                                    <tr>
                                        <td><?php echo $viewVal($r['institution'] ?? null); ?></td>
                                        <td><?php echo $viewVal($r['title'] ?? null); ?></td>
                                        <td><?php echo $viewVal($r['grade'] ?? null); ?></td>
                                        <?php if ($oa): ?><td><?php echo $viewVal($r['month'] ?? null); ?></td><?php endif; ?>
                                        <td><?php echo $viewVal($r['year'] ?? null); ?></td>
                                        <?php if ($oa): ?><td><?php echo $viewVal($r['examining_board'] ?? null); ?></td><?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    };
    $vcard('Professional qualifications', 'bi-award', $pqView, false, 'Institution', 'Qualification');
    $vcard('Ordinary Level (O-Level)', 'bi-journal-bookmark', $olView, true, 'School', 'Subject');
    $vcard('Advanced Level (A-Level)', 'bi-journal-richtext', $alView, true, 'School / college', 'Subject');
    $vcard('Other certifications', 'bi-patch-check', $ocView, false, 'Institution / provider', 'Certificate / course');
    ?>
    <div class="card animate-in mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                <h5 class="mb-0 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-briefcase"></i></span> Work experience</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit"><i class="bi bi-pencil-square"></i></button>
            </div>
            <?php
            $wxRows = [];
            foreach ($wxView as $r) {
                if ($wxRowHasContent($r)) {
                    $wxRows[] = $r;
                }
            }
            if (!$wxRows) {
                echo '<p class="text-muted small mb-0">None added yet.</p>';
            } else {
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employer</th>
                                <th>Job title</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Current</th>
                                <th>Summary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wxRows as $r): ?>
                                <tr>
                                    <td><?php echo $viewVal($r['employer'] ?? null); ?></td>
                                    <td><?php echo $viewVal($r['job_title'] ?? null); ?></td>
                                    <td><?php echo $viewVal($r['start'] ?? null); ?></td>
                                    <td><?php echo $viewVal($r['end'] ?? null); ?></td>
                                    <td><?php echo !empty($r['current']) ? 'Yes' : 'No'; ?></td>
                                    <td><?php $dsc = trim((string)($r['description'] ?? '')); echo $dsc !== '' ? nl2br(escape($dsc)) : '<span class="text-muted fst-italic">Not provided</span>'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="card animate-in mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                <h5 class="mb-0 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--docs"><i class="bi bi-file-earmark-text"></i></span> Documents</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit"><i class="bi bi-pencil-square"></i></button>
            </div>
            <p class="text-muted small mb-2">Attached automatically when you apply.</p>
            <ul class="small text-muted mb-3 ps-3">
                <li><strong>CV:</strong> one PDF only.</li>
                <li><strong>Qualifications:</strong> one combined PDF of certified copies of certificates.</li>
                <li><strong>CV max:</strong> <?php echo escape((string)max(1, ceil((int)CV_MAX_FILE_SIZE / (1024 * 1024))) . ' MB'); ?>.</li>
                <li><strong>Qualifications max:</strong> <?php echo escape((string)max(1, ceil((int)CERTIFICATES_MAX_FILE_SIZE / (1024 * 1024))) . ' MB'); ?>.</li>
            </ul>
            <div class="profile-view-fields">
                <div class="profile-view-field"><div class="profile-view-label">CV</div><div class="profile-view-value"><?php if (!empty($profile['cv_path'])): ?><a href="<?php echo escape(candidateMyDocumentUrl('cv')); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-file-earmark-pdf me-1"></i>View CV</a><?php else: ?><span class="text-muted fst-italic">No file</span><?php endif; ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Qualifications PDF</div><div class="profile-view-value"><?php if (!empty($profile['certificates_path'])): ?><a href="<?php echo escape(candidateMyDocumentUrl('certs')); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-file-earmark-pdf me-1"></i>View</a><?php else: ?><span class="text-muted fst-italic">No file</span><?php endif; ?></div></div>
            </div>
        </div>
    </div>
    <div class="card animate-in mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                <h5 class="mb-0 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--refs"><i class="bi bi-person-lines-fill"></i></span> References</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit"><i class="bi bi-pencil-square"></i></button>
            </div>
            <?php if (!$references): ?>
                <p class="text-muted small mb-0">No references yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Full name</th>
                                <th>Job title / relationship</th>
                                <th>Organisation</th>
                                <th>Email</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($references as $ref): ?>
                                <tr>
                                    <td><?php echo $viewVal($ref['full_name'] ?? null); ?></td>
                                    <td><?php echo $viewVal($ref['job_title'] ?? null); ?></td>
                                    <td><?php echo $viewVal($ref['organisation'] ?? null); ?></td>
                                    <td><?php echo $viewVal($ref['email'] ?? null); ?></td>
                                    <td><?php echo $viewVal($ref['phone'] ?? null); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between mb-4">
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-search me-1"></i> Browse jobs</a>
        <button type="button" class="btn btn-primary jp-profile-card-edit"><i class="bi bi-pencil-square me-1"></i> Edit profile</button>
    </div>
</div>

<div id="profile-edit-panel" class="<?php echo $initialEditMode ? '' : 'd-none'; ?>">
<form method="post" enctype="multipart/form-data" id="profile-edit-form">
    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
    <?php if (!empty($hasProfilePhotoColumn)): ?>
        <div class="card animate-in mb-4">
            <div class="card-body">
                <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-camera"></i></span> Profile photo</h5>
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-3">
                        <?php if (!empty($profile['profile_photo_path'])): ?>
                            <img src="<?php echo escape(candidateProfilePhotoUrl((string)$profile['profile_photo_path']) ?? ''); ?>" alt="Current profile photo" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2px solid #eee;">
                        <?php else: ?>
                            <div class="text-muted small">No photo uploaded yet.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-md-9">
                        <label class="form-label">Upload profile photo</label>
                        <input type="file" name="profile_photo" id="profile-photo-file" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-bytes="<?php echo (int) MAX_FILE_SIZE; ?>">
                        <div class="form-text">Accepted: JPG, PNG, WEBP. Max <?php echo escape(maxUploadSizeLabel()); ?>.</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-person"></i></span> Personal details</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <tbody>
                        <tr>
                            <th style="width: 22%;">Date of Birth <span class="text-danger">*</span></th>
                            <td>
                                <input type="date" name="date_of_birth" class="form-control form-control-sm" value="<?php echo escape($profile['date_of_birth'] ?? ''); ?>" required title="Select your date of birth (saved as YYYY-MM-DD)">
                            </td>
                            <th style="width: 18%;">Gender <span class="text-danger">*</span></th>
                            <td>
                                <select name="gender" class="form-select form-select-sm" required title="Male, Female, or Other">
                                    <?php $curG = (string)($profile['gender'] ?? ''); ?>
                                    <option value=""<?php echo $curG === '' ? ' selected' : ''; ?>>Select…</option>
                                    <?php foreach (candidateGenderOptions() as $g): ?>
                                        <option value="<?php echo escape($g); ?>"<?php echo $curG === $g ? ' selected' : ''; ?>><?php echo escape($g); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Country <span class="text-danger">*</span></th>
                            <td>
                                <input type="text" name="country" class="form-control form-control-sm" value="<?php echo escape($profile['country'] ?? ''); ?>" required placeholder="e.g. Zimbabwe" title="Full country name">
                            </td>
                            <?php if (!empty($hasNationalIdColumn)): ?>
                                <th>National ID Number <span class="text-danger">*</span></th>
                                <td>
                                    <input type="text" name="national_id_number" class="form-control form-control-sm" value="<?php echo escape($profile['national_id_number'] ?? ''); ?>" maxlength="50" required placeholder="e.g. 12-3456789 A12" title="Enter your national identity number">
                                </td>
                            <?php else: ?>
                                <th></th>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <th>City <span class="text-danger">*</span></th>
                            <td>
                                <input type="text" name="city" class="form-control form-control-sm" value="<?php echo escape($profile['city'] ?? ''); ?>" required placeholder="e.g. Lupane, Bulawayo, Harare">
                            </td>
                            <th>State / Province <span class="text-danger">*</span></th>
                            <td>
                                <input type="text" name="state" class="form-control form-control-sm" value="<?php echo escape($profile['state'] ?? ''); ?>" required placeholder="e.g. Matabeleland North, Harare Province">
                            </td>
                        </tr>
                        <tr>
                            <th>Postal Code <span class="text-danger">*</span></th>
                            <td>
                                <input type="text" name="postal_code" class="form-control form-control-sm" value="<?php echo escape($profile['postal_code'] ?? ''); ?>" required placeholder="e.g. 00000 or P.O. Box 170">
                            </td>
                            <th></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Street Address <span class="text-danger">*</span></th>
                            <td colspan="3">
                                <textarea name="address" class="form-control form-control-sm" rows="2" required placeholder="e.g. 12 Main Street, CBD, near City Hall"><?php echo escape($profile['address'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <style>
                #profile-edit-panel table.table-sm th,
                #profile-edit-panel table.table-sm td {
                    padding: 0.45rem 0.55rem;
                }
                #profile-edit-panel table.table-sm th {
                    font-weight: 600;
                    white-space: nowrap;
                }
            </style>
        </div>
    </div>

    <?php
    $repeatTableCard = static function (
        string $title,
        string $icon,
        string $pfx,
        int $initial,
        array $slots,
        int $qmax,
        string $tplId,
        string $addLabel,
        bool $oaExtra,
        callable $rowRenderer,
        string $instLabel,
        string $titleLabel
    ): void {
        $copyFirstName = in_array($pfx, ['ol', 'al'], true) ? ($pfx . '_inst[]') : '';
        ?>
        <div class="card animate-in mb-4">
            <div class="card-body">
                <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi <?php echo escape($icon); ?>"></i></span> <?php echo escape($title); ?></h5>
                <p class="text-muted small mb-3"><?php echo escape($addLabel); ?> (up to <?php echo (int)$qmax; ?>).</p>
                <div class="jp-repeatable" data-repeat-max="<?php echo (int)$qmax; ?>"<?php echo $copyFirstName !== '' ? ' data-copy-first-name="' . escape($copyFirstName) . '"' : ''; ?>>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo escape($instLabel); ?></th>
                                    <th><?php echo escape($titleLabel); ?></th>
                                    <th>Grade</th>
                                    <?php if ($oaExtra): ?><th>Month</th><?php endif; ?>
                                    <th>Year</th>
                                    <?php if ($oaExtra): ?><th>Examining board</th><?php endif; ?>
                                    <th style="width: 1%;">Action</th>
                                </tr>
                            </thead>
                            <tbody class="jp-repeatable-rows">
                                <?php for ($i = 0; $i < $initial; $i++): ?>
                                    <?php $rowRenderer($pfx, $slots[$i], false, $oaExtra); ?>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm jp-repeat-add" data-template="<?php echo escape($tplId); ?>"><i class="bi bi-plus-lg me-1"></i> Add entry</button>
                </div>
                <template id="<?php echo escape($tplId); ?>">
                    <?php $rowRenderer($pfx, [], true, $oaExtra); ?>
                </template>
            </div>
        </div>
        <?php
    };

    $repeatCard = static function (
        string $title,
        string $icon,
        string $pfx,
        int $initial,
        array $slots,
        int $qmax,
        string $tplId,
        string $addLabel,
        bool $oaExtra,
        callable $rowFields
    ): void {
        ?>
        <div class="card animate-in mb-4">
            <div class="card-body">
                <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi <?php echo escape($icon); ?>"></i></span> <?php echo escape($title); ?></h5>
                <p class="text-muted small mb-3"><?php echo escape($addLabel); ?> (up to <?php echo (int)$qmax; ?>).</p>
                <div class="jp-repeatable" data-repeat-max="<?php echo (int)$qmax; ?>">
                    <div class="jp-repeatable-rows">
                        <?php for ($i = 0; $i < $initial; $i++): ?>
                            <div class="jp-repeat-row jp-ref-slot border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div class="fw-semibold text-muted small"><?php echo escape($title); ?> <span class="jp-repeat-num"><?php echo (int)($i + 1); ?></span></div>
                                    <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
                                </div>
                                <?php $rowFields($pfx, $slots[$i], false, $oaExtra); ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm jp-repeat-add" data-template="<?php echo escape($tplId); ?>"><i class="bi bi-plus-lg me-1"></i> Add entry</button>
                </div>
                <template id="<?php echo escape($tplId); ?>">
                    <div class="jp-repeat-row jp-ref-slot border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div class="fw-semibold text-muted small"><?php echo escape($title); ?> <span class="jp-repeat-num">1</span></div>
                            <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
                        </div>
                        <?php $rowFields($pfx, [], true, $oaExtra); ?>
                    </div>
                </template>
            </div>
        </div>
        <?php
    };
    $repeatTableCard('Professional qualifications', 'bi-award', 'pq', $pqInitial, $pqSlots, $qmax, 'tpl-repeat-pq', 'Use table rows to enter each qualification', false, $jpQualTableRow, 'Institution', 'Qualification');
    $repeatTableCard('Ordinary Level (O-Level)', 'bi-journal-bookmark', 'ol', $olInitial, $olSlots, $qmax, 'tpl-repeat-ol', 'Use table rows for O-Level entries', true, $jpQualTableRow, 'School', 'Subject');
    $repeatTableCard('Advanced Level (A-Level)', 'bi-journal-richtext', 'al', $alInitial, $alSlots, $qmax, 'tpl-repeat-al', 'Use table rows for A-Level entries', true, $jpQualTableRow, 'School / college', 'Subject');
    $repeatTableCard('Other certifications', 'bi-patch-check', 'oc', $ocInitial, $ocSlots, $qmax, 'tpl-repeat-oc', 'Use table rows for certifications', false, $jpQualTableRow, 'Institution / provider', 'Certificate / course');
    ?>

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-briefcase"></i></span> Work experience</h5>
            <div class="jp-repeatable" data-repeat-max="<?php echo (int)$wmax; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Employer</th>
                                <th>Job title</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Current</th>
                                <th>Summary</th>
                                <th style="width: 1%;">Action</th>
                            </tr>
                        </thead>
                        <tbody class="jp-repeatable-rows">
                            <?php for ($i = 0; $i < $wxInitial; $i++): $jpWorkTableRow($wxSlots[$i], false); endfor; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm jp-repeat-add" data-template="tpl-repeat-wx"><i class="bi bi-plus-lg me-1"></i> Add role</button>
            </div>
            <template id="tpl-repeat-wx">
                <?php $jpWorkTableRow([], true); ?>
            </template>
        </div>
    </div>

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--docs"><i class="bi bi-file-earmark-arrow-up"></i></span> Documents</h5>
            <p class="text-muted small">CV: PDF only (max <?php echo escape((string)max(1, ceil((int)CV_MAX_FILE_SIZE / (1024 * 1024))) . ' MB'); ?>). Qualifications: one combined PDF of certified copies (max <?php echo escape((string)max(1, ceil((int)CERTIFICATES_MAX_FILE_SIZE / (1024 * 1024))) . ' MB'); ?>).</p>
            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <label class="form-label">CV (Max <?php echo escape((string)max(1, ceil((int)CV_MAX_FILE_SIZE / (1024 * 1024))) . ' MB'); ?>) <span class="text-danger">*</span></label>
                    <input type="file" name="cv" id="profile-cv-file" class="form-control" accept=".pdf,application/pdf" data-max-bytes="<?php echo (int) CV_MAX_FILE_SIZE; ?>">
                    <div class="form-text"><?php if (!empty($profile['cv_path'])): ?>Current: <a href="<?php echo escape(candidateMyDocumentUrl('cv')); ?>" target="_blank" rel="noopener noreferrer">View</a><?php else: ?>Required<?php endif; ?></div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Qualifications PDF (Certified copies, max <?php echo escape((string)max(1, ceil((int)CERTIFICATES_MAX_FILE_SIZE / (1024 * 1024))) . ' MB'); ?>) <span class="text-danger">*</span></label>
                    <input type="file" name="certificates" id="profile-certificates-file" class="form-control" accept=".pdf,application/pdf" data-max-bytes="<?php echo (int) CERTIFICATES_MAX_FILE_SIZE; ?>">
                    <div class="form-text"><?php if (!empty($profile['certificates_path'])): ?>Current: <a href="<?php echo escape(candidateMyDocumentUrl('certs')); ?>" target="_blank" rel="noopener noreferrer">View</a><?php else: ?>Required<?php endif; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--refs"><i class="bi bi-person-lines-fill"></i></span> References</h5>
            <div class="jp-repeatable" data-repeat-max="<?php echo (int)$refMax; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Full name</th>
                                <th>Job title / relationship</th>
                                <th>Organisation</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th style="width: 1%;">Action</th>
                            </tr>
                        </thead>
                        <tbody class="jp-repeatable-rows">
                            <?php for ($i = 0; $i < $refInitial; $i++): $jpReferenceTableRow($refSlots[$i], false); endfor; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm jp-repeat-add" data-template="tpl-repeat-ref"><i class="bi bi-plus-lg me-1"></i> Add reference</button>
            </div>
            <template id="tpl-repeat-ref">
                <?php $jpReferenceTableRow([], true); ?>
            </template>
        </div>
    </div>

    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-between mb-4">
        <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-check-lg me-1"></i> Save profile</button>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <button type="button" class="btn btn-outline-secondary" id="btn-profile-discard"><i class="bi bi-x-lg me-1"></i> Cancel</button>
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-search me-1"></i> Browse jobs</a>
        </div>
    </div>
</form>
</div>

<script>
(function () {
    var viewPanel = document.getElementById('profile-view-panel');
    var editPanel = document.getElementById('profile-edit-panel');
    var btnEdit = document.getElementById('btn-profile-edit');
    var btnView = document.getElementById('btn-profile-view');
    var btnDiscard = document.getElementById('btn-profile-discard');
    var hint = document.getElementById('profile-mode-hint');
    var profileUrl = <?php echo json_encode(BASE_URL . '/candidate/profile.php'); ?>;
    if (!viewPanel || !editPanel || !btnEdit || !btnView) return;
    function enterEdit() {
        viewPanel.classList.add('d-none');
        editPanel.classList.remove('d-none');
        btnEdit.hidden = true;
        btnView.hidden = false;
        if (hint) hint.innerHTML = 'You are <strong>editing</strong> your profile. Save or use <strong>View mode</strong> / <strong>Cancel</strong>.';
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) { window.scrollTo(0, 0); }
    }
    function exitEdit() { window.location.href = profileUrl; }
    btnEdit.addEventListener('click', enterEdit);
    btnView.addEventListener('click', exitEdit);
    document.querySelectorAll('.jp-profile-card-edit').forEach(function (el) { el.addEventListener('click', enterEdit); });
    if (btnDiscard) btnDiscard.addEventListener('click', exitEdit);
    var maxBytes = <?php echo (int) MAX_FILE_SIZE; ?>;
    var maxLabel = <?php echo json_encode(maxUploadSizeLabel()); ?>;
    function formatBytesLabel(bytes) {
        var mb = Math.ceil((parseInt(bytes, 10) || 0) / (1024 * 1024));
        return String(Math.max(1, mb)) + ' MB';
    }
    function bindFileLimit(input) {
        if (!input) return;
        input.addEventListener('change', function () {
            var localMax = parseInt(input.getAttribute('data-max-bytes') || String(maxBytes), 10) || maxBytes;
            var f = input.files && input.files[0];
            if (!f) return;
            if (!/\.pdf$/i.test(f.name)) { alert('Only PDF files are accepted.'); input.value = ''; return; }
            if (f.size > localMax) { alert('File too large. Max ' + formatBytesLabel(localMax) + '.'); input.value = ''; }
        });
    }
    bindFileLimit(document.getElementById('profile-cv-file'));
    bindFileLimit(document.getElementById('profile-certificates-file'));
    function bindImageLimit(input) {
        if (!input || !maxBytes) return;
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f) return;
            if (!/\.(jpg|jpeg|png|webp)$/i.test(f.name)) { alert('Only JPG, PNG, or WEBP images are accepted.'); input.value = ''; return; }
            if (f.size > maxBytes) { alert('File too large. Max ' + maxLabel + '.'); input.value = ''; }
        });
    }
    bindImageLimit(document.getElementById('profile-photo-file'));
    function initProfileRepeatable(root) {
        var rowsWrap = root.querySelector('.jp-repeatable-rows');
        var addBtn = root.querySelector('.jp-repeat-add');
        if (!rowsWrap || !addBtn) return;
        var tplId = addBtn.getAttribute('data-template');
        var copyFirstName = root.getAttribute('data-copy-first-name') || '';
        var max = parseInt(root.getAttribute('data-repeat-max'), 10);
        if (!tplId || !max) return;
        function getRows() { return rowsWrap.querySelectorAll('.jp-repeat-row'); }
        function rowCount() { return getRows().length; }
        function getNamedInput(row, name) {
            if (!row || !name) return null;
            var safeName = String(name).replace(/"/g, '\\"');
            return row.querySelector('[name="' + safeName + '"]');
        }
        function getFirstInput() {
            var rows = getRows();
            if (!rows.length || !copyFirstName) return null;
            return getNamedInput(rows[0], copyFirstName);
        }
        function copyFirstValueIntoEmptyRows() {
            if (!copyFirstName) return;
            var firstInput = getFirstInput();
            if (!firstInput) return;
            var firstVal = (firstInput.value || '').trim();
            if (!firstVal) return;
            var rows = getRows();
            rows.forEach(function (row, idx) {
                if (idx === 0) return;
                var target = getNamedInput(row, copyFirstName);
                if (!target) return;
                if ((target.value || '').trim() === '') {
                    target.value = firstVal;
                }
            });
        }
        function bindFirstCopyListener() {
            if (!copyFirstName) return;
            var firstInput = getFirstInput();
            if (!firstInput || firstInput.dataset.copyBound === '1') return;
            firstInput.dataset.copyBound = '1';
            var handler = function () { copyFirstValueIntoEmptyRows(); };
            firstInput.addEventListener('input', handler);
            firstInput.addEventListener('change', handler);
        }
        function renumber() {
            getRows().forEach(function (row, idx) {
                var n = row.querySelector('.jp-repeat-num');
                if (n) n.textContent = String(idx + 1);
            });
        }
        function updateAddBtn() { addBtn.hidden = rowCount() >= max; }
        function bindWxCurrent(row) {
            var hid = row.querySelector('.jp-wx-current-hidden');
            var cb = row.querySelector('.jp-wx-current-cb');
            if (!hid || !cb) return;
            cb.checked = hid.value === '1';
            cb.addEventListener('change', function () { hid.value = cb.checked ? '1' : '0'; });
        }
        function bindRow(row) {
            var rm = row.querySelector('.jp-repeat-remove');
            if (rm) rm.addEventListener('click', function () {
                if (rowCount() <= 1) return;
                row.remove();
                renumber();
                updateAddBtn();
            });
            bindWxCurrent(row);
        }
        addBtn.addEventListener('click', function () {
            if (rowCount() >= max) return;
            var tplEl = document.getElementById(tplId);
            if (!tplEl || !tplEl.content || !tplEl.content.firstElementChild) return;
            var node = tplEl.content.firstElementChild.cloneNode(true);
            rowsWrap.appendChild(node);
            bindRow(node);
            renumber();
            updateAddBtn();
            copyFirstValueIntoEmptyRows();
            bindFirstCopyListener();
        });
        getRows().forEach(bindRow);
        renumber();
        updateAddBtn();
        copyFirstValueIntoEmptyRows();
        bindFirstCopyListener();
    }
    document.querySelectorAll('#profile-edit-form .jp-repeatable').forEach(initProfileRepeatable);
})();
</script>
<?php require_once BASE_PATH . '/includes/footer.php'; ?>
