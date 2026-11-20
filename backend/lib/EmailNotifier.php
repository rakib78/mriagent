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
        $this->from_email = $config['site']['notify_email'] ?? 'noreply@mrakibulislam.com';
        $this->from_name = $config['site']['name'] ?? 'AI Consultant';
        $this->notify_to = $config['site']['notify_email'] ?? 'hello@mrakibulislam.com';
    }

    /**
     * Send lead notification to Rakibul
     */
    public function notify_lead($lead) {
        $subject = "New lead: " . ($lead['name'] ?? 'Unknown');

        $body = "New lead from AI Consultant widget:\n\n";
        $body .= "Name: " . ($lead['name'] ?? '') . "\n";
        $body .= "Email: " . ($lead['email'] ?? '') . "\n";
        $body .= "Company: " . ($lead['company'] ?? '') . "\n";
        $body .= "Problem: " . ($lead['problem'] ?? '') . "\n";
        $body .= "Submitted: " . date('Y-m-d H:i:s') . "\n";

        $headers = "From: " . $this->from_email . "\r\n";
        $headers .= "Reply-To: " . ($lead['email'] ?? $this->from_email) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return mail($this->notify_to, $subject, $body, $headers);
    }

    /**
     * Send confirmation to lead
     */
    public function confirm_lead($lead) {
        $subject = "We received your message";

        $body = "Hi " . ($lead['name'] ?? 'there') . ",\n\n";
        $body .= "Thank you for reaching out. We've received your message and will get back to you within 24 hours.\n\n";
        $body .= "Or book a call directly: https://cal.com/md.-rakibul-islam-vjxvrf/30min\n\n";
        $body .= "Best,\n" . $this->from_name;

        $headers = "From: " . $this->from_email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $to = $lead['email'] ?? null;
        if (!$to) return false;

        return mail($to, $subject, $body, $headers);
    }
}
?>
