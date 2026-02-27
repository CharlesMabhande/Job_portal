<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

$pageTitle = 'Interviews';
$db = getDBConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();

    $applicationId = (int)($_POST['application_id'] ?? 0);
    $scheduledDate = $_POST['scheduled_date'] ?? '';
    $interviewType = sanitize($_POST['interview_type'] ?? 'In-person');
    $duration = (int)($_POST['duration_minutes'] ?? 60);
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
    ");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch();

    if (!$app) {
        $error = 'Application not found.';
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

$stmt = $db->prepare("
    SELECT i.interview_id, i.interview_type, i.scheduled_date, i.duration_minutes, i.location, i.meeting_link, i.status,
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
            <div class="col-12 col-md-2">
                <label class="form-label small">Application ID</label>
                <input class="form-control" name="application_id" type="number" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">Date & Time</label>
                <input class="form-control" name="scheduled_date" type="datetime-local" required>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small">Type</label>
                <select class="form-select" name="interview_type">
                    <?php foreach (['Phone','Video','In-person','Panel'] as $t): ?>
                        <option value="<?php echo escape($t); ?>"><?php echo escape($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small">Duration (min)</label>
                <input class="form-control" name="duration_minutes" type="number" value="60">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">Location / Link</label>
                <input class="form-control mb-2" name="location" placeholder="Location (optional)">
                <input class="form-control" name="meeting_link" placeholder="Meeting link (optional)">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>When</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$interviews): ?>
                        <tr><td colspan="5" class="text-muted">No interviews scheduled.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($interviews as $i): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo escape($i['first_name'] . ' ' . $i['last_name']); ?></div>
                                <div class="small text-muted"><?php echo escape($i['email']); ?></div>
                            </td>
                            <td><?php echo escape($i['job_title']); ?></td>
                            <td class="text-muted"><?php echo escape(date('M j, Y g:i A', strtotime($i['scheduled_date']))); ?></td>
                            <td><?php echo escape($i['interview_type']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo escape($i['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

