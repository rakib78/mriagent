<?php
/**
 * EmailNotifier.php
 *
 * Send lead notifications and confirmations.
 */

class EmailNotifier {
    private $from_email;
    private $from_name;
    private $notify_to;

    public function __construct($config) {
        // Read from email config section
        $this->from_email = $config['email']['from'] ?? 'noreply@mrakibulislam.com';
        $this->from_name = 'AI Consultant';
        $this->notify_to = $config['email']['to'] ?? 'hello@mrakibulislam.com';
    }

    /**
     * Sanitize email address to prevent header injection
     */
    private function sanitize_email($email) {
        // Remove line breaks and other dangerous characters
        return preg_replace('/[\r\n\0]/', '', filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '');
    }

    /**
     * Send lead notification to Rakibul
     */
    public function notify_lead($lead) {
        $name = $lead['name'] ?? 'Unknown';
        $email = $this->sanitize_email($lead['email'] ?? '');

        // Don't send if we can't construct valid headers
        if (!$email) {
            error_log("EmailNotifier: Invalid lead email, skipping notification");
            return false;
        }

        $subject = "New lead: " . preg_replace('/[^\w\s\-]/', '', $name);

        $body = "New lead from AI Consultant widget:\n\n";
        $body .= "Name: " . strip_tags($name) . "\n";
        $body .= "Email: " . $email . "\n";
        $body .= "Company: " . strip_tags($lead['company'] ?? '') . "\n";
        $body .= "Problem: " . strip_tags($lead['problem'] ?? '') . "\n";
        $body .= "Submitted: " . date('Y-m-d H:i:s') . "\n";

        $headers = "From: " . $this->from_email . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $result = @mail($this->notify_to, $subject, $body, $headers);
        if (!$result) {
            error_log("EmailNotifier: Failed to send lead notification to " . $this->notify_to);
        }
        return $result;
    }

    /**
     * Send confirmation to lead (no specific timeline promise)
     */
    public function confirm_lead($lead) {
        $email = $this->sanitize_email($lead['email'] ?? '');

        if (!$email) {
            error_log("EmailNotifier: Invalid lead email for confirmation");
            return false;
        }

        $name = strip_tags($lead['name'] ?? 'there');
        $subject = "We received your message";

        $body = "Hi " . $name . ",\n\n";
        $body .= "Thank you for reaching out. We've received your inquiry.\n\n";
        $body .= "Rakibul will review your message and respond soon.\n\n";
        $body .= "In the meantime, feel free to book a call directly:\n";
        $body .= "https://cal.com/md.-rakibul-islam-vjxvrf/30min\n\n";
        $body .= "Best regards,\n" . $this->from_name;

        $headers = "From: " . $this->from_email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $result = @mail($email, $subject, $body, $headers);
        if (!$result) {
            error_log("EmailNotifier: Failed to send confirmation to " . $email);
        }
        return $result;
    }
}
?>
