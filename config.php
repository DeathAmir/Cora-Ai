<?php
declare(strict_types=1);

$local = [];
$localPath = __DIR__ . '/config.local.php';
if (is_file($localPath)) {
    $candidate = require $localPath;
    if (is_array($candidate)) $local = $candidate;
}

$hfToken = (string)($local['hf_token'] ?? getenv('CORA_HF_TOKEN') ?: getenv('HF_TOKEN') ?: '');

return [
    'app' => [
        'name' => 'Cora',
        'url' => rtrim(getenv('CORA_APP_URL') ?: 'http://localhost/Cora-Ai', '/'),
        'env' => getenv('CORA_ENV') ?: 'production',
        'debug' => filter_var(getenv('CORA_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
        'max_prompt_chars' => 16000,
        'max_title_chars' => 120,
        'daily_chat_limit' => (int)(getenv('CORA_DAILY_CHAT_LIMIT') ?: 60),
        'daily_image_limit' => (int)(getenv('CORA_DAILY_IMAGE_LIMIT') ?: 8),
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
        'token' => $hfToken,
        'chat_url' => 'https://router.huggingface.co/v1/chat/completions',
        'image_base_url' => 'https://router.huggingface.co/hf-inference/models/',
        'timeout' => (int)(getenv('CORA_AI_TIMEOUT') ?: 180),
        'system_prompt' => 'You are Cora, a polished general-purpose AI assistant. Reply in the user language. Be accurate, useful and natural. Use clean Markdown when useful. Do not mention infrastructure providers or internal APIs unless the user explicitly asks about implementation.',
        'watermark' => 'CORA AI',
        'text_model' => getenv('CORA_TEXT_MODEL') ?: 'Qwen/Qwen2.5-7B-Instruct-1M:cheapest',
        'image_model' => getenv('CORA_IMAGE_MODEL') ?: 'stabilityai/stable-diffusion-3-medium-diffusers',
        'text_models' => [
            [
                'id' => 'Qwen/Qwen2.5-7B-Instruct-1M:cheapest',
                'name' => 'Qwen 2.5',
                'author' => 'Qwen',
                'avatar' => 'https://huggingface.co/Qwen.png',
                'description' => 'سریع و متعادل برای گفتگوی روزمره',
                'badge' => 'پیشنهادی',
            ],
            [
                'id' => 'openai/gpt-oss-20b:cheapest',
                'name' => 'GPT-OSS 20B',
                'author' => 'OpenAI',
                'avatar' => 'https://huggingface.co/openai.png',
                'description' => 'استدلال و پاسخ‌های دقیق‌تر',
                'badge' => 'Reasoning',
            ],
            [
                'id' => 'Qwen/Qwen2.5-Coder-32B-Instruct:cheapest',
                'name' => 'Qwen Coder',
                'author' => 'Qwen',
                'avatar' => 'https://huggingface.co/Qwen.png',
                'description' => 'بهینه‌شده برای برنامه‌نویسی و دیباگ',
                'badge' => 'Code',
            ],
            [
                'id' => 'meta-llama/Llama-3.2-3B-Instruct:cheapest',
                'name' => 'Llama 3.2',
                'author' => 'Meta',
                'avatar' => 'https://huggingface.co/meta-llama.png',
                'description' => 'سبک و سریع برای پاسخ‌های کوتاه',
                'badge' => 'Fast',
            ],
        ],
        'image_models' => [
            [
                'id' => 'stabilityai/stable-diffusion-3-medium-diffusers',
                'name' => 'Cora Image',
                'author' => 'Stability AI',
                'avatar' => 'https://huggingface.co/stabilityai.png',
                'description' => 'مدل اصلی تولید تصویر Cora',
                'badge' => 'Image',
                'steps' => 28,
            ],
        ],
    ],
];
