<?php
declare(strict_types=1);

final class AiClient
{
    public function __construct(private array $cfg) {}

    public function chat(array $messages, ?string $model = null): array
    {
        $this->ensureToken();
        $model = $model ?: $this->cfg['text_model'];
        array_unshift($messages, ['role' => 'system', 'content' => $this->cfg['system_prompt']]);

        $data = $this->requestJson($this->cfg['chat_url'], [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.65,
            'max_tokens' => 4096,
            'stream' => false,
        ]);

        $text = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Hugging Face پاسخ متنی معتبری برنگرداند.');
        }

        return ['text' => $text, 'model' => (string)($data['model'] ?? $model)];
    }

    public function image(string $prompt, string $size = '1024x1024', ?string $model = null): array
    {
        $this->ensureToken();
        $model = $model ?: $this->cfg['image_model'];
        [$width, $height] = $this->normalizeSize($size);
        $path = implode('/', array_map('rawurlencode', explode('/', $model)));
        $url = rtrim($this->cfg['image_base_url'], '/') . '/' . $path;

        $result = $this->requestRaw($url, [
            'inputs' => $prompt,
            'parameters' => ['width'=>$width,'height'=>$height,'num_inference_steps'=>4],
        ]);

        $mime = $result['content_type'];
        if (!str_starts_with($mime, 'image/')) throw new RuntimeException('خروجی سرویس تصویر، فایل تصویری معتبر نیست.');
        return ['type'=>'data','url'=>'data:'.$mime.';base64,'.base64_encode($result['body']),'model'=>$model];
    }

    private function ensureToken(): void
    {
        if (trim((string)($this->cfg['hf_token'] ?? '')) === '') {
            throw new RuntimeException('توکن Hugging Face تنظیم نشده است. config.local.php را بساز و hf_token را وارد کن.');
        }
    }

    private function normalizeSize(string $size): array
    {
        return match ($size) {
            '768x1024' => [768,1024],
            '1024x768' => [1024,768],
            default => [1024,1024],
        };
    }

    private function requestJson(string $url, array $payload): array
    {
        $response = $this->execute($url,$payload,'application/json');
        $json = json_decode($response['body'],true);
        if (!is_array($json)) throw new RuntimeException('پاسخ Hugging Face قابل خواندن نیست.');
        return $json;
    }

    private function requestRaw(string $url, array $payload): array { return $this->execute($url,$payload,'*/*'); }

    private function execute(string $url, array $payload, string $accept): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->cfg['hf_token'],'Content-Type: application/json','Accept: '.$accept],
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HEADER=>true,
            CURLOPT_TIMEOUT=>(int)$this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT=>12,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        $headerSize = (int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        $contentType = (string)(curl_getinfo($ch,CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream');
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('اتصال به Hugging Face برقرار نشد: '.$error);
        $body = substr($raw,$headerSize);
        if ($status < 200 || $status >= 300) throw new RuntimeException($this->extractError($body,$status));
        return ['body'=>$body,'content_type'=>strtolower(trim(explode(';',$contentType)[0]))];
    }

    private function extractError(string $body, int $status): string
    {
        $json = json_decode($body,true);
        if (is_array($json)) {
            $message = $json['error']['message'] ?? $json['error'] ?? $json['message'] ?? null;
            if (is_string($message) && $message !== '') return $message;
        }
        if ($status===401 || $status===403) return 'توکن Hugging Face معتبر نیست یا مجوز Inference Providers ندارد.';
        if ($status===402) return 'اعتبار رایگان Hugging Face کافی نیست یا سرویس انتخاب‌شده نیاز به اعتبار دارد.';
        if ($status===429) return 'Hugging Face فعلاً درخواست‌های زیادی دریافت کرده؛ کمی بعد دوباره امتحان کن.';
        if ($status===503) return 'مدل در حال بارگذاری یا موقتاً خارج از دسترس است.';
        return 'خطای Hugging Face با کد '.$status;
    }
}
