<?php
/**
 * Email Notification System using PHPMailer
 */

// PHPMailer is optional in dev; if not installed yet we fall back to a no-op sender.
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Note: we intentionally avoid `use` imports here so this file can load even when PHPMailer isn't installed.

/**
 * Standard HTML footer for transactional emails (contact address).
 */
function emailContactFooterHtml() {
    $e = htmlspecialchars(SITE_CONTACT_EMAIL, ENT_QUOTES, 'UTF-8');
    $addr = nl2br(htmlspecialchars(str_replace("\r", '', SITE_CONTACT_ADDRESS), ENT_QUOTES, 'UTF-8'));
    $phone = htmlspecialchars(SITE_CONTACT_PHONE, ENT_QUOTES, 'UTF-8');
    $fax = htmlspecialchars(SITE_CONTACT_FAX, ENT_QUOTES, 'UTF-8');
    return "<p style='margin-top:1.25em;padding-top:1em;border-top:1px solid #eee;color:#444;font-size:0.9em;'>"
        . "Lupane State University &mdash; e-Recruitment<br>"
        . "{$addr}<br>"
        . "Tel: {$phone} &nbsp;|&nbsp; Fax: {$fax}<br>"
        . "For enquiries: <a href=\"mailto:{$e}\">{$e}</a></p>";
}

/**
 * Send email
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        // Dev-friendly fallback: don't fatal if PHPMailer isn't installed yet.
        error_log('PHPMailer not installed; email suppressed: ' . $subject . ' -> ' . $to);
        return ['success' => true, 'suppressed' => true];
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return ['success' => true];
    } catch (\Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

/**
 * Send welcome email
 */
function sendWelcomeEmail($email, $firstName) {
    $subject = "Welcome to Lupane State University Job Portal";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Welcome, {$firstName}!</h2>
        <p>Thank you for registering with the Lupane State University Job Portal.</p>
        <p>You can now start applying for job positions.</p>
        <p>Best regards,<br>Lupane State University e-Recruitment Team</p>
        " . emailContactFooterHtml() . "
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send application confirmation email
 */
function sendApplicationConfirmation($email, $firstName, $jobTitle, $applicationRef = '') {
    $refHtml = '';
    if ($applicationRef !== '') {
        $r = htmlspecialchars($applicationRef, ENT_QUOTES, 'UTF-8');
        $refHtml = "<p style='font-size:1.1em;margin:1em 0;'><strong>Application number:</strong> <span style='color:#c61f26;font-family:monospace;'>{$r}</span></p>"
            . "<p>Quote this number in any enquiry about your application.</p>";
    }
    $subject = "Application Received - {$jobTitle}";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Application Received</h2>
        <p>Dear {$firstName},</p>
        <p>Thank you for applying for the position: <strong>{$jobTitle}</strong></p>
        {$refHtml}
        <p>We have received your application and will review it shortly.</p>
        <p>You can track your application status in your dashboard using the application number above.</p>
        <p>Best regards,<br>Lupane State University e-Recruitment Team</p>
        " . emailContactFooterHtml() . "
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send interview scheduled email
 */
function sendInterviewScheduledEmail($email, $firstName, $jobTitle, $interviewDate, $location = null, $meetingLink = null) {
    $subject = "Interview Scheduled - {$jobTitle}";
    $locationText = $location ? "<p><strong>Location:</strong> {$location}</p>" : "";
    $linkText = $meetingLink ? "<p><strong>Meeting Link:</strong> <a href='{$meetingLink}'>{$meetingLink}</a></p>" : "";
    
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Interview Scheduled</h2>
        <p>Dear {$firstName},</p>
        <p>Your interview for the position <strong>{$jobTitle}</strong> has been scheduled.</p>
        <p><strong>Date & Time:</strong> " . htmlspecialchars(formatDateTimeDisplay($interviewDate), ENT_QUOTES, 'UTF-8') . "</p>
        {$locationText}
        {$linkText}
        <p>Please be prepared and arrive on time.</p>
        <p>Best regards,<br>Lupane State University e-Recruitment Team</p>
        " . emailContactFooterHtml() . "
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send status update email
 */
function sendStatusUpdateEmail($email, $firstName, $jobTitle, $status) {
    $subject = "Application Status Update - {$jobTitle}";
    $statusMessages = [
        'Shortlisted' => 'Congratulations! Your application has been shortlisted.',
        'Rejected' => 'Thank you for your interest. Unfortunately, your application was not selected.',
        'Offer Extended' => 'Congratulations! We would like to extend an offer to you.'
    ];
    
    $message = $statusMessages[$status] ?? "Your application status has been updated to: {$status}";
    
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Application Status Update</h2>
        <p>Dear {$firstName},</p>
        <p>Your application for <strong>{$jobTitle}</strong> has been updated.</p>
        <p><strong>Status:</strong> {$status}</p>
        <p>{$message}</p>
        <p>You can view more details in your dashboard.</p>
        <p>Best regards,<br>Lupane State University e-Recruitment Team</p>
        " . emailContactFooterHtml() . "
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}
