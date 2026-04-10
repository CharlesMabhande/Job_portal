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
            <div class="profile-view-fields">
                <div class="profile-view-field"><div class="profile-view-label">Full name</div><div class="profile-view-value"><?php echo $viewVal(trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))); ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Email</div><div class="profile-view-value"><?php echo $viewVal($profile['email'] ?? null); ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Phone</div><div class="profile-view-value"><?php echo $viewVal($profile['phone'] ?? null); ?></div></div>
            </div>
        </div>
    </div>
    <div class="card animate-in mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                <h5 class="mb-0 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-person"></i></span> Personal details</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary jp-profile-card-edit"><i class="bi bi-pencil-square"></i></button>
            </div>
            <div class="profile-view-fields">
                <div class="profile-view-field"><div class="profile-view-label">Date of birth</div><div class="profile-view-value"><?php
                    $dob = $profile['date_of_birth'] ?? '';
                    if ($dob !== '') {
                        echo escape(formatDateDisplay($dob));
                        if (isset($ageYears) && $ageYears !== null) {
                            echo ' <span class="text-muted">(age ' . (int)$ageYears . ')</span>';
                        }
                    } else {
                        echo '<span class="text-muted fst-italic">Not provided</span>';
                    }
                ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Gender</div><div class="profile-view-value"><?php echo $viewVal($profile['gender'] ?? null); ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Country</div><div class="profile-view-value"><?php echo $viewVal($profile['country'] ?? null); ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Street address</div><div class="profile-view-value"><?php $addr = trim((string)($profile['address'] ?? '')); echo $addr !== '' ? nl2br(escape($addr)) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">City</div><div class="profile-view-value"><?php echo $viewVal($profile['city'] ?? null); ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">State / province</div><div class="profile-view-value"><?php echo $viewVal($profile['state'] ?? null); ?></div></div>
                <div class="profile-view-field"><div class="profile-view-label">Postal code</div><div class="profile-view-value"><?php echo $viewVal($profile['postal_code'] ?? null); ?></div></div>
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
                $any = false;
                foreach ($rows as $r) {
                    if (!$qualRowHasContent($r)) {
                        continue;
                    }
                    $any = true;
                    ?>
                    <div class="jp-ref-view-block mb-3">
                        <div class="profile-view-fields">
                            <div class="profile-view-field"><div class="profile-view-label"><?php echo escape($instLabel); ?></div><div class="profile-view-value"><?php echo $viewVal($r['institution'] ?? null); ?></div></div>
                            <div class="profile-view-field"><div class="profile-view-label"><?php echo escape($subjLabel); ?></div><div class="profile-view-value"><?php echo $viewVal($r['title'] ?? null); ?></div></div>
                            <div class="profile-view-field"><div class="profile-view-label">Grade</div><div class="profile-view-value"><?php echo $viewVal($r['grade'] ?? null); ?></div></div>
                            <?php if ($oa): ?>
                                <div class="profile-view-field"><div class="profile-view-label">Month</div><div class="profile-view-value"><?php echo $viewVal($r['month'] ?? null); ?></div></div>
                            <?php endif; ?>
                            <div class="profile-view-field"><div class="profile-view-label">Year</div><div class="profile-view-value"><?php echo $viewVal($r['year'] ?? null); ?></div></div>
                            <?php if ($oa): ?>
                                <div class="profile-view-field"><div class="profile-view-label">Examining board</div><div class="profile-view-value"><?php echo $viewVal($r['examining_board'] ?? null); ?></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
                if (!$any) {
                    echo '<p class="text-muted small mb-0">None added yet.</p>';
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
            $wxAny = false;
            foreach ($wxView as $r) {
                if (!$wxRowHasContent($r)) {
                    continue;
                }
                $wxAny = true;
                $cur = !empty($r['current']);
                ?>
                <div class="jp-ref-view-block mb-3">
                    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-2">
                        <div class="fw-semibold"><?php echo $viewVal($r['employer'] ?? null); ?></div>
                        <?php if ($cur): ?><span class="badge bg-primary">Current</span><?php endif; ?>
                    </div>
                    <div class="small text-muted mb-2"><?php echo $viewVal($r['job_title'] ?? null); ?></div>
                    <div class="small mb-2"><?php
                        $sd = trim((string)($r['start'] ?? ''));
                        $ed = trim((string)($r['end'] ?? ''));
                        if ($sd !== '' || $ed !== '' || $cur) {
                            echo escape($sd !== '' ? $sd : '?') . ' — ';
                            echo $cur ? '<span class="text-muted">Present</span>' : escape($ed !== '' ? $ed : '?');
                        } else {
                            echo '<span class="text-muted fst-italic">Dates not provided</span>';
                        }
                    ?></div>
                    <?php $dsc = trim((string)($r['description'] ?? '')); ?>
                    <?php if ($dsc !== ''): ?><div class="small mt-2"><?php echo nl2br(escape($dsc)); ?></div><?php endif; ?>
                </div>
                <?php
            }
            if (!$wxAny) {
                echo '<p class="text-muted small mb-0">None added yet.</p>';
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
                <li><strong>Qualifications:</strong> one combined PDF.</li>
                <li><strong>Max <?php echo escape(maxUploadSizeLabel()); ?></strong> each.</li>
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
                <div class="jp-ref-view-list">
                    <?php foreach ($references as $ridx => $ref): ?>
                        <div class="jp-ref-view-block">
                            <div class="fw-semibold"><?php echo escape($ref['full_name']); ?></div>
                            <?php if (trim((string)($ref['job_title'] ?? '')) !== ''): ?><div class="small text-muted mt-1"><?php echo escape($ref['job_title']); ?></div><?php endif; ?>
                            <?php if (trim((string)($ref['organisation'] ?? '')) !== ''): ?><div class="small mt-1"><?php echo escape($ref['organisation']); ?></div><?php endif; ?>
                            <div class="small mt-2 d-flex flex-wrap gap-3">
                                <?php if (trim((string)($ref['email'] ?? '')) !== ''): ?><span><i class="bi bi-envelope me-1 text-muted"></i><a href="mailto:<?php echo escape($ref['email']); ?>"><?php echo escape($ref['email']); ?></a></span><?php endif; ?>
                                <?php if (trim((string)($ref['phone'] ?? '')) !== ''): ?><span><i class="bi bi-telephone me-1 text-muted"></i><?php echo escape($ref['phone']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-person"></i></span> Personal details</h5>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo escape($profile['date_of_birth'] ?? ''); ?>" required title="Select your date of birth (saved as YYYY-MM-DD)">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required title="Male, Female, or Other">
                        <?php $curG = (string)($profile['gender'] ?? ''); ?>
                        <option value=""<?php echo $curG === '' ? ' selected' : ''; ?>>Select…</option>
                        <?php foreach (candidateGenderOptions() as $g): ?>
                            <option value="<?php echo escape($g); ?>"<?php echo $curG === $g ? ' selected' : ''; ?>><?php echo escape($g); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Country <span class="text-danger">*</span></label>
                    <input type="text" name="country" class="form-control" value="<?php echo escape($profile['country'] ?? ''); ?>" required placeholder="e.g. Zimbabwe" title="Full country name">
                </div>
                <div class="col-12">
                    <label class="form-label">Street Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required placeholder="e.g. 12 Main Street, CBD, near City Hall"><?php echo escape($profile['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control" value="<?php echo escape($profile['city'] ?? ''); ?>" required placeholder="e.g. Lupane, Bulawayo, Harare">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">State / Province <span class="text-danger">*</span></label>
                    <input type="text" name="state" class="form-control" value="<?php echo escape($profile['state'] ?? ''); ?>" required placeholder="e.g. Matabeleland North, Harare Province">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Postal Code <span class="text-danger">*</span></label>
                    <input type="text" name="postal_code" class="form-control" value="<?php echo escape($profile['postal_code'] ?? ''); ?>" required placeholder="e.g. 00000 or P.O. Box 170">
                </div>
            </div>
        </div>
    </div>

    <?php
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
    $repeatCard('Professional qualifications', 'bi-award', 'pq', $pqInitial, $pqSlots, $qmax, 'tpl-repeat-pq', 'Click <strong>Add entry</strong> for another qualification', false, $jpQualRowFields);
    $repeatCard('Ordinary Level (O-Level)', 'bi-journal-bookmark', 'ol', $olInitial, $olSlots, $qmax, 'tpl-repeat-ol', 'Add <strong>Month</strong> and <strong>Examining board</strong> if applicable', true, $jpQualRowFields);
    $repeatCard('Advanced Level (A-Level)', 'bi-journal-richtext', 'al', $alInitial, $alSlots, $qmax, 'tpl-repeat-al', 'Add <strong>Month</strong> and <strong>Examining board</strong> if applicable', true, $jpQualRowFields);
    $repeatCard('Other certifications', 'bi-patch-check', 'oc', $ocInitial, $ocSlots, $qmax, 'tpl-repeat-oc', 'Click <strong>Add entry</strong>', false, $jpQualRowFields);
    ?>

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--primary"><i class="bi bi-briefcase"></i></span> Work experience</h5>
            <div class="jp-repeatable" data-repeat-max="<?php echo (int)$wmax; ?>">
                <div class="jp-repeatable-rows">
                    <?php for ($i = 0; $i < $wxInitial; $i++): $wx = $wxSlots[$i]; ?>
                        <div class="jp-repeat-row jp-ref-slot border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="fw-semibold text-muted small">Role <span class="jp-repeat-num"><?php echo (int)($i + 1); ?></span></div>
                                <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-md-6"><label class="form-label">Employer</label><input type="text" name="wx_employer[]" class="form-control" value="<?php echo escape($wx['employer']); ?>" maxlength="255" placeholder="e.g. Lupane State University"></div>
                                <div class="col-12 col-md-6"><label class="form-label">Job title</label><input type="text" name="wx_job_title[]" class="form-control" value="<?php echo escape($wx['job_title']); ?>" maxlength="255" placeholder="e.g. Administrative Assistant"></div>
                                <div class="col-12 col-md-4"><label class="form-label">Start</label><input type="text" name="wx_start[]" class="form-control" value="<?php echo escape($wx['start']); ?>" maxlength="40" placeholder="e.g. Jan 2020 or 2020"></div>
                                <div class="col-12 col-md-4"><label class="form-label">End</label><input type="text" name="wx_end[]" class="form-control" value="<?php echo escape($wx['end']); ?>" maxlength="40" placeholder="Leave blank if current"></div>
                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <input type="hidden" name="wx_current[]" value="<?php echo !empty($wx['current']) ? '1' : '0'; ?>" class="jp-wx-current-hidden" autocomplete="off">
                                    <div class="form-check mb-2"><label class="form-check-label d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input jp-wx-current-cb"<?php echo !empty($wx['current']) ? ' checked' : ''; ?>> Current role</label></div>
                                </div>
                                <div class="col-12"><label class="form-label">Summary</label><textarea name="wx_description[]" class="form-control" rows="2" maxlength="2000" placeholder="Main duties, achievements (optional)"><?php echo escape($wx['description']); ?></textarea></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm jp-repeat-add" data-template="tpl-repeat-wx"><i class="bi bi-plus-lg me-1"></i> Add role</button>
            </div>
            <template id="tpl-repeat-wx">
                <div class="jp-repeat-row jp-ref-slot border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="fw-semibold text-muted small">Role <span class="jp-repeat-num">1</span></div>
                        <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><label class="form-label">Employer</label><input type="text" name="wx_employer[]" class="form-control" maxlength="255" placeholder="e.g. Lupane State University"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Job title</label><input type="text" name="wx_job_title[]" class="form-control" maxlength="255" placeholder="e.g. Administrative Assistant"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Start</label><input type="text" name="wx_start[]" class="form-control" maxlength="40" placeholder="e.g. Jan 2020 or 2020"></div>
                        <div class="col-12 col-md-4"><label class="form-label">End</label><input type="text" name="wx_end[]" class="form-control" maxlength="40" placeholder="Leave blank if current"></div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <input type="hidden" name="wx_current[]" value="0" class="jp-wx-current-hidden" autocomplete="off">
                            <div class="form-check mb-2"><label class="form-check-label d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input jp-wx-current-cb"> Current role</label></div>
                        </div>
                        <div class="col-12"><label class="form-label">Summary</label><textarea name="wx_description[]" class="form-control" rows="2" maxlength="2000" placeholder="Main duties, achievements (optional)"></textarea></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--docs"><i class="bi bi-file-earmark-arrow-up"></i></span> Documents</h5>
            <p class="text-muted small">PDF only, max <?php echo escape(maxUploadSizeLabel()); ?> each.</p>
            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <label class="form-label">CV <span class="text-danger">*</span></label>
                    <input type="file" name="cv" id="profile-cv-file" class="form-control" accept=".pdf,application/pdf" data-max-bytes="<?php echo (int) MAX_FILE_SIZE; ?>">
                    <div class="form-text"><?php if (!empty($profile['cv_path'])): ?>Current: <a href="<?php echo escape(candidateMyDocumentUrl('cv')); ?>" target="_blank" rel="noopener noreferrer">View</a><?php else: ?>Required<?php endif; ?></div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Qualifications PDF <span class="text-danger">*</span></label>
                    <input type="file" name="certificates" id="profile-certificates-file" class="form-control" accept=".pdf,application/pdf" data-max-bytes="<?php echo (int) MAX_FILE_SIZE; ?>">
                    <div class="form-text"><?php if (!empty($profile['certificates_path'])): ?>Current: <a href="<?php echo escape(candidateMyDocumentUrl('certs')); ?>" target="_blank" rel="noopener noreferrer">View</a><?php else: ?>Required<?php endif; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card animate-in mb-4">
        <div class="card-body">
            <h5 class="mb-3 d-flex align-items-center gap-2"><span class="jp-profile-section-icon jp-profile-section-icon--refs"><i class="bi bi-person-lines-fill"></i></span> References</h5>
            <div class="jp-repeatable" data-repeat-max="<?php echo (int)$refMax; ?>">
                <div class="jp-repeatable-rows">
                    <?php for ($i = 0; $i < $refInitial; $i++): $rs = $refSlots[$i]; ?>
                        <div class="jp-repeat-row jp-ref-slot border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="fw-semibold text-muted small">Reference <span class="jp-repeat-num"><?php echo (int)($i + 1); ?></span></div>
                                <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-md-6"><label class="form-label">Full name</label><input type="text" name="ref_name[]" class="form-control" value="<?php echo escape($rs['full_name']); ?>" maxlength="200" placeholder="e.g. Dr Jane Moyo"></div>
                                <div class="col-12 col-md-6"><label class="form-label">Job title / relationship</label><input type="text" name="ref_title[]" class="form-control" value="<?php echo escape($rs['job_title']); ?>" maxlength="200" placeholder="e.g. Former line manager, Lecturer"></div>
                                <div class="col-12"><label class="form-label">Organisation</label><input type="text" name="ref_org[]" class="form-control" value="<?php echo escape($rs['organisation']); ?>" maxlength="255" placeholder="e.g. Lupane State University"></div>
                                <div class="col-12 col-md-6"><label class="form-label">Email</label><input type="email" name="ref_email[]" class="form-control" value="<?php echo escape($rs['email']); ?>" maxlength="255" placeholder="name@example.com"></div>
                                <div class="col-12 col-md-6"><label class="form-label">Phone</label><input type="text" name="ref_phone[]" class="form-control" value="<?php echo escape($rs['phone']); ?>" maxlength="50" placeholder="e.g. +263 77 000 0000"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm jp-repeat-add" data-template="tpl-repeat-ref"><i class="bi bi-plus-lg me-1"></i> Add reference</button>
            </div>
            <template id="tpl-repeat-ref">
                <div class="jp-repeat-row jp-ref-slot border rounded-3 p-3 mb-3 bg-body-secondary bg-opacity-25">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="fw-semibold text-muted small">Reference <span class="jp-repeat-num">1</span></div>
                        <button type="button" class="btn btn-sm btn-outline-danger jp-repeat-remove"><i class="bi bi-trash"></i> Remove</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><label class="form-label">Full name</label><input type="text" name="ref_name[]" class="form-control" maxlength="200" placeholder="e.g. Dr Jane Moyo"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Job title / relationship</label><input type="text" name="ref_title[]" class="form-control" maxlength="200" placeholder="e.g. Former line manager, Lecturer"></div>
                        <div class="col-12"><label class="form-label">Organisation</label><input type="text" name="ref_org[]" class="form-control" maxlength="255" placeholder="e.g. Lupane State University"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Email</label><input type="email" name="ref_email[]" class="form-control" maxlength="255" placeholder="name@example.com"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Phone</label><input type="text" name="ref_phone[]" class="form-control" maxlength="50" placeholder="e.g. +263 77 000 0000"></div>
                    </div>
                </div>
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
    function bindFileLimit(input) {
        if (!input || !maxBytes) return;
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f) return;
            if (!/\.pdf$/i.test(f.name)) { alert('Only PDF files are accepted.'); input.value = ''; return; }
            if (f.size > maxBytes) { alert('File too large. Max ' + maxLabel + '.'); input.value = ''; }
        });
    }
    bindFileLimit(document.getElementById('profile-cv-file'));
    bindFileLimit(document.getElementById('profile-certificates-file'));
    function initProfileRepeatable(root) {
        var rowsWrap = root.querySelector('.jp-repeatable-rows');
        var addBtn = root.querySelector('.jp-repeat-add');
        if (!rowsWrap || !addBtn) return;
        var tplId = addBtn.getAttribute('data-template');
        var max = parseInt(root.getAttribute('data-repeat-max'), 10);
        if (!tplId || !max) return;
        function getRows() { return rowsWrap.querySelectorAll('.jp-repeat-row'); }
        function rowCount() { return getRows().length; }
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
        });
        getRows().forEach(bindRow);
        renumber();
        updateAddBtn();
    }
    document.querySelectorAll('#profile-edit-form .jp-repeatable').forEach(initProfileRepeatable);
})();
</script>
<?php require_once BASE_PATH . '/includes/footer.php'; ?>
