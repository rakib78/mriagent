<?php
/**
 * ai-consultant/api/voice-token.php
 *
 * POST /ai-consultant/api/voice-token.php
 *
 * Issues short-lived credentials for the OpenAI Realtime API (voice).
 *
 * Request: { "session_id": "...", "consent_to_record": true }
 * Response: { "success": true, "token": "...", "expires_in": 3600, "model": "..." }
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

// Load config
try {
    $config = require(__DIR__ . '/../../../config/secrets.php');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service not configured']);
    exit;
}

// Check if voice is enabled
if (!($config['voice']['enabled'] ?? false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Voice is not available']);
    exit;
}

// Load libraries
require(__DIR__ . '/../lib/RateLimiter.php');
require(__DIR__ . '/../lib/AuditLog.php');

// Input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$session_id = trim($data['session_id'] ?? '');
$consent = (bool)($data['consent_to_record'] ?? false);

// Validate
if (empty($session_id)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Session ID is required']);
    exit;
}

if (!$consent) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Recording consent is required']);
    exit;
}

// Rate limit: 1 token per minute per session
$limiter = new RateLimiter(['voice_token_per_minute' => 1]);
if (!$limiter->check('voice_token_per_minute')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Please wait before requesting another voice session']);
    exit;
}

// Check concurrent session limit (simple file-based)
$sessions_file = sys_get_temp_dir() . '/mri-voice-sessions.json';
$sessions = [];
if (file_exists($sessions_file)) {
    $sessions = json_decode(file_get_contents($sessions_file), true) ?? [];
    // Remove expired sessions (older than 1 hour)
    $now = time();
    $sessions = array_filter($sessions, fn($t) => ($now - $t) < 3600);
}

if (count($sessions) >= ($config['voice']['max_concurrent_sessions'] ?? 10)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Voice service at capacity. Please try again later.']);
    exit;
}

// MySQL connection for audit logging
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
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Get OpenAI API key
$openai_key = $config['openai']['api_key'] ?? null;
if (!$openai_key) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'OpenAI not configured']);
    exit;
}

// Request ephemeral token from OpenAI API
// See: https://platform.openai.com/docs/guides/realtime-getting-started
$token_request = [
    'model' => $config['voice']['model'] ?? 'gpt-4o-realtime-preview',
    'expire_time' => date('c', time() + 3600),  // 1 hour from now
];

$opts = [
    'http' => [
        'method' => 'POST',
        'header' => [
            'Authorization: Bearer ' . $openai_key,
            'Content-Type: application/json',
        ],
        'content' => json_encode($token_request),
        'timeout' => 15,
        'ignore_errors' => true,
    ],
];

$ctx = stream_context_create($opts);
$response = @file_get_contents('https://api.openai.com/v1/realtime/sessions', false, $ctx);

if ($response === false) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Could not create voice session. Try again later.']);
    exit;
}

$token_response = json_decode($response, true);

if (!$token_response || !isset($token_response['client_secret']['value'])) {
    error_log("OpenAI voice token error: " . $response);
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Voice service unavailable']);
    exit;
}

// Record this session
$sessions[$session_id] = time();
file_put_contents($sessions_file, json_encode($sessions));

// Audit log
$audit = new AuditLog($pdo);
$audit->log('voice_session_started', [
    'session_id' => $session_id,
    'model' => $token_request['model'],
]);

// Return token to client
echo json_encode([
    'success' => true,
    'token' => $token_response['client_secret']['value'],
    'expires_in' => 3600,
    'model' => $token_request['model'],
    'session_id' => $session_id,
]);
?>
