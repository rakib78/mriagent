<?php
/**
 * ai-consultant/api/chat.php
 *
 * POST /ai-consultant/api/chat.php
 *
 * Request: { "message": "...", "session_id": "...", "consent_to_store": false }
 * Response: { "success": true|false, "reply": "...", "error": "...", "session_id": "..." }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://mrakibulislam.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

// Load config (must be at ./config/secrets.php relative to this API directory)
try {
    $config_path = __DIR__ . '/config/secrets.php';
    if (!file_exists($config_path)) {
        throw new Exception("Config not found at: $config_path");
    }
    $config = require($config_path);
} catch (Exception $e) {
    error_log("Config error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service not configured']);
    exit;
}

// Load libraries
require(__DIR__ . '/../lib/OpenAIClient.php');
require(__DIR__ . '/../lib/RateLimiter.php');
require(__DIR__ . '/../lib/AuditLog.php');
require(__DIR__ . '/../lib/ConversationManager.php');

// Request size limit (5KB max)
$content_length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($content_length > 5000) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Request too large']);
    exit;
}

// Input
$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 5000) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Request too large']);
    exit;
}

$data = @json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$message = trim($data['message'] ?? '');
$consent = (bool)($data['consent_to_store'] ?? false);

// Resolve session ID: prefer client-supplied session_id (widget.js persists this
// in sessionStorage across requests), validated strictly against expected format.
// Fall back to PHP's native session cookie if client doesn't supply a valid one.
// This avoids the previous bug where the client's session_id was silently ignored
// and conversation history was keyed on PHP's session cookie instead - which is
// unreliable for a widget (cookie/SameSite restrictions can vary per embed context).
$client_session_id = $data['session_id'] ?? '';
if (is_string($client_session_id) && preg_match('/^sess_[0-9]{10,14}_[a-z0-9]{1,12}$/', $client_session_id)) {
    $session_id = $client_session_id;
} else {
    // Invalid or missing client session id - use PHP's native session as fallback
    $session_id = session_id();
}

// Validate message presence and type
if (!is_string($message) || empty($message)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

// Validate message length
if (strlen($message) > 1000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Message is too long (max 1000 chars)']);
    exit;
}

// Validate message contains content (not just whitespace)
if (strlen(trim($message)) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Message is too short']);
    exit;
}

// Rate limit
$limiter = new RateLimiter($config['rate_limit']);
if (!$limiter->check('chat_per_minute')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Please wait before sending another message']);
    exit;
}

// MySQL connection
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s',
            $config['mysql']['host'],
            $config['mysql']['port'],
            $config['mysql']['database']
        ),
        $config['mysql']['username'],
        $config['mysql']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("MySQL error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error. Please email hello@mrakibulislam.com']);
    exit;
}

// Audit
$audit = new AuditLog($pdo);
$audit->log('chat_message', ['message_length' => strlen($message)]);

// Get conversation history
$conv = new ConversationManager($pdo, $session_id);
$history = $conv->get_history();

// Call OpenAI with history
$client = new OpenAIClient($config);
$result = $client->chat_with_history($message, $history);

// Save messages to history
$conv->add_message('user', $message);
if ($result['success']) {
    $conv->add_message('assistant', $result['reply']);
}

if (!$result['success']) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Service temporarily unavailable. Please email hello@mrakibulislam.com'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'reply' => $result['reply'],
    'session_id' => $session_id,
]);
?>
