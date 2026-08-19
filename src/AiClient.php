<?php
declare(strict_types=1);

final class AiClient {
    public function __construct(private array $cfg) {}

    public function chat(array $messages): array {
        return $this->cfg['provider'] === 'ollama' ? $this->chatOllama($messages) : $this->chatOpenAI($messages);
    }

    public function image(string $prompt, string $size = '1024x1024'): array {
        if ($this->cfg['provider'] === 'ollama') {
            throw new RuntimeException('برای ساخت تصویر CORA_AI_PROVIDER را روی openai_compatible و endpoint سازگار تنظیم کنید.');
        }
        $payload = [
            'model' => $this->cfg['image_model'],
            'prompt' => $prompt,
            'size' => in_array($size, ['1024x1024','1024x1536','1536x1024'], true) ? $size : '1024x1024',
            'n' => 1,
        ];
        $data = $this->request('/v1/images/generations', $payload);
        $item = $data['data'][0] ?? [];
        if (!empty($item['b64_json'])) return ['type' => 'data', 'url' => 'data:image/png;base64,' . $item['b64_json']];
        if (!empty($item['url'])) return ['type' => 'url', 'url' => $item['url']];
        throw new RuntimeException('مدل تصویر خروجی معتبری برنگرداند.');
    }

    private function chatOpenAI(array $messages): array {
        array_unshift($messages, ['role' => 'system', 'content' => $this->cfg['system_prompt']]);
        $data = $this->request('/v1/chat/completions', [
            'model' => $this->cfg['text_model'],
            'messages' => $messages,
            'temperature' => 0.65,
        ]);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($text)) throw new RuntimeException('پاسخ مدل متنی نامعتبر بود.');
        return ['text' => $text, 'model' => $data['model'] ?? $this->cfg['text_model']];
    }

    private function chatOllama(array $messages): array {
        array_unshift($messages, ['role' => 'system', 'content' => $this->cfg['system_prompt']]);
        $data = $this->request('/api/chat', [
            'model' => $this->cfg['text_model'],
            'messages' => $messages,
            'stream' => false,
            'options' => ['temperature' => 0.65],
        ], false);
        $text = $data['message']['content'] ?? null;
        if (!is_string($text)) throw new RuntimeException('Ollama پاسخ معتبری برنگرداند.');
        return ['text' => $text, 'model' => $data['model'] ?? $this->cfg['text_model']];
    }

    private function request(string $path, array $payload, bool $bearer = true): array {
        $ch = curl_init($this->cfg['base_url'] . $path);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($bearer && $this->cfg['api_key'] !== '') $headers[] = 'Authorization: Bearer ' . $this->cfg['api_key'];
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new RuntimeException('اتصال به سرویس AI برقرار نشد: ' . $err);
        $json = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($json) ? ($json['error']['message'] ?? $json['error'] ?? 'خطای سرویس AI') : 'خطای سرویس AI';
            throw new RuntimeException(is_string($message) ? $message : 'خطای سرویس AI');
        }
        if (!is_array($json)) throw new RuntimeException('پاسخ سرویس AI قابل خواندن نیست.');
        return $json;
    }
}
