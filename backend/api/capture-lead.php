<?php
/**
 * ai-consultant/api/capture-lead.php
 *
 * POST /ai-consultant/api/capture-lead.php
 *
 * Request: { "name": "...", "email": "...", "company": "...", "problem": "...", "consent": true }
 * Response: { "success": true|false, "error": "..." }
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

// Load libraries
require(__DIR__ . '/../lib/RateLimiter.php');
require(__DIR__ . '/../lib/LeadStore.php');
require(__DIR__ . '/../lib/AuditLog.php');

// Input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

// Honeypot
if (!empty($data['website_hp'])) {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// Validate
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$company = trim($data['company'] ?? '');
$problem = trim($data['problem'] ?? '');
$consent = (bool)($data['consent'] ?? false);

$errors = [];
if (empty($name)) $errors[] = 'Name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (!$consent) $errors[] = 'Consent is required';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// Rate limit
$limiter = new RateLimiter($config['rate_limit']);
if (!$limiter->check('lead_per_minute')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Please wait before submitting another lead']);
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

// Save lead
$store = new LeadStore($pdo);
$result = $store->save(compact('name', 'email', 'company', 'problem', 'consent'));

// Audit
$audit = new AuditLog($pdo);
$audit->log('lead_capture', [
    'success' => $result['success'],
    'email' => $email
]);

if (!$result['success']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save lead. Please email hello@mrakibulislam.com']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Thank you. We\'ll be in touch']);
?>
