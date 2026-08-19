<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Method not allowed'],405);
$userId=require_user();
$data=json_input();
require_csrf($data);
rate_limit('chat_stream',50,300);
enforce_daily_limit($userId,'chat');

$prompt=clean_text((string)($data['message']??''),$config['app']['max_prompt_chars']);
$model=clean_text((string)($data['model']??$config['ai']['text_model']),180);
if($prompt==='') json_response(['error'=>'پیام خالی است.'],422);
if(!model_allowed('chat',$model)) json_response(['error'=>'مدل انتخاب‌شده معتبر نیست.'],422);
if(trim((string)$config['ai']['token'])==='') json_response(['error'=>'موتور Cora هنوز فعال نشده است.'],503);

$pdo=db();
$conversationId=isset($data['conversation_id'])?(int)$data['conversation_id']:0;
if($conversationId>0){conversation_owner($conversationId,$userId);}else{
    $title=clean_text((string)preg_replace('/\s+/u',' ',$prompt),70);
    $pdo->prepare("INSERT INTO conversations(user_id,title,mode,model) VALUES(?,?,'chat',?)")->execute([$userId,$title?:'گفتگوی جدید',$model]);
    $conversationId=(int)$pdo->lastInsertId();
}
$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content) VALUES(?,'user','text',?)")->execute([$conversationId,$prompt]);
$stmt=$pdo->prepare("SELECT role,content FROM messages WHERE conversation_id=? AND kind='text' AND role IN ('user','assistant') ORDER BY id DESC LIMIT 24");
$stmt->execute([$conversationId]);
$history=array_reverse($stmt->fetchAll());
$messages=array_map(static fn($m)=>['role'=>$m['role'],'content'=>$m['content']],$history);
array_unshift($messages,['role'=>'system','content'=>$config['ai']['system_prompt']]);

session_write_close();
ini_set('output_buffering','0');
ini_set('zlib.output_compression','0');
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-transform');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

echo "event: meta\ndata: ".json_encode(['conversation_id'=>$conversationId,'model'=>$model],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n\n";
@ob_flush();@flush();

$payload=json_encode([
    'model'=>$model,
    'messages'=>$messages,
    'temperature'=>0.62,
    'max_tokens'=>4096,
    'stream'=>true,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

$answer='';
$buffer='';
$rawError='';
$ch=curl_init($config['ai']['chat_url']);
curl_setopt_array($ch,[
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>$payload,
    CURLOPT_HTTPHEADER=>[
        'Authorization: Bearer '.$config['ai']['token'],
        'Content-Type: application/json',
        'Accept: text/event-stream',
    ],
    CURLOPT_RETURNTRANSFER=>false,
    CURLOPT_TIMEOUT=>(int)$config['ai']['timeout'],
    CURLOPT_CONNECTTIMEOUT=>12,
    CURLOPT_FOLLOWLOCATION=>false,
    CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
    CURLOPT_SSL_VERIFYPEER=>true,
    CURLOPT_SSL_VERIFYHOST=>2,
    CURLOPT_WRITEFUNCTION=>function($curl,string $chunk) use (&$buffer,&$answer,&$rawError): int {
        $buffer.=$chunk;
        while(($pos=strpos($buffer,"\n"))!==false){
            $line=trim(substr($buffer,0,$pos));
            $buffer=substr($buffer,$pos+1);
            if($line==='' || $line==='data: [DONE]') continue;
            if(!str_starts_with($line,'data:')){$rawError.=$line;continue;}
            $json=trim(substr($line,5));
            $data=json_decode($json,true);
            if(!is_array($data)) continue;
            $text=$data['choices'][0]['delta']['content']??'';
            if(!is_string($text)||$text==='') continue;
            $answer.=$text;
            echo "event: delta\ndata: ".json_encode(['text'=>$text],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n\n";
            @ob_flush();@flush();
        }
        return strlen($chunk);
    },
]);
$ok=curl_exec($ch);
$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
$curlError=curl_error($ch);
curl_close($ch);

if($ok===false || $status<200 || $status>=300 || trim($answer)===''){
    error_log('Cora stream failed status='.$status.' curl='.$curlError.' body='.mb_substr($rawError,0,600));
    echo "event: error\ndata: ".json_encode(['message'=>'پاسخ Cora موقتاً قطع شد. دوباره امتحان کن.'],JSON_UNESCAPED_UNICODE)."\n\n";
    @ob_flush();@flush();
    exit;
}

$answer=clean_text($answer,120000);
$meta=json_encode(['model'=>$model,'stream'=>true],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content,meta_json) VALUES(?,'assistant','text',?,?)")->execute([$conversationId,$answer,$meta]);
$pdo->prepare('UPDATE conversations SET model=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$model,$conversationId,$userId]);
$usage=daily_usage($userId);
echo "event: done\ndata: ".json_encode(['conversation_id'=>$conversationId,'model'=>$model,'usage'=>$usage],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n\n";
@ob_flush();@flush();
