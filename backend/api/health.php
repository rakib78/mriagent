<?php
/**
 * ai-consultant/api/health.php
 *
 * GET /ai-consultant/api/health.php
 *
 * Returns service status without exposing secrets.
 * Response: { "status": "ok", "openai": "ok"|"unreachable", "mysql": "ok"|"unreachable", "timestamp": "..." }
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Load config
try {
    $config = require(__DIR__ . '/../../../config/secrets.php');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Configuration error',
        'timestamp' => date('c')
    ]);
    exit;
}

// Load libraries
require(__DIR__ . '/../lib/OpenAIClient.php');

$status = 'ok';
$checks = [];

// Check OpenAI
$client = new OpenAIClient($config);
$checks['openai'] = $client->health_check() ? 'ok' : 'unreachable';
if ($checks['openai'] !== 'ok') {
    $status = 'degraded';
}

// Check MySQL
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
    $pdo->query("SELECT 1");
    $checks['mysql'] = 'ok';
} catch (Exception $e) {
    $checks['mysql'] = 'unreachable';
    $status = 'degraded';
}

echo json_encode([
    'status' => $status,
    'openai' => $checks['openai'],
    'mysql' => $checks['mysql'],
    'timestamp' => date('c')
]);
?>
