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
    private $max_messages = 10;  // Retrieve last 10 messages max
    private $max_tokens_for_context = 2000;

    public function __construct($pdo, $session_id) {
        $this->pdo = $pdo;
        $this->session_id = $session_id;
    }

    /**
     * Get conversation history for context
     * Returns array of messages in [role, content] format for OpenAI
     */
    public function get_history() {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT role, content FROM conversation_messages
                 WHERE session_id = ?
                 ORDER BY created_at ASC
                 LIMIT ?"
            );
            $stmt->execute([$this->session_id, $this->max_messages]);

            $messages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
     * role: 'user' or 'assistant'
     */
    public function add_message($role, $content) {
        if (!in_array($role, ['user', 'assistant', 'system'])) {
            error_log("Invalid message role: $role");
            return false;
        }

        // Truncate extremely long messages
        if (strlen($content) > 10000) {
            $content = substr($content, 0, 10000) . '...';
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO conversation_messages (session_id, role, content)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$this->session_id, $role, $content]);

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
