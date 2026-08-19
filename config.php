<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => getenv('CORA_APP_NAME') ?: 'Cora AI',
        'url' => rtrim(getenv('CORA_APP_URL') ?: 'http://localhost/Cora-Ai', '/'),
        'env' => getenv('CORA_ENV') ?: 'production',
        'debug' => filter_var(getenv('CORA_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
        'max_prompt_chars' => 16000,
        'max_title_chars' => 120,
    ],
    'db' => [
        'host' => getenv('CORA_DB_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('CORA_DB_PORT') ?: 3306),
        'name' => getenv('CORA_DB_NAME') ?: 'coradb',
        'user' => getenv('CORA_DB_USER') ?: 'root',
        'pass' => getenv('CORA_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'ai' => [
        // openai_compatible or ollama
        'provider' => getenv('CORA_AI_PROVIDER') ?: 'ollama',
        'base_url' => rtrim(getenv('CORA_AI_BASE_URL') ?: 'http://127.0.0.1:11434', '/'),
        'api_key' => getenv('CORA_AI_API_KEY') ?: '',
        'text_model' => getenv('CORA_TEXT_MODEL') ?: 'qwen2.5:7b',
        'image_model' => getenv('CORA_IMAGE_MODEL') ?: 'gpt-image-1',
        'system_prompt' => getenv('CORA_SYSTEM_PROMPT') ?: 'You are Cora, a helpful, accurate and concise AI assistant. Reply in the user language. Use Markdown when useful.',
        'timeout' => (int)(getenv('CORA_AI_TIMEOUT') ?: 120),
    ],
];
