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
$session_id = session_id();

// Load config
try {
    $config = require(__DIR__ . '/../../../config/secrets.php');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service not configured']);
    exit;
}

// Load libraries
require(__DIR__ . '/../lib/OpenAIClient.php');
require(__DIR__ . '/../lib/RateLimiter.php');
require(__DIR__ . '/../lib/AuditLog.php');

// Input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$message = trim($data['message'] ?? '');
$consent = (bool)($data['consent_to_store'] ?? false);

// Validate
if (empty($message)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

if (strlen($message) > 1000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Message is too long']);
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

// Call OpenAI
$client = new OpenAIClient($config);
$result = $client->chat($message);

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
