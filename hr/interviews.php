<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

$pageTitle = 'Interviews';
$db = getDBConnection();
$error = null;

/**
 * Parse duration from hour/minute fields while remaining compatible with legacy duration_minutes.
 */
function parseInterviewDurationMinutes(array $src): int {
    $hours = (int)($src['duration_hours'] ?? 0);
    $mins = (int)($src['duration_mins'] ?? $src['duration_minutes'] ?? 0);
    $hours = max(0, $hours);
    $mins = max(0, min(59, $mins));
    $total = ($hours * 60) + $mins;
    return max(1, $total > 0 ? $total : 60);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    $action = $_POST['action'] ?? 'schedule';

    if ($action === 'delete_interview') {
        $interviewId = (int)($_POST['interview_id'] ?? 0);
        if ($interviewId < 1) {
            $error = 'Invalid interview.';
        } else {
            $stmt = $db->prepare("SELECT interview_id, application_id FROM interviews WHERE interview_id = ?");
            $stmt->execute([$interviewId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $error = 'Interview not found.';
            } else {
                $applicationId = (int)$row['application_id'];
                try {
                    $db->beginTransaction();
                    $db->prepare("DELETE FROM interviews WHERE interview_id = ?")->execute([$interviewId]);
                    $cntStmt = $db->prepare("SELECT COUNT(*) FROM interviews WHERE application_id = ?");
                    $cntStmt->execute([$applicationId]);
                    $remaining = (int)$cntStmt->fetchColumn();
                    if ($remaining === 0) {
                        $db->prepare("UPDATE applications SET status = 'Under Review' WHERE application_id = ? AND status = 'Interview Scheduled'")
                            ->execute([$applicationId]);
                    }
                    $db->commit();
                    logAudit('interview_deleted', 'interviews', $interviewId, null, ['application_id' => $applicationId]);
                    redirect('/hr/interviews.php', 'Interview removed.', 'success');
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Interview delete failed: ' . $e->getMessage());
                    $error = 'Could not delete interview.';
                }
            }
        }
    } elseif ($action === 'update_interview') {
        $interviewId = (int)($_POST['interview_id'] ?? 0);
        $scheduledDate = $_POST['scheduled_date'] ?? '';
        $interviewType = sanitize($_POST['interview_type'] ?? 'In-person');
        $duration = parseInterviewDurationMinutes($_POST);
        $location = sanitize($_POST['location'] ?? '');
        $meetingLink = sanitize($_POST['meeting_link'] ?? '');
        $status = sanitize($_POST['status'] ?? 'Scheduled');
        $allowedStatus = ['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'Scheduled';
        }
        $allowedType = ['Phone', 'Video', 'In-person', 'Panel'];
        if (!in_array($interviewType, $allowedType, true)) {
            $interviewType = 'In-person';
        }
        if ($interviewId < 1 || $scheduledDate === '') {
            $error = 'Invalid interview or missing date/time.';
        } else {
            $stmt = $db->prepare("
                SELECT i.interview_id, i.application_id, j.title AS job_title,
                       c.user_id AS candidate_user_id
                FROM interviews i
                JOIN applications a ON i.application_id = a.application_id
                JOIN jobs j ON a.job_id = j.job_id
                JOIN candidates c ON a.candidate_id = c.candidate_id
                WHERE i.interview_id = ?
            ");
            $stmt->execute([$interviewId]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$inv) {
                $error = 'Interview not found.';
            } else {
                $stmt = $db->prepare("
                    UPDATE interviews
                    SET interview_type = ?, scheduled_date = ?, duration_minutes = ?, location = ?, meeting_link = ?, status = ?
                    WHERE interview_id = ?
                ");
                $stmt->execute([$interviewType, $scheduledDate, $duration, $location, $meetingLink, $status, $interviewId]);
                createNotification((int)$inv['candidate_user_id'], 'interview_updated', 'Interview Updated',
                    "Your interview for {$inv['job_title']} has been updated ({$status}).", $interviewId, 'interview');
                logAudit('interview_updated', 'interviews', $interviewId, null, [
                    'scheduled_date' => $scheduledDate, 'status' => $status, 'type' => $interviewType,
                ]);
                redirect('/hr/interviews.php', 'Interview updated.', 'success');
            }
        }
    } else {
        /* schedule (default) */
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $scheduledDate = $_POST['scheduled_date'] ?? '';
        $interviewType = sanitize($_POST['interview_type'] ?? 'In-person');
        $duration = parseInterviewDurationMinutes($_POST);
        $location = sanitize($_POST['location'] ?? '');
        $meetingLink = sanitize($_POST['meeting_link'] ?? '');

        $stmt = $db->prepare("
            SELECT a.application_id, j.title AS job_title,
                   c.user_id AS candidate_user_id, u.email, u.first_name
            FROM applications a
            JOIN jobs j ON a.job_id = j.job_id
            JOIN candidates c ON a.candidate_id = c.candidate_id
            JOIN users u ON c.user_id = u.user_id
            WHERE a.application_id = ?
              AND a.status IN ('Pending', 'Under Review', 'Shortlisted')
        ");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch();

        if (!$app) {
            $error = 'Application not found or not eligible for scheduling (must be Pending, Under Review, or Shortlisted).';
        } else {
            $stmt = $db->prepare("
                INSERT INTO interviews (application_id, scheduled_by, interview_type, scheduled_date, duration_minutes, location, meeting_link, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')
            ");
            $stmt->execute([$applicationId, (int)$_SESSION['user_id'], $interviewType, $scheduledDate, $duration, $location, $meetingLink]);
            $interviewId = (int)$db->lastInsertId();

            $stmt = $db->prepare("UPDATE applications SET status = 'Interview Scheduled' WHERE application_id = ?");
            $stmt->execute([$applicationId]);

            createNotification((int)$app['candidate_user_id'], 'interview_scheduled', 'Interview Scheduled',
                "Your interview for {$app['job_title']} has been scheduled.", $interviewId, 'interview');
            sendInterviewScheduledEmail($app['email'], $app['first_name'], $app['job_title'], $scheduledDate, $location ?: null, $meetingLink ?: null);
            logAudit('interview_scheduled', 'interviews', $interviewId);

            redirect('/hr/interviews.php', 'Interview scheduled.', 'success');
        }
    }
}

$stmt = $db->prepare("
    SELECT i.interview_id, i.application_id, i.interview_type, i.scheduled_date, i.duration_minutes, i.location, i.meeting_link, i.status,
           j.title AS job_title,
           u.first_name, u.last_name, u.email
    FROM interviews i
    JOIN applications a ON i.application_id = a.application_id
    JOIN jobs j ON a.job_id = j.job_id
    JOIN candidates c ON a.candidate_id = c.candidate_id
    JOIN users u ON c.user_id = u.user_id
    ORDER BY i.scheduled_date DESC
    LIMIT 200
");
$stmt->execute();
$interviews = $stmt->fetchAll();

// Applications HR can attach an interview to (same pipeline as review before “Interview Scheduled”)
$schedulableApplications = [];
$schedulableStmt = $db->query("
    SELECT a.application_id, a.status, a.applied_at,
           u.first_name, u.last_name, u.email,
           j.title AS job_title, j.department
    FROM applications a
    JOIN candidates c ON a.candidate_id = c.candidate_id
    JOIN users u ON c.user_id = u.user_id
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.status IN ('Pending', 'Under Review', 'Shortlisted')
    ORDER BY a.applied_at DESC
    LIMIT 500
");
if ($schedulableStmt !== false) {
    $schedulableApplications = $schedulableStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Interviews</h1>
        <div class="text-muted">Schedule and track interviews.</div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo escape($error); ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <h2 class="h5">Schedule Interview</h2>
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
            <input type="hidden" name="action" value="schedule">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small">Candidate</label>
                <select class="form-select" name="application_id" required title="Choose the applicant (and job) to schedule">
                    <option value="">— Select candidate —</option>
                    <?php foreach ($schedulableApplications as $row): ?>
                        <?php
                        $label = trim($row['first_name'] . ' ' . $row['last_name'])
                            . ' — ' . ($row['job_title'] ?? '')
                            . ' (' . ($row['status'] ?? '') . ')';
                        ?>
                        <option value="<?php echo (int)$row['application_id']; ?>">
                            <?php echo escape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$schedulableApplications): ?>
                    <div class="form-text text-warning small">No eligible applications yet. Candidates appear here when status is <strong>Pending</strong>, <strong>Under Review</strong>, or <strong>Shortlisted</strong> on <a href="<?php echo BASE_URL; ?>/hr/applications.php">Applications</a>.</div>
                <?php else: ?>
                    <div class="form-text small text-muted">Listed from active applications (not yet interview-scheduled).</div>
                <?php endif; ?>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">Date & Time</label>
                <input class="form-control" name="scheduled_date" type="datetime-local" required placeholder="Click to choose date &amp; time" title="Pick date and local time for the interview (use the calendar control)">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small">Type</label>
                <select class="form-select" name="interview_type">
                    <?php foreach (['Phone','Video','In-person','Panel'] as $t): ?>
                        <option value="<?php echo escape($t); ?>"><?php echo escape($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small">Hours</label>
                <input class="form-control" name="duration_hours" type="number" min="0" value="1" placeholder="e.g. 1" title="Interview duration hours">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small">Minutes</label>
                <input class="form-control" name="duration_mins" type="number" min="0" max="59" value="0" placeholder="e.g. 30" title="Interview duration minutes (0-59)">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">Location / Link</label>
                <input class="form-control mb-2" name="location" placeholder="e.g. ICT Boardroom, Main Campus" title="Physical venue (for in-person / panel)">
                <input class="form-control" name="meeting_link" placeholder="e.g. https://meet.google.com/xxx-yyyy-zzz" title="Video link (for Phone / Video interviews)">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit" <?php echo !$schedulableApplications ? 'disabled' : ''; ?>>Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive jp-table-scroll">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>When</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end text-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$interviews): ?>
                        <tr><td colspan="6" class="text-muted">No interviews scheduled.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($interviews as $i): ?>
                        <?php
                        $iid = (int)$i['interview_id'];
                        $dtLocal = date('Y-m-d\TH:i', strtotime($i['scheduled_date']));
                        $durHours = intdiv(max(1, (int)$i['duration_minutes']), 60);
                        $durMins = max(1, (int)$i['duration_minutes']) % 60;
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo escape($i['first_name'] . ' ' . $i['last_name']); ?></div>
                                <div class="small text-muted"><?php echo escape($i['email']); ?></div>
                            </td>
                            <td><?php echo escape($i['job_title']); ?></td>
                            <td class="text-muted text-nowrap"><?php echo escape(formatDateTimeDisplay($i['scheduled_date'])); ?></td>
                            <td><?php echo escape($i['interview_type']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($i['status']); ?></span></td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#iv<?php echo $iid; ?>" title="Edit interview">
                                        <i class="bi bi-pencil-square"></i><span class="d-none d-md-inline ms-1">Update</span>
                                    </button>
                                    <form method="post" class="d-inline flex-shrink-0" onsubmit="return confirm('Remove this scheduled interview? If this was the only interview, the application will return to Under Review.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                        <input type="hidden" name="action" value="delete_interview">
                                        <input type="hidden" name="interview_id" value="<?php echo $iid; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete interview">
                                            <i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="collapse" id="iv<?php echo $iid; ?>">
                            <td colspan="6" class="bg-light border-bottom p-3">
                                <form method="post" class="row g-2 align-items-end">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                                    <input type="hidden" name="action" value="update_interview">
                                    <input type="hidden" name="interview_id" value="<?php echo $iid; ?>">
                                    <div class="col-12 col-md-3">
                                        <label class="form-label small fw-bold">Date &amp; Time</label>
                                        <input class="form-control form-control-sm" name="scheduled_date" type="datetime-local" required value="<?php echo escape($dtLocal); ?>" title="Pick date and local time for the interview">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small fw-bold">Type</label>
                                        <select class="form-select form-select-sm" name="interview_type">
                                            <?php foreach (['Phone', 'Video', 'In-person', 'Panel'] as $t): ?>
                                                <option value="<?php echo escape($t); ?>" <?php echo $i['interview_type'] === $t ? 'selected' : ''; ?>><?php echo escape($t); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <label class="form-label small fw-bold">Hours</label>
                                        <input class="form-control form-control-sm" type="number" name="duration_hours" min="0" value="<?php echo (int)$durHours; ?>" placeholder="e.g. 1" title="Interview duration hours">
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <label class="form-label small fw-bold">Minutes</label>
                                        <input class="form-control form-control-sm" type="number" name="duration_mins" min="0" max="59" value="<?php echo (int)$durMins; ?>" placeholder="e.g. 30" title="Interview duration minutes (0-59)">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label class="form-label small fw-bold">Status</label>
                                        <select class="form-select form-select-sm" name="status">
                                            <?php foreach (['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'] as $st): ?>
                                                <option value="<?php echo escape($st); ?>" <?php echo $i['status'] === $st ? 'selected' : ''; ?>><?php echo escape($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Location</label>
                                        <input class="form-control form-control-sm" name="location" value="<?php echo escape($i['location'] ?? ''); ?>" placeholder="e.g. ICT Boardroom">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-bold">Meeting link</label>
                                        <input class="form-control form-control-sm" name="meeting_link" value="<?php echo escape($i['meeting_link'] ?? ''); ?>" placeholder="https://…">
                                    </div>
                                    <div class="col-12 col-md-2 text-md-end">
                                        <button class="btn btn-sm btn-primary w-100" type="submit">
                                            <i class="bi bi-check-lg me-1"></i> Save
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

