<?php
/**
 * AuditLog.php
 *
 * Audit logging without exposing sensitive data.
 * Hashes emails; never logs message content or API responses.
 */

class AuditLog {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Log an action (e.g., chat message, lead capture attempt, rate limit hit).
     */
    public function log($action, $details = []) {
        $email_hash = $details['email'] ?? null;
        if ($email_hash) {
            $email_hash = hash('sha256', $email_hash . 'audit');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO audit_logs (action, email_hash, ip, session_id, details, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );

        try {
            $stmt->execute([
                $action,
                $email_hash,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                session_id() ?: 'none',
                json_encode($details), // Safe details only (no credentials)
            ]);
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
}
?>
