<?php
/**
 * RateLimiter.php
 *
 * Session-based rate limiting with IP fallback.
 * Prevents repeated abuse; a bot dropping cookies still hits the IP limit.
 */

class RateLimiter {
    private $limits;

    public function __construct($limits) {
        $this->limits = $limits;
    }

    /**
     * Check if an action is allowed.
     * Returns true if OK, false if rate-limited.
     */
    public function check($action) {
        $limit = $this->limits[$action] ?? null;
        if (!$limit) {
            return true; // No limit defined
        }

        // Session-based: count requests in this session in the past minute
        session_start();
        $key = "rl_" . $action;
        $now = time();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Remove old entries (older than 60 sec)
        $_SESSION[$key] = array_filter($_SESSION[$key], fn($t) => ($now - $t) < 60);

        if (count($_SESSION[$key]) >= $limit) {
            // Session limit hit; check IP as fallback
            if (!$this->check_ip($action, $limit)) {
                return false; // Both limits exceeded
            }
        }

        // Record this request
        $_SESSION[$key][] = $now;

        return true;
    }

    private function check_ip($action, $limit) {
        // Simple IP-based tracking in a file (Hostinger safe).
        // In production, use Redis or Memcached if available.
        $dir = sys_get_temp_dir() . '/mri-ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700);
        }

        $file = $dir . '/' . md5($_SERVER['REMOTE_ADDR'] . $action);
        $now = time();

        $data = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
            $data = array_filter($data, fn($t) => ($now - $t) < 60);
        }

        if (count($data) >= $limit) {
            return false;
        }

        $data[] = $now;
        file_put_contents($file, json_encode($data));

        return true;
    }
}
?>
