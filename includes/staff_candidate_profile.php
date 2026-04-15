<?php
/**
 * Read-only candidate profile presentation for HR / Management / SysAdmin.
 */

function staffCandidateDisplayName(array $profile): string {
    $name = trim((string)($profile['first_name'] ?? '') . ' ' . (string)($profile['last_name'] ?? ''));

    return $name !== '' ? $name : 'Candidate';
}

/**
 * @return list<array<string,mixed>>
 */
function staffCandidateQualificationRows(?string $json): array {
    $list = decodeCandidateProfileJsonList($json);
    $out = [];
    foreach ($list as $r) {
        if (!is_array($r)) {
            continue;
        }
        $has = false;
        foreach (['institution', 'title', 'grade', 'year', 'month', 'examining_board'] as $k) {
            if (trim((string)($r[$k] ?? '')) !== '') {
                $has = true;
                break;
            }
        }
        if ($has) {
            $out[] = $r;
        }
    }

    return $out;
}

/**
 * @return list<array<string,mixed>>
 */
function staffCandidateWorkRows(?string $json): array {
    $list = decodeCandidateProfileJsonList($json);
    $out = [];
    foreach ($list as $r) {
        if (!is_array($r)) {
            continue;
        }
        if (!empty($r['current'])) {
            $out[] = $r;
            continue;
        }
        $has = false;
        foreach (['employer', 'job_title', 'start', 'end', 'description'] as $k) {
            if (trim((string)($r[$k] ?? '')) !== '') {
                $has = true;
                break;
            }
        }
        if ($has) {
            $out[] = $r;
        }
    }

    return $out;
}

/**
 * @param list<array<string,mixed>> $references
 */
function staffBuildCandidateProfileHtml(array $profile, array $references, string $mode = 'screen'): string {
    $isPdf = ($mode === 'pdf');
    $isPrint = ($mode === 'print');
    $usePlainTable = ($isPdf || $isPrint);
    $wrapStyle = $isPdf
        ? 'font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222;'
        : 'font-family: system-ui, sans-serif; max-width: 800px; margin: 0 auto; padding: 1rem; color: #222;';
    $h1Style = $usePlainTable ? 'font-size: 16pt; margin: 0 0 8pt 0;' : 'font-size: 1.5rem; margin: 0 0 0.5rem 0;';
    $h2Style = $usePlainTable ? 'font-size: 12pt; margin: 16pt 0 8pt 0; border-bottom: 1pt solid #c61f26; padding-bottom: 4pt;' : 'font-size: 1.15rem; margin: 1.25rem 0 0.5rem 0; border-bottom: 2px solid #c61f26; padding-bottom: 0.25rem;';
    $tblOpen = $usePlainTable
        ? '<table width="100%" border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; margin-bottom:10pt;">'
        : '<table class="table table-bordered table-sm" style="max-width:100%;">';

    $dob = !empty($profile['date_of_birth']) ? formatDateDisplay($profile['date_of_birth']) : '—';
    $ageYears = ageFromDateOfBirth($profile['date_of_birth'] ?? null);
    $ageLabel = $ageYears !== null ? (string)(int)$ageYears : '—';
    $cvOk = !empty($profile['cv_path']);
    $certOk = !empty($profile['certificates_path']);

    $row = static function (string $label, string $valueHtml) use ($usePlainTable): string {
        $thBg = $usePlainTable ? 'background:#f5f5f5;' : '';

        return '<tr><th style="width:32%;text-align:left;' . $thBg . '">' . escape($label) . '</th><td>' . $valueHtml . '</td></tr>';
    };

    $addr = trim((string)($profile['address'] ?? ''));
    $addrHtml = $addr !== '' ? nl2br(escape($addr)) : '—';

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    $html .= '<title>' . escape(staffCandidateDisplayName($profile)) . ' — Profile</title>';
    if (!$isPdf) {
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
    }
    $html .= '</head><body style="' . $wrapStyle . '">';
    $html .= '<h1 style="' . $h1Style . '">' . escape(staffCandidateDisplayName($profile)) . '</h1>';
    $html .= '<p style="margin:0 0 12pt 0; color:#555; font-size:' . ($usePlainTable ? '9pt' : '0.9rem') . ';">';
    $html .= 'Candidate ID ' . (int)($profile['candidate_id'] ?? 0) . ' · Exported ' . escape(formatDateTimeDisplay(date('Y-m-d H:i:s')));
    $html .= '</p>';

    $html .= '<h2 style="' . $h2Style . '">Account</h2>' . $tblOpen;
    $html .= $row('Full name', escape(staffCandidateDisplayName($profile)));
    $html .= $row('Email', escape(trim((string)($profile['email'] ?? '')) !== '' ? (string)$profile['email'] : '—'));
    $html .= $row('Phone', escape(trim((string)($profile['phone'] ?? '')) !== '' ? (string)$profile['phone'] : '—'));
    $html .= '</table>';

    $html .= '<h2 style="' . $h2Style . '">Personal details</h2>' . $tblOpen;
    $html .= $row('Date of birth', escape($dob));
    $html .= $row('Age (from date of birth)', escape($ageLabel));
    $gLabel = trim((string)($profile['gender'] ?? ''));
    $html .= $row('Gender', escape($gLabel !== '' ? $gLabel : '—'));
    $html .= $row('National ID Number', escape(trim((string)($profile['national_id_number'] ?? '')) !== '' ? (string)$profile['national_id_number'] : '—'));
    $html .= $row('Country', escape(trim((string)($profile['country'] ?? '')) !== '' ? (string)$profile['country'] : '—'));
    $html .= $row('Street address', $addrHtml);
    $html .= $row('City', escape(trim((string)($profile['city'] ?? '')) !== '' ? (string)$profile['city'] : '—'));
    $html .= $row('State / province', escape(trim((string)($profile['state'] ?? '')) !== '' ? (string)$profile['state'] : '—'));
    $html .= $row('Postal code', escape(trim((string)($profile['postal_code'] ?? '')) !== '' ? (string)$profile['postal_code'] : '—'));
    $html .= '</table>';

    $qualSection = static function (string $title, array $rows, array $headers, array $fieldKeys) use ($h2Style, $tblOpen): string {
        if ($rows === []) {
            return '';
        }
        $h = '<h2 style="' . $h2Style . '">' . escape($title) . '</h2>' . $tblOpen;
        $h .= '<thead><tr style="background:#f5f5f5;">';
        foreach ($headers as $hd) {
            $h .= '<th>' . escape($hd) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $h .= '<tr>';
            foreach ($fieldKeys as $k) {
                $v = trim((string)($r[$k] ?? ''));
                $h .= '<td>' . ($v !== '' ? escape($v) : '—') . '</td>';
            }
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';

        return $h;
    };

    $html .= $qualSection('Professional qualifications', staffCandidateQualificationRows($profile['professional_qualifications'] ?? null), ['Institution', 'Qualification', 'Grade / result', 'Year'], ['institution', 'title', 'grade', 'year']);
    $html .= $qualSection('Ordinary Level (O-Level)', staffCandidateQualificationRows($profile['o_level_qualifications'] ?? null), ['School', 'Subject', 'Grade', 'Month', 'Year', 'Examining board'], ['institution', 'title', 'grade', 'month', 'year', 'examining_board']);
    $html .= $qualSection('Advanced Level (A-Level)', staffCandidateQualificationRows($profile['a_level_qualifications'] ?? null), ['School / college', 'Subject', 'Grade', 'Month', 'Year', 'Examining board'], ['institution', 'title', 'grade', 'month', 'year', 'examining_board']);
    $html .= $qualSection('Other certifications', staffCandidateQualificationRows($profile['other_certifications'] ?? null), ['Institution / provider', 'Certificate / course', 'Grade / result', 'Year'], ['institution', 'title', 'grade', 'year']);

    $wxRows = staffCandidateWorkRows($profile['experience'] ?? null);
    $html .= '<h2 style="' . $h2Style . '">Work experience</h2>';
    if ($wxRows === []) {
        $html .= '<p style="color:#666;">No work experience listed.</p>';
    } else {
        $html .= $tblOpen;
        $html .= '<thead><tr style="background:#f5f5f5;"><th>Employer</th><th>Job title</th><th>Start</th><th>End</th><th>Current</th><th>Summary</th></tr></thead><tbody>';
        foreach ($wxRows as $wx) {
            $cur = !empty($wx['current']) ? 'Yes' : 'No';
            $endDisp = !empty($wx['current']) ? '—' : (trim((string)($wx['end'] ?? '')) !== '' ? (string)$wx['end'] : '—');
            $sum = trim((string)($wx['description'] ?? ''));
            $sumHtml = $sum !== '' ? nl2br(escape($sum)) : '—';
            $html .= '<tr>';
            $html .= '<td>' . escape(trim((string)($wx['employer'] ?? '')) !== '' ? (string)$wx['employer'] : '—') . '</td>';
            $html .= '<td>' . escape(trim((string)($wx['job_title'] ?? '')) !== '' ? (string)$wx['job_title'] : '—') . '</td>';
            $html .= '<td>' . escape(trim((string)($wx['start'] ?? '')) !== '' ? (string)$wx['start'] : '—') . '</td>';
            $html .= '<td>' . escape($endDisp) . '</td>';
            $html .= '<td>' . escape($cur) . '</td>';
            $html .= '<td style="' . ($usePlainTable ? 'max-width:200pt;word-wrap:break-word;' : '') . '">' . $sumHtml . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '<h2 style="' . $h2Style . '">Documents (on file)</h2>' . $tblOpen;
    $html .= $row('CV', $cvOk ? '<strong>Yes</strong> — open the job portal to view or download the file.' : '—');
    $html .= $row('Qualifications / certificates', $certOk ? '<strong>Yes</strong> — open the job portal to view or download the file.' : '—');
    $html .= '</table>';

    $html .= '<h2 style="' . $h2Style . '">References</h2>';
    if (!$references) {
        $html .= '<p style="color:#666;">No references listed.</p>';
    } else {
        $html .= $tblOpen;
        $html .= '<thead><tr style="background:#f5f5f5;"><th>#</th><th>Name</th><th>Title / relationship</th><th>Organisation</th><th>Email</th><th>Phone</th></tr></thead><tbody>';
        foreach ($references as $i => $ref) {
            $html .= '<tr><td>' . ((int)$i + 1) . '</td>';
            $html .= '<td>' . escape((string)($ref['full_name'] ?? '')) . '</td>';
            $html .= '<td>' . escape((string)($ref['job_title'] ?? '—')) . '</td>';
            $html .= '<td>' . escape((string)($ref['organisation'] ?? '—')) . '</td>';
            $html .= '<td>' . escape((string)($ref['email'] ?? '—')) . '</td>';
            $html .= '<td>' . escape((string)($ref['phone'] ?? '—')) . '</td></tr>';
        }
        $html .= '</tbody></table>';
    }

    if ($mode === 'print') {
        $html .= '<p style="margin-top:1.5rem; padding:0.75rem; background:#fff3cd; border:1px solid #ffc107; border-radius:6px; font-size:0.9rem;">';
        $html .= '<strong>Save as PDF:</strong> use your browser’s <strong>Print</strong> dialog and choose <strong>Save as PDF</strong>.';
        $html .= '</p><p><button type="button" onclick="window.print()" style="padding:0.5rem 1rem; cursor:pointer;">Print / Save as PDF</button></p>';
    }

    $html .= '</body></html>';

    return $html;
}

/**
 * Stream a PDF download (attachment) or inline view.
 *
 * @param list<array<string,mixed>> $references
 */
function staffStreamCandidateProfilePdf(array $profile, array $references, bool $attachment = true): void {
    $autoload = BASE_PATH . '/vendor/autoload.php';
    if (!is_readable($autoload)) {
        throw new RuntimeException('PDF export requires Composer packages. From the project folder run: composer install');
    }
    require_once $autoload;
    if (!class_exists(\Dompdf\Dompdf::class)) {
        throw new RuntimeException('Dompdf is not installed. Run composer install in the project root.');
    }

    $html = staffBuildCandidateProfileHtml($profile, $references, 'pdf');
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', staffCandidateDisplayName($profile));
    if ($safe === '' || $safe === '_') {
        $safe = 'candidate';
    }
    $filename = $safe . '_profile_' . (int)($profile['candidate_id'] ?? 0) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => $attachment]);
}
