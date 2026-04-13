<?php
/**
 * Interviews API Endpoints
 */

require_once '../config/config.php';
requireLogin();
requireRole(['HR', 'SysAdmin']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDBConnection();

function parseInterviewDurationMinutes(array $src): int {
    $hours = (int)($src['duration_hours'] ?? 0);
    $mins = (int)($src['duration_mins'] ?? $src['duration_minutes'] ?? 0);
    $hours = max(0, $hours);
    $mins = max(0, min(59, $mins));
    $total = ($hours * 60) + $mins;
    return max(1, $total > 0 ? $total : 60);
}

switch ($action) {
    case 'schedule':
        requireCSRFToken();
        
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $scheduledDate = $_POST['scheduled_date'] ?? '';
        $interviewType = sanitize($_POST['interview_type'] ?? 'In-person');
        $duration = parseInterviewDurationMinutes($_POST);
        $location = sanitize($_POST['location'] ?? '');
        $meetingLink = sanitize($_POST['meeting_link'] ?? '');
        
        $errors = validateInput([
            'application_id' => $applicationId,
            'scheduled_date' => $scheduledDate
        ], [
            'application_id' => 'required',
            'scheduled_date' => 'required'
        ]);
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Get application details
        $stmt = $db->prepare("
            SELECT a.*, j.title as job_title, c.user_id as candidate_user_id, u.email, u.first_name
            FROM applications a
            JOIN jobs j ON a.job_id = j.job_id
            JOIN candidates c ON a.candidate_id = c.candidate_id
            JOIN users u ON c.user_id = u.user_id
            WHERE a.application_id = ?
        ");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch();
        
        if (!$application) {
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            exit;
        }
        
        // Create interview
        $stmt = $db->prepare("
            INSERT INTO interviews (application_id, scheduled_by, interview_type, scheduled_date, duration_minutes, location, meeting_link, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')
        ");
        $stmt->execute([$applicationId, $_SESSION['user_id'], $interviewType, $scheduledDate, $duration, $location, $meetingLink]);
        $interviewId = $db->lastInsertId();
        
        // Update application status
        $stmt = $db->prepare("UPDATE applications SET status = 'Interview Scheduled' WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        
        // Create notification
        createNotification($application['candidate_user_id'], 'interview_scheduled', 
            'Interview Scheduled', 
            "An interview has been scheduled for your application: {$application['job_title']}",
            $interviewId, 'interview');
        
        // Send email
        sendInterviewScheduledEmail($application['email'], $application['first_name'], 
            $application['job_title'], $scheduledDate, $location, $meetingLink);
        
        logAudit('interview_scheduled', 'interviews', $interviewId);
        
        echo json_encode(['success' => true, 'interview_id' => $interviewId]);
        break;
        
    case 'list':
        $page = (int)($_GET['page'] ?? 1);
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT i.*, 
                   a.application_id,
                   j.title as job_title,
                   u.first_name as candidate_first_name,
                   u.last_name as candidate_last_name,
                   u.email as candidate_email
            FROM interviews i
            JOIN applications a ON i.application_id = a.application_id
            JOIN jobs j ON a.job_id = j.job_id
            JOIN candidates c ON a.candidate_id = c.candidate_id
            JOIN users u ON c.user_id = u.user_id
            ORDER BY i.scheduled_date DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $interviews = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'interviews' => $interviews]);
        break;
        
    case 'update_feedback':
        requireCSRFToken();
        
        $interviewId = (int)($_POST['interview_id'] ?? 0);
        $feedback = $_POST['feedback'] ?? '';
        $rating = (int)($_POST['rating'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'Completed');
        
        $stmt = $db->prepare("
            UPDATE interviews 
            SET feedback = ?, rating = ?, status = ?
            WHERE interview_id = ?
        ");
        $stmt->execute([$feedback, $rating, $status, $interviewId]);
        
        logAudit('interview_feedback_updated', 'interviews', $interviewId);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
