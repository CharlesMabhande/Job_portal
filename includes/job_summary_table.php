<?php
/**
 * HR / Management: printable “Summary table for the post of …” (HTML export).
 */

if (!defined('BASE_PATH')) {
    exit;
}

require_once BASE_PATH . '/includes/staff_candidate_profile.php';

/**
 * Vacancy line for the document title (e.g. "1 POST" / "4 POSTS").
 */
function jobSummaryTablePostsLabel(array $job): string {
    $max = (int)($job['max_applications'] ?? 0);
    if ($max > 0) {
        return $max === 1 ? '1 POST' : "{$max} POSTS";
    }

    return '1 POST';
}

function jobSummaryTableSalutation(?string $gender): string {
    $g = trim((string)$gender);
    if ($g === 'Male') {
        return 'Mr';
    }
    if ($g === 'Female') {
        return 'Ms';
    }
    if ($g === 'Other') {
        return 'Mx';
    }

    return '';
}

function jobSummaryTableGenderCode(?string $gender): string {
    $g = trim((string)$gender);
    if ($g === 'Male') {
        return 'M';
    }
    if ($g === 'Female') {
        return 'F';
    }
    if ($g === 'Other') {
        return 'O';
    }

    return '—';
}

/**
 * One qualification row as prose: Title (grade), Institution (Year …).
 */
function jobSummaryTableQualificationLine(array $r, bool $oa): string {
    $title = trim((string)($r['title'] ?? ''));
    $grade = trim((string)($r['grade'] ?? ''));
    $inst = trim((string)($r['institution'] ?? ''));
    $year = trim((string)($r['year'] ?? ''));
    $month = trim((string)($r['month'] ?? ''));
    $board = trim((string)($r['examining_board'] ?? ''));

    $head = $title;
    if ($grade !== '') {
        $head .= ($head !== '' ? ' (' : '') . $grade . ($head !== '' ? ')' : '');
    }
    $mid = $head;
    if ($inst !== '') {
        $mid .= ($mid !== '' ? ', ' : '') . $inst;
    }
    $when = $year;
    if ($oa && $month !== '' && $year !== '') {
        $when = $month . ' ' . $year;
    } elseif ($oa && $month !== '' && $year === '') {
        $when = $month;
    }
    $tail = '';
    if ($when !== '') {
        $tail = '(' . $when . ')';
    }
    if ($oa && $board !== '') {
        $tail .= ($tail !== '' ? ' · ' : '') . $board;
    }
    $line = $mid;
    if ($tail !== '') {
        $line .= ($line !== '' ? ' ' : '') . $tail;
    }

    return trim($line);
}

function jobSummaryTableQualificationsBlock(array $appRow): string {
    $sections = [
        ['Professional qualifications', $appRow['professional_qualifications'] ?? null, false],
        ['Ordinary Level (O-Level)', $appRow['o_level_qualifications'] ?? null, true],
        ['Advanced Level (A-Level)', $appRow['a_level_qualifications'] ?? null, true],
        ['Other certifications', $appRow['other_certifications'] ?? null, false],
    ];
    $blocks = [];
    foreach ($sections as $sec) {
        [$label, $json, $oa] = $sec;
        $rows = staffCandidateQualificationRows($json);
        if ($rows === []) {
            continue;
        }
        $lines = [];
        foreach ($rows as $r) {
            $line = jobSummaryTableQualificationLine($r, $oa);
            if ($line !== '') {
                $lines[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            }
        }
        if ($lines !== []) {
            $blocks[] = '<strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                . implode('<br>', $lines);
        }
    }
    if ($blocks === []) {
        return '—';
    }

    return implode('<br><br>', $blocks);
}

function jobSummaryTableWorkBlock(array $appRow): string {
    $rows = staffCandidateWorkRows($appRow['experience'] ?? null);
    if ($rows === []) {
        return '—';
    }
    $out = [];
    foreach ($rows as $r) {
        $title = trim((string)($r['job_title'] ?? ''));
        $emp = trim((string)($r['employer'] ?? ''));
        $start = trim((string)($r['start'] ?? ''));
        $end = trim((string)($r['end'] ?? ''));
        $cur = !empty($r['current']);
        $left = implode(', ', array_filter([$title, $emp]));
        $span = '';
        if ($start !== '' || $end !== '' || $cur) {
            $span = '(' . ($start !== '' ? $start : '…') . ' – ' . ($cur ? 'Present' : ($end !== '' ? $end : '…')) . ')';
        }
        $line = trim($left . ($span !== '' ? ' ' . $span : ''));
        if ($line !== '') {
            $out[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
        }
    }

    return $out !== [] ? implode('<br>', $out) : '—';
}

function jobSummaryTablePageUrl(int $jobId, bool $download = false): string {
    $q = ['job_id' => $jobId];
    if ($download) {
        $q['download'] = '1';
    }

    return BASE_URL . '/hr/job-summary-table.php?' . http_build_query($q);
}

function jobSummaryTableSafeFilename(string $title, int $jobId): string {
    $s = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $title);
    $s = trim((string)$s, '-');
    if ($s === '') {
        $s = 'job';
    }
    if (strlen($s) > 60) {
        $s = substr($s, 0, 60);
    }

    return 'Summary-Table-' . $s . '-' . $jobId . '.html';
}

/**
 * @param list<array<string,mixed>> $applicants Rows from applications + users + candidates join.
 * @param string $printQrDataUri Optional data URI for verification QR (fixed top-right; repeats on each printed page in most browsers).
 */
function jobSummaryTableRenderDocument(array $job, array $applicants, string $printQrDataUri = ''): string {
    $titleUpper = strtoupper(trim((string)($job['title'] ?? 'VACANCY')));
    $deptUpper = strtoupper(trim((string)($job['department'] ?? '')));
    if ($deptUpper === '') {
        $deptUpper = '(DEPARTMENT NOT SPECIFIED)';
    }
    $posts = htmlspecialchars(jobSummaryTablePostsLabel($job), ENT_QUOTES, 'UTF-8');
    $headingMain = 'SUMMARY TABLE FOR THE POST OF ' . htmlspecialchars($titleUpper, ENT_QUOTES, 'UTF-8')
        . ' (' . $posts . ') IN THE DEPARTMENT OF ' . htmlspecialchars($deptUpper, ENT_QUOTES, 'UTF-8');
    $dots = str_repeat('·', 80);
    $logoUrl = htmlspecialchars(SITE_LOGO_URL, ENT_QUOTES, 'UTF-8');
    $motto = 'Building Communities through Knowledge.';

    $rowsHtml = '';
    $n = 0;
    foreach ($applicants as $a) {
        $n++;
        $fn = trim((string)($a['first_name'] ?? ''));
        $ln = trim((string)($a['last_name'] ?? ''));
        $full = trim($fn . ' ' . $ln);
        $sal = jobSummaryTableSalutation($a['gender'] ?? null);
        $nameCell = ($sal !== '' ? htmlspecialchars($sal, ENT_QUOTES, 'UTF-8') . ' ' : '')
            . htmlspecialchars($full !== '' ? $full : 'Candidate', ENT_QUOTES, 'UTF-8');
        $age = ageFromDateOfBirth($a['date_of_birth'] ?? null);
        $ageStr = $age !== null ? (string)(int)$age : '—';
        $genderCode = htmlspecialchars(jobSummaryTableGenderCode($a['gender'] ?? null), ENT_QUOTES, 'UTF-8');
        $quals = jobSummaryTableQualificationsBlock($a);
        $work = jobSummaryTableWorkBlock($a);
        $rowsHtml .= '<tr>'
            . '<td class="sn">' . htmlspecialchars((string)$n, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td class="name">' . $nameCell . '</td>'
            . '<td class="cen">' . htmlspecialchars($ageStr, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td class="cen">' . $genderCode . '</td>'
            . '<td class="wrap">' . $quals . '</td>'
            . '<td class="wrap">' . $work . '</td>'
            . '<td class="wrap">Not mentioned</td>'
            . '<td class="wrap comment">&nbsp;</td>'
            . "</tr>\n";
    }
    if ($rowsHtml === '') {
        $rowsHtml = '<tr><td class="sn">—</td><td colspan="7" class="wrap"><em>No applications for this post yet.</em></td></tr>';
    }

    $generated = htmlspecialchars(formatDateTimeDisplay(date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8');

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Summary Table — ' . htmlspecialchars($titleUpper, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<style>
@page { size: A4 landscape; margin: 12mm; }
body { font-family: "Times New Roman", Times, serif; font-size: 10pt; color: #000; margin: 0; padding: 12px; }
.jp-st-head { text-align: center; margin-bottom: 10px; }
.jp-st-head img { max-height: 72px; display: block; margin: 0 auto 6px; }
.jp-st-motto { font-size: 9pt; font-style: italic; color: #333; margin-bottom: 8px; }
.jp-st-title { font-size: 10.5pt; font-weight: bold; text-transform: uppercase; text-align: center; line-height: 1.35; margin: 0 0 4px; }
.jp-st-dots { text-align: center; font-size: 6pt; letter-spacing: 1px; color: #666; margin: 0 0 12px; word-break: break-all; }
.jp-st-meta { font-size: 8pt; color: #555; text-align: right; margin-bottom: 8px; }
table.jp-st { width: 100%; border-collapse: collapse; table-layout: fixed; }
table.jp-st th, table.jp-st td { border: 1px solid #000; padding: 5px 6px; vertical-align: top; }
table.jp-st th { font-weight: bold; text-align: center; background: #f0f0f0; }
td.sn { width: 3%; text-align: center; }
td.name { width: 14%; }
td.cen { width: 4%; text-align: center; }
td.wrap { word-wrap: break-word; overflow-wrap: break-word; }
td.comment { min-height: 2.5em; }
col.col-q { width: 26%; }
.jp-st-page-no {
  text-align: center;
  margin: 16px 0 0;
  font-size: 9pt;
}
/* Verification QR: fixed top-right; most Chromium/WebKit engines repeat fixed content on every printed page. */
.jp-st-print-qr {
  position: fixed;
  top: 8px;
  right: 8px;
  z-index: 9999;
  width: 88px;
  height: 88px;
  line-height: 0;
  pointer-events: none;
}
.jp-st-print-qr img {
  display: block;
  width: 88px;
  height: 88px;
  object-fit: contain;
}
body.jp-st-body--qr {
  padding-right: 100px;
}
@media print {
  body { padding: 0; }
  body.jp-st-body--qr {
    padding-right: 0;
  }
  .jp-st-no-print { display: none !important; }
  .jp-st-print-qr {
    top: 5mm;
    right: 6mm;
    width: 22mm;
    height: 22mm;
  }
  .jp-st-print-qr img {
    width: 22mm;
    height: 22mm;
  }
}
</style></head><body'
        . ($printQrDataUri !== '' ? ' class="jp-st-body--qr"' : '')
        . '>'
        . ($printQrDataUri !== ''
            ? '<div class="jp-st-print-qr" aria-hidden="true"><img src="' . htmlspecialchars($printQrDataUri, ENT_QUOTES, 'UTF-8') . '" width="88" height="88" alt=""></div>'
            : '')
        . '<div class="jp-st-head">'
        . '<img src="' . $logoUrl . '" alt="">'
        . '<div class="jp-st-motto">' . htmlspecialchars($motto, ENT_QUOTES, 'UTF-8') . '</div>'
        . '</div>'
        . '<h1 class="jp-st-title">' . $headingMain . '</h1>'
        . '<p class="jp-st-dots">' . htmlspecialchars($dots, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p class="jp-st-meta">Generated: ' . $generated . '</p>'
        . '<p class="jp-st-no-print" style="margin:0 0 10px;"><button type="button" onclick="window.print()">Print / Save as PDF</button></p>'
        . '<table class="jp-st" role="table">'
        . '<colgroup><col><col><col><col><col class="col-q"><col class="col-q"><col><col></colgroup>'
        . '<thead><tr>'
        . '<th>S/N</th><th>Name of Candidate</th><th>Age</th><th>Gender</th>'
        . '<th>Qualifications and Year of Completion</th><th>Work Experience</th>'
        . '<th>Current Salary</th><th>Comment</th>'
        . '</tr></thead><tbody>'
        . $rowsHtml
        . '</tbody></table>';
    $pageNoBlock = '<p class="jp-st-page-no">1</p>';

    return $html . $pageNoBlock . '</body></html>';
}
