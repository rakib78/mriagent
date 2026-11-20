<?php
/**
 * LeadStore.php
 *
 * MySQL lead storage with consent tracking and soft deletes.
 */

class LeadStore {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Save a lead if consent is given.
     */
    public function save($data) {
        if (!$data['consent']) {
            return ['success' => false, 'error' => 'Consent required'];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO leads (name, email, company, problem, consent_at, created_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );

        try {
            $stmt->execute([
                $data['name'] ?? '',
                $data['email'] ?? '',
                $data['company'] ?? '',
                $data['problem'] ?? '',
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Lead save error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Clean up old, soft-deleted leads (retention policy).
     */
    public function cleanup_expired($retention_months) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-$retention_months months"));

        try {
            $this->pdo->prepare(
                "DELETE FROM leads WHERE deleted_at IS NOT NULL AND deleted_at < ?"
            )->execute([$cutoff]);
        } catch (PDOException $e) {
            error_log("Lead cleanup error: " . $e->getMessage());
        }
    }
}
?>
