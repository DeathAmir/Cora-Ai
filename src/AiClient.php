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
        $text=$data['choices'][0]['message']['content']??null;
        if(!is_string($text)||trim($text)==='') throw new RuntimeException('CORA_BAD_RESPONSE');
        return ['text'=>$text,'model'=>(string)($data['model']??$model)];
    }

    public function image(string $prompt,string $size='1024x1024',?string $model=null): array
    {
        $this->ensureToken();
        $model=$model?:$this->cfg['image_model'];
        [$width,$height]=$this->normalizeSize($size);
        $steps=$this->imageSteps($model);
        $data=$this->requestJson($this->cfg['image_url'],[
            'model'=>$model,
            'prompt'=>$prompt,
            'width'=>$width,
            'height'=>$height,
            'steps'=>$steps,
            'response_format'=>'base64',
        ]);
        $b64=$data['data'][0]['b64_json']??null;
        if(!is_string($b64)||$b64==='') throw new RuntimeException('CORA_BAD_IMAGE_RESPONSE');
        $bytes=base64_decode($b64,true);
        if($bytes===false||strlen($bytes)<1024) throw new RuntimeException('CORA_BAD_IMAGE_RESPONSE');
        $mime=$this->detectMime($bytes);
        $bytes=$this->watermark($bytes,$mime,(string)($this->cfg['watermark']??'CORA AI'));
        return ['type'=>'data','url'=>'data:'.$mime.';base64,'.base64_encode($bytes),'model'=>$model];
    }

    private function imageSteps(string $model): int
    {
        foreach(($this->cfg['image_models']??[]) as $item) if(($item['id']??'')===$model) return max(1,min(50,(int)($item['steps']??4)));
        return 4;
    }

    private function detectMime(string $bytes): string
    {
        if(str_starts_with($bytes,"\x89PNG")) return 'image/png';
        if(str_starts_with($bytes,"\xFF\xD8\xFF")) return 'image/jpeg';
        if(substr($bytes,0,4)==='RIFF'&&substr($bytes,8,4)==='WEBP') return 'image/webp';
        return 'image/jpeg';
    }

    private function watermark(string $bytes,string $mime,string $label): string
    {
        if(!function_exists('imagecreatefromstring')) return $bytes;
        $im=@imagecreatefromstring($bytes);if(!$im)return $bytes;
        $w=imagesx($im);$h=imagesy($im);$pad=max(12,(int)round($w*.018));$boxW=max(112,(int)round($w*.18));$boxH=max(34,(int)round($h*.046));$x=$w-$boxW-$pad;$y=$h-$boxH-$pad;
        $bg=imagecolorallocatealpha($im,0,0,0,54);$fg=imagecolorallocatealpha($im,255,255,255,20);imagefilledrectangle($im,$x,$y,$x+$boxW,$y+$boxH,$bg);imagestring($im,4,$x+12,$y+(int)(($boxH-16)/2),$label,$fg);
        ob_start();if($mime==='image/png')imagepng($im,null,6);elseif($mime==='image/webp'&&function_exists('imagewebp'))imagewebp($im,null,92);else imagejpeg($im,null,94);$out=(string)ob_get_clean();imagedestroy($im);return $out!==''?$out:$bytes;
    }

    private function ensureToken(): void
    {
        if(trim((string)($this->cfg['token']??''))==='') throw new RuntimeException('CORA_TOKEN_MISSING');
    }

    private function normalizeSize(string $size): array
    {
        return match($size){'768x1024'=>[768,1024],'1024x768'=>[1024,768],default=>[1024,1024]};
    }

    private function requestJson(string $url,array $payload): array
    {
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->cfg['token'],'Content-Type: application/json','Accept: application/json'],
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>(int)$this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT=>12,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
        ]);
        $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
        if($body===false)throw new RuntimeException('CORA_NETWORK:'.$error);
        $json=json_decode($body,true);
        if($status<200||$status>=300)throw new RuntimeException($this->extractError(is_array($json)?$json:[],$status));
        if(!is_array($json))throw new RuntimeException('CORA_BAD_RESPONSE');
        return $json;
    }

    private function extractError(array $json,int $status): string
    {
        $message=$json['error']['message']??$json['error']??$json['message']??'';$message=is_string($message)?$message:'';
        if($status===401||$status===403)return 'CORA_AUTH_OR_MODEL_ACCESS';
        if($status===402)return 'CORA_CREDITS';
        if($status===429)return 'CORA_RATE_LIMIT';
        if($status===503)return 'CORA_MODEL_LOADING';
        return 'CORA_IMAGE_HTTP_'.$status.($message!==''?':'.$message:'');
    }
}
