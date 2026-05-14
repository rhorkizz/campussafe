<?php
/**
 * Email Service
 * Handles sending system notifications via real SMTP (SMTPMailer).
 * Falls back to error_log in case of failure (local dev safety net).
 */

require_once __DIR__ . '/SMTPMailer.php';

class EmailService {

    /**
     * Low-level send — routes through SMTPMailer.
     * @param string $to      Recipient address
     * @param string $toName  Recipient display name (can be empty)
     * @param string $subject Email subject
     * @param string $body    Plain-text body
     * @return bool
     */
    public static function sendEmail($to, $subject, $body, $toName = '') {
        $sent = SMTPMailer::send($to, $toName, $subject, $body);

        if (!$sent) {
            // Safety fallback: log the email so nothing is silently lost
            error_log("--- EMAIL FAILED (logged as fallback) ---");
            error_log("To: $to");
            error_log("Subject: $subject");
            error_log("Body:\n$body");
            error_log("-----------------------------------------");
        }

        return $sent;
    }

    /**
     * Notify officer of a new assigned incident
     */
    public static function notifyOfficer($incidentId, $title, $priority, $officerEmail = '') {
        $subject  = "[URGENT] New Incident Assigned: #$incidentId";
        $body     = "Hello Officer,\n\nA new incident requires your attention.\n\n";
        $body    .= "Incident ID: $incidentId\n";
        $body    .= "Title: $title\n";
        $body    .= "Priority: " . strtoupper($priority) . "\n\n";
        $body    .= "Please log into the CIRS dashboard to review and resolve this issue.\n";

        return self::sendEmail($officerEmail, $subject, $body, 'Campus Officer');
    }

    /**
     * Send a password-reset link to a user
     *
     * @param string $toEmail   Recipient UPSA email
     * @param string $toName    Recipient full name
     * @param string $resetUrl  Full reset URL with token
     * @return bool
     */
    public static function sendPasswordReset($toEmail, $toName, $resetUrl) {
        $subject = '[CIRS] Password Reset Request';

        $body  = "Hello {$toName},\n\n";
        $body .= "We received a request to reset your CIRS (Campus Incident Reporting System) password.\n\n";
        $body .= "Click the link below to reset your password (valid for 1 hour):\n";
        $body .= "{$resetUrl}\n\n";
        $body .= "If you did NOT request a password reset, please ignore this email — your account remains secure.\n\n";
        $body .= "Regards,\nUPSA CIRS Team";

        return self::sendEmail($toEmail, $subject, $body, $toName);
    }

    /**
     * Notify student of an incident status update
     */
    public static function notifyStudent($incidentId, $newStatus, $studentEmail = '', $studentName = '') {
        $subject  = "Update on your Incident Report #$incidentId";
        $body     = "Hello,\n\nThere has been an update regarding your recent incident report.\n\n";
        $body    .= "Incident ID: $incidentId\n";
        $body    .= "New Status: $newStatus\n\n";
        $body    .= "You can view the full details on your CIRS dashboard.\n";

        return self::sendEmail($studentEmail, $subject, $body, $studentName);
    }
}
