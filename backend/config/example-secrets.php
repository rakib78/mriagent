<?php
/**
 * config/example-secrets.php
 *
 * EXAMPLE ONLY. Never track secrets.php in git.
 *
 * On your Hostinger server:
 * 1. Create the config/ folder outside public_html: ~/config/
 * 2. Copy this file to ~/config/secrets.php (replace .php with the real values)
 * 3. Run: chmod 600 ~/config/secrets.php
 *
 * The api/ scripts load this via require(__DIR__ . '/../../../config/secrets.php')
 * which reaches outside the web root.
 */

return [
    'openai' => [
        'api_key' => 'sk-...', // Your OpenAI API key
        'model' => 'gpt-4o-mini',
        'max_tokens' => 500,
        'temperature' => 0.7,
    ],
    'smtp' => [
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'secure' => 'ssl',
        'username' => 'forms@mrakibulislam.com',
        'password' => 'your-password-here', // Rotate before deploying
    ],
    'mysql' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'mrakibulislam',
        'username' => 'db_user',
        'password' => 'db_password',
    ],
    'site' => [
        'name' => 'Md Rakibul Islam',
        'domain' => 'mrakibulislam.com',
        'notify_email' => 'hello@mrakibulislam.com',
    ],
    'rate_limit' => [
        'chat_per_minute' => 10,
        'lead_per_minute' => 1,
    ],
    'retention' => [
        'lead_months' => 12,
        'conversation_days' => 90,
    ],
    'widget' => [
        'enabled' => true,  // AI consultant widget active
    ],
    'voice' => [
        'enabled' => false,  // Voice beta disabled for text-only MVP
        'max_session_minutes' => 30,
        'max_concurrent_sessions' => 10,
        'budget_per_day_cents' => 10000,
        'warn_at_minutes' => 20,
        'model' => 'gpt-4o-realtime-preview',
    ],
];
?>
