<?php
declare(strict_types=1);

$local = [];
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $loaded = require $localFile;
    if (is_array($loaded)) $local = $loaded;
}

$hfToken = (string)($local['hf_token'] ?? (getenv('CORA_HF_TOKEN') ?: getenv('CORA_AI_API_KEY') ?: ''));

return [
    'app' => [
        'name' => getenv('CORA_APP_NAME') ?: 'Cora AI',
        'url' => rtrim(getenv('CORA_APP_URL') ?: 'http://localhost/Cora-Ai', '/'),
        'env' => getenv('CORA_ENV') ?: 'production',
        'debug' => filter_var(getenv('CORA_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
        'max_prompt_chars' => (int)(getenv('CORA_MAX_PROMPT_CHARS') ?: 16000),
        'max_title_chars' => 120,
        'daily_chat_limit' => (int)(getenv('CORA_DAILY_CHAT_LIMIT') ?: 60),
        'daily_image_limit' => (int)(getenv('CORA_DAILY_IMAGE_LIMIT') ?: 6),
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
        'provider' => 'huggingface',
        'hf_token' => $hfToken,
        'chat_url' => 'https://router.huggingface.co/v1/chat/completions',
        'image_base_url' => 'https://router.huggingface.co/hf-inference/models',
        'text_model' => getenv('CORA_TEXT_MODEL') ?: 'Qwen/Qwen2.5-7B-Instruct:cheapest',
        'image_model' => getenv('CORA_IMAGE_MODEL') ?: 'black-forest-labs/FLUX.1-schnell',
        'system_prompt' => getenv('CORA_SYSTEM_PROMPT') ?: 'You are Cora, a helpful, accurate and concise AI assistant. Reply in the user language. Use Markdown when useful. For code, always use fenced code blocks with the correct language identifier.',
        'timeout' => (int)(getenv('CORA_AI_TIMEOUT') ?: 150),
        'text_models' => [
            ['id'=>'Qwen/Qwen2.5-7B-Instruct:cheapest','name'=>'Qwen 2.5 7B','provider'=>'Qwen','description'=>'سریع، دقیق و مناسب گفتگوی عمومی فارسی','icon'=>'https://cdn.simpleicons.org/qwen','badge'=>'پیشنهادی'],
            ['id'=>'Qwen/Qwen2.5-1.5B-Instruct:cheapest','name'=>'Qwen 2.5 1.5B','provider'=>'Qwen','description'=>'سبک و کم‌مصرف برای پاسخ‌های سریع','icon'=>'https://cdn.simpleicons.org/qwen','badge'=>'سریع'],
            ['id'=>'Qwen/Qwen3-8B:cheapest','name'=>'Qwen 3 8B','provider'=>'Qwen','description'=>'مدل جدیدتر برای تحلیل و استدلال','icon'=>'https://cdn.simpleicons.org/qwen','badge'=>'Reasoning'],
            ['id'=>'Qwen/Qwen2.5-Coder-32B-Instruct:cheapest','name'=>'Qwen Coder 32B','provider'=>'Qwen','description'=>'انتخاب قوی برای برنامه‌نویسی و دیباگ','icon'=>'https://cdn.simpleicons.org/qwen','badge'=>'Code'],
            ['id'=>'openai/gpt-oss-20b:cheapest','name'=>'GPT-OSS 20B','provider'=>'OpenAI','description'=>'مدل متن‌باز عمومی با کیفیت بالا','icon'=>'https://cdn.simpleicons.org/openai','badge'=>'Open'],
            ['id'=>'meta-llama/Llama-3.2-3B-Instruct:cheapest','name'=>'Llama 3.2 3B','provider'=>'Meta','description'=>'سبک و مناسب گفتگوهای روزمره','icon'=>'https://cdn.simpleicons.org/meta','badge'=>'Lite'],
        ],
        'image_models' => [
            ['id'=>'black-forest-labs/FLUX.1-schnell','name'=>'FLUX.1 Schnell','provider'=>'Black Forest Labs','description'=>'مدل سریع ساخت تصویر روی HF Inference','icon'=>'https://cdn.simpleicons.org/huggingface','badge'=>'HF Free tier'],
        ],
    ],
];
