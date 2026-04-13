<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['Candidate']);

$pageTitle = 'My Applications';
$db = getDBConnection();

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare("SELECT candidate_id FROM candidates WHERE user_id = ?");
$stmt->execute([$userId]);
$candidateId = (int)($stmt->fetch()['candidate_id'] ?? 0);

$stmt = $db->prepare("
    SELECT a.application_id, a.application_ref, a.status, a.applied_at, j.title, j.department, j.location,
           i.interview_id, i.interview_type, i.scheduled_date, i.duration_minutes, i.location AS interview_location, i.meeting_link, i.status AS interview_status
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    LEFT JOIN interviews i ON i.interview_id = (
        SELECT ii.interview_id
        FROM interviews ii
        WHERE ii.application_id = a.application_id
        ORDER BY ii.scheduled_date DESC, ii.interview_id DESC
        LIMIT 1
    )
    WHERE a.candidate_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$candidateId]);
$apps = $stmt->fetchAll();

require_once BASE_PATH . '/includes/header.php';

function statusBadgeClass($status) {
    $map = [
        'Pending' => 'status-pending',
        'Under Review' => 'status-under-review',
        'Shortlisted' => 'status-shortlisted',
        'Interview Scheduled' => 'status-interview',
        'Rejected' => 'status-rejected',
        'Offer Extended' => 'status-offer',
        'Accepted' => 'status-accepted',
        'Withdrawn' => 'status-withdrawn',
    ];
    return $map[$status] ?? 'status-pending';
}
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1><i class="bi bi-file-earmark-text me-2"></i>My Applications</h1>
        <p>Track all your submitted applications and their status.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-light btn-sm text-primary fw-bold" href="<?php echo BASE_URL; ?>/index.php">
            <i class="bi bi-plus-circle me-1"></i> Apply to a Job
        </a>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Application no.</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$apps): ?>
                        <tr><td colspan="6" class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                            <h6 class="text-muted">No applications yet</h6>
                            <p class="text-muted small mb-3">Start exploring available positions and apply today!</p>
                            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i> Browse Jobs
                            </a>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td class="text-nowrap font-monospace small fw-bold" title="Quote this number when contacting HR"><?php echo escape($a['application_ref'] ?? ('#' . (int)$a['application_id'])); ?></td>
                            <td class="fw-semibold"><?php echo escape($a['title']); ?></td>
                            <td class="text-muted"><?php echo escape($a['department'] ?? '-'); ?></td>
                            <td class="text-muted">
                                <?php if (!empty($a['location'])): ?>
                                    <i class="bi bi-geo-alt me-1" style="color: #c61f26"></i><?php echo escape($a['location']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo statusBadgeClass($a['status']); ?>"><?php echo escape($a['status']); ?></span>
                                <?php if (!empty($a['interview_id']) && !empty($a['scheduled_date'])): ?>
                                    <div class="small text-muted mt-2">
                                        <div><i class="bi bi-calendar-event me-1"></i><?php echo escape(formatDateTimeDisplay($a['scheduled_date'])); ?></div>
                                        <div><i class="bi bi-person-video3 me-1"></i><?php echo escape($a['interview_type'] ?: 'Interview'); ?><?php echo !empty($a['duration_minutes']) ? ' - ' . (int)$a['duration_minutes'] . ' min' : ''; ?></div>
                                        <?php if (!empty($a['interview_location'])): ?>
                                            <div><i class="bi bi-geo-alt me-1"></i><?php echo escape($a['interview_location']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($a['meeting_link'])): ?>
                                            <div>
                                                <i class="bi bi-link-45deg me-1"></i>
                                                <a href="<?php echo escape($a['meeting_link']); ?>" target="_blank" rel="noopener noreferrer">Join meeting link</a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($a['interview_status'])): ?>
                                            <div><i class="bi bi-info-circle me-1"></i>Interview status: <?php echo escape($a['interview_status']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?php echo escape(formatDateDisplay($a['applied_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
