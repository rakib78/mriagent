<?php
/**
 * OpenAIClient.php
 *
 * Calls the OpenAI Responses API (text chat).
 * Returns sanitised responses; never exposes API errors to the visitor.
 */

class OpenAIClient {
    private $api_key;
    private $model;
    private $max_tokens;
    private $temperature;
    private $system_prompt;

    public function __construct($config) {
        $this->api_key = $config['openai']['api_key'] ?? null;
        $this->model = $config['openai']['model'] ?? 'gpt-4o-mini';
        $this->max_tokens = $config['openai']['max_tokens'] ?? 500;
        $this->temperature = $config['openai']['temperature'] ?? 0.7;

        // Load system instructions from facts.json
        $this->system_prompt = $this->load_system_prompt();
    }

    private function load_system_prompt() {
        // Load the actual grounded knowledge and system instructions
        require_once(__DIR__ . '/KnowledgeLoader.php');
        $loader = new KnowledgeLoader();
        return $loader->build_system_prompt();
    }

    /**
     * Chat with history: includes prior messages for context
     */
    public function chat_with_history($message, $history = []) {
        if (!$this->api_key) {
            return ['success' => false, 'error' => 'OpenAI not configured'];
        }

        // Build messages: history + new message
        $messages = array_merge($history, [
            ['role' => 'user', 'content' => $message],
        ]);

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $this->system_prompt]],
                $messages
            ),
        ];

        $response = $this->call_api('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response || !isset($response['choices'][0]['message']['content'])) {
            return ['success' => false, 'error' => 'No response from OpenAI'];
        }

        return [
            'success' => true,
            'reply' => $response['choices'][0]['message']['content'],
        ];
    }

    /**
     * Chat: single message, no streaming for now.
     */
    public function chat($message) {
        if (!$this->api_key) {
            return ['success' => false, 'error' => 'OpenAI not configured'];
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'messages' => [
                ['role' => 'system', 'content' => $this->system_prompt],
                ['role' => 'user', 'content' => $message],
            ],
        ];

        $response = $this->call_api('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response || !isset($response['choices'][0]['message']['content'])) {
            return ['success' => false, 'error' => 'No response from OpenAI'];
        }

        return [
            'success' => true,
            'reply' => $response['choices'][0]['message']['content'],
        ];
    }

    /**
     * Check if OpenAI is reachable.
     */
    public function health_check() {
        if (!$this->api_key) {
            return false;
        }

        $response = $this->call_api('https://api.openai.com/v1/models/gpt-4o-mini', null, 'GET');
        return $response !== null;
    }

    private function call_api($url, $payload = null, $method = 'POST') {
        $opts = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Authorization: Bearer ' . $this->api_key,
                    'Content-Type: application/json',
                ],
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ];

        if ($payload) {
            $opts['http']['content'] = json_encode($payload);
        }

        $ctx = stream_context_create($opts);

        try {
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) {
                return null;
            }
            return json_decode($response, true);
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
