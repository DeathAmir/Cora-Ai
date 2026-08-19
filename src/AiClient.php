<?php
declare(strict_types=1);

final class AiClient
{
    public function __construct(private array $cfg) {}

    public function chat(array $messages, ?string $model = null): array
    {
        $this->ensureToken();
        $model = $model ?: $this->cfg['text_model'];
        array_unshift($messages, ['role'=>'system','content'=>$this->cfg['system_prompt']]);
        $data = $this->requestJson($this->cfg['chat_url'], [
            'model'=>$model,
            'messages'=>$messages,
            'temperature'=>0.62,
            'max_tokens'=>4096,
            'stream'=>false,
        ]);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($text) || trim($text)==='') throw new RuntimeException('پاسخ متنی معتبر نبود.');
        return ['text'=>$text,'model'=>(string)($data['model'] ?? $model)];
    }

    public function image(string $prompt, string $size='1024x1024', ?string $model=null): array
    {
        $this->ensureToken();
        $model = $model ?: $this->cfg['image_model'];
        [$width,$height] = $this->normalizeSize($size);
        $path = implode('/', array_map('rawurlencode', explode('/', $model)));
        $url = rtrim($this->cfg['image_base_url'],'/') . '/' . $path;
        $steps = $this->imageSteps($model);
        $result = $this->requestRaw($url,[
            'inputs'=>$prompt,
            'parameters'=>[
                'width'=>$width,
                'height'=>$height,
                'num_inference_steps'=>$steps,
                'guidance_scale'=>6.5,
            ],
            'options'=>['wait_for_model'=>true,'use_cache'=>false],
        ]);
        $mime = $result['content_type'];
        if (!str_starts_with($mime,'image/')) throw new RuntimeException('خروجی تصویر معتبر نبود.');
        $bytes = $this->watermark($result['body'],$mime,(string)($this->cfg['watermark'] ?? 'CORA AI'));
        return ['type'=>'data','url'=>'data:'.$mime.';base64,'.base64_encode($bytes),'model'=>$model];
    }

    private function imageSteps(string $model): int
    {
        foreach (($this->cfg['image_models'] ?? []) as $item) {
            if (($item['id'] ?? '') === $model) return max(1,min(50,(int)($item['steps'] ?? 24)));
        }
        return 24;
    }

    private function watermark(string $bytes,string $mime,string $label): string
    {
        if (!function_exists('imagecreatefromstring')) return $bytes;
        $im = @imagecreatefromstring($bytes);
        if (!$im) return $bytes;
        $w = imagesx($im); $h = imagesy($im);
        $pad = max(12,(int)round($w*0.018));
        $boxW = max(108,(int)round($w*0.17));
        $boxH = max(32,(int)round($h*0.045));
        $x = $w-$boxW-$pad; $y = $h-$boxH-$pad;
        $bg = imagecolorallocatealpha($im,0,0,0,54);
        $fg = imagecolorallocatealpha($im,255,255,255,22);
        imagefilledrectangle($im,$x,$y,$x+$boxW,$y+$boxH,$bg);
        imagestring($im,4,$x+12,$y+(int)(($boxH-16)/2),$label,$fg);
        ob_start();
        if ($mime==='image/jpeg') imagejpeg($im,null,94); else imagepng($im,null,6);
        $out = (string)ob_get_clean();
        imagedestroy($im);
        return $out !== '' ? $out : $bytes;
    }

    private function ensureToken(): void
    {
        if (trim((string)($this->cfg['token'] ?? ''))==='') throw new RuntimeException('CORA_TOKEN_MISSING');
    }

    private function normalizeSize(string $size): array
    {
        return match($size){
            '768x1024'=>[768,1024],
            '1024x768'=>[1024,768],
            default=>[1024,1024],
        };
    }

    private function requestJson(string $url,array $payload): array
    {
        $response=$this->execute($url,$payload,'application/json');
        $json=json_decode($response['body'],true);
        if(!is_array($json)) throw new RuntimeException('CORA_BAD_RESPONSE');
        return $json;
    }

    private function requestRaw(string $url,array $payload): array { return $this->execute($url,$payload,'image/*,*/*;q=0.8'); }

    private function execute(string $url,array $payload,string $accept): array
    {
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->cfg['token'],'Content-Type: application/json','Accept: '.$accept],
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HEADER=>true,
            CURLOPT_TIMEOUT=>(int)$this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT=>12,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
        ]);
        $raw=curl_exec($ch);
        $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        $headerSize=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        $contentType=(string)(curl_getinfo($ch,CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream');
        $error=curl_error($ch);
        curl_close($ch);
        if($raw===false) throw new RuntimeException('CORA_NETWORK:'.$error);
        $body=substr($raw,$headerSize);
        if($status<200||$status>=300) throw new RuntimeException($this->extractError($body,$status));
        return ['body'=>$body,'content_type'=>strtolower(trim(explode(';',$contentType)[0]))];
    }

    private function extractError(string $body,int $status): string
    {
        $json=json_decode($body,true);
        $message=is_array($json)?($json['error']['message']??$json['error']??$json['message']??''):'';
        $message=is_string($message)?$message:'';
        if($status===401||$status===403) return 'CORA_AUTH_OR_MODEL_ACCESS';
        if($status===402) return 'CORA_CREDITS';
        if($status===429) return 'CORA_RATE_LIMIT';
        if($status===503) return 'CORA_MODEL_LOADING';
        return 'CORA_IMAGE_HTTP_'.$status.($message!==''?':'.$message:'');
    }
}
