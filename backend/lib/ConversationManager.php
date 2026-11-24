<?php
/**
 * ConversationManager.php
 *
 * Manages conversation history per session.
 * Retrieves prior messages to pass as context to OpenAI.
 * Bounded by message count and token limit.
 */

class ConversationManager {
    private $pdo;
    private $session_id;
    private $max_messages = 10;  // Retrieve latest 10 messages max
    private $conversation_created = false;

    public function __construct($pdo, $session_id) {
        $this->pdo = $pdo;
        $this->session_id = $session_id;
    }

    /**
     * Ensure conversation record exists (required for FK constraint)
     */
    private function ensure_conversation_exists() {
        if ($this->conversation_created) {
            return true;
        }

        try {
            // Check if conversation already exists
            $stmt = $this->pdo->prepare(
                "SELECT id FROM conversations WHERE session_id = ?"
            );
            $stmt->execute([$this->session_id]);

            if ($stmt->fetch()) {
                $this->conversation_created = true;
                return true;
            }

            // Create new conversation record
            $stmt = $this->pdo->prepare(
                "INSERT INTO conversations (session_id, consent_to_store)
                 VALUES (?, FALSE)"
            );
            $stmt->execute([$this->session_id]);
            $this->conversation_created = true;
            return true;
        } catch (PDOException $e) {
            error_log("ConversationManager ensure_conversation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get conversation history for context
     * Returns LATEST 10 messages in chronological order (oldest to newest)
     * for OpenAI context window
     */
    public function get_history() {
        try {
            // Get latest 10 messages (DESC), then reverse to chronological order
            $stmt = $this->pdo->prepare(
                "SELECT role, content FROM conversation_messages
                 WHERE session_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$this->session_id, $this->max_messages]);

            $messages = [];
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Reverse to chronological order (oldest to newest)
            $rows = array_reverse($rows);

            foreach ($rows as $row) {
                // STRICT: Only allow user and assistant roles
                if (!in_array($row['role'], ['user', 'assistant'])) {
                    error_log("ConversationManager: Skipping invalid role: " . $row['role']);
                    continue;
                }
                $messages[] = [
                    'role' => $row['role'],
                    'content' => $row['content'],
                ];
            }

            return $messages;
        } catch (PDOException $e) {
            error_log("ConversationManager get_history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a message to history
     * role: ONLY 'user' or 'assistant' allowed (never system from browser)
     */
    public function add_message($role, $content) {
        // STRICT: Only user and assistant roles allowed
        if (!in_array($role, ['user', 'assistant'])) {
            error_log("ConversationManager: Rejected invalid role: $role");
            return false;
        }

        if (empty($content)) {
            error_log("ConversationManager: Empty message");
            return false;
        }

        // Truncate extremely long messages
        if (strlen($content) > 10000) {
            error_log("ConversationManager: Truncated message from " . strlen($content) . " chars");
            $content = substr($content, 0, 10000) . '...';
        }

        // Ensure conversation record exists (FK constraint)
        if (!$this->ensure_conversation_exists()) {
            error_log("ConversationManager: Failed to ensure conversation exists for $this->session_id");
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO conversation_messages (session_id, role, content)
                 VALUES (?, ?, ?)"
            );
            $result = $stmt->execute([$this->session_id, $role, $content]);

            if (!$result) {
                error_log("ConversationManager: Failed to insert message");
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("ConversationManager add_message error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up old conversations (retention policy)
     */
    public static function cleanup_old_conversations($pdo, $days = 90) {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));
            $pdo->prepare(
                "DELETE FROM conversation_messages
                 WHERE created_at < ?
                 AND session_id NOT IN (
                     SELECT session_id FROM conversations
                     WHERE deleted_at IS NULL
                 )"
            )->execute([$cutoff]);
        } catch (Exception $e) {
            error_log("Conversation cleanup error: " . $e->getMessage());
        }
    }
}
?>
